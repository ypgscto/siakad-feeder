# Library bersama — dot-source dari install.ps1 / update.ps1
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
    Write-Host "  OK — $Message" -ForegroundColor Green
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
                    Write-Host "  [FIX] aktifkan extension=$name" -ForegroundColor Yellow
                }
                break
            }
        }
        if (-not $found) {
            Add-Content -Path $IniPath -Value "extension=$name"
            $changed = $true
            Write-Host "  [FIX] tambah extension=$name" -ForegroundColor Yellow
        }
    }

    if ($changed) { Set-Content -Path $IniPath -Value $lines -Encoding UTF8 }
    return $changed
}

function Ensure-PhpSqlite {
    param([string]$PhpExe)

    if ((Test-PhpExtensionLoaded $PhpExe "pdo_sqlite") -and (Test-PhpExtensionLoaded $PhpExe "sqlite3")) {
        Write-DeployOk "pdo_sqlite + sqlite3 aktif"
        return
    }

    $iniInfo = & $PhpExe --ini 2>&1 | Out-String
    $iniMatch = [regex]::Match($iniInfo, "Loaded Configuration File:\s+(.+)")
    if (-not $iniMatch.Success) { throw "php.ini tidak ditemukan. Jalankan: php --ini" }

    $iniPath = $iniMatch.Groups[1].Value.Trim()
    Write-Host "  php.ini: $iniPath"

    Enable-PhpIniExtension $iniPath @("pdo_sqlite", "sqlite3") | Out-Null

    if (-not (Test-PhpExtensionLoaded $PhpExe "pdo_sqlite")) {
        throw @"
pdo_sqlite masih belum aktif.

Buka $iniPath dan pastikan ada (tanpa titik koma):
  extension=pdo_sqlite
  extension=sqlite3

Lalu restart Apache dan jalankan ulang skrip deploy.
"@
    }

    Write-DeployOk "SQLite aktif di PHP CLI"
}

function Initialize-DeployGitRepo {
  if (Test-Path (Join-Path $script:DeployAppDir ".git")) { return }

  Write-DeployWarn "Folder belum punya .git — inisialisasi ke GitHub"
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
    if ($LASTEXITCODE -ne 0) { throw "git fetch gagal — cek koneksi internet & akses GitHub" }

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
        throw "public/build/manifest.json tidak ada — npm run build gagal"
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
}

function Show-DeployFinishMessage {
    param([switch]$IsFreshInstall)

    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host " Deploy selesai — $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Tes di browser:"
    Write-Host "  http://98.142.245.18/siakad-feeder/public/up"
    Write-Host "  http://98.142.245.18/siakad-feeder/public/"
    Write-Host ""
    if ($IsFreshInstall) {
        Write-Host "Langkah berikutnya:"
        Write-Host "  1. Edit .env — APP_URL, SIAKAD_API_*, FEEDER_*"
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
