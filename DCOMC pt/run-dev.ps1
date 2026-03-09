# DCOMC - Run development environment (Windows)
# Requires: PHP 8.2+, Node/npm, MySQL running

Write-Host "Checking MySQL on 127.0.0.1:3306..." -ForegroundColor Cyan
$tcp = New-Object System.Net.Sockets.TcpClient
try {
    $tcp.Connect("127.0.0.1", 3306)
    $tcp.Close()
    Write-Host "MySQL is reachable." -ForegroundColor Green
} catch {
    Write-Host "ERROR: Cannot connect to MySQL at 127.0.0.1:3306" -ForegroundColor Red
    Write-Host "Start MySQL (XAMPP, WAMP, or 'net start MySQL') and ensure database 'dcomc_system' exists." -ForegroundColor Yellow
    exit 1
}

Write-Host "`nStarting Laravel (server + queue + Vite)..." -ForegroundColor Cyan
Write-Host "App URL: http://127.0.0.1:8000" -ForegroundColor Green
Write-Host "Press Ctrl+C to stop.`n" -ForegroundColor Gray
composer run dev:win
