# Automated SSH Deployment to Netcup
Write-Host "🤖 AUTOMATED SSH DEPLOYMENT TO NETCUP" -ForegroundColor Magenta
Write-Host "=======================================" -ForegroundColor Magenta

# SSH credentials from .env.netcup
$sshHost = "hosting223936.ae94b.netcup.net"
$sshUser = "hosting223936"
$sshPass = "hallo.4Netcup"
$webRoot = "/home/hosting223936/11seconds.de"

Write-Host "📡 Target: $sshUser@$sshHost" -ForegroundColor Cyan
Write-Host "📁 Web Root: $webRoot" -ForegroundColor Cyan

# Create SSH commands to execute
$sshCommands = @"
echo '🚀 Starting deployment...'
cd $webRoot
echo '📁 Current directory:'
pwd
echo '📋 Files in directory:'
ls -la
echo '🔍 Checking Node.js...'
node --version || echo 'Node.js not found'
npm --version || echo 'npm not found'
echo '📦 Installing dependencies...'
npm install
echo '🚀 Starting application...'
nohup node app.js > app.log 2>&1 &
echo '✅ App started in background'
echo '📊 Process status:'
ps aux | grep node | grep -v grep
echo '🌐 App should be available at: https://11seconds.de:3011'
echo '📝 Check app.log for output: tail -f app.log'
"@

# Try automated SSH connection
Write-Host "`n🔐 Attempting automated SSH connection..." -ForegroundColor Yellow

try {
    # Use plink if available (from PuTTY)
    if (Get-Command plink -ErrorAction SilentlyContinue) {
        Write-Host "Using plink for SSH connection..." -ForegroundColor Green
        $plinkCmd = "echo '$sshCommands' | plink -ssh -batch -pw $sshPass $sshUser@$sshHost"
        Invoke-Expression $plinkCmd
    } 
    # Try native SSH with expect script
    elseif (Get-Command ssh -ErrorAction SilentlyContinue) {
        Write-Host "Using native SSH..." -ForegroundColor Green
        
        # Create expect-like script for PowerShell
        $expectScript = @"
#!/usr/bin/expect -f
spawn ssh $sshUser@$sshHost
expect "password:"
send "$sshPass\r"
expect "$ "
send "cd $webRoot\r"
expect "$ "
send "pwd\r"
expect "$ "
send "ls -la\r"
expect "$ "
send "node --version\r"
expect "$ "
send "npm install\r"
expect "$ "
send "nohup node app.js > app.log 2>&1 &\r"
expect "$ "
send "ps aux | grep node\r"
expect "$ "
send "exit\r"
interact
"@
        
        Write-Host "Manual SSH command needed:" -ForegroundColor Yellow
        Write-Host "ssh $sshUser@$sshHost" -ForegroundColor White
        Write-Host "Password: $sshPass" -ForegroundColor White
    }
    else {
        Write-Host "No SSH client found. Installing OpenSSH..." -ForegroundColor Yellow
        # Try to install OpenSSH on Windows
        Add-WindowsCapability -Online -Name OpenSSH.Client~~~~0.0.1.0
    }
} catch {
    Write-Host "❌ Automated SSH failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n📋 MANUAL SSH COMMANDS (if automated fails):" -ForegroundColor Green
Write-Host "1. Open PowerShell/CMD and run:" -ForegroundColor Yellow
Write-Host "   ssh $sshUser@$sshHost" -ForegroundColor White
Write-Host "   Password: $sshPass" -ForegroundColor White
Write-Host "`n2. Then execute these commands:" -ForegroundColor Yellow
$sshCommands.Split("`n") | ForEach-Object { Write-Host "   $_" -ForegroundColor White }

Write-Host "`n🎯 ALTERNATIVE: Use VS Code Remote SSH" -ForegroundColor Green
Write-Host "1. Install 'Remote - SSH' extension" -ForegroundColor White
Write-Host "2. Ctrl+Shift+P → 'Remote-SSH: Connect to Host'" -ForegroundColor White
Write-Host "3. Enter: $sshUser@$sshHost" -ForegroundColor White
Write-Host "4. Enter password: $sshPass" -ForegroundColor White
