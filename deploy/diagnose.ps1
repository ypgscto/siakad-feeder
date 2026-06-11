# Cek penyebab HTTP 500 di server Windows.
#   powershell -ExecutionPolicy Bypass -File deploy\diagnose.ps1
$ErrorActionPreference = "Continue"
. (Join-Path $PSScriptRoot "lib\common.ps1")

Set-Location $script:DeployAppDir

$php = Get-DeployPhp
$ok = $true
$dbConn = Get-DeployDbConnection

Write-Host ""
Write-Host "========================================"
Write-Host " Siakad-Feeder - DIAGNOSA HTTP 500"
Write-Host "========================================"
Write-Host "Folder: $script:DeployAppDir"
Write-Host "PHP:    $php"
Write-Host "DB:     $dbConn"
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
Test-ItemOk (Test-Path ".env") ".env ada" "copy .env.example .env lalu edit DB_* dan php artisan key:generate"
Test-ItemOk (Test-Path "vendor\autoload.php") "vendor/ (composer install)" "jalankan: deploy\update.ps1"
Test-ItemOk (Test-Path "public\build\manifest.json") "public/build/manifest.json (npm build)" "jalankan: npm ci && npm run build"
Test-ItemOk (Test-Path "storage\logs") "storage/logs writable" "pastikan folder storage bisa ditulis Apache/PHP"

Write-Host ""
Write-Host "--- PHP ---"
$mods = & $php -m 2>$null
if ($dbConn -eq "mysql") {
    Test-ItemOk ($mods -match '(?m)^pdo_mysql$') "ekstensi pdo_mysql" "aktifkan extension=php_pdo_mysql.dll di php.ini"
} elseif ($dbConn -eq "sqlite") {
    Test-ItemOk ($mods -match '(?m)^pdo_sqlite$') "ekstensi pdo_sqlite" "atau ganti .env ke DB_CONNECTION=mysql"
}

Write-Host ""
Write-Host "--- .env database ---"
if (Test-Path ".env") {
    $envRaw = Get-Content ".env" -Raw
    Test-ItemOk ($envRaw -match 'APP_KEY=base64:[A-Za-z0-9+\/=]{20,}') "APP_KEY terisi" "php artisan key:generate"
    Test-ItemOk ($envRaw -match 'APP_URL=http') "APP_URL terisi" "set APP_URL=http://98.142.245.18/siakad-feeder/public"
    if ($dbConn -eq "mysql") {
        Test-ItemOk ($envRaw -match 'DB_DATABASE=\S+') "DB_DATABASE terisi" "set DB_DATABASE=siakad_feeder"
        Test-ItemOk ($envRaw -notmatch '(?m)^\s*DB_CONNECTION\s*=\s*sqlite\s*$') "bukan SQLite" "set DB_CONNECTION=mysql di .env"
    }
} else {
    $ok = $false
}

Write-Host ""
Write-Host "--- Database / migrate ---"
if (Test-Path "vendor\autoload.php") {
  $migrateOut = & $php artisan migrate:status 2>&1 | Out-String
  if ($migrateOut -match 'Pending|FAIL|could not find driver|SQLSTATE|Unknown database') {
    Test-ItemOk $false "migrate:status" "perbaiki .env MySQL lalu: php artisan migrate --force"
    Write-Host $migrateOut -ForegroundColor DarkGray
  } else {
    Test-ItemOk $true "migrate:status" ""
  }
}

Write-Host ""
Write-Host "--- Log error terakhir (laravel.log) ---"
$logFile = Join-Path $script:DeployAppDir "storage\logs\laravel.log"
if (Test-Path $logFile) {
    Get-Content $logFile -Tail 25 | ForEach-Object { Write-Host $_ }
} else {
    Write-Host "  (belum ada log)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "========================================"
if ($ok) {
    Write-Host " Semua cek dasar OK." -ForegroundColor Green
} else {
    Write-Host " Perbaiki item [FAIL] lalu: deploy\update.ps1" -ForegroundColor Red
}
Write-Host "========================================"
Write-Host ""

if (-not $ok) { exit 1 }
