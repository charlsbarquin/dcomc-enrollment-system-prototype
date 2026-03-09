# Create shortcuts with DCC logo icon so batch files look like custom apps in the folder.
# Run once: right-click -> Run with PowerShell, or: powershell -ExecutionPolicy Bypass -File "create-dcc-shortcuts.ps1"

$ErrorActionPreference = "Stop"
$scriptDir = $PSScriptRoot
if (-not $scriptDir) { $scriptDir = Get-Location }
$pngPath = Join-Path $scriptDir "public\images\dcc-logo.png"
$icoPath = Join-Path $scriptDir "public\images\dcc-logo.ico"

if (-not (Test-Path $pngPath)) {
    Write-Host "Logo not found at: $pngPath" -ForegroundColor Yellow
    exit 1
}

# Try to create .ico from PNG (best compatibility for shortcut icons)
$iconPath = $pngPath
try {
    Add-Type -AssemblyName System.Drawing
    $bmp = [System.Drawing.Bitmap]::FromFile($pngPath)
    $icon = [System.Drawing.Icon]::FromHandle($bmp.GetHicon())
    $ms = New-Object System.IO.MemoryStream
    $icon.Save($ms)
    [System.IO.File]::WriteAllBytes($icoPath, $ms.ToArray())
    $ms.Close()
    $icon.Dispose()
    $bmp.Dispose()
    $iconPath = $icoPath
    Write-Host "Created icon: public\images\dcc-logo.ico" -ForegroundColor Green
} catch {
    Write-Host "Using PNG for icon (Windows 10+): $_" -ForegroundColor Yellow
}

# ----- Create shortcuts -----
$WshShell = New-Object -ComObject WScript.Shell
$shortcuts = @(
    @{ Name = "Start DCOMC (Local)";     Target = "start-local.bat";     Description = "Start DCOMC local server and Vite" },
    @{ Name = "Start DCOMC (Network)";    Target = "start-server.bat";    Description = "Start DCOMC for network access" },
    @{ Name = "Stop DCOMC";               Target = "stop-system.bat";     Description = "Stop Laravel and Vite" },
    @{ Name = "Setup New PC";             Target = "setup-new-pc.bat";     Description = "Setup project on a new computer" }
)

foreach ($s in $shortcuts) {
    $batPath = Join-Path $scriptDir $s.Target
    if (-not (Test-Path $batPath)) { continue }
    $lnkPath = Join-Path $scriptDir ($s.Name + ".lnk")
    $shortcut = $WshShell.CreateShortcut($lnkPath)
    $shortcut.TargetPath = $batPath
    $shortcut.WorkingDirectory = $scriptDir
    $shortcut.Description = $s.Description
    $shortcut.IconLocation = "$iconPath,0"
    $shortcut.Save()
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($shortcut) | Out-Null
    Write-Host "Created shortcut: $($s.Name).lnk" -ForegroundColor Green
}
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($WshShell) | Out-Null

Write-Host ""
Write-Host "Done. Use the new .lnk files (e.g. 'Start DCOMC (Local).lnk') for the DCC icon. You can hide or delete the .bat files from the folder if you prefer only the shortcuts." -ForegroundColor Cyan
