$ErrorActionPreference = "Stop"

$Source = Join-Path $PSScriptRoot "publish"
$Target = Join-Path $env:LOCALAPPDATA "ENFAS\ScanAgent"
$Exe = Join-Path $Target "Enfas.Scan.Agent.exe"

if (-not (Test-Path $Source)) {
    throw "Pasta publish nao encontrada. Execute dotnet publish antes."
}

New-Item -ItemType Directory -Force -Path $Target | Out-Null
Copy-Item -Path (Join-Path $Source "*") -Destination $Target -Recurse -Force

schtasks /Delete /TN "ENFAS Scan Agent" /F 2>$null | Out-Null
schtasks /Create /TN "ENFAS Scan Agent" /SC ONLOGON /TR ('"' + $Exe + '"') /RL LIMITED /F | Out-Null
Start-Process -FilePath $Exe

Write-Host "ENFAS Scan Agent instalado em $Target"
Write-Host "Teste: http://127.0.0.1:19876/health"
