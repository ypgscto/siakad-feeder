# Siakad-Feeder — UPDATE dari GitHub (jalankan setiap ada perubahan di repo).
#
#   cd C:\webserver\www\siakad-feeder
#   powershell -ExecutionPolicy Bypass -File deploy\update.ps1
#
# Menggantikan: git pull, git-pull-deploy.ps1, remote-post-deploy.ps1
# Aman untuk folder yang pernah di-edit manual — .env & database tidak dihapus.
#
$ErrorActionPreference = "Stop"
. (Join-Path $PSScriptRoot "lib\common.ps1")

Set-Location $script:DeployAppDir

Write-Host ""
Write-Host "========================================"
Write-Host " Siakad-Feeder — UPDATE dari GitHub"
Write-Host "========================================"
Write-Host "Folder: $script:DeployAppDir"
Write-Host ""

$php = Get-DeployPhp
$composer = Get-DeployComposer
$npm = Get-DeployNpm
Write-Host "PHP: $php"

$total = 3

Write-DeployStep 1 $total "Sinkron kode (fetch + reset + clean konflik)"
Sync-DeployFromGitHub

Write-DeployStep 2 $total "Build & migrate (.env tidak diubah)"
Invoke-DeployBuild -Php $php -Composer $composer -Npm $npm

Write-DeployStep 3 $total "Selesai"
Show-DeployFinishMessage
