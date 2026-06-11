# WAJIB dijalankan sekali jika migrate error: could not find driver (sqlite)
#
#   cd C:\webserver\www\siakad-feeder
#   powershell -ExecutionPolicy Bypass -File deploy\enable-php-sqlite.ps1
#
# Lalu:
#   php artisan migrate --force
#
$ErrorActionPreference = "Stop"
. (Join-Path $PSScriptRoot "lib\common.ps1")

Set-Location $script:DeployAppDir

$php = Get-DeployPhp
Ensure-PhpSqlite $php

Write-Host ""
Write-Host "Lanjutkan:" -ForegroundColor Green
Write-Host "  php artisan migrate --force"
Write-Host "  powershell -ExecutionPolicy Bypass -File deploy\update.ps1"
Write-Host ""
