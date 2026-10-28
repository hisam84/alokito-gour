# Alokito Gour Local Server Launcher
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

try {
    $Host.UI.RawUI.WindowTitle = "Alokito Gour Local Server"
} catch {}

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectRoot = Split-Path -Parent $scriptDir
Set-Location $projectRoot

function Show-Banner {
    Write-Host "======================================================================" -ForegroundColor Cyan
    Write-Host "       ALOKITO GOUR NEWS PORTAL - LOCAL DEVELOPMENT SERVER            " -ForegroundColor Yellow
    Write-Host "======================================================================" -ForegroundColor Cyan
    Write-Host ""
}

Show-Banner

# 1. Search for PHP executable across all common locations
function Find-PhpBinary {
    # Check project local portable PHP
    $localPhp = Join-Path $projectRoot "php\php.exe"
    if (Test-Path $localPhp) { return $localPhp }

    # Check system PATH
    $cmd = Get-Command php.exe -ErrorAction SilentlyContinue
    if ($cmd -and (Test-Path $cmd.Source)) { return $cmd.Source }

    # Common Windows installation locations
    $searchPatterns = @(
        "C:\xampp\php\php.exe",
        "D:\xampp\php\php.exe",
        "E:\xampp\php\php.exe",
        "C:\laragon\bin\php\php*\php.exe",
        "D:\laragon\bin\php\php*\php.exe",
        "C:\wamp64\bin\php\php*\php.exe",
        "C:\wamp\bin\php\php*\php.exe",
        "C:\Program Files\php*\php.exe",
        "C:\Program Files (x86)\php*\php.exe",
        "C:\php\php.exe",
        "D:\php\php.exe",
        "C:\tools\php\php.exe",
        "$env:LOCALAPPDATA\Programs\php\php.exe",
        "$env:USERPROFILE\scoop\apps\php\current\php.exe",
        "C:\Users\$env:USERNAME\.config\herd\bin\php.exe"
    )

    foreach ($pat in $searchPatterns) {
        $foundItems = Resolve-Path $pat -ErrorAction SilentlyContinue
        if ($foundItems) {
            $matched = $foundItems | Select-Object -Last 1 -ExpandProperty Path
            if (Test-Path $matched) { return $matched }
        }
    }

    return $null
}

$phpBin = Find-PhpBinary

# If PHP is not found anywhere on the computer, prompt with options
while (-not $phpBin) {
    Write-Host "[!] PHP was not detected on this computer!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please choose an option to run the server:" -ForegroundColor Yellow
    Write-Host "  [1] Automatically download and setup portable PHP (Recommended)" -ForegroundColor Cyan
    Write-Host "  [2] Open official XAMPP download page" -ForegroundColor White
    Write-Host "  [3] Enter custom PHP path manually" -ForegroundColor White
    Write-Host "  [0] Exit" -ForegroundColor Gray
    Write-Host ""
    
    $choice = Read-Host "Enter your choice (1/2/3/0)"

    if ($choice -eq "1") {
        $setupScript = Join-Path $projectRoot "tools\setup_portable_php.ps1"
        if (Test-Path $setupScript) {
            & $setupScript
        } else {
            Write-Host "[!] setup_portable_php.ps1 was not found!" -ForegroundColor Red
        }
        $phpBin = Find-PhpBinary
        if (-not $phpBin) {
            Write-Host "[!] PHP download could not be completed. Please install XAMPP or check connection." -ForegroundColor Red
        }
    } elseif ($choice -eq "2") {
        Start-Process "https://www.apachefriends.org/download.html"
        Write-Host "After installing XAMPP, please run this script again." -ForegroundColor Yellow
        exit 0
    } elseif ($choice -eq "3") {
        $customPath = Read-Host "Enter full path to php.exe (e.g. C:\php\php.exe)"
        if ($customPath -and (Test-Path $customPath)) {
            $phpBin = (Resolve-Path $customPath).Path
        } else {
            Write-Host "[!] File not found: $customPath" -ForegroundColor Red
        }
    } elseif ($choice -eq "0") {
        exit 0
    }
}

# 2. Configure PHP extension directory dynamically
$phpDir = Split-Path -Parent $phpBin
$extDir = Join-Path $phpDir "ext"
$extArgs = @()
if (Test-Path $extDir) {
    $extArgs += "-d"
    $extArgs += "extension_dir=$extDir"
}

# 3. Check SQLite database, auto-initialize if missing
$sqlitePath = Join-Path $projectRoot "database\alokito_gour.sqlite"
if (-not (Test-Path $sqlitePath)) {
    Write-Host "[*] Database not found. Initializing local SQLite database..." -ForegroundColor Yellow
    $initScript = Join-Path $projectRoot "database\init_sqlite.php"
    $iniFile = Join-Path $projectRoot "php.ini"
    & $phpBin @extArgs -c $iniFile $initScript
    Write-Host "[OK] Database ready!" -ForegroundColor Green
    Write-Host ""
}

# 4. Check available port (Default: 8000, fallback: 8080, 8888, 8001)
function Test-PortOccupied($p) {
    try {
        $listener = [System.Net.Sockets.TcpListener]::new([System.Net.IPAddress]::Loopback, $p)
        $listener.Start()
        $listener.Stop()
        return $false
    } catch {
        return $true
    }
}

$port = 8000
if (Test-PortOccupied $port) {
    Write-Host "[!] Port 8000 is busy. Trying port 8080..." -ForegroundColor Yellow
    $port = 8080
    if (Test-PortOccupied $port) {
        Write-Host "[!] Port 8080 is also busy. Trying port 8888..." -ForegroundColor Yellow
        $port = 8888
        if (Test-PortOccupied $port) {
            $port = 8001
        }
    }
}

# 5. Detect local LAN IP for mobile preview on same Wi-Fi
$localIp = $null
try {
    $ipObj = Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue |
        Where-Object { $_.IPAddress -notlike '127.*' -and $_.IPAddress -notlike '169.254.*' } |
        Select-Object -First 1
    if ($ipObj) { $localIp = $ipObj.IPAddress }
} catch {}

# 6. Display server connection details
Write-Host "======================================================================" -ForegroundColor Green
Write-Host "   SERVER IS READY AND RUNNING!                                       " -ForegroundColor Green
Write-Host "======================================================================" -ForegroundColor Green
Write-Host "  [OK] PHP Engine:   $phpBin" -ForegroundColor Gray
Write-Host "  [OK] Database:     database\alokito_gour.sqlite [Active]" -ForegroundColor Gray
Write-Host "  [OK] Web Portal:   http://localhost:$port" -ForegroundColor Cyan
Write-Host "  [OK] Admin Panel:  http://localhost:$port/admin/login.php" -ForegroundColor Cyan
if ($localIp) {
    Write-Host "  [OK] Mobile / LAN: http://$($localIp):$port (On same Wi-Fi)" -ForegroundColor White
}
Write-Host "----------------------------------------------------------------------" -ForegroundColor DarkGray
Write-Host "  Admin Login Credentials:" -ForegroundColor Yellow
Write-Host "    Email:    admin@alokitogour.com" -ForegroundColor White
Write-Host "    Password: admin123" -ForegroundColor White
Write-Host "----------------------------------------------------------------------" -ForegroundColor DarkGray
Write-Host "  [*] Opening website in your default browser..." -ForegroundColor DarkCyan
Write-Host "  [*] Press Ctrl + C in this window to stop the server." -ForegroundColor Yellow
Write-Host "======================================================================" -ForegroundColor Green
Write-Host ""

# 7. Open browser automatically in background
try {
    Start-Job -ScriptBlock {
        param($url)
        Start-Sleep -Seconds 1
        Start-Process $url
    } -ArgumentList "http://localhost:$port" | Out-Null
} catch {}

# 8. Start PHP Built-in Server
$iniFile = Join-Path $projectRoot "php.ini"
$routerFile = Join-Path $projectRoot "router.php"

& $phpBin @extArgs -c $iniFile -S "0.0.0.0:$port" $routerFile
