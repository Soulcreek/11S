# 🚀 Automatisches Netcup Deployment Script
# Erstellt Production Package und deployt zu Netcup Webhosting

param(
    [string]$FtpHost = "",
    [string]$FtpUser = "",
    [string]$FtpPassword = "",
    [switch]$SkipFtp = $false
)

Write-Host "🚀 Starting automated Netcup deployment..." -ForegroundColor Green
Write-Host "📅 $(Get-Date)" -ForegroundColor Gray

# Arbeitsverzeichnis setzen
$ProjectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $ProjectRoot

Write-Host "📁 Project root: $ProjectRoot" -ForegroundColor Cyan

# 1. Production Package erstellen
Write-Host "`n📦 Building production package..." -ForegroundColor Yellow

$DeployDir = "deploy-netcup-auto"
if (Test-Path $DeployDir) {
    Remove-Item $DeployDir -Recurse -Force
    Write-Host "🗑️  Cleaned old deployment directory" -ForegroundColor Gray
}

New-Item -ItemType Directory -Path $DeployDir | Out-Null

# 2. Dateien kopieren
Write-Host "📋 Copying files..." -ForegroundColor Yellow

$FilesToCopy = @(
    @{Source = "app.js"; Dest = "$DeployDir\app.js"},
    @{Source = "package-production.json"; Dest = "$DeployDir\package.json"},
    @{Source = ".env-production-example"; Dest = "$DeployDir\.env-template"},
    @{Source = "setup-extended-questions.js"; Dest = "$DeployDir\setup-extended-questions.js"}
)

foreach ($file in $FilesToCopy) {
    if (Test-Path $file.Source) {
        Copy-Item $file.Source $file.Dest -Force
        Write-Host "✅ $($file.Source) → $($file.Dest)" -ForegroundColor Green
    } else {
        Write-Host "⚠️  Missing: $($file.Source)" -ForegroundColor Red
    }
}

# Verzeichnisse kopieren
$DirsToC copy = @("api", "httpdocs")
foreach ($dir in $DirsToC copy) {
    if (Test-Path $dir) {
        Copy-Item $dir "$DeployDir\" -Recurse -Force
        Write-Host "✅ $dir/ → $DeployDir\$dir\" -ForegroundColor Green
    } else {
        Write-Host "⚠️  Missing directory: $dir" -ForegroundColor Red
    }
}

# 3. Package Info erstellen
$PackageInfo = @"
# 🎮 11Seconds Quiz Game - Production Package
Generated: $(Get-Date)
Build: Automated VS Code Deployment

## 📁 Contents:
- app.js (Main server)
- package.json (Dependencies)
- .env-template (Configuration template)
- setup-extended-questions.js (Database setup)
- api/ (Backend API)
- httpdocs/ (Frontend)

## 🚀 Deployment Steps:
1. Upload all files to Netcup root directory
2. Rename .env-template to .env
3. Configure MySQL credentials in .env
4. Run: npm install
5. Run: node setup-extended-questions.js
6. Run: node app.js

## 🌐 Access:
- Game: http://your-domain.de:3011
- API: http://your-domain.de:3011/api
"@

$PackageInfo | Out-File "$DeployDir\DEPLOYMENT-INFO.txt" -Encoding UTF8
Write-Host "✅ Created deployment info" -ForegroundColor Green

# 4. ZIP Package erstellen
Write-Host "`n📦 Creating ZIP package..." -ForegroundColor Yellow

$ZipFile = "11seconds-netcup-$(Get-Date -Format 'yyyyMMdd-HHmmss').zip"
if (Get-Command Compress-Archive -ErrorAction SilentlyContinue) {
    Compress-Archive -Path "$DeployDir\*" -DestinationPath $ZipFile -Force
    Write-Host "✅ Created: $ZipFile" -ForegroundColor Green
    
    $ZipSize = [math]::Round((Get-Item $ZipFile).Length / 1MB, 2)
    Write-Host "📊 Package size: $ZipSize MB" -ForegroundColor Cyan
} else {
    Write-Host "⚠️  Compress-Archive not available, skipping ZIP creation" -ForegroundColor Yellow
}

# 5. FTP Upload (optional)
if (-not $SkipFtp -and $FtpHost -and $FtpUser -and $FtpPassword) {
    Write-Host "`n🌐 Uploading to FTP..." -ForegroundColor Yellow
    
    try {
        # WinSCP PowerShell Module oder native FTP
        if (Get-Module -ListAvailable -Name WinSCP) {
            Import-Module WinSCP
            
            $sessionOptions = New-Object WinSCP.SessionOptions -Property @{
                Protocol = [WinSCP.Protocol]::Ftp
                HostName = $FtpHost
                UserName = $FtpUser
                Password = $FtpPassword
            }
            
            $session = New-Object WinSCP.Session
            $session.Open($sessionOptions)
            
            $transferOptions = New-Object WinSCP.TransferOptions
            $transferOptions.TransferMode = [WinSCP.TransferMode]::Binary
            
            $session.PutFiles("$DeployDir\*", "/", $False, $transferOptions)
            $session.Close()
            
            Write-Host "✅ FTP upload completed successfully!" -ForegroundColor Green
        } else {
            Write-Host "⚠️  WinSCP module not available. Manual upload required." -ForegroundColor Yellow
            Write-Host "💡 Install with: Install-Module -Name WinSCP" -ForegroundColor Gray
        }
    } catch {
        Write-Host "❌ FTP upload failed: $($_.Exception.Message)" -ForegroundColor Red
        Write-Host "📁 Files available in: $DeployDir" -ForegroundColor Cyan
    }
}

# 6. Zusammenfassung
Write-Host "`n🎉 Deployment preparation completed!" -ForegroundColor Green
Write-Host "📁 Package directory: $DeployDir" -ForegroundColor Cyan
if (Test-Path $ZipFile) {
    Write-Host "📦 ZIP package: $ZipFile" -ForegroundColor Cyan
}

Write-Host "`n📋 Next steps:" -ForegroundColor Yellow
Write-Host "1. Upload files to your Netcup Webhosting" -ForegroundColor White
Write-Host "2. Configure .env with your MySQL credentials" -ForegroundColor White
Write-Host "3. Run: npm install && node app.js" -ForegroundColor White
Write-Host "4. Access: http://your-domain.de:3011" -ForegroundColor White

# 7. Öffne Explorer mit Deployment-Ordner
if (Test-Path $DeployDir) {
    Write-Host "`n📂 Opening deployment directory..." -ForegroundColor Gray
    Start-Process explorer.exe -ArgumentList $DeployDir
}

Write-Host "`n✨ Deployment script finished at $(Get-Date)" -ForegroundColor Green
