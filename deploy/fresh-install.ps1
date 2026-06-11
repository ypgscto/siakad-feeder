# Instalasi PERTAMA Siakad-Feeder di server Windows (aplikasi baru).
# Jalankan setelah: git clone ke C:\laragon\www\siakad-feeder
#
#   cd C:\webserver\www\siakad-feeder
#   powershell -ExecutionPolicy Bypass -File deploy\fresh-install.ps1
#
# Deploy ulang berikutnya: deploy\git-pull-deploy.ps1 (bukan skrip ini).
$ErrorActionPreference = "Stop"

$AppDir = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
Set-Location $AppDir

function Get-DeployPhp {
    if ($env:SIFEEDER_PHP -and (Test-Path $env:SIFEEDER_PHP)) {
        return $env:SIFEEDER_PHP
    }

    $roots = @($env:LARAGON_ROOT, "C:\laragon", "C:\webserver", "C:\xampp") |
        Where-Object { $_ -and (Test-Path $_) } | Select-Object -Unique

    foreach ($root in $roots) {
        $php = Get-ChildItem -Path "$root\bin\php\*\php.exe" -ErrorAction SilentlyContinue |
            Sort-Object { $_.Directory.Name } -Descending |
            Select-Object -First 1
        if ($php) {
            return $php.FullName
        }
    }

    return "php"
}

function Invoke-DeployCommand {
    param([string]$Executable, [string[]]$Arguments)

    Write-Host ">> $Executable $($Arguments -join ' ')"
    & $Executable @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "Perintah gagal (exit $LASTEXITCODE): $Executable"
    }
}

Write-Host ""
Write-Host "========================================"
Write-Host " Siakad-Feeder — FRESH INSTALL"
Write-Host "========================================"
Write-Host "Folder: $AppDir"
Write-Host ""

if (Test-Path ".env") {
    Write-Host "[INFO] .env sudah ada — tidak ditimpa."
} elseif (Test-Path ".env.example") {
    Copy-Item ".env.example" ".env"
    Write-Host "[INFO] .env dibuat dari .env.example."
} else {
    throw ".env.example tidak ditemukan."
}

$php = Get-DeployPhp
Write-Host "PHP: $php"
Write-Host ""

$envContent = Get-Content ".env" -Raw
if ($envContent -notmatch 'APP_KEY=base64:[A-Za-z0-9+\/=]{20,}') {
    Invoke-DeployCommand $php @("artisan", "key:generate")
} else {
    Write-Host "[SKIP] APP_KEY sudah ada."
}

Write-Host ""
Write-Host "--- Dependensi, migrate, build ---"
& powershell -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot "remote-post-deploy.ps1")

Write-Host ""
Write-Host "--- Seed admin & pemetaan awal ---"
Invoke-DeployCommand $php @("artisan", "db:seed", "--force")

Write-Host ""
Write-Host "========================================"
Write-Host " Instalasi selesai"
Write-Host "========================================"
Write-Host "1. Edit .env jika belum: APP_URL, SIAKAD_API_BASE_URL, FEEDER_*"
Write-Host "2. Buka URL aplikasi → login"
Write-Host "3. Menu Pengaturan Koneksi → Tes Siakad-API & Tes Neo Feeder"
Write-Host "4. Pemetaan Feeder (prodi, agama, dll.)"
Write-Host ""
