# Complete Deployment Script with React Build Upload
Write-Host "COMPLETE DEPLOYMENT WITH REACT BUILD" -ForegroundColor Green
Write-Host "====================================" -ForegroundColor Green

# Load environment variables from .env.netcup
if (Test-Path ".env.netcup") {
    Write-Host "Loading .env.netcup configuration..." -ForegroundColor Yellow
    Get-Content ".env.netcup" | Where-Object { $_ -match "^[^#].*=" } | ForEach-Object {
        $parts = $_ -split "=", 2
        if ($parts.Length -eq 2) {
            $name = $parts[0].Trim()
            $value = $parts[1].Trim().Trim('"')
            Set-Variable -Name $name -Value $value
        }
    }
} else {
    Write-Host "ERROR: .env.netcup file not found!" -ForegroundColor Red
    exit 1
}

$ftpHost = $NETCUP_FTP_HOST
$ftpUser = $NETCUP_FTP_USER
$ftpPass = $NETCUP_FTP_PASSWORD

Write-Host "FTP Target: $ftpHost" -ForegroundColor Cyan
Write-Host "FTP User: $ftpUser" -ForegroundColor Cyan

# Check if build exists
if (!(Test-Path "web/build")) {
    Write-Host "ERROR: web/build directory not found! Run 'npm run build' in web folder first." -ForegroundColor Red
    exit 1
}

Write-Host "Build directory found, uploading complete project..." -ForegroundColor Green

# Create comprehensive FTP script
$ftpScript = @"
open $ftpHost
$ftpUser
$ftpPass
binary
cd /

# Upload backend files
put app.js app.js
put package.json package.json

# Create and upload API directory
mkdir api
cd api
put api/db.js db.js
put api/db-switcher.js db-switcher.js

mkdir routes
cd routes
put api/routes/auth.js auth.js
put api/routes/game.js game.js
put api/routes/admin.js admin.js
cd ..

mkdir middleware
cd middleware
put api/middleware/auth.js auth.js
put api/middleware/admin.js admin.js
cd ..

mkdir data
cd data
put api/data/insert_extra_questions.sql insert_extra_questions.sql
cd ../..

# Upload React build files
mkdir static
cd static

mkdir css
cd css
put web/build/static/css/*.css
cd ..

mkdir js
cd js
put web/build/static/js/*.js
cd ..

cd ..

# Upload build root files
put web/build/index.html index.html
put web/build/manifest.json manifest.json
put web/build/robots.txt robots.txt
put web/build/favicon.ico favicon.ico

quit
"@

$ftpScriptFile = "ftp_complete_deploy.txt"
$ftpScript | Out-File -FilePath $ftpScriptFile -Encoding ASCII

Write-Host "Uploading complete project via FTP..." -ForegroundColor Yellow

try {
    # Use Windows built-in FTP client
    ftp -s:$ftpScriptFile
    Write-Host "Complete FTP upload finished!" -ForegroundColor Green
} catch {
    Write-Host "FTP upload failed: $($_.Exception.Message)" -ForegroundColor Red
}

# Clean up
Remove-Item $ftpScriptFile -Force -ErrorAction SilentlyContinue

Write-Host ""
Write-Host "DEPLOYMENT COMPLETED!" -ForegroundColor Green
Write-Host "Backend files uploaded: app.js, API routes, middleware" -ForegroundColor White
Write-Host "Frontend build uploaded: React production build" -ForegroundColor White
Write-Host ""
Write-Host "NEXT: SSH TO RESTART THE APP" -ForegroundColor Yellow
Write-Host "ssh hosting223936@hosting223936.ae94b.netcup.net" -ForegroundColor White
Write-Host "Password: hallo.4Netcup" -ForegroundColor White
Write-Host ""
Write-Host "Commands on server:" -ForegroundColor Green
Write-Host "cd 11seconds.de" -ForegroundColor White
Write-Host "pkill -f 'node app.js' || echo 'No existing process'" -ForegroundColor White  
Write-Host "npm install --production" -ForegroundColor White
Write-Host "nohup node app.js > app.log 2>&1 &" -ForegroundColor White
Write-Host "ps aux | grep node" -ForegroundColor White
Write-Host ""
Write-Host "App will be running on https://11seconds.de" -ForegroundColor Cyan
