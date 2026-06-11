# Dijalankan di server Windows setelah git pull (GitHub Actions atau manual).
# Contoh: powershell -NoProfile -ExecutionPolicy Bypass -File deploy\remote-post-deploy.ps1
$ErrorActionPreference = "Stop"

$AppDir = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
Set-Location $AppDir

function Get-ToolRoots {
    return @($env:LARAGON_ROOT, "C:\laragon", "C:\webserver", "C:\xampp") |
        Where-Object { $_ -and (Test-Path $_) } | Select-Object -Unique
}

function Get-DeployPhp {
    if ($env:SIFEEDER_PHP -and (Test-Path $env:SIFEEDER_PHP)) {
        return $env:SIFEEDER_PHP
    }

    foreach ($root in Get-ToolRoots) {
        $php = Get-ChildItem -Path "$root\bin\php\*\php.exe" -ErrorAction SilentlyContinue |
            Sort-Object { $_.Directory.Name } -Descending |
            Select-Object -First 1
        if ($php) {
            return $php.FullName
        }
    }

    return "php"
}

function Get-DeployComposer {
    if ($env:SIFEEDER_COMPOSER -and (Test-Path $env:SIFEEDER_COMPOSER)) {
        return $env:SIFEEDER_COMPOSER
    }

    foreach ($root in Get-ToolRoots) {
        $composerBat = Join-Path $root "bin\composer\composer.bat"
        if (Test-Path $composerBat) {
            return $composerBat
        }
    }

    return "composer"
}

function Get-DeployNpm {
    if ($env:SIFEEDER_NPM -and (Test-Path $env:SIFEEDER_NPM)) {
        return $env:SIFEEDER_NPM
    }

    foreach ($root in Get-ToolRoots) {
        $npm = Get-ChildItem -Path "$root\bin\nodejs\*\npm.cmd" -ErrorAction SilentlyContinue |
            Select-Object -First 1
        if ($npm) {
            return $npm.FullName
        }
    }

    return "npm"
}

function Invoke-DeployCommand {
    param(
        [string]$Executable,
        [string[]]$Arguments
    )

    Write-Host ">> $Executable $($Arguments -join ' ')"
    & $Executable @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "Perintah gagal (exit $LASTEXITCODE): $Executable"
    }
}

$php = Get-DeployPhp
$composer = Get-DeployComposer
$npm = Get-DeployNpm

Write-Host "App: $AppDir"
Write-Host "PHP: $php"

# .env tidak pernah dibuat/ditimpa oleh deploy — hanya setup awal (server-setup.ps1) atau manual.
if (-not (Test-Path ".env")) {
    throw ".env tidak ditemukan. Buat sekali manual: copy .env.example .env lalu php artisan key:generate"
}
Write-Host ".env ditemukan — nilai existing tidak diubah oleh skrip deploy."

$dirs = @(
    "storage\framework\cache",
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\logs",
    "bootstrap\cache",
    "database"
)
foreach ($dir in $dirs) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }
}

$sqlite = Join-Path $AppDir "database\database.sqlite"
if (-not (Test-Path $sqlite)) {
    New-Item -ItemType File -Path $sqlite -Force | Out-Null
}

Invoke-DeployCommand $composer @("install", "--no-dev", "--prefer-dist", "--no-interaction", "--optimize-autoloader")
Invoke-DeployCommand $npm @("ci")
Invoke-DeployCommand $npm @("run", "build")

if (-not (Test-Path "public\build\manifest.json")) {
    throw "public/build/manifest.json tidak ada setelah npm build — cek log npm di atas."
}

Invoke-DeployCommand $php @("artisan", "config:clear")
Invoke-DeployCommand $php @("artisan", "view:clear")
Invoke-DeployCommand $php @("artisan", "migrate", "--force")
try {
    Invoke-DeployCommand $php @("artisan", "storage:link")
} catch {
    Write-Host "storage:link dilewati (mungkin sudah ada)."
}

Invoke-DeployCommand $php @("artisan", "config:cache")
Invoke-DeployCommand $php @("artisan", "route:cache")
Invoke-DeployCommand $php @("artisan", "view:cache")

Write-Host "Deploy selesai: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
