@echo off
title DCOMC - Setup on New PC
cd /d "%~dp0"

echo ============================================
echo   DCOMC - First-time setup on this computer
echo ============================================
echo.

REM Check PHP
php -v >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP not found. Install PHP 8.2+ and add it to PATH.
    pause
    exit /b 1
)
echo [OK] PHP found.

REM Check Composer
composer --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Composer not found. Install Composer and add it to PATH.
    pause
    exit /b 1
)
echo [OK] Composer found.

REM Check Node/npm
node -v >nul 2>&1
if errorlevel 1 (
    echo ERROR: Node.js not found. Install Node.js LTS and add it to PATH.
    pause
    exit /b 1
)
echo [OK] Node.js found.
echo.

REM 1. Composer install
echo [1/6] Installing PHP dependencies (composer install)...
composer install --no-interaction
if errorlevel 1 (
    echo Composer install failed.
    pause
    exit /b 1
)
echo.

REM 2. NPM install
echo [2/6] Installing Node dependencies (npm install)...
call npm install
if errorlevel 1 (
    echo npm install failed.
    pause
    exit /b 1
)
echo.

REM 3. .env and key
if not exist .env (
    echo [3/6] No .env found. Copying .env.example to .env and generating key...
    copy .env.example .env
    php artisan key:generate
    echo.
    echo IMPORTANT: Edit .env and set your database name, user, and password.
    echo Then run this script again, or run: php artisan migrate
    echo.
) else (
    echo [3/6] .env exists. Skipping key generate (run "php artisan key:generate" if needed).
)
echo.

REM 4. Remind about database
echo [4/6] Make sure MySQL is running and database "dcomc_system" exists.
echo       If not, create it in HeidiSQL or phpMyAdmin, then press any key to continue...
pause >nul
echo.

REM 5. Migrate
echo [5/6] Running database migrations...
php artisan migrate --force
if errorlevel 1 (
    echo Migration failed. Create the database "dcomc_system" and check .env DB_* settings, then run this script again.
    pause
    exit /b 1
)
echo.

REM 6. Build frontend
echo [6/6] Building frontend assets (npm run build)...
call npm run build
if errorlevel 1 (
    echo npm run build failed.
    pause
    exit /b 1
)
echo.

echo ============================================
echo   Setup complete.
echo   Run start-local.bat or start-server.bat to start the system.
echo ============================================
echo.
echo Optional: To seed sample data, run:  php artisan db:seed
echo.
pause
