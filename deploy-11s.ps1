# One-Command Deployment Script for 11Seconds Quiz Game
# Usage: ./deploy-11s.ps1

Write-Host "Starting 11Seconds Quiz Game Deployment..." -ForegroundColor Green

# Check if we're in the right directory
if (!(Test-Path "web/package.json")) {
    Write-Host "Error: Please run this script from the project root directory" -ForegroundColor Red
    exit 1
}

# Check if Node.js is installed
try {
    $nodeVersion = node --version
    Write-Host "Node.js version: $nodeVersion" -ForegroundColor Green
}
catch {
    Write-Host "Error: Node.js is not installed or not in PATH" -ForegroundColor Red
    exit 1
}

# Step 1: Install dependencies (if needed)
Write-Host "Checking dependencies..." -ForegroundColor Yellow
Set-Location web
if (!(Test-Path "node_modules")) {
    Write-Host "Installing dependencies..." -ForegroundColor Yellow
    npm install
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Error: Failed to install dependencies" -ForegroundColor Red
        exit 1
    }
}

# Step 2: Build production version
Write-Host "Building production version..." -ForegroundColor Yellow
$env:GENERATE_SOURCEMAP = "false"
npm run build
if ($LASTEXITCODE -ne 0) {
    Write-Host "Error: Build failed" -ForegroundColor Red
    exit 1
}

# Step 3: Copy to deployment directory
Write-Host "Copying files to deployment directory..." -ForegroundColor Yellow
if (Test-Path "../deploy-netcup-auto/httpdocs") {
    Remove-Item "../deploy-netcup-auto/httpdocs/*" -Recurse -Force -ErrorAction SilentlyContinue
}
Copy-Item "build/*" "../deploy-netcup-auto/httpdocs/" -Recurse -Force

# Step 4: Check if deployment was successful
if (Test-Path "../deploy-netcup-auto/httpdocs/index.html") {
    Write-Host "Files successfully copied to deployment directory" -ForegroundColor Green
}
else {
    Write-Host "Error: Failed to copy files to deployment directory" -ForegroundColor Red
    exit 1
}

Set-Location ..

# Step 5: Show deployment summary
Write-Host ""
Write-Host "Deployment Summary:" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
$buildSize = (Get-ChildItem "web/build" -Recurse | Measure-Object -Property Length -Sum).Sum
Write-Host "Build Size: $('{0:N2}' -f ($buildSize / 1MB)) MB" -ForegroundColor White
Write-Host "Files: $((Get-ChildItem 'web/build' -Recurse -File | Measure-Object).Count) files" -ForegroundColor White
Write-Host "Target: deploy-netcup-auto/httpdocs/" -ForegroundColor White

# Step 6: Optional FTP upload
Write-Host ""
Write-Host "Deployment Options:" -ForegroundColor Cyan
Write-Host "1. Local deployment complete" -ForegroundColor Green
Write-Host "2. For FTP upload to Netcup, run: ./quick-ftp-deploy.ps1" -ForegroundColor Yellow
Write-Host "3. For testing locally, serve from: deploy-netcup-auto/httpdocs/" -ForegroundColor Blue

Write-Host ""
Write-Host "Deployment completed successfully!" -ForegroundColor Green
Write-Host "Your 11Seconds Quiz Game is ready to deploy!" -ForegroundColor Green

# Optional: Ask if user wants to upload to FTP
$upload = Read-Host "`nDo you want to upload to Netcup FTP now? (y/N)"
if ($upload -eq "y" -or $upload -eq "Y") {
    if (Test-Path "quick-ftp-deploy.ps1") {
        Write-Host "Starting FTP upload..." -ForegroundColor Yellow
        & .\quick-ftp-deploy.ps1
    }
    else {
        Write-Host "FTP deployment script not found" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "Ready to play 11Seconds Quiz Game!" -ForegroundColor Magenta
