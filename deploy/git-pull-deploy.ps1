# Deploy manual di server Windows: git pull + post-deploy
# Jalankan dari folder aplikasi:
#   powershell -ExecutionPolicy Bypass -File deploy\git-pull-deploy.ps1
$ErrorActionPreference = "Stop"
$AppDir = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
Set-Location $AppDir

Write-Host ">> git pull origin main"
git pull origin main
if ($LASTEXITCODE -ne 0) { throw "git pull gagal" }

& powershell -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot "remote-post-deploy.ps1")
