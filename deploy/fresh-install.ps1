# DEPRECATED — gunakan deploy\install.ps1
Write-Host "fresh-install.ps1 diganti → install.ps1" -ForegroundColor Yellow
& powershell -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot "install.ps1")
