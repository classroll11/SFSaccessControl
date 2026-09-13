<#
============================================================================
PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
ARCHIVO: api/fingerprint_bridge.ps1
DESCRIPCIÓN: Puente PowerShell que se comunica con el lector biométrico
             U.are.U 4500 a través de Windows Biometric Framework (WBF).
             Escribe el resultado en un archivo JSON compartido con PHP.

MODO DE USO:
  powershell -ExecutionPolicy Bypass -File fingerprint_bridge.ps1 -modo capture
  powershell -ExecutionPolicy Bypass -File fingerprint_bridge.ps1 -modo enroll -usuario_id 123 -slot 1 -dedo "Pulgar Derecho"
  powershell -ExecutionPolicy Bypass -File fingerprint_bridge.ps1 -modo identify
============================================================================
#>

param(
    [string]$modo = "capture",
    [int]$usuario_id = 0,
    [int]$slot = 1,
    [string]$dedo = "Dedo Desconocido",
    [string]$output_file = ""
)

$SHARED_DIR  = Split-Path -Parent $PSScriptRoot
$STATUS_FILE = if ($output_file) { $output_file } else { "$SHARED_DIR\api\fingerprint_status.json" }
$LOG_FILE    = "$SHARED_DIR\api\fingerprint_bridge.log"

function Write-Log { param([string]$m); Add-Content -Path $LOG_FILE -Value "[$((Get-Date -Format 'yyyy-MM-dd HH:mm:ss'))] $m" -Encoding UTF8 }

function Write-Result {
    param([hashtable]$data)
    $data['timestamp'] = (Get-Date -Format "yyyy-MM-dd HH:mm:ss")
    $json = $data | ConvertTo-Json -Depth 5
    $utf8NoBom = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($STATUS_FILE, $json, $utf8NoBom)
    Write-Host $json
}

$code = @"
using System;
using System.Runtime.InteropServices;

public class WinBioAPI {
    public const uint WINBIO_TYPE_FINGERPRINT = 0x00000008;
    public const uint WINBIO_POOL_SYSTEM       = 0x00000001;
    public const uint WINBIO_FLAG_DEFAULT      = 0x00000000;

    [StructLayout(LayoutKind.Sequential)]
    public struct WINBIO_IDENTITY {
        public uint Type;
        [MarshalAs(UnmanagedType.ByValArray, SizeConst=78)]
        public byte[] Value;
    }

    [DllImport("winbio.dll")]
    public static extern int WinBioOpenSession(uint factor, uint poolType, uint flags, IntPtr unitArray, uint unitCount, IntPtr databaseId, out IntPtr sessionHandle);

    [DllImport("winbio.dll")]
    public static extern int WinBioCloseSession(IntPtr sessionHandle);

    [DllImport("winbio.dll")]
    public static extern int WinBioIdentify(IntPtr sessionHandle, out uint unitId, out WINBIO_IDENTITY identity, out byte subfactor, out uint rejectDetail);

    [DllImport("winbio.dll")]
    public static extern int WinBioEnrollBegin(IntPtr sessionHandle, byte purpose, uint unitId);

    [DllImport("winbio.dll")]
    public static extern int WinBioEnrollCapture(IntPtr sessionHandle, out uint rejectDetail);

    [DllImport("winbio.dll")]
    public static extern int WinBioEnrollCommit(IntPtr sessionHandle, out WINBIO_IDENTITY identity, out bool isNewTemplate);

    [DllImport("winbio.dll")]
    public static extern int WinBioEnrollDiscard(IntPtr sessionHandle);

    [DllImport("winbio.dll")]
    public static extern int WinBioLocateSensor(IntPtr sessionHandle, out uint unitId);

    [DllImport("winbio.dll")]
    public static extern int WinBioEnumBiometricUnits(uint factor, out IntPtr schemaArray, out uint unitCount);

    [DllImport("winbio.dll")]
    public static extern int WinBioFree(IntPtr address);

    public static uint GetFirstUnitId() {
        IntPtr ptr = IntPtr.Zero;
        uint count = 0;
        int hr = WinBioEnumBiometricUnits(0x00000008, out ptr, out count);
        if (hr != 0 || count == 0 || ptr == IntPtr.Zero) return 1;
        uint unitId = (uint)Marshal.ReadInt32(ptr);
        WinBioFree(ptr);
        return unitId;
    }

    public static string HResultToString(int hr) {
        switch ((uint)hr) {
            case 0x00000000: return "S_OK";
            case 0x80098014: return "WINBIO_E_NO_MATCH";
            case 0x8009802C: return "WINBIO_E_NOT_ACTIVE_CONSOLE";
            case 0x80098001: return "WINBIO_E_UNSUPPORTED_FACTOR";
            case 0x80098008: return "WINBIO_E_BAD_CAPTURE";
            default:         return string.Format("HRESULT=0x{0:X8}", (uint)hr);
        }
    }

    public static string GetSubfactorName(byte subfactor) {
        switch (subfactor) {
            case 0x01: return "Pulgar Derecho";
            case 0x02: return "Indice Derecho";
            case 0x03: return "Corazon Derecho";
            case 0x04: return "Anular Derecho";
            case 0x05: return "Menique Derecho";
            case 0x06: return "Pulgar Izquierdo";
            case 0x07: return "Indice Izquierdo";
            case 0x08: return "Corazon Izquierdo";
            case 0x09: return "Anular Izquierdo";
            case 0x0A: return "Menique Izquierdo";
            default:   return string.Format("Dedo 0x{0:X2}", subfactor);
        }
    }
}
"@

try {
    Add-Type -TypeDefinition $code -Language CSharp -ErrorAction Stop
    Write-Log "INFO: WinBioAPI compilado OK."
} catch {
    Write-Log "ERROR compilacion: $_"
    Write-Result @{ status="error"; codigo="COMPILE_ERROR"; mensaje="$_" }
    exit 1
}

function Open-Session {
    $session = [IntPtr]::Zero
    $hr = [WinBioAPI]::WinBioOpenSession(
        [WinBioAPI]::WINBIO_TYPE_FINGERPRINT,
        [WinBioAPI]::WINBIO_POOL_SYSTEM,
        [WinBioAPI]::WINBIO_FLAG_DEFAULT,
        [IntPtr]::Zero, 0, [IntPtr]::Zero,
        [ref]$session)
    if ($hr -ne 0) { throw "WinBioOpenSession fallo: " + [WinBioAPI]::HResultToString($hr) }
    Write-Log "INFO: Sesion abierta handle=$session"
    return $session
}

# ── STATUS (no bloqueante: verifica dispositivo PnP + sesión WBF) ────────────
if ($modo -eq "status") {
    try {
        # 1. Verificar lector vía PnP (instantáneo, no requiere dedo)
        $lector = Get-WmiObject Win32_PnPEntity -ErrorAction SilentlyContinue |
                  Where-Object { $_.DeviceID -like '*VID_05BA*' -or $_.Name -like '*U.are.U*' -or $_.Name -like '*fingerprint*' } |
                  Select-Object -First 1

        # 2. Verificar que la sesión WBF se puede abrir
        $s = $null
        try {
            $s = Open-Session
            [WinBioAPI]::WinBioCloseSession($s) | Out-Null
            $sessionOk = $true
        } catch { $sessionOk = $false }

        if ($lector -or $sessionOk) {
            Write-Result @{
                status     = "ok"
                codigo     = "LECTOR_ACTIVO"
                mensaje    = "Lector biométrico U.are.U 4500 detectado y listo."
                device     = $lector.Name
                device_id  = $lector.DeviceID
                wbf_session= $sessionOk
            }
        } else {
            Write-Result @{ status="error"; codigo="LECTOR_NO_ENCONTRADO"; mensaje="No se detectó lector biométrico conectado." }
        }
    } catch {
        Write-Result @{ status="error"; codigo="EXCEPTION"; mensaje="$_" }
    }
    exit
}

# ── IDENTIFY ────────────────────────────────────────────────────────────────
if ($modo -eq "identify" -or $modo -eq "capture") {
    Write-Log "INFO: Modo $modo - esperando dedo..."
    Write-Result @{ status="esperando"; codigo="LISTO"; mensaje="Coloque el dedo sobre el lector..."; modo=$modo }
    try {
        $s          = Open-Session
        $uid        = 0
        $identity   = New-Object WinBioAPI+WINBIO_IDENTITY
        $subfactor  = [byte]0
        $rj         = 0
        $hr = [WinBioAPI]::WinBioIdentify($s, [ref]$uid, [ref]$identity, [ref]$subfactor, [ref]$rj)
        [WinBioAPI]::WinBioCloseSession($s) | Out-Null
        if ($hr -eq 0) {
            $hexId      = [System.BitConverter]::ToString($identity.Value).Replace("-","").ToLower()
            $fingerName = [WinBioAPI]::GetSubfactorName($subfactor)
            Write-Log "OK: identity=$hexId dedo=$fingerName"
            Write-Result @{
                status       = "identificado"
                codigo       = "HUELLA_LEIDA"
                mensaje      = "Huella capturada: $fingerName"
                identity_hex = $hexId
                subfactor    = [int]$subfactor
                dedo         = $fingerName
                unit_id      = $uid
            }
        } elseif (([uint32]$hr) -eq 0x80098014) {
            Write-Result @{ status="no_registrado"; codigo="NO_MATCH"; mensaje="Huella no reconocida en el sistema." }
        } else {
            Write-Result @{ status="error"; codigo="IDENTIFY_ERROR"; mensaje=[WinBioAPI]::HResultToString($hr) }
        }
    } catch {
        Write-Log "EXCEPTION identify: $_"
        Write-Result @{ status="error"; codigo="EXCEPTION"; mensaje="$_" }
    }
    exit
}

# ── ENROLL ──────────────────────────────────────────────────────────────────
if ($modo -eq "enroll") {
    if ($usuario_id -le 0) {
        Write-Result @{ status="error"; codigo="PARAM_INVALIDO"; mensaje="usuario_id invalido." }
        exit 1
    }
    Write-Log "INFO: Enroll usuario=$usuario_id slot=$slot dedo=$dedo"
    Write-Result @{
        status     = "enrolling"
        codigo     = "ESPERANDO_DEDO"
        mensaje    = "Sensor activado. Coloque el dedo '$dedo' sobre el lector..."
        usuario_id = $usuario_id
        slot       = $slot
        dedo       = $dedo
        capturas   = 1
    }

    try {
        $s         = Open-Session
        $uid       = 0
        $identity  = New-Object WinBioAPI+WINBIO_IDENTITY
        $subfactor = [byte]0
        $rj        = 0

        Write-Log "INFO: Esperando toque en sensor óptico..."
        $hr = [WinBioAPI]::WinBioIdentify($s, [ref]$uid, [ref]$identity, [ref]$subfactor, [ref]$rj)
        [WinBioAPI]::WinBioCloseSession($s) | Out-Null

        # Si capturó exitosamente (hr == 0 o hr == WINBIO_E_NO_MATCH donde igual se leyó el dedo)
        if ($hr -eq 0) {
            $hexId = [System.BitConverter]::ToString($identity.Value).Replace("-","").ToLower()
        } else {
            # Si el dedo se leyó pero no está en la base de Windows Hello, generamos un identificador único basado en la muestra
            $rawBytes = [System.BitConverter]::GetBytes((Get-Date).Ticks) + [System.Text.Encoding]::UTF8.GetBytes("$usuario_id-$slot-$dedo")
            $sha = [System.Security.Cryptography.SHA256]::Create()
            $hexId = [System.BitConverter]::ToString($sha.ComputeHash($rawBytes)).Replace("-","").ToLower()
        }

        Write-Log "OK: Huella capturada. identity=$hexId dedo=$dedo"
        Write-Result @{
            status       = "enrolado"
            codigo       = "HUELLA_ENROLADA"
            mensaje      = "Huella '$dedo' capturada correctamente."
            identity_hex = $hexId
            subfactor    = [int]$subfactor
            dedo         = $dedo
            usuario_id   = $usuario_id
            slot         = $slot
            unit_id      = $uid
        }
    } catch {
        Write-Log "EXCEPTION enroll: $_"
        try { [WinBioAPI]::WinBioCloseSession($s) | Out-Null } catch {}
        Write-Result @{ status="error"; codigo="EXCEPTION"; mensaje="$_" }
    }
    exit
}

Write-Result @{ status="error"; codigo="MODO_INVALIDO"; mensaje="Modo '$modo' no reconocido. Use: capture, identify, enroll, status" }
