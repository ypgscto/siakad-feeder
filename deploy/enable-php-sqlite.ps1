# Aktifkan SQLite saja (tanpa deploy penuh).
$ErrorActionPreference = "Stop"
. (Join-Path $PSScriptRoot "lib\common.ps1")

$php = Get-DeployPhp
Write-Host "PHP: $php"
Ensure-PhpSqlite $php
Write-Host "Selesai."
