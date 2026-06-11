# Dipanggil oleh GitHub Actions / wrapper lama - delegasi ke update.ps1 (tanpa git sync).
$ErrorActionPreference = "Stop"
. (Join-Path $PSScriptRoot "lib\common.ps1")

Set-Location $script:DeployAppDir

$php = Get-DeployPhp
$composer = Get-DeployComposer
$npm = Get-DeployNpm

Write-Host "remote-post-deploy.ps1 → build saja (tanpa git sync)"
Invoke-DeployBuild -Php $php -Composer $composer -Npm $npm
Write-Host "Selesai: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
