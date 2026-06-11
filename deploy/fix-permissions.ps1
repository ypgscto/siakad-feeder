# Perbaiki Access denied pada storage\framework\views (Windows Apache).
# Jalankan PowerShell sebagai Administrator:
#   powershell -ExecutionPolicy Bypass -File deploy\fix-permissions.ps1
$ErrorActionPreference = "Stop"
. (Join-Path $PSScriptRoot "lib\common.ps1")

Set-Location $script:DeployAppDir

$php = Get-DeployPhp
Ensure-DeployAppKey $php
Ensure-DeployStoragePermissions

& $php artisan view:clear
& $php artisan config:clear
& $php artisan config:cache

Write-Host ""
Write-Host "Selesai. Refresh browser." -ForegroundColor Green
