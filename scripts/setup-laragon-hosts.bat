@echo off
REM Tambahkan entri hosts untuk siakad-feeder.test (jalankan sebagai Administrator)
set HOSTS=%SystemRoot%\System32\drivers\etc\hosts
findstr /C:"siakad-feeder.test" %HOSTS% >nul
if %errorlevel%==0 (
    echo Entri siakad-feeder.test sudah ada di hosts.
) else (
    echo 127.0.0.1      siakad-feeder.test       #laragon magic!>> %HOSTS%
    echo Entri hosts ditambahkan. Restart Apache di Laragon.
)
pause
