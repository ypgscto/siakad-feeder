# Perbaiki APP_URL yang salah (path folder C:\... bukan URL HTTP).
#   powershell -ExecutionPolicy Bypass -File deploy\fix-env-urls.ps1
$ErrorActionPreference = "Stop"
Set-Location (Resolve-Path (Join-Path $PSScriptRoot "..")).Path

if (-not (Test-Path ".env")) {
    throw ".env tidak ditemukan"
}

$correctUrl = "http://98.142.245.18/siakad-feeder/public"
$correctSub = "/siakad-feeder/public"

$content = Get-Content ".env" -Raw
$backup = ".deploy-backup\env-before-url-fix.bak"
New-Item -ItemType Directory -Path ".deploy-backup" -Force | Out-Null
Copy-Item ".env" $backup -Force
Write-Host "Backup: $backup"

$lines = Get-Content ".env"
$out = New-Object System.Collections.ArrayList

foreach ($line in $lines) {
    if ($line -match '^\s*APP_URL\s*=') {
        [void]$out.Add("APP_URL=$correctUrl")
        Write-Host "[FIX] APP_URL -> $correctUrl" -ForegroundColor Yellow
        continue
    }
    if ($line -match '^\s*ASSET_URL\s*=') {
        [void]$out.Add("ASSET_URL=$correctUrl")
        Write-Host "[FIX] ASSET_URL -> $correctUrl" -ForegroundColor Yellow
        continue
    }
    if ($line -match '^\s*APP_SUBDIRECTORY\s*=') {
        [void]$out.Add("APP_SUBDIRECTORY=$correctSub")
        Write-Host "[FIX] APP_SUBDIRECTORY -> $correctSub" -ForegroundColor Yellow
        continue
    }
    if ($line -match '^\s*SESSION_PATH\s*=') {
        [void]$out.Add("SESSION_PATH=$correctSub")
        Write-Host "[FIX] SESSION_PATH -> $correctSub" -ForegroundColor Yellow
        continue
    }
    [void]$out.Add($line)
}

if (-not ($out -join "`n" -match 'APP_URL=')) {
    [void]$out.Add("APP_URL=$correctUrl")
}
if (-not ($out -join "`n" -match 'ASSET_URL=')) {
    [void]$out.Add("ASSET_URL=$correctUrl")
}
if (-not ($out -join "`n" -match 'APP_SUBDIRECTORY=')) {
    [void]$out.Add("APP_SUBDIRECTORY=$correctSub")
}
if (-not ($out -join "`n" -match 'SESSION_PATH=')) {
    [void]$out.Add("SESSION_PATH=$correctSub")
}

Set-Content ".env" ($out -join "`r`n") -Encoding UTF8

$php = "php"
foreach ($root in @("C:\webserver", "C:\laragon")) {
    $p = Get-ChildItem "$root\bin\php\*\php.exe" -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($p) { $php = $p.FullName; break }
}

& $php artisan config:clear
& $php artisan config:cache

Write-Host ""
Write-Host "Selesai. Buka:" -ForegroundColor Green
Write-Host "  $correctUrl/login"
Write-Host ""
Write-Host "JANGAN pakai path folder (C:\webserver\...) di APP_URL." -ForegroundColor Cyan
Write-Host ""
Write-Host "Siakad-API di .env:" -ForegroundColor Cyan
Write-Host "  SIAKAD_API_BASE_URL=http://98.142.245.18/siakad-api/public"
Write-Host "  SIAKAD_API_TOKEN=<sama dengan siakad-api .env>"
