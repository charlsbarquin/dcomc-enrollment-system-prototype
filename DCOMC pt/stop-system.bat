@echo off
setlocal enabledelayedexpansion
title DCOMC - Stop System
cd /d "%~dp0"

echo Stopping DCOMC (Laravel + Vite)...

REM Find and kill process on port 5173 (Vite dev server)
set FOUND=0
for /f "tokens=5" %%a in ('netstat -ano 2^>nul ^| findstr ":5173" ^| findstr "LISTENING"') do (
    echo Stopping Vite PID %%a ...
    taskkill /PID %%a /F 2>nul
    set FOUND=1
)

REM Find and kill process on port 8000 (php artisan serve)
for /f "tokens=5" %%a in ('netstat -ano 2^>nul ^| findstr ":8000" ^| findstr "LISTENING"') do (
    echo Stopping Laravel PID %%a ...
    taskkill /PID %%a /F 2>nul
    set FOUND=1
)

if !FOUND!==0 (
    echo No server found on port 8000 or 5173.
    echo Try closing the terminal windows, or run this as Administrator.
) else (
    echo Stopped.
)

echo.
pause
