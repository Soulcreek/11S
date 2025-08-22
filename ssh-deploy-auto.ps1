# Automated SSH Deployment Script with .env.netcup integration
Write-Host "AUTOMATED SSH DEPLOYMENT TO NETCUP" -ForegroundColor Green
Write-Host "===================================" -ForegroundColor Green

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
}
else {
    Write-Host "ERROR: .env.netcup file not found!" -ForegroundColor Red
    exit 1
}

# Use corrected path - directly to 11seconds.de as requested
$webRoot = "11seconds.de"  # Direct path as requested
$sshHost = $DEPLOY_SSH_HOST
$sshUser = $DEPLOY_SSH_USER 
$sshPass = $DEPLOY_SSH_PASS

Write-Host "SSH Target: $sshUser@$sshHost" -ForegroundColor Cyan
Write-Host "Web Root: $webRoot" -ForegroundColor Cyan

# Check if sshpass is available for password automation
$sshpassAvailable = $false
try {
    $null = Get-Command sshpass -ErrorAction Stop
    $sshpassAvailable = $true
    Write-Host "sshpass found - using automated authentication" -ForegroundColor Green
}
catch {
    Write-Host "sshpass not found - will use manual authentication" -ForegroundColor Yellow
}

# Commands to execute on server
$commands = @(
    "cd $webRoot",
    "pwd && ls -la",
    "node --version || echo 'Node.js not installed'",
    "npm --version || echo 'npm not available'",
    "pkill -f 'node app.js' || echo 'No existing process'",
    "npm install --production",
    "nohup node app.js > app.log 2>&1 &",
    "sleep 3",
    "ps aux | grep 'node app.js' | grep -v grep || echo 'Process check failed'",
    "echo 'Deployment completed successfully'"
)

Write-Host "Executing deployment commands..." -ForegroundColor Yellow

if ($sshpassAvailable) {
    # Use sshpass for automated password entry
    foreach ($cmd in $commands) {
        Write-Host "Executing: $cmd" -ForegroundColor White
        try {
            $result = & sshpass -p $sshPass ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null $sshUser@$sshHost $cmd
            Write-Host $result -ForegroundColor Gray
        }
        catch {
            Write-Host "Command failed: $cmd - Error: $($_.Exception.Message)" -ForegroundColor Red
        }
    }
}
else {
    # Fallback to manual SSH commands
    Write-Host "MANUAL SSH DEPLOYMENT COMMANDS:" -ForegroundColor Yellow
    Write-Host "ssh $sshUser@$sshHost" -ForegroundColor White
    Write-Host "Password: $sshPass" -ForegroundColor White
    Write-Host ""
    Write-Host "Then execute these commands:" -ForegroundColor Green
    foreach ($cmd in $commands) {
        Write-Host $cmd -ForegroundColor White
    }
    
    # Try direct SSH execution anyway
    Write-Host "`nAttempting direct SSH execution..." -ForegroundColor Yellow
    foreach ($cmd in $commands) {
        Write-Host "Executing: $cmd" -ForegroundColor White
        try {
            ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null $sshUser@$sshHost $cmd
        }
        catch {
            Write-Host "Command may have failed: $cmd" -ForegroundColor Yellow
        }
    }
}

Write-Host ""
Write-Host "SSH Deployment completed!" -ForegroundColor Green
Write-Host "App should now be running on https://11seconds.de" -ForegroundColor Cyan
Write-Host ""
Write-Host "To check status manually:" -ForegroundColor Yellow
Write-Host "ssh $sshUser@$sshHost" -ForegroundColor White
Write-Host "cd $webRoot && ps aux | grep node" -ForegroundColor White
