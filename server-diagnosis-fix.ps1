# Server Diagnose und Fix Script
Write-Host "🔍 SERVER DIAGNOSE UND REPARATUR" -ForegroundColor Magenta
Write-Host "=================================" -ForegroundColor Magenta

$sshHost = "hosting223936.ae94b.netcup.net"
$sshUser = "hosting223936"
$sshPass = "hallo.4Netcup"
$webRoot = "/home/hosting223936/11seconds.de"

Write-Host "📡 Connecting to: $sshUser@$sshHost" -ForegroundColor Cyan

# Erweiterte Diagnose-Kommandos
$diagnosisCommands = @(
    "echo '🔍 === SYSTEM DIAGNOSE ==='",
    "whoami",
    "pwd",
    "echo '📁 Checking web root directory...'",
    "ls -la $webRoot 2>/dev/null || echo 'Web root not found!'",
    "cd $webRoot 2>/dev/null && pwd || echo 'Cannot access web root'",
    "",
    "echo '🔍 === NODE.JS CHECK ==='",
    "which node || echo 'Node.js not in PATH'",
    "node --version 2>/dev/null || echo 'Node.js not working'",
    "which npm || echo 'npm not found'",
    "npm --version 2>/dev/null || echo 'npm not working'",
    "",
    "echo '🔍 === PROCESS CHECK ==='",
    "ps aux | grep node | grep -v grep || echo 'No Node.js processes running'",
    "ps aux | grep app.js | grep -v grep || echo 'No app.js processes running'",
    "",
    "echo '🔍 === FILE CHECK ==='",
    "cd $webRoot 2>/dev/null || exit 1",
    "ls -la app.js 2>/dev/null || echo 'app.js not found!'",
    "ls -la package.json 2>/dev/null || echo 'package.json not found!'",
    "ls -la .env 2>/dev/null || echo '.env not found!'",
    "ls -la node_modules 2>/dev/null || echo 'node_modules not found!'",
    "",
    "echo '🔍 === LOG CHECK ==='",
    "ls -la *.log 2>/dev/null || echo 'No log files found'",
    "tail -10 app.log 2>/dev/null || echo 'No app.log found'",
    "",
    "echo '🔍 === PORT CHECK ==='",
    "netstat -tlnp | grep :3011 || echo 'Port 3011 not listening'",
    "netstat -tlnp | grep node || echo 'No Node.js ports listening'",
    "",
    "echo '🔍 === ENVIRONMENT CHECK ==='",
    "cat .env 2>/dev/null | head -5 || echo 'Cannot read .env'",
    "",
    "echo '🛠️ === ATTEMPTING FIXES ==='",
    "echo '📦 Installing/updating dependencies...'",
    "npm install 2>&1 || echo 'npm install failed'",
    "",
    "echo '🚀 Killing old processes...'",
    "pkill -f 'node app.js' 2>/dev/null || echo 'No processes to kill'",
    "sleep 2",
    "",
    "echo '🚀 Starting application...'",
    "nohup node app.js > app.log 2>&1 &",
    "sleep 3",
    "",
    "echo '✅ === FINAL STATUS ==='",
    "ps aux | grep 'node app.js' | grep -v grep || echo 'App not running!'",
    "tail -5 app.log 2>/dev/null || echo 'No log output'",
    "netstat -tlnp | grep :3011 || echo 'Port 3011 not active'",
    "",
    "echo '🌐 If successful, app should be at: https://11seconds.de:3011'"
)

# Kombiniere alle Kommandos zu einem Script
$fullScript = $diagnosisCommands -join " && "

Write-Host "🔄 Running comprehensive diagnosis and fix..." -ForegroundColor Yellow

try {
    # SSH mit automatischer Passwort-Eingabe
    $sshCommand = "ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null $sshUser@$sshHost `"$fullScript`""
    
    Write-Host "Executing SSH command..." -ForegroundColor Gray
    
    # Verwende cmd für bessere SSH-Kompatibilität
    $process = Start-Process -FilePath "cmd" -ArgumentList "/c", "echo $sshPass | $sshCommand" -NoNewWindow -Wait -PassThru
    
    if ($process.ExitCode -eq 0) {
        Write-Host "✅ SSH command completed successfully!" -ForegroundColor Green
    } else {
        Write-Host "⚠️ SSH command completed with exit code: $($process.ExitCode)" -ForegroundColor Yellow
    }
    
} catch {
    Write-Host "❌ SSH automation failed: $($_.Exception.Message)" -ForegroundColor Red
    
    Write-Host "`n💡 MANUAL SSH COMMANDS:" -ForegroundColor Yellow
    Write-Host "ssh $sshUser@$sshHost" -ForegroundColor White
    Write-Host "Password: $sshPass" -ForegroundColor White
    Write-Host "`nThen run these commands one by one:" -ForegroundColor Yellow
    
    foreach ($cmd in $diagnosisCommands) {
        if ($cmd.Trim()) {
            Write-Host "  $cmd" -ForegroundColor White
        }
    }
}

Write-Host "`n🎯 NEXT STEPS:" -ForegroundColor Green
Write-Host "1. Check if app is running: https://11seconds.de:3011" -ForegroundColor White
Write-Host "2. If not working, check the diagnosis output above" -ForegroundColor White
Write-Host "3. Common issues: Node.js not installed, files not uploaded, port blocked" -ForegroundColor White
