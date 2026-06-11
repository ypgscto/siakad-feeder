# Setup awal Siakad-Feeder di server Windows + Laragon (jalankan sekali).
# Contoh (PowerShell Admin tidak wajib):
#   cd C:\laragon\www\siakad-feeder
#   powershell -ExecutionPolicy Bypass -File deploy\server-setup.ps1
param(
    [string]$AppPath = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
)

$ErrorActionPreference = "Stop"
Set-Location $AppPath

Write-Host "Setup Siakad-Feeder di: $AppPath"

if (-not (Test-Path ".env")) {
    if (Test-Path ".env.example") {
        Copy-Item ".env.example" ".env"
        Write-Host ".env dibuat dari .env.example — edit sebelum production."
    } else {
        throw ".env.example tidak ditemukan."
    }
}

& powershell -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot "remote-post-deploy.ps1")

Write-Host ""
Write-Host "Langkah berikutnya:"
Write-Host "  1. Edit $AppPath\.env (APP_URL, Siakad-API, Feeder)"
Write-Host "  2. Laragon: pastikan folder www\siakad-feeder aktif (Auto virtual hosts)"
Write-Host "  3. Akses http://siakad-feeder.test atau sesuaikan hosts + APP_URL"
Write-Host "  4. Atur GitHub Secrets untuk deploy otomatis (lihat docs/DEPLOY.md)"
