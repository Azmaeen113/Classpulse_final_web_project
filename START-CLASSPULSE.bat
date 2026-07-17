@echo off
title ClassPulse Server
cd /d "%~dp0"
echo.
echo  Starting ClassPulse...
echo  Open this in your browser:
echo.
echo     http://127.0.0.1:8877/login
echo.
echo  Keep this window OPEN while you use the app.
echo  Press Ctrl+C to stop.
echo.
php artisan serve --host=127.0.0.1 --port=8877
pause
