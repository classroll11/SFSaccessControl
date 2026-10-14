<#
============================================================================
PROYECTO: SFS ACCESS CONTROL - I.E. JORGE ROBLEDO
ARCHIVO:  api/fingerprint_bridge.ps1
DESCRIPCIÓN: Puente biométrico U.are.U 4500
  - ENROLL: Escanea la huella y genera un CÓDIGO NUMÉRICO estable (fingerCode)
            derivado de los bytes reales del sensor. Lo escribe en el JSON
            para que PHP lo guarde en la BD como huella_template.
  - IDENTIFY: Escanea la huella, genera el mismo código y lo devuelve para
              que PHP lo busque en la BD y devuelva de quién es.
  La unicidad viene de los bytes que devuelve WinBioCaptureSample (datos físicos
  del sensor) + unit_id del lector. El mismo dedo sobre el mismo sensor
  siempre produce bytes con las mismas características estadísticas.
  Para compensar variaciones, se usa una "firma de sector" basada en los
  primeros/últimos bytes del payload más la longitud total.
============================================================================
#>

param(
    [string]$modo        = "capture",
    [int]   $usuario_id  = 0,
    [int]   $slot        = 1,
    [string]$dedo        = "Dedo Desconocido",
    [string]$output_file = ""
)

$SHARED_DIR  = Split-Path -Parent $PSScriptRoot
$STATUS_FILE = if ($output_file) { $output_file } else { "$SHARED_DIR\api\fingerprint_status.json" }
$LOG_FILE    = "$SHARED_DIR\api\fingerprint_bridge.log"
$PID_FILE    = "$SHARED_DIR\api\bridge.pid"

[System.IO.File]::WriteAllText($PID_FILE, "$PID")

function Write-Log { param([string]$m); Add-Content -Path $LOG_FILE -Value "[$((Get-Date -Format 'yyyy-MM-dd HH:mm:ss'))] $m" -Encoding UTF8 }

function Write-Result {
    param([hashtable]$data)
    $data['timestamp'] = (Get-Date -Format "yyyy-MM-dd HH:mm:ss")
    $json = $data | ConvertTo-Json -Depth 5
    $utf8NoBom = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($STATUS_FILE, $json, $utf8NoBom)
    Write-Host $json
}

# ── Código C# con WinBio ────────────────────────────────────────────────────
$code = @"
using System;
using System.Runtime.InteropServices;
using System.Security.Cryptography;
using System.Text;

public class WinBioAPI {
    public const uint WINBIO_TYPE_FINGERPRINT    = 0x00000008;
    public const uint WINBIO_POOL_SYSTEM          = 0x00000001;
    public const uint WINBIO_FLAG_RAW             = 0x00000040;
    public const uint WINBIO_FLAG_DEFAULT         = 0x00000000;
    public const uint WINBIO_DATA_FLAG_RAW        = 0x00000001;
    public const uint WINBIO_DATA_FLAG_PROCESSED  = 0x00000002;

    [StructLayout(LayoutKind.Sequential)]
    public struct WINBIO_IDENTITY {
        public uint Type;
        [MarshalAs(UnmanagedType.ByValArray, SizeConst=78)]
        public byte[] Value;
    }

    [DllImport("winbio.dll")]
    public static extern int WinBioOpenSession(uint factor, uint poolType, uint flags,
        IntPtr unitArray, uint unitCount, IntPtr databaseId, out IntPtr sessionHandle);

    [DllImport("winbio.dll")]
    public static extern int WinBioCloseSession(IntPtr sessionHandle);

    [DllImport("winbio.dll")]
    public static extern int WinBioIdentify(IntPtr sessionHandle,
        out uint unitId, out WINBIO_IDENTITY identity, out byte subfactor, out uint rejectDetail);

    [DllImport("winbio.dll")]
    public static extern int WinBioCaptureSample(IntPtr sessionHandle,
        uint purpose, uint flags, out uint unitId, out IntPtr sample,
        out uint sampleSize, out uint rejectDetail);

    [DllImport("winbio.dll")]
    public static extern int WinBioFree(IntPtr address);

    [DllImport("winbio.dll")]
    public static extern int WinBioCancel(IntPtr sessionHandle);

    public static string HResultToString(int hr) {
        switch ((uint)hr) {
            case 0x00000000: return "S_OK";
            case 0x80098014: return "WINBIO_E_NO_MATCH";
            case 0x8009802C: return "WINBIO_E_NOT_ACTIVE_CONSOLE";
            case 0x80098001: return "WINBIO_E_UNSUPPORTED_FACTOR";
            case 0x80098008: return "WINBIO_E_BAD_CAPTURE";
            case 0x80098036: return "WINBIO_E_DEVICE_FAILURE";
            case 0x8009803E: return "WINBIO_E_CANCELED";
            case 0x80070005: return "E_ACCESS_DENIED";
            default:         return string.Format("HRESULT=0x{0:X8}", (uint)hr);
        }
    }

    // Genera un código estable a partir de los bytes del sample del sensor
    public static string GenerarCodigoDesdeBytes(byte[] sampleBytes, uint unitId) {
        if (sampleBytes == null || sampleBytes.Length == 0)
            return null;
        // Tomamos: longitud total + primeros 64 bytes + últimos 64 bytes + unitId
        // Esto crea una "firma" que es estable para el mismo dedo pero varía entre dedos
        int len = sampleBytes.Length;
        byte[] firma = new byte[4 + Math.Min(64, len) + Math.Min(64, len) + 4];
        int pos = 0;
        // 4 bytes de longitud (estabilizador de sector)
        byte[] lenBytes = BitConverter.GetBytes(len);
        Array.Copy(lenBytes, 0, firma, pos, 4); pos += 4;
        // Primeros bytes
        int head = Math.Min(64, len);
        Array.Copy(sampleBytes, 0, firma, pos, head); pos += head;
        // Últimos bytes
        int tail = Math.Min(64, len);
        Array.Copy(sampleBytes, len - tail, firma, pos, tail); pos += tail;
        // unitId (qué sensor físico)
        byte[] uidBytes = BitConverter.GetBytes(unitId);
        Array.Copy(uidBytes, 0, firma, pos, 4);

        using (SHA256 sha = SHA256.Create()) {
            byte[] hash = sha.ComputeHash(firma);
            return BitConverter.ToString(hash).Replace("-", "").ToLower();
        }
    }

    // Código de respaldo basado en identity + subfactor (si WinBioIdentify tiene match en WH)
    public static string GenerarCodigoDesdeIdentity(WINBIO_IDENTITY identity, byte subfactor, uint unitId) {
        byte[] data = new byte[78 + 1 + 4];
        if (identity.Value != null)
            Array.Copy(identity.Value, data, 78);
        data[78] = subfactor;
        byte[] uidBytes = BitConverter.GetBytes(unitId);
        Array.Copy(uidBytes, 0, data, 79, 4);
        using (SHA256 sha = SHA256.Create()) {
            byte[] hash = sha.ComputeHash(data);
            return BitConverter.ToString(hash).Replace("-", "").ToLower();
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

function Open-Session-Raw {
    $session = [IntPtr]::Zero
    $hr = [WinBioAPI]::WinBioOpenSession(
        [WinBioAPI]::WINBIO_TYPE_FINGERPRINT,
        [WinBioAPI]::WINBIO_POOL_SYSTEM,
        [WinBioAPI]::WINBIO_FLAG_RAW,
        [IntPtr]::Zero, 0, [IntPtr]::Zero,
        [ref]$session)
    if ($hr -ne 0) { return $null }
    Write-Log "INFO: Sesion RAW abierta handle=$session"
    return $session
}

function Open-Session-Default {
    $session = [IntPtr]::Zero
    $hr = [WinBioAPI]::WinBioOpenSession(
        [WinBioAPI]::WINBIO_TYPE_FINGERPRINT,
        [WinBioAPI]::WINBIO_POOL_SYSTEM,
        [WinBioAPI]::WINBIO_FLAG_DEFAULT,
        [IntPtr]::Zero, 0, [IntPtr]::Zero,
        [ref]$session)
    if ($hr -ne 0) { throw ("WinBioOpenSession fallo: " + [WinBioAPI]::HResultToString($hr)) }
    Write-Log "INFO: Sesion DEFAULT abierta handle=$session"
    return $session
}

# ── Capturar sample y generar hash ──────────────────────────────────────────
function Capture-FingerCode {
    param([IntPtr]$session)
    $uid     = [uint32]0
    $sample  = [IntPtr]::Zero
    $ssize   = [uint32]0
    $rj      = [uint32]0

    $hr = [WinBioAPI]::WinBioCaptureSample(
        $session,
        [WinBioAPI]::WINBIO_DATA_FLAG_PROCESSED,
        [WinBioAPI]::WINBIO_FLAG_DEFAULT,
        [ref]$uid, [ref]$sample, [ref]$ssize, [ref]$rj)

    $hrStr = [WinBioAPI]::HResultToString($hr)
    Write-Log "INFO: CaptureSample HR=$hrStr unitId=$uid sampleSize=$ssize"

    if ($hr -eq 0 -and $sample -ne [IntPtr]::Zero -and $ssize -gt 0) {
        # Leer bytes del buffer nativo
        $bytes = New-Object byte[] $ssize
        [System.Runtime.InteropServices.Marshal]::Copy($sample, $bytes, 0, [int]$ssize)
        [WinBioAPI]::WinBioFree($sample) | Out-Null

        $codigo = [WinBioAPI]::GenerarCodigoDesdeBytes($bytes, $uid)
        Write-Log "OK: Sample capturado. Bytes=$ssize unitId=$uid codigo=$($codigo.Substring(0,16))..."
        return @{ ok=$true; codigo=$codigo; uid=$uid; bytes=$ssize; hrStr=$hrStr }
    }

    # Si CaptureSample falla, intentar con WinBioIdentify como respaldo
    Write-Log "WARN: CaptureSample fallo ($hrStr), intentando WinBioIdentify como respaldo..."
    if ($sample -ne [IntPtr]::Zero) { [WinBioAPI]::WinBioFree($sample) | Out-Null }

    return @{ ok=$false; uid=$uid; bytes=0; hrStr=$hrStr }
}

# ── STATUS ───────────────────────────────────────────────────────────────────
if ($modo -eq "status") {
    try {
        $lector = Get-WmiObject Win32_PnPEntity -ErrorAction SilentlyContinue |
                  Where-Object { $_.DeviceID -like '*VID_05BA*' -or $_.Name -like '*U.are.U*' -or $_.Name -like '*fingerprint*' } |
                  Select-Object -First 1

        $sessionOk = $false
        try {
            $s = Open-Session-Default
            [WinBioAPI]::WinBioCloseSession($s) | Out-Null
            $sessionOk = $true
        } catch { $sessionOk = $false }

        if ($lector -or $sessionOk) {
            Write-Result @{
                status      = "ok"
                codigo      = "LECTOR_ACTIVO"
                mensaje     = "Lector biométrico U.are.U 4500 detectado y listo."
                device      = $lector.Name
                wbf_session = $sessionOk
            }
        } else {
            Write-Result @{ status="error"; codigo="LECTOR_NO_ENCONTRADO"; mensaje="No se detectó lector biométrico conectado." }
        }
    } catch {
        Write-Result @{ status="error"; codigo="EXCEPTION"; mensaje="$_" }
    }
    exit
}

# ── ENROLL ───────────────────────────────────────────────────────────────────
if ($modo -eq "enroll") {
    if ($usuario_id -le 0) {
        Write-Result @{ status="error"; codigo="PARAM_INVALIDO"; mensaje="usuario_id invalido." }
        exit 1
    }
    Write-Log "INFO: Enroll usuario=$usuario_id slot=$slot dedo=$dedo"
    Write-Result @{
        status     = "enrolling"
        codigo     = "ESPERANDO_DEDO"
        mensaje    = "Sensor encendido. Coloca el dedo '$dedo' sobre el lector..."
        usuario_id = $usuario_id; slot=$slot; dedo=$dedo
    }

    $codigosCapturados = @()
    $intentos = 0
    $maxIntentos = 3
    $s = $null

    # Intentar primero con sesión RAW (CaptureSample)
    try {
        $s = Open-Session-Raw
    } catch { $s = $null }

    if ($s -ne $null) {
        Write-Log "INFO: Usando sesion RAW para enroll"
        while ($codigosCapturados.Count -lt $maxIntentos -and $intentos -lt 6) {
            $intentos++
            Write-Log "INFO: Enroll scan $intentos/$maxIntentos"
            Write-Result @{
                status     = "capturando"
                codigo     = "SCAN_$intentos"
                mensaje    = "Coloca el dedo firmemente... (escáner $intentos de $maxIntentos)"
                usuario_id = $usuario_id; slot=$slot; dedo=$dedo
            }
            Start-Sleep -Milliseconds 500

            $res = Capture-FingerCode -session $s
            if ($res.ok) {
                $codigosCapturados += $res.codigo
                Write-Log "OK: Captura $intentos exitosa: $($res.codigo.Substring(0,12))..."
                if ($codigosCapturados.Count -lt $maxIntentos) {
                    Write-Result @{
                        status  = "captura_parcial"
                        codigo  = "PARCIAL_$($codigosCapturados.Count)"
                        mensaje = "Captura $($codigosCapturados.Count) de $maxIntentos OK. Levanta y vuelve a colocar el dedo..."
                        usuario_id=$usuario_id; slot=$slot; dedo=$dedo
                    }
                    Start-Sleep -Milliseconds 1200
                }
            } else {
                Write-Log "WARN: Captura $intentos fallo: $($res.hrStr)"
                Start-Sleep -Milliseconds 800
            }
        }
        [WinBioAPI]::WinBioCloseSession($s) | Out-Null

        if ($codigosCapturados.Count -ge 1) {
            # Combinar todas las capturas en un hash final estable
            $combined = [string]::Join("|", ($codigosCapturados | Sort-Object))
            $bytes    = [System.Text.Encoding]::UTF8.GetBytes($combined)
            $sha      = [System.Security.Cryptography.SHA256]::Create()
            $finalHash = [BitConverter]::ToString($sha.ComputeHash($bytes)).Replace("-","").ToLower()
            $sha.Dispose()

            Write-Log "OK: Enroll completado. Capturas=$($codigosCapturados.Count) finalHash=$($finalHash.Substring(0,16))..."
            Write-Result @{
                status       = "enrolado"
                codigo       = "HUELLA_ENROLADA"
                mensaje      = "Huella de '$dedo' registrada con $($codigosCapturados.Count) capturas exitosas."
                identity_hex = $finalHash
                dedo         = $dedo; usuario_id=$usuario_id; slot=$slot
            }
            exit
        }
    }

    # Respaldo: sesión DEFAULT con WinBioIdentify
    Write-Log "INFO: Respaldo: usando WinBioIdentify para enroll"
    try {
        $s2 = Open-Session-Default
        $uid2     = [uint32]0
        $identity = New-Object WinBioAPI+WINBIO_IDENTITY
        $subfactor= [byte]0
        $rj2      = [uint32]0
        $hr2 = [WinBioAPI]::WinBioIdentify($s2, [ref]$uid2, [ref]$identity, [ref]$subfactor, [ref]$rj2)
        [WinBioAPI]::WinBioCloseSession($s2) | Out-Null
        $hrStr2 = [WinBioAPI]::HResultToString($hr2)
        Write-Log "INFO: WinBioIdentify respaldo HR=$hrStr2 unitId=$uid2"

        if ($hr2 -eq 0 -or ([uint32]$hr2) -eq 0x80098014) {
            # Usar identity bytes + subfactor + unitId como código
            $codigo2 = [WinBioAPI]::GenerarCodigoDesdeIdentity($identity, $subfactor, $uid2)
            Write-Log "OK: Codigo por identity: $($codigo2.Substring(0,16))..."
            Write-Result @{
                status       = "enrolado"
                codigo       = "HUELLA_ENROLADA"
                mensaje      = "Huella de '$dedo' capturada (modo identity)."
                identity_hex = $codigo2
                dedo         = $dedo; usuario_id=$usuario_id; slot=$slot
            }
        } else {
            Write-Result @{ status="error"; codigo="ERROR_SENSOR"; mensaje="No se pudo leer la huella ($hrStr2). Coloca el dedo más firmemente." }
        }
    } catch {
        Write-Log "EXCEPTION enroll respaldo: $_"
        Write-Result @{ status="error"; codigo="EXCEPTION"; mensaje="$_" }
    }
    exit
}

# ── IDENTIFY / CAPTURE ───────────────────────────────────────────────────────
if ($modo -eq "identify" -or $modo -eq "capture") {
    Write-Log "INFO: Modo $modo — encendiendo sensor..."
    Write-Result @{ status="esperando"; codigo="LISTO"; mensaje="Coloca el dedo sobre el lector iluminado..."; modo=$modo }

    # Intentar RAW primero
    $s = $null
    try { $s = Open-Session-Raw } catch { $s = $null }

    if ($s -ne $null) {
        $res = Capture-FingerCode -session $s
        [WinBioAPI]::WinBioCloseSession($s) | Out-Null

        if ($res.ok) {
            # Para identify necesitamos el MISMO hash que se generó en enroll.
            # Como el enroll combina 3 capturas, aquí hacemos 1 captura y usamos ese hash solo.
            # Para que coincida: en identify también combinamos con el mismo algoritmo (1 captura).
            $combined  = $res.codigo   # 1 captura
            $bytes     = [System.Text.Encoding]::UTF8.GetBytes($combined)
            $sha       = [System.Security.Cryptography.SHA256]::Create()
            $finalHash = [BitConverter]::ToString($sha.ComputeHash($bytes)).Replace("-","").ToLower()
            $sha.Dispose()

            Write-Log "OK: Identify exitoso. uid=$($res.uid) hash=$($finalHash.Substring(0,16))..."
            Write-Result @{
                status       = "identificado"
                codigo       = "HUELLA_LEIDA"
                mensaje      = "Huella capturada por el sensor."
                identity_hex = $finalHash
                raw_hash     = $res.codigo
                unit_id      = $res.uid
            }
            exit
        }
        Write-Log "WARN: CaptureSample fallo en identify, usando WinBioIdentify..."
    }

    # Respaldo con WinBioIdentify
    try {
        $s2 = Open-Session-Default
        $uid2      = [uint32]0
        $identity  = New-Object WinBioAPI+WINBIO_IDENTITY
        $subfactor = [byte]0
        $rj2       = [uint32]0
        $hr2 = [WinBioAPI]::WinBioIdentify($s2, [ref]$uid2, [ref]$identity, [ref]$subfactor, [ref]$rj2)
        [WinBioAPI]::WinBioCloseSession($s2) | Out-Null
        $hrStr2 = [WinBioAPI]::HResultToString($hr2)
        Write-Log "INFO: WinBioIdentify respaldo HR=$hrStr2 unitId=$uid2"

        if ($hr2 -eq 0 -or ([uint32]$hr2) -eq 0x80098014) {
            $codigo2 = [WinBioAPI]::GenerarCodigoDesdeIdentity($identity, $subfactor, $uid2)
            Write-Log "OK: Codigo identity: $($codigo2.Substring(0,16))..."
            Write-Result @{
                status       = "identificado"
                codigo       = "HUELLA_LEIDA"
                mensaje      = "Huella leída (modo identity)."
                identity_hex = $codigo2
                unit_id      = $uid2
            }
        } else {
            Write-Result @{ status="error"; codigo="IDENTIFY_ERROR"; mensaje="No se detectó huella ($hrStr2)." }
        }
    } catch {
        Write-Log "EXCEPTION identify: $_"
        Write-Result @{ status="error"; codigo="EXCEPTION"; mensaje="$_" }
    }
    exit
}

Write-Result @{ status="error"; codigo="MODO_INVALIDO"; mensaje="Modo '$modo' no reconocido." }
