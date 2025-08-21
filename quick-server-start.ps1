# 🚀 SCHNELLER SERVER-START
Write-Host "🔧 STARTE SERVER AUF NETCUP..." -ForegroundColor Green

$sshHost = "hosting223936.ae94b.netcup.net"
$sshUser = "hosting223936"
$sshPass = "hallo.4Netcup"

# Einfache Server-Start Commands
$startCommands = @(
    "cd 11seconds.de",
    "echo '🔍 Checking current directory:'",
    "pwd && ls -la app.js",
    "echo '🛑 Stopping any running processes:'",
    "pkill -f 'node app.js' || echo 'No process to kill'",
    "echo '📦 Installing dependencies:'",
    "npm install --production",
    "echo '🚀 Starting server:'",
    "nohup node app.js > server.log 2>&1 &",
    "echo '⏳ Waiting 3 seconds...'",
    "sleep 3",
    "echo '📊 Process status:'",
    "ps aux | grep 'node app.js' | head -5",
    "echo '🌐 Port check:'",
    "netstat -tlnp | grep :3011 || echo 'Port 3011 not found'",
    "echo '📝 Recent logs:'",
    "tail -10 server.log 2>/dev/null || echo 'No logs yet'",
    "echo '✅ DONE! Check: https://11seconds.de:3011'"
)

Write-Host "🔄 Connecting and starting server..." -ForegroundColor Yellow

try {
    if (Get-Command plink -ErrorAction SilentlyContinue) {
        # Mit PuTTY plink
        $commandString = $startCommands -join "; "
        echo $sshPass | plink -ssh -batch -pw $sshPass $sshUser@$sshHost $commandString
    } elseif (Get-Command ssh -ErrorAction SilentlyContinue) {
        # Mit SSH
        $commandString = $startCommands -join "; "
        echo $sshPass | ssh -o StrictHostKeyChecking=no $sshUser@$sshHost $commandString
    } else {
        Write-Host "❌ SSH client not found. Manual commands:" -ForegroundColor Red
        Write-Host "ssh $sshUser@$sshHost" -ForegroundColor Cyan
        foreach ($cmd in $startCommands) {
            Write-Host $cmd -ForegroundColor White
        }
    }
} catch {
    Write-Host "❌ Error: $_" -ForegroundColor Red
    Write-Host "🔧 Try manual SSH:" -ForegroundColor Yellow
    Write-Host "ssh $sshUser@$sshHost" -ForegroundColor Cyan
    Write-Host "cd 11seconds.de && npm install && nohup node app.js > server.log 2>&1 &" -ForegroundColor White
}

Write-Host "🎯 Server should be running at: https://11seconds.de:3011" -ForegroundColor Green
