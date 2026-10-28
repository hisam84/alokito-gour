[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectDir = Split-Path -Parent $scriptDir
$phpDir = Join-Path $projectDir "php"
$zipPath = Join-Path $projectDir "php-temp.zip"

Write-Host "====================================================" -ForegroundColor Cyan
Write-Host "  Alokito Gour - Portable PHP Auto-Downloader" -ForegroundColor Cyan
Write-Host "====================================================" -ForegroundColor Cyan

if (Test-Path (Join-Path $phpDir "php.exe")) {
    Write-Host "[OK] Portable PHP is already installed in $phpDir" -ForegroundColor Green
    exit 0
}

Write-Host "[*] Checking available PHP versions from windows.php.net..." -ForegroundColor Yellow

$phpZipUrl = "https://windows.php.net/downloads/releases/archives/php-8.2.20-nts-Win32-vs16-x64.zip"

try {
    $releasesHtml = (Invoke-WebRequest -Uri "https://windows.php.net/downloads/releases/" -UseBasicParsing -TimeoutSec 10).Content
    if ($releasesHtml -match 'href="(?<zip>php-8\.[23]\.[0-9]+-nts-Win32-vs16-x64\.zip)"') {
        $phpZipUrl = "https://windows.php.net/downloads/releases/" + $Matches['zip']
    }
} catch {
    Write-Host "[!] Using fallback verified release URL." -ForegroundColor DarkYellow
}

Write-Host "[*] Downloading portable PHP package..." -ForegroundColor Cyan
Write-Host "    Source: $phpZipUrl" -ForegroundColor Gray
Write-Host "    This usually takes about 20-30 seconds depending on your connection..." -ForegroundColor Gray

try {
    [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
    Invoke-WebRequest -Uri $phpZipUrl -OutFile $zipPath -UseBasicParsing
    
    Write-Host "[*] Extracting PHP to project folder ($phpDir)..." -ForegroundColor Cyan
    if (-not (Test-Path $phpDir)) {
        New-Item -ItemType Directory -Path $phpDir -Force | Out-Null
    }
    
    if (Get-Command tar.exe -ErrorAction SilentlyContinue) {
        tar -xf "$zipPath" -C "$phpDir"
    } else {
        Expand-Archive -Path $zipPath -DestinationPath $phpDir -Force
    }
    
    if (Test-Path $zipPath) {
        Remove-Item $zipPath -Force
    }
    
    $phpIniDev = Join-Path $phpDir "php.ini-development"
    $phpIniFile = Join-Path $phpDir "php.ini"
    if ((Test-Path $phpIniDev) -and (-not (Test-Path $phpIniFile))) {
        Copy-Item $phpIniDev $phpIniFile
        $iniContent = Get-Content $phpIniFile -Raw
        $iniContent = $iniContent -replace ';extension_dir = "ext"', 'extension_dir = "ext"'
        $iniContent = $iniContent -replace ';extension=pdo_sqlite', 'extension=pdo_sqlite'
        $iniContent = $iniContent -replace ';extension=sqlite3', 'extension=sqlite3'
        $iniContent = $iniContent -replace ';extension=gd', 'extension=gd'
        $iniContent = $iniContent -replace ';extension=curl', 'extension=curl'
        $iniContent = $iniContent -replace ';extension=mbstring', 'extension=mbstring'
        $iniContent = $iniContent -replace ';extension=fileinfo', 'extension=fileinfo'
        $iniContent = $iniContent -replace ';extension=openssl', 'extension=openssl'
        $iniContent = $iniContent -replace ';extension=mysqli', 'extension=mysqli'
        $iniContent = $iniContent -replace ';extension=pdo_mysql', 'extension=pdo_mysql'
        Set-Content -Path $phpIniFile -Value $iniContent
    }

    Write-Host "[OK] Portable PHP setup complete!" -ForegroundColor Green
    exit 0
} catch {
    Write-Host "[ERROR] Could not complete PHP download: $($_.Exception.Message)" -ForegroundColor Red
    if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
    exit 1
}
