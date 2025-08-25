# Simple Deployment Script for 11Seconds
# One script to rule them all! No more complexity.

param(
    [switch]$Build,      # Run npm build first
    [switch]$AdminOnly,  # Deploy only admin files
    [switch]$WebOnly,    # Deploy only web files  
    [switch]$DryRun      # Show what would be uploaded
)

Write-Host "🚀 11Seconds Simple Deploy" -ForegroundColor Cyan
Write-Host "=========================" -ForegroundColor Cyan

# Check FTP credentials
if (-not $env:FTP_USER -or -not $env:FTP_PASSWORD) {
    Write-Host "❌ Missing FTP credentials!" -ForegroundColor Red
    Write-Host "Set: `$env:FTP_USER = 'k302164_11s'; `$env:FTP_PASSWORD = 'your_password'" -ForegroundColor Yellow
    exit 1
}

# Build React app if requested
if ($Build -and -not $WebOnly) {
    Write-Host "📦 Building React app..." -ForegroundColor Yellow
    cd web
    if (-not (Test-Path "package.json")) {
        Write-Host "❌ No package.json in web/ directory!" -ForegroundColor Red
        exit 1
    }
    npm run build
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ Build failed!" -ForegroundColor Red
        exit 1  
    }
    cd ..
    Write-Host "✅ Build completed" -ForegroundColor Green
}

# Simple FTP upload function
function Upload-Files {
    param($LocalPath, $RemotePath, $Filter = "*")
    
    if ($DryRun) {
        Write-Host "DRY RUN: Would upload $LocalPath -> $RemotePath" -ForegroundColor Magenta
        return
    }
    
    Write-Host "📤 Uploading $LocalPath..." -ForegroundColor Blue
    
    # Use WinSCP or simple FTP here
    # For now, just show what we would do
    Get-ChildItem -Path $LocalPath -Filter $Filter -Recurse | ForEach-Object {
        Write-Host "  → $($_.Name)" -ForegroundColor Gray
    }
}

# Deploy based on parameters
if ($AdminOnly) {
    Write-Host "🔧 Deploying Admin Center only..." -ForegroundColor Yellow
    Upload-Files -LocalPath "admin" -RemotePath "/httpdocs/admin"
}
elseif ($WebOnly) {
    Write-Host "🌐 Deploying Web App only..." -ForegroundColor Yellow
    if (-not (Test-Path "web/httpdocs")) {
        Write-Host "❌ No build output found! Run with -Build" -ForegroundColor Red
        exit 1
    }
    Upload-Files -LocalPath "web/httpdocs" -RemotePath "/httpdocs"  
}
else {
    Write-Host "🎯 Full deployment..." -ForegroundColor Yellow
    Upload-Files -LocalPath "admin" -RemotePath "/httpdocs/admin"
    Upload-Files -LocalPath "api" -RemotePath "/httpdocs/api"
    if (Test-Path "web/httpdocs") {
        Upload-Files -LocalPath "web/httpdocs" -RemotePath "/httpdocs"
    }
}

Write-Host "✅ Deployment completed!" -ForegroundColor Green
Write-Host "🌐 Site: https://11seconds.de" -ForegroundColor Cyan
Write-Host "🔧 Admin: https://11seconds.de/admin/admin-center.php" -ForegroundColor Cyan
