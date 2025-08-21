# SSH Deployment Script mit korrekten Netcup-Zugangsdaten
Write-Host "🚀 SSH DEPLOYMENT ZU NETCUP" -ForegroundColor Green
Write-Host "============================" -ForegroundColor Green

# SSH-Zugangsdaten aus .env.netcup
$sshHost = "hosting223936.ae94b.netcup.net"
$sshUser = "hosting223936"
$sshPass = "hallo.4Netcup"
$webRoot = "/home/hosting223936/11seconds.de"

Write-Host "📡 SSH Target: $sshUser@$sshHost" -ForegroundColor Cyan
Write-Host "📁 Web Root: $webRoot" -ForegroundColor Cyan

# Test SSH connection first
Write-Host "`n🔐 Testing SSH connection..." -ForegroundColor Yellow

try {
    # Create SSH connection test command
    $testCommand = "echo 'SSH connection successful'"
    
    Write-Host "💡 Manual SSH test command:" -ForegroundColor Yellow
    Write-Host "ssh $sshUser@$sshHost" -ForegroundColor White
    Write-Host "Password: $sshPass" -ForegroundColor White
    
    Write-Host "`n📋 After SSH connection, run these commands:" -ForegroundColor Green
    
    $sshCommands = @"
# Navigate to web root
cd $webRoot

# Show current directory and files
pwd
ls -la

# Check if Node.js is available
node --version
npm --version

# If Node.js not available, install it
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt-get install -y nodejs

# Install dependencies
npm install

# Start the application
node app.js

# Or run in background
nohup node app.js > app.log 2>&1 &

# Check if running
ps aux | grep node
"@

    Write-Host $sshCommands -ForegroundColor White
    
} catch {
    Write-Host "❌ SSH connection failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n🎯 AUTOMATED SSH DEPLOYMENT:" -ForegroundColor Green
Write-Host "If you want to automate this, we can use PowerShell SSH commands" -ForegroundColor Yellow
Write-Host "or create a batch script that handles the SSH connection." -ForegroundColor Yellow
