# Siakad-Feeder - INSTALASI PERTAMA di server Windows (Apache).
#
#   cd C:\webserver\www\siakad-feeder
#   powershell -ExecutionPolicy Bypass -File deploy\install.ps1
#
$ErrorActionPreference = "Stop"
. (Join-Path $PSScriptRoot "lib\common.ps1")

Set-Location $script:DeployAppDir

Write-Host ""
Write-Host "========================================"
Write-Host " Siakad-Feeder - INSTALASI PERTAMA"
Write-Host "========================================"
Write-Host "Folder: $script:DeployAppDir"
Write-Host ""

$php = Get-DeployPhp
$composer = Get-DeployComposer
$npm = Get-DeployNpm
Write-Host "PHP: $php"

$total = 5

Write-DeployStep 1 $total "Sinkron kode dari GitHub"
Sync-DeployFromGitHub

Write-DeployStep 2 $total "Siapkan .env"
if (Test-Path ".env") {
    Write-DeployOk ".env sudah ada - tidak ditimpa"
} elseif (Test-Path ".env.example") {
    Copy-Item ".env.example" ".env"
    Write-DeployOk ".env dibuat dari .env.example"
} else {
    throw ".env.example tidak ditemukan"
}

Write-DeployStep 3 $total "APP_KEY"
$envContent = Get-Content ".env" -Raw
if ($envContent -notmatch 'APP_KEY=base64:[A-Za-z0-9+\/=]{20,}') {
    Invoke-DeployCommand $php @("artisan", "key:generate")
    Write-DeployOk "APP_KEY dibuat"
} else {
    Write-DeployOk "APP_KEY sudah ada"
}

Write-DeployStep 4 $total "Dependensi, build, migrate, seed"
Invoke-DeployBuild -Php $php -Composer $composer -Npm $npm -Seed

Write-DeployStep 5 $total "Selesai"
Show-DeployFinishMessage -IsFreshInstall
