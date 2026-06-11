# Cek penyebab HTTP 500 di server Windows.
#   powershell -ExecutionPolicy Bypass -File deploy\diagnose.ps1
$ErrorActionPreference = "Continue"
. (Join-Path $PSScriptRoot "lib\common.ps1")

Set-Location $script:DeployAppDir

$php = Get-DeployPhp
$ok = $true

Write-Host ""
Write-Host "========================================"
Write-Host " Siakad-Feeder - DIAGNOSA HTTP 500"
Write-Host "========================================"
Write-Host "Folder: $script:DeployAppDir"
Write-Host "PHP:    $php"
Write-Host ""

function Test-ItemOk {
    param([bool]$Pass, [string]$Label, [string]$FailHint)
    if ($Pass) {
        Write-Host "  [OK]   $Label" -ForegroundColor Green
    } else {
        Write-Host "  [FAIL] $Label" -ForegroundColor Red
        Write-Host "         $FailHint" -ForegroundColor Yellow
        $script:ok = $false
    }
}

Write-Host "--- File penting ---"
Test-ItemOk (Test-Path ".env") ".env ada" "copy .env.example .env lalu php artisan key:generate"
Test-ItemOk (Test-Path "vendor\autoload.php") "vendor/ (composer install)" "jalankan: deploy\update.ps1"
Test-ItemOk (Test-Path "public\build\manifest.json") "public/build/manifest.json (npm build)" "jalankan: npm ci && npm run build"
Test-ItemOk (Test-Path "database\database.sqlite") "database/database.sqlite" "buat file kosong di database\"
Test-ItemOk (Test-Path "storage\logs") "storage/logs writable" "pastikan folder storage bisa ditulis Apache/PHP"

Write-Host ""
Write-Host "--- PHP ---"
$sqliteOk = $false
try {
    $mods = & $php -m 2>$null
    $sqliteOk = ($mods -match '(?m)^pdo_sqlite$')
} catch { }
Test-ItemOk $sqliteOk "ekstensi pdo_sqlite" "jalankan: deploy\enable-php-sqlite.ps1 lalu restart Apache"

Write-Host ""
Write-Host "--- .env ---"
if (Test-Path ".env") {
    $envRaw = Get-Content ".env" -Raw
    Test-ItemOk ($envRaw -match 'APP_KEY=base64:[A-Za-z0-9+\/=]{20,}') "APP_KEY terisi" "php artisan key:generate"
    Test-ItemOk ($envRaw -match 'APP_URL=http') "APP_URL terisi" "set APP_URL=http://98.142.245.18/siakad-feeder/public"
    if ($envRaw -match '(?m)^\s*SESSION_DRIVER\s*=\s*database') {
        Write-Host "  [INFO] SESSION_DRIVER=database (butuh tabel sessions dari migrate)" -ForegroundColor Cyan
    }
} else {
    $ok = $false
}

Write-Host ""
Write-Host "--- Database / migrate ---"
if (Test-Path "vendor\autoload.php") {
  $migrateOut = & $php artisan migrate:status 2>&1 | Out-String
  if ($migrateOut -match 'Pending|FAIL|could not find driver|SQLSTATE') {
    Test-ItemOk $false "migrate:status" "php artisan migrate --force"
    Write-Host $migrateOut -ForegroundColor DarkGray
  } else {
    Test-ItemOk $true "migrate:status" ""
  }
}

Write-Host ""
Write-Host "--- Tes artisan ---"
$aboutOut = & $php artisan about --only=environment 2>&1 | Out-String
if ($aboutOut -match 'Error|Exception|FAIL') {
    Test-ItemOk $false "php artisan about" "php artisan config:clear"
    Write-Host $aboutOut -ForegroundColor DarkGray
} else {
    Write-Host $aboutOut -ForegroundColor DarkGray
}

Write-Host ""
Write-Host "--- Log error terakhir (laravel.log) ---"
$logFile = Join-Path $script:DeployAppDir "storage\logs\laravel.log"
if (Test-Path $logFile) {
    Get-Content $logFile -Tail 25 | ForEach-Object { Write-Host $_ }
} else {
    Write-Host "  (belum ada log - aktifkan APP_DEBUG=true sementara untuk detail)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "========================================"
if ($ok) {
    Write-Host " Semua cek dasar OK." -ForegroundColor Green
    Write-Host " Jika masih 500, baca baris ERROR di log di atas."
    Write-Host " Tes: http://98.142.245.18/siakad-feeder/public/up"
    Write-Host "      http://98.142.245.18/siakad-feeder/public/login"
} else {
    Write-Host " Ada masalah - perbaiki item [FAIL] lalu jalankan:" -ForegroundColor Red
    Write-Host "   powershell -ExecutionPolicy Bypass -File deploy\update.ps1"
}
Write-Host "========================================"
Write-Host ""

if (-not $ok) { exit 1 }
