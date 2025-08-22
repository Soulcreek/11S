# Fully Automated SSH Deployment with Password Automation
Write-Host "FULLY AUTOMATED SSH DEPLOYMENT" -ForegroundColor Green
Write-Host "==============================" -ForegroundColor Green

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

$webRoot = "11seconds.de"
$sshHost = $DEPLOY_SSH_HOST
$sshUser = $DEPLOY_SSH_USER 
$sshPass = $DEPLOY_SSH_PASS

Write-Host "SSH Target: $sshUser@$sshHost" -ForegroundColor Cyan
Write-Host "Web Root: $webRoot" -ForegroundColor Cyan

# Create a temporary expect script for password automation
$expectScript = @"
#!/usr/bin/expect -f
set timeout 30
set host $sshHost
set user $sshUser
set password $sshPass

# Execute all commands in a single SSH session
spawn ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null `$user@`$host

expect {
    "*password:*" {
        send "`$password\r"
        exp_continue
    }
    "*`$*" {
        # We're now connected, execute all commands
        send "cd $webRoot\r"
        expect "*`$*"
        
        send "pwd && ls -la\r"
        expect "*`$*"
        
        send "node --version || echo 'Node.js not installed'\r"
        expect "*`$*"
        
        send "npm --version || echo 'npm not available'\r" 
        expect "*`$*"
        
        send "pkill -f 'node app.js' || echo 'No existing process'\r"
        expect "*`$*"
        
        send "npm install --production\r"
        expect "*`$*"
        
        send "nohup node app.js > app.log 2>&1 &\r"
        expect "*`$*"
        
        send "sleep 3\r"
        expect "*`$*"
        
        send "ps aux | grep 'node app.js' | grep -v grep || echo 'Process check failed'\r"
        expect "*`$*"
        
        send "echo 'Deployment completed successfully'\r"
        expect "*`$*"
        
        send "exit\r"
        expect eof
    }
    timeout {
        puts "Connection timeout"
        exit 1
    }
}
"@

# Write expect script to temporary file
$tempExpectFile = Join-Path $env:TEMP "deploy_expect.exp"
$expectScript | Out-File -FilePath $tempExpectFile -Encoding UTF8

Write-Host "Created expect script: $tempExpectFile" -ForegroundColor Yellow

# Try different methods for automated password entry
$methods = @(
    @{ Name = "WSL with expect"; Command = "wsl expect `"$tempExpectFile`"" },
    @{ Name = "PowerShell with plink"; Command = "echo y | plink -ssh -pw `"$sshPass`" $sshUser@$sshHost `"cd $webRoot && pwd && ls -la && node --version && npm install --production && pkill -f 'node app.js' || true && nohup node app.js > app.log 2>&1 & && sleep 3 && ps aux | grep node`"" },
    @{ Name = "Direct PowerShell SSH"; Command = "ssh -o BatchMode=no -o StrictHostKeyChecking=no $sshUser@$sshHost" }
)

$deploymentSuccessful = $false

foreach ($method in $methods) {
    Write-Host "Trying method: $($method.Name)" -ForegroundColor Yellow
    
    try {
        if ($method.Name -eq "WSL with expect") {
            # Check if WSL is available
            if (Get-Command wsl -ErrorAction SilentlyContinue) {
                Write-Host "Executing via WSL expect..." -ForegroundColor Cyan
                Invoke-Expression $method.Command
                $deploymentSuccessful = $true
                break
            }
            else {
                Write-Host "WSL not available, trying next method..." -ForegroundColor Yellow
                continue
            }
        }
        elseif ($method.Name -eq "PowerShell with plink") {
            # Check if plink is available
            if (Get-Command plink -ErrorAction SilentlyContinue) {
                Write-Host "Executing via plink..." -ForegroundColor Cyan
                Invoke-Expression $method.Command
                $deploymentSuccessful = $true
                break
            }
            else {
                Write-Host "plink not available, trying next method..." -ForegroundColor Yellow
                continue
            }
        }
        else {
            Write-Host "Fallback to interactive SSH (you'll need to enter password manually)" -ForegroundColor Red
            Write-Host "Password: $sshPass" -ForegroundColor White
            Write-Host "Commands to run:" -ForegroundColor Yellow
            Write-Host "cd $webRoot" -ForegroundColor White
            Write-Host "pwd && ls -la" -ForegroundColor White
            Write-Host "npm install --production" -ForegroundColor White
            Write-Host "pkill -f 'node app.js' || true" -ForegroundColor White
            Write-Host "nohup node app.js > app.log 2>&1 &" -ForegroundColor White
            Write-Host "ps aux | grep node" -ForegroundColor White
        }
    }
    catch {
        Write-Host "Method $($method.Name) failed: $($_.Exception.Message)" -ForegroundColor Red
        continue
    }
}

# Clean up temporary file
if (Test-Path $tempExpectFile) {
    Remove-Item $tempExpectFile -Force
}

if ($deploymentSuccessful) {
    Write-Host ""
    Write-Host "SSH Deployment completed successfully!" -ForegroundColor Green
    Write-Host "App should now be running on https://11seconds.de" -ForegroundColor Cyan
}
else {
    Write-Host ""
    Write-Host "Automated deployment failed. Manual deployment required." -ForegroundColor Red
    Write-Host "ssh $sshUser@$sshHost" -ForegroundColor White
    Write-Host "Password: $sshPass" -ForegroundColor White
}
