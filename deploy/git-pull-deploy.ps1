# DEPRECATED — gunakan deploy\update.ps1
Write-Host "git-pull-deploy.ps1 diganti → update.ps1" -ForegroundColor Yellow
& powershell -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot "update.ps1")
