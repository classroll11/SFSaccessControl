param(
    [string]$modo = "identify",
    [int]$usuario_id = 0,
    [int]$slot = 1,
    [string]$dedo = "",
    [string]$output_file = "",
    [string]$candidate_file = ""
)

$apiDir = $PSScriptRoot
$statusFile = if ($output_file) { $output_file } else { Join-Path $apiDir 'fingerprint_status.json' }
$pidFile = Join-Path $apiDir 'bridge.pid'
$logFile = Join-Path $apiDir 'fingerprint_bridge.log'
$sdkBin = Join-Path $env:ProgramFiles 'DigitalPersona\One Touch SDK\.NET\Bin'
if (-not (Test-Path (Join-Path $sdkBin 'DPFPDevNET.dll'))) {
    $sdkBin = Join-Path ${env:ProgramFiles(x86)} 'DigitalPersona\One Touch SDK\.NET\Bin'
}

[System.IO.File]::WriteAllText($pidFile, "$PID")

function Write-Log([string]$message) {
    Add-Content -Path $logFile -Value "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] $message" -Encoding UTF8
}

function Write-Result([hashtable]$data) {
    $data.timestamp = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'
    $json = ConvertTo-Json -InputObject $data -Depth 5
    [System.IO.File]::WriteAllText($statusFile, $json, (New-Object System.Text.UTF8Encoding($false)))
    Write-Host $json
}

try {
    if (-not (Test-Path (Join-Path $sdkBin 'DPFPDevNET.dll'))) {
        throw 'No se encontró el SDK DigitalPersona .NET en Program Files.'
    }

    $env:PATH = $sdkBin + ';' + $env:PATH
    $assemblies = @('DPFPDevNET.dll', 'DPFPEngNET.dll', 'DPFPVerNET.dll', 'DPFPShrNET.dll') |
        ForEach-Object { Join-Path $sdkBin $_ }
    foreach ($assembly in $assemblies) {
        if (-not (Test-Path $assembly)) { throw "Falta una biblioteca del SDK: $assembly" }
        [void][System.Reflection.Assembly]::LoadFrom($assembly)
    }

    $source = Get-Content -Path (Join-Path $apiDir 'DigitalPersonaCapture.cs') -Raw
    Add-Type -TypeDefinition $source -ReferencedAssemblies $assemblies -ErrorAction Stop

    if ($modo -eq 'enroll') {
        if ($usuario_id -le 0 -or $slot -lt 1 -or $slot -gt 6) {
            throw 'El usuario o el slot de enrolamiento no son válidos.'
        }

        Write-Result @{ status = 'capturando'; codigo = 'ENROLL_CAPTURE'; mensaje = "Coloca el dedo '$dedo' varias veces hasta completar el enrolamiento."; usuario_id = $usuario_id; slot = $slot; dedo = $dedo }
        $template = [SfsAccess.Biometrics.DigitalPersonaCapture]::Enroll()
        Write-Result @{ status = 'enrolado'; codigo = 'HUELLA_ENROLADA'; mensaje = "Plantilla DigitalPersona creada para '$dedo'."; identity_hex = $template; usuario_id = $usuario_id; slot = $slot; dedo = $dedo }
    }
    elseif ($modo -eq 'identify') {
        if (-not $candidate_file -or -not (Test-Path $candidate_file)) {
            throw 'No se encontró el archivo temporal de plantillas para comparar.'
        }

        $candidates = Get-Content -Path $candidate_file -Raw
        Write-Result @{ status = 'esperando'; codigo = 'LISTO'; mensaje = 'Coloca el dedo en el lector para identificarlo.' }
        $matchedTemplate = [SfsAccess.Biometrics.DigitalPersonaCapture]::Identify($candidates)
        if ([string]::IsNullOrEmpty($matchedTemplate)) {
            Write-Result @{ status = 'no_registrado'; codigo = 'NO_MATCH'; mensaje = 'La huella fue leída, pero no coincide con una plantilla enrolada.' }
        }
        else {
            Write-Result @{ status = 'identificado'; codigo = 'HUELLA_LEIDA'; mensaje = 'Huella identificada por el SDK DigitalPersona.'; identity_hex = $matchedTemplate }
        }
    }
    else {
        throw "Modo '$modo' no reconocido."
    }
}
catch {
    Write-Log "ERROR: $($_.Exception.Message)"
    Write-Result @{ status = 'error'; codigo = 'SDK_ERROR'; mensaje = $_.Exception.Message }
}
finally {
    if ($candidate_file -and (Test-Path $candidate_file)) {
        Remove-Item -LiteralPath $candidate_file -Force -ErrorAction SilentlyContinue
    }
}