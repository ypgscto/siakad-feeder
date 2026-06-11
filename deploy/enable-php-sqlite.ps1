# Aktifkan ekstensi SQLite di PHP Windows (wajib untuk Siakad-Feeder default DB_CONNECTION=sqlite).
# Jalankan sebagai Administrator jika perlu restart Apache.
#   powershell -ExecutionPolicy Bypass -File deploy\enable-php-sqlite.ps1
$ErrorActionPreference = "Stop"

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

function Test-PhpExtensionLoaded {
    param([string]$PhpExe, [string]$ExtensionName)
    $out = & $PhpExe -m 2>$null
    return $out -match "^$([regex]::Escape($ExtensionName))$"
}

function Enable-PhpIniExtension {
    param([string]$IniPath, [string[]]$Names)

    $lines = Get-Content $IniPath
    $changed = $false

    foreach ($name in $Names) {
        $pattern = "^\s*;?\s*extension\s*=\s*$([regex]::Escape($name))(\s|$)"
        $found = $false
        for ($i = 0; $i -lt $lines.Count; $i++) {
            if ($lines[$i] -match $pattern) {
                $found = $true
                if ($lines[$i] -match '^\s*;') {
                    $lines[$i] = "extension=$name"
                    $changed = $true
                    Write-Host "[FIX] $IniPath : aktifkan extension=$name"
                }
                break
            }
        }
        if (-not $found) {
            Add-Content -Path $IniPath -Value "extension=$name"
            $changed = $true
            Write-Host "[FIX] $IniPath : tambah extension=$name"
        }
    }

    if ($changed) {
        Set-Content -Path $IniPath -Value $lines -Encoding UTF8
    }

    return $changed
}

$php = Get-DeployPhp
Write-Host "PHP: $php"

if ((Test-PhpExtensionLoaded $php "pdo_sqlite") -and (Test-PhpExtensionLoaded $php "sqlite3")) {
    Write-Host "SQLite sudah aktif (pdo_sqlite + sqlite3)." -ForegroundColor Green
    exit 0
}

$iniInfo = & $php --ini 2>&1 | Out-String
$iniMatch = [regex]::Match($iniInfo, "Loaded Configuration File:\s+(.+)")
if (-not $iniMatch.Success) {
    throw "Tidak bisa menemukan php.ini. Jalankan: php --ini"
}

$iniPath = $iniMatch.Groups[1].Value.Trim()
if (-not (Test-Path $iniPath)) {
    throw "php.ini tidak ditemukan: $iniPath"
}

Write-Host "php.ini: $iniPath" -ForegroundColor Cyan

$changed = Enable-PhpIniExtension $iniPath @("pdo_sqlite", "sqlite3")

if (-not $changed) {
    Write-Host "Ekstensi belum aktif dan tidak ada baris yang bisa di-uncomment." -ForegroundColor Yellow
    Write-Host "Buka $iniPath manual, pastikan ada:"
    Write-Host "  extension=pdo_sqlite"
    Write-Host "  extension=sqlite3"
    exit 1
}

Write-Host ""
Write-Host "Verifikasi ulang..."
if ((Test-PhpExtensionLoaded $php "pdo_sqlite") -and (Test-PhpExtensionLoaded $php "sqlite3")) {
    Write-Host "SQLite aktif." -ForegroundColor Green
} else {
    Write-Host "Ekstensi di php.ini sudah diubah tapi belum ter-load — RESTART Apache/web server." -ForegroundColor Yellow
    foreach ($svc in @("Apache2.4", "apache", "httpd")) {
        $s = Get-Service -Name $svc -ErrorAction SilentlyContinue
        if ($s) {
            Write-Host "Restart: Restart-Service $svc"
            try {
                Restart-Service $svc -Force -ErrorAction Stop
                Write-Host "Service $svc di-restart." -ForegroundColor Green
            } catch {
                Write-Host "Gagal restart $svc — jalankan PowerShell sebagai Administrator." -ForegroundColor Red
            }
            break
        }
    }
}

Write-Host ""
Write-Host "Lanjut: php artisan migrate --force"
