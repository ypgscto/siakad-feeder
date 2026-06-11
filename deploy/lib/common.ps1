# Library bersama - dot-source dari install.ps1 / update.ps1
$ErrorActionPreference = "Stop"

$script:DeployAppDir = (Resolve-Path (Join-Path $PSScriptRoot "..\..")).Path
$script:DeployGitRemote = "https://github.com/ypgscto/siakad-feeder.git"
$script:DeployGitBranch = "main"

function Get-DeployAppDir { return $script:DeployAppDir }

function Write-DeployStep {
    param([int]$Number, [int]$Total, [string]$Message)
    Write-Host ""
    Write-Host "[$Number/$Total] $Message" -ForegroundColor Cyan
}

function Write-DeployOk([string]$Message) {
    Write-Host "  OK - $Message" -ForegroundColor Green
}

function Write-DeployWarn([string]$Message) {
    Write-Host "  ! $Message" -ForegroundColor Yellow
}

function Get-ToolRoots {
    return @($env:LARAGON_ROOT, "C:\laragon", "C:\webserver", "C:\xampp") |
        Where-Object { $_ -and (Test-Path $_) } | Select-Object -Unique
}

function Get-DeployPhp {
    if ($env:SIFEEDER_PHP -and (Test-Path $env:SIFEEDER_PHP)) { return $env:SIFEEDER_PHP }
    foreach ($root in Get-ToolRoots) {
        $php = Get-ChildItem "$root\bin\php\*\php.exe" -ErrorAction SilentlyContinue |
            Sort-Object { $_.Directory.Name } -Descending | Select-Object -First 1
        if ($php) { return $php.FullName }
    }
    return "php"
}

function Get-DeployComposer {
    if ($env:SIFEEDER_COMPOSER -and (Test-Path $env:SIFEEDER_COMPOSER)) { return $env:SIFEEDER_COMPOSER }
    foreach ($root in Get-ToolRoots) {
        $composerBat = Join-Path $root "bin\composer\composer.bat"
        if (Test-Path $composerBat) { return $composerBat }
    }
    return "composer"
}

function Get-DeployNpm {
    if ($env:SIFEEDER_NPM -and (Test-Path $env:SIFEEDER_NPM)) { return $env:SIFEEDER_NPM }
    foreach ($root in Get-ToolRoots) {
        $npm = Get-ChildItem "$root\bin\nodejs\*\npm.cmd" -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($npm) { return $npm.FullName }
    }
    return "npm"
}

function Invoke-DeployCommand {
    param([string]$Executable, [string[]]$Arguments)
    Write-Host "  >> $Executable $($Arguments -join ' ')" -ForegroundColor DarkGray
    & $Executable @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "Perintah gagal (exit $LASTEXITCODE): $Executable $($Arguments -join ' ')"
    }
}

function Ensure-DeployBackupDir {
    $dir = Join-Path $script:DeployAppDir ".deploy-backup"
    if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
    return $dir
}

function Backup-DeployProtectedFiles {
    $backupDir = Ensure-DeployBackupDir
    $stamp = Get-Date -Format "yyyyMMdd-HHmmss"

    if (Test-Path (Join-Path $script:DeployAppDir ".env")) {
        Copy-Item (Join-Path $script:DeployAppDir ".env") (Join-Path $backupDir "env-$stamp.bak") -Force
        Write-DeployOk ".env di-backup ke .deploy-backup\env-$stamp.bak"
    }

    $sqlite = Join-Path $script:DeployAppDir "database\database.sqlite"
    if (Test-Path $sqlite) {
        Copy-Item $sqlite (Join-Path $backupDir "database-$stamp.sqlite") -Force
        Write-DeployOk "database.sqlite di-backup"
    }
}

function Ensure-DeployDirectories {
    foreach ($dir in @(
        "storage\framework\cache", "storage\framework\sessions", "storage\framework\views",
        "storage\logs", "bootstrap\cache", "database"
    )) {
        $path = Join-Path $script:DeployAppDir $dir
        if (-not (Test-Path $path)) { New-Item -ItemType Directory -Path $path -Force | Out-Null }
    }

    $sqlite = Join-Path $script:DeployAppDir "database\database.sqlite"
    if (-not (Test-Path $sqlite)) { New-Item -ItemType File -Path $sqlite -Force | Out-Null }
}

function Test-PhpExtensionLoaded {
    param([string]$PhpExe, [string]$ExtensionName)
    $out = & $PhpExe -m 2>$null
    return $out -match "^$([regex]::Escape($ExtensionName))$"
}

function Get-PhpIniPath {
    param([string]$PhpExe)
    $iniInfo = & $PhpExe --ini 2>&1 | Out-String
    $iniMatch = [regex]::Match($iniInfo, "Loaded Configuration File:\s+(.+)")
    if (-not $iniMatch.Success) { return $null }
    $path = $iniMatch.Groups[1].Value.Trim()
    if ($path -eq '(none)') { return $null }
    return $path
}

function Save-PhpIniLines {
    param([string]$IniPath, [string[]]$Lines)
    $backup = "$IniPath.bak-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
    Copy-Item $IniPath $backup -Force
    Write-Host "  [BACKUP] $backup" -ForegroundColor DarkGray
    [System.IO.File]::WriteAllLines($IniPath, $Lines)
}

function Set-PhpIniExtensionDir {
    param([string]$IniPath, [string]$PhpDir)
    $lines = [System.Collections.ArrayList]@(Get-Content $IniPath)
    $extDir = Join-Path $PhpDir "ext"
    $changed = $false
    $found = $false

    for ($i = 0; $i -lt $lines.Count; $i++) {
        if ($lines[$i] -match '^\s*;?\s*extension_dir\s*=') {
            $found = $true
            if ($lines[$i] -match '^\s*;' -or $lines[$i] -notmatch [regex]::Escape($extDir)) {
                $lines[$i] = "extension_dir = `"$extDir`""
                $changed = $true
                Write-Host "  [FIX] extension_dir = $extDir" -ForegroundColor Yellow
            }
            break
        }
    }

    if (-not $found) {
        [void]$lines.Add("extension_dir = `"$extDir`"")
        $changed = $true
        Write-Host "  [FIX] tambah extension_dir = $extDir" -ForegroundColor Yellow
    }

    if ($changed) { Save-PhpIniLines $IniPath $lines.ToArray() }
}

function Enable-PhpIniExtensionLine {
    param(
        [string]$IniPath,
        [string[]]$Candidates
    )
    $lines = [System.Collections.ArrayList]@(Get-Content $IniPath)
    $changed = $false

    foreach ($candidate in $Candidates) {
        $escaped = [regex]::Escape($candidate)
        $pattern = "^\s*;?\s*extension\s*=\s*$escaped(\s|$)"
        $found = $false

        for ($i = 0; $i -lt $lines.Count; $i++) {
            if ($lines[$i] -match $pattern) {
                $found = $true
                if ($lines[$i] -match '^\s*;') {
                    $lines[$i] = "extension=$candidate"
                    $changed = $true
                    Write-Host "  [FIX] aktifkan extension=$candidate" -ForegroundColor Yellow
                }
                break
            }
        }

        if (-not $found) {
            [void]$lines.Add("extension=$candidate")
            $changed = $true
            Write-Host "  [FIX] tambah extension=$candidate" -ForegroundColor Yellow
        }
    }

    if ($changed) { Save-PhpIniLines $IniPath $lines.ToArray() }
    return $changed
}

function Restart-DeployApache {
    foreach ($svc in @("Apache2.4", "apache", "httpd", "wampapache64", "wampapache")) {
        $s = Get-Service -Name $svc -ErrorAction SilentlyContinue
        if (-not $s) { continue }
        Write-Host "  >> Restart-Service $svc" -ForegroundColor DarkGray
        try {
            Restart-Service $svc -Force -ErrorAction Stop
            Write-DeployOk "Apache ($svc) di-restart"
        } catch {
            Write-DeployWarn "Gagal restart $svc - jalankan PowerShell sebagai Administrator"
        }
        return
    }
    Write-DeployWarn "Service Apache tidak ditemukan - restart manual web server"
}

function Ensure-PhpSqlite {
    param([string]$PhpExe)

    Write-Host ""
    Write-Host "--- Aktifkan SQLite di PHP ---" -ForegroundColor Cyan
    Write-Host "  PHP: $PhpExe"

    if ((Test-PhpExtensionLoaded $PhpExe "pdo_sqlite") -and (Test-PhpExtensionLoaded $PhpExe "sqlite3")) {
        Write-DeployOk "pdo_sqlite + sqlite3 sudah aktif"
        return
    }

    $phpDir = Split-Path $PhpExe -Parent
    $extDir = Join-Path $phpDir "ext"
    Write-Host "  Folder ext: $extDir"

    $pdoDll = Join-Path $extDir "php_pdo_sqlite.dll"
    $sqliteDll = Join-Path $extDir "php_sqlite3.dll"
    if (-not (Test-Path $pdoDll)) {
        throw @"
File tidak ditemukan: $pdoDll

PHP di server ini tidak punya modul SQLite. Solusi:
  1. Install ulang PHP "Thread Safe" yang menyertakan ext\php_pdo_sqlite.dll
  2. Atau salin folder ext dari instalasi PHP lengkap (Laragon) ke $extDir
"@
    }

    $iniPath = Get-PhpIniPath $PhpExe
    if (-not $iniPath -or -not (Test-Path $iniPath)) {
        throw "php.ini tidak ditemukan. Jalankan: `"$PhpExe`" --ini"
    }
    Write-Host "  php.ini: $iniPath"

    Set-PhpIniExtensionDir $iniPath $phpDir
    Enable-PhpIniExtensionLine $iniPath @("pdo_sqlite", "php_pdo_sqlite.dll") | Out-Null
    Enable-PhpIniExtensionLine $iniPath @("sqlite3", "php_sqlite3.dll") | Out-Null

    if (-not (Test-PhpExtensionLoaded $PhpExe "pdo_sqlite")) {
        Write-DeployWarn "pdo_sqlite belum aktif setelah edit php.ini"
        Restart-DeployApache
        Start-Sleep -Seconds 2
    }

    if (-not (Test-PhpExtensionLoaded $PhpExe "pdo_sqlite")) {
        Write-Host ""
        Write-Host "  Verifikasi manual:" -ForegroundColor Yellow
        Write-Host "    `"$PhpExe`" -m | findstr -i sqlite"
        throw @"
pdo_sqlite masih belum aktif.

Edit manual $iniPath :
  extension_dir = "$extDir"
  extension=php_pdo_sqlite.dll
  extension=php_sqlite3.dll

Simpan, restart Apache, lalu: php -m | findstr -i sqlite
"@
    }

    Write-DeployOk "pdo_sqlite aktif"
    & $PhpExe -m 2>$null | Select-String -Pattern "sqlite" | ForEach-Object { Write-Host "    $_" -ForegroundColor Green }
    Restart-DeployApache
}

function Initialize-DeployGitRepo {
  if (Test-Path (Join-Path $script:DeployAppDir ".git")) { return }

  Write-DeployWarn "Folder belum punya .git - inisialisasi ke GitHub"
  Set-Location $script:DeployAppDir
  Invoke-DeployCommand "git" @("init")
  $remotes = & git remote 2>$null
  if ($remotes -notcontains "origin") {
    Invoke-DeployCommand "git" @("remote", "add", "origin", $script:DeployGitRemote)
  }
}

function Sync-DeployFromGitHub {
    Set-Location $script:DeployAppDir
    Initialize-DeployGitRepo

    Backup-DeployProtectedFiles

    Write-Host "  >> git fetch origin $($script:DeployGitBranch)"
    & git fetch origin $script:DeployGitBranch
    if ($LASTEXITCODE -ne 0) { throw 'git fetch gagal - cek koneksi internet dan akses GitHub' }

    Write-Host "  >> git checkout -B $($script:DeployGitBranch) origin/$($script:DeployGitBranch)"
    & git checkout -B $script:DeployGitBranch "origin/$($script:DeployGitBranch)"
    if ($LASTEXITCODE -ne 0) { throw "git checkout gagal" }

    Write-Host "  >> git reset --hard origin/$($script:DeployGitBranch)"
    & git reset --hard "origin/$($script:DeployGitBranch)"
    if ($LASTEXITCODE -ne 0) { throw "git reset gagal" }

    Write-Host "  >> git clean -fd (kecuali .env, database, storage)"
    & git clean -fd `
        -e .env `
        -e "database/database.sqlite" `
        -e storage `
        -e ".deploy-backup"
    if ($LASTEXITCODE -ne 0) { Write-DeployWarn "git clean mengembalikan peringatan (bisa diabaikan jika folder sudah bersih)" }

    Write-DeployOk "Kode sama dengan GitHub branch $($script:DeployGitBranch)"
}

function Test-DeployUsesSqlite {
    $envPath = Join-Path $script:DeployAppDir ".env"
    if (-not (Test-Path $envPath)) { return $true }
    $raw = Get-Content $envPath -Raw
    return $raw -match '(?m)^\s*DB_CONNECTION\s*=\s*sqlite\s*$'
}

function Invoke-DeployBuild {
    param(
        [string]$Php,
        [string]$Composer,
        [string]$Npm,
        [switch]$Seed
    )

    Ensure-DeployDirectories

    if (Test-DeployUsesSqlite) {
        Ensure-PhpSqlite $Php
    }

    $envPath = Join-Path $script:DeployAppDir ".env"
    if (-not (Test-Path $envPath)) {
        throw ".env tidak ada. Instalasi pertama: jalankan deploy\install.ps1"
    }

    Invoke-DeployCommand $Composer @("install", "--no-dev", "--prefer-dist", "--no-interaction", "--optimize-autoloader")
    Invoke-DeployCommand $Npm @("ci")
    Invoke-DeployCommand $Npm @("run", "build")

    if (-not (Test-Path (Join-Path $script:DeployAppDir "public\build\manifest.json"))) {
        throw "public/build/manifest.json tidak ada - npm run build gagal"
    }
    Write-DeployOk "Asset Vite ter-build"

    Invoke-DeployCommand $Php @("artisan", "config:clear")
    Invoke-DeployCommand $Php @("artisan", "view:clear")
    Invoke-DeployCommand $Php @("artisan", "migrate", "--force")

    try {
        Invoke-DeployCommand $Php @("artisan", "storage:link")
    } catch {
        Write-DeployWarn "storage:link dilewati"
    }

    if ($Seed) {
        Invoke-DeployCommand $Php @("artisan", "db:seed", "--force")
    }

    Invoke-DeployCommand $Php @("artisan", "config:cache")
    Invoke-DeployCommand $Php @("artisan", "route:cache")
    Invoke-DeployCommand $Php @("artisan", "view:cache")

    Write-Host ""
    Write-Host "  >> Verifikasi cepat"
    $about = & $Php artisan about --only=environment 2>&1 | Out-String
    if ($about -match 'Error|Exception') {
        throw "Laravel gagal bootstrap setelah deploy. Jalankan: deploy\diagnose.ps1"
    }
    Write-DeployOk "Laravel bootstrap OK"
}

function Show-DeployFinishMessage {
    param([switch]$IsFreshInstall)

    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host " Deploy selesai - $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Tes di browser:"
    Write-Host "  http://98.142.245.18/siakad-feeder/public/up"
    Write-Host "  http://98.142.245.18/siakad-feeder/public/"
    Write-Host "  (atau http://98.142.245.18/siakad-feeder/ - redirect ke /public/)"
    Write-Host ""
    if ($IsFreshInstall) {
        Write-Host "Langkah berikutnya:"
        Write-Host "  1. Edit .env - APP_URL, SIAKAD_API_*, FEEDER_*"
        Write-Host "  2. php artisan config:cache"
        Write-Host "  3. Login: admin@gmail.com / 123456"
        Write-Host "  4. Menu Pengaturan Koneksi"
    } else {
        Write-Host "Update berikutnya:"
        Write-Host "  powershell -ExecutionPolicy Bypass -File deploy\update.ps1"
    }
    Write-Host ""
    Write-Host "Log error: storage\logs\laravel.log"
    Write-Host ""
}
