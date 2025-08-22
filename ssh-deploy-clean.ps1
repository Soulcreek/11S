# Clean SSH Deployment Script for Netcup
Write-Host "SSH DEPLOYMENT TO NETCUP" -ForegroundColor Green
Write-Host "========================" -ForegroundColor Green

# SSH connection details
$sshHost = "hosting223936.ae94b.netcup.net"
$sshUser = "hosting223936" 
$sshPass = "hallo.4Netcup"
$webRoot = "/home/hosting223936/11seconds.de"

Write-Host "SSH Target: $sshUser@$sshHost" -ForegroundColor Cyan
Write-Host "Web Root: $webRoot" -ForegroundColor Cyan

# Check if we have SSH client available
if (Get-Command ssh -ErrorAction SilentlyContinue) {
    Write-Host "SSH client found, attempting connection..." -ForegroundColor Yellow
    
    # Commands to execute on server
    $commands = @(
        "cd $webRoot",
        "pwd",
        "ls -la",
        "node --version",
        "npm --version", 
        "pkill -f 'node app.js' || true",
        "npm install",
        "nohup node app.js > app.log 2>&1 &",
        "sleep 2",
        "ps aux | grep node",
        "echo 'Deployment completed'"
    )
    
    # Execute commands via SSH
    foreach ($cmd in $commands) {
        Write-Host "Executing: $cmd" -ForegroundColor White
        try {
            # Use SSH to execute command
            ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null $sshUser@$sshHost $cmd
        }
        catch {
            Write-Host "Command failed: $cmd" -ForegroundColor Red
        }
    }
}
else {
    Write-Host "SSH client not found. Please install OpenSSH or use manual connection:" -ForegroundColor Red
    Write-Host "" 
    Write-Host "Manual SSH Commands:" -ForegroundColor Yellow
    Write-Host "ssh $sshUser@$sshHost" -ForegroundColor White
    Write-Host "Password: $sshPass" -ForegroundColor White
    Write-Host ""
    Write-Host "Then run these commands on server:" -ForegroundColor Green
    Write-Host "cd $webRoot" -ForegroundColor White
    Write-Host "pwd && ls -la" -ForegroundColor White
    Write-Host "node --version" -ForegroundColor White
    Write-Host "npm install" -ForegroundColor White
    Write-Host "pkill -f 'node app.js' || true" -ForegroundColor White
    Write-Host "nohup node app.js > app.log 2>&1 &" -ForegroundColor White
    Write-Host "ps aux | grep node" -ForegroundColor White
}

Write-Host ""
Write-Host "App should now be running on server!" -ForegroundColor Green
Write-Host "Check status with: ps aux | grep node" -ForegroundColor Yellow
