# Deprecated — gunakan deploy\fresh-install.ps1 untuk instalasi pertama.
# Deploy ulang: deploy\git-pull-deploy.ps1
# Contoh:
#   powershell -ExecutionPolicy Bypass -File deploy\fresh-install.ps1
param(
    [string]$AppPath = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
)

$ErrorActionPreference = "Stop"
Set-Location $AppPath

Write-Host "server-setup.ps1 digantikan oleh fresh-install.ps1"
& powershell -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot "fresh-install.ps1")
