# Perbaikan 500 / routing Apache (Windows, tanpa IIS).
# Jalankan dari folder siakad-feeder:
#   powershell -ExecutionPolicy Bypass -File deploy\fix-apache-production.ps1
$ErrorActionPreference = "Stop"
Set-Location (Resolve-Path (Join-Path $PSScriptRoot "..")).Path

function Get-DeployPhp {
    if ($env:SIFEEDER_PHP -and (Test-Path $env:SIFEEDER_PHP)) { return $env:SIFEEDER_PHP }
    foreach ($root in @($env:LARAGON_ROOT, "C:\laragon", "C:\webserver", "C:\xampp")) {
        if (-not $root -or -not (Test-Path $root)) { continue }
        $php = Get-ChildItem "$root\bin\php\*\php.exe" -ErrorAction SilentlyContinue |
            Sort-Object { $_.Directory.Name } -Descending | Select-Object -First 1
        if ($php) { return $php.FullName }
    }
    return "php"
}

function Get-DeployNpm {
    if ($env:SIFEEDER_NPM -and (Test-Path $env:SIFEEDER_NPM)) { return $env:SIFEEDER_NPM }
    foreach ($root in @($env:LARAGON_ROOT, "C:\laragon", "C:\webserver", "C:\xampp")) {
        if (-not $root -or -not (Test-Path $root)) { continue }
        $npm = Get-ChildItem "$root\bin\nodejs\*\npm.cmd" -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($npm) { return $npm.FullName }
    }
    return "npm"
}

$php = Get-DeployPhp
$npm = Get-DeployNpm

Write-Host "=== Perbaikan Apache production ===" -ForegroundColor Cyan

if (-not (Test-Path ".env")) {
    throw ".env tidak ditemukan."
}

foreach ($dir in @("storage", "storage\framework", "storage\framework\cache", "storage\framework\sessions",
    "storage\framework\views", "storage\logs", "bootstrap\cache", "database")) {
    if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
}

$sqlite = "database\database.sqlite"
if (-not (Test-Path $sqlite)) { New-Item -ItemType File -Path $sqlite -Force | Out-Null }

if (-not (Test-Path "public\build\manifest.json")) {
    Write-Host "[FIX] public/build/manifest.json tidak ada — jalankan npm build (penyebab 500 umum)" -ForegroundColor Yellow
    if (Test-Path "package.json") {
        & $npm ci
        if ($LASTEXITCODE -ne 0) { throw "npm ci gagal" }
        & $npm run build
        if ($LASTEXITCODE -ne 0) { throw "npm run build gagal" }
    }
}

Write-Host ">> Clear cache Laravel"
& $php artisan config:clear
& $php artisan route:clear
& $php artisan view:clear
& $php artisan cache:clear

Write-Host ">> Migrate"
& $php artisan migrate --force

Write-Host ">> Rebuild cache"
& $php artisan config:cache
& $php artisan route:cache
& $php artisan view:cache

Write-Host ""
Write-Host "Pastikan .env berisi:" -ForegroundColor Green
Write-Host "  APP_URL=http://98.142.245.18/siakad-feeder/public"
Write-Host "  APP_SUBDIRECTORY=/siakad-feeder/public  (opsional — kode terbaru auto-detect)"
Write-Host ""
Write-Host "Tes: http://98.142.245.18/siakad-feeder/public/up"
Write-Host "Log error: storage\logs\laravel.log"
Write-Host "Selesai."
