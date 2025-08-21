# PowerShell Deployment Script for Netcup Webhosting 4000
# Run this script to prepare deployment package

Write-Host "🚀 Creating Netcup deployment package..." -ForegroundColor Green

# Create deployment directory
$deployDir = "deploy-netcup"
if (Test-Path $deployDir) {
    Remove-Item $deployDir -Recurse -Force
}
New-Item -ItemType Directory -Path $deployDir | Out-Null

Write-Host "📁 Copying files..." -ForegroundColor Yellow

# Copy essential files
Copy-Item "app.js" "$deployDir\" -Force
Copy-Item "package-production.json" "$deployDir\package.json" -Force
Copy-Item ".env.netcup-template" "$deployDir\.env-template" -Force
Copy-Item "setup-extended-questions.js" "$deployDir\" -Force
Copy-Item "NETCUP-DEPLOYMENT.md" "$deployDir\README.md" -Force

# Copy directories
Copy-Item "api" "$deployDir\" -Recurse -Force
Copy-Item "httpdocs" "$deployDir\" -Recurse -Force

Write-Host "✅ Deployment package created successfully!" -ForegroundColor Green
Write-Host "📦 Files are ready in: $deployDir" -ForegroundColor Cyan
Write-Host ""
Write-Host "📋 Next steps:" -ForegroundColor Yellow
Write-Host "1. Zip the 'deploy-netcup' folder" -ForegroundColor White
Write-Host "2. Upload to your Netcup Webhosting" -ForegroundColor White  
Write-Host "3. Extract files in your web root" -ForegroundColor White
Write-Host "4. Configure .env with your MySQL credentials" -ForegroundColor White
Write-Host "5. Run: npm install && node app.js" -ForegroundColor White
Write-Host ""
Write-Host "📖 Read README.md in deploy-netcup folder for detailed instructions!" -ForegroundColor Magenta

# Show directory contents
Write-Host "📁 Package contents:" -ForegroundColor Green
Get-ChildItem $deployDir -Recurse | Format-Table Name, Length, LastWriteTime
