# Alternative SSH Automation mit sshpass-ähnlicher Funktionalität
Write-Host "🚀 ALTERNATIVE SSH AUTOMATION" -ForegroundColor Green

$sshHost = "hosting223936.ae94b.netcup.net"
$sshUser = "hosting223936" 
$sshPass = "hallo.4Netcup"

# Create temporary batch file for automated SSH
$batchContent = @"
@echo off
echo Connecting to Netcup server...
echo $sshPass | ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null $sshUser@$sshHost "cd /home/hosting223936/11seconds.de && pwd && ls -la && node --version && npm install && pkill -f 'node app.js' || echo 'No process to kill' && nohup node app.js > app.log 2>&1 & && sleep 2 && ps aux | grep 'node app.js' | grep -v grep && echo 'App deployed successfully!' && echo 'URL: https://11seconds.de:3011'"
"@

$batchFile = "ssh-deploy.bat"
$batchContent | Out-File -FilePath $batchFile -Encoding ASCII

Write-Host "📝 Created batch file: $batchFile" -ForegroundColor Cyan
Write-Host "🔄 Executing SSH deployment..." -ForegroundColor Yellow

try {
    & ".\$batchFile"
} catch {
    Write-Host "❌ Batch execution failed: $($_.Exception.Message)" -ForegroundColor Red
}

# Clean up
Remove-Item $batchFile -Force -ErrorAction SilentlyContinue

Write-Host "✅ SSH automation completed!" -ForegroundColor Green
