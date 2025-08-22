# Simple FTP Deployment Script for 11seconds.de
Write-Host "SIMPLE FTP DEPLOYMENT TO NETCUP" -ForegroundColor Green
Write-Host "================================" -ForegroundColor Green

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

# Create FTP script
$ftpScript = @"
open $ftpHost
$ftpUser
$ftpPass
binary
cd /
put app.js app.js
put package.json package.json
cd api
put api/db.js db.js
put api/db-switcher.js db-switcher.js
cd routes
put api/routes/auth.js auth.js
put api/routes/game.js game.js
put api/routes/admin.js admin.js
cd ..
cd middleware
put api/middleware/auth.js auth.js
put api/middleware/admin.js admin.js
cd ../..
cd web/src
put web/src/App.js App.js
put web/src/index.js index.js
put web/src/index.css index.css
cd pages
put web/src/pages/AdminPage.js AdminPage.js
put web/src/pages/GamePage.js GamePage.js
put web/src/pages/LoginPage.js LoginPage.js
put web/src/pages/MenuPage.js MenuPage.js
put web/src/pages/HighscorePage.js HighscorePage.js
put web/src/pages/GameConfigPage.js GameConfigPage.js
cd ../../../
quit
"@

$ftpScriptFile = "ftp_deploy.txt"
$ftpScript | Out-File -FilePath $ftpScriptFile -Encoding ASCII

Write-Host "Uploading files via FTP..." -ForegroundColor Yellow

try {
    # Use Windows built-in FTP client
    ftp -s:$ftpScriptFile
    Write-Host "FTP upload completed!" -ForegroundColor Green
} catch {
    Write-Host "FTP upload failed: $($_.Exception.Message)" -ForegroundColor Red
}

# Clean up
Remove-Item $ftpScriptFile -Force -ErrorAction SilentlyContinue

Write-Host ""
Write-Host "NEXT: SSH TO RESTART THE APP" -ForegroundColor Yellow
Write-Host "ssh hosting223936@hosting223936.ae94b.netcup.net" -ForegroundColor White
Write-Host "Password: hallo.4Netcup" -ForegroundColor White
Write-Host ""
Write-Host "Then execute these commands on server:" -ForegroundColor Green
Write-Host "cd 11seconds.de" -ForegroundColor White
Write-Host "pkill -f 'node app.js' || echo 'No existing process'" -ForegroundColor White  
Write-Host "npm install --production" -ForegroundColor White
Write-Host "nohup node app.js > app.log 2>&1 &" -ForegroundColor White
Write-Host "ps aux | grep node" -ForegroundColor White
Write-Host ""
Write-Host "App should be running on https://11seconds.de" -ForegroundColor Cyan

# Provide summary
Write-Host ""
Write-Host "DEPLOYMENT SUMMARY:" -ForegroundColor Green
Write-Host "1. Files uploaded via FTP" -ForegroundColor White
Write-Host "2. SQL for new questions: api/data/insert_extra_questions.sql" -ForegroundColor White
Write-Host "3. Manual SSH restart required (commands above)" -ForegroundColor White
