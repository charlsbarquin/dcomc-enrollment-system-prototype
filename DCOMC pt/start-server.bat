@echo off
title DCOMC - Network Server
cd /d "%~dp0"

REM ========== CONFIG (edit these if needed) ==========
set HEIDISQL="C:\Program Files\HeidiSQL\heidisql.exe"
REM ===================================================

echo Starting DCOMC system (network access)...

REM --- Show DCOMC / Daraga Community College logo ---
if exist "%~dp0public\images\dcc-logo.png" start "" "%~dp0public\images\dcc-logo.png"

REM --- Start MySQL/MariaDB ---
echo Checking database...
net start MySQL 2>nul
if %errorlevel% neq 0 net start MariaDB 2>nul
if %errorlevel% neq 0 net start MySQL80 2>nul
if %errorlevel% neq 0 net start MySQL57 2>nul
echo Database should be running.

REM --- Optional: open HeidiSQL ---
if exist %HEIDISQL% (
    echo Opening HeidiSQL...
    start "" %HEIDISQL%
    timeout /t 2 /nobreak >nul
)

REM --- Start Vite dev server (assets, hot reload) in a new window ---
echo.
echo Starting Vite (npm run dev)...
start "Vite" cmd /k "npm run dev"
timeout /t 3 /nobreak >nul

REM --- Start Laravel in a new window (network: 0.0.0.0:8000) ---
echo Starting Laravel at http://0.0.0.0:8000
echo Other devices can use: http://YOUR_IP:8000
start "Laravel Server" cmd /k "php artisan serve --host=0.0.0.0 --port=8000"

REM Wait until server is actually running (shows "Server running on [http://127.0.0.1:8000]...") then open Edge
echo Waiting for server to be ready...
set WAIT_COUNT=0
:wait_for_server
timeout /t 2 /nobreak >nul
powershell -NoProfile -Command "try { $r = Invoke-WebRequest -Uri 'http://127.0.0.1:8000' -UseBasicParsing -TimeoutSec 3; exit 0 } catch { exit 1 }" 2>nul
if %errorlevel% equ 0 goto server_ready
set /a WAIT_COUNT+=1
if %WAIT_COUNT% geq 30 (
    echo Server did not respond in time. Opening browser anyway...
    goto server_ready
)
goto wait_for_server
:server_ready
echo Server is running. Opening Microsoft Edge...
start msedge http://127.0.0.1:8000

echo.
echo Laravel and Vite are running in the other windows.
echo Use stop-system.bat or close those windows to stop.
pause
