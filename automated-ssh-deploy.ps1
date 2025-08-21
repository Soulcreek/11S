# Vollautomatisches SSH-Deployment mit PowerShell
Write-Host "🤖 AUTOMATISCHES SSH-DEPLOYMENT" -ForegroundColor Magenta
Write-Host "================================" -ForegroundColor Magenta

# SSH-Zugangsdaten
$sshHost = "hosting223936.ae94b.netcup.net"
$sshUser = "hosting223936"
$sshPass = "hallo.4Netcup"

Write-Host "📡 Connecting to: $sshUser@$sshHost" -ForegroundColor Cyan

# Commands to execute on server
$commands = @(
    "cd 11seconds.de",
    "pwd",
    "ls -la",
    "echo '🔍 Checking Node.js...'",
    "node --version || echo 'Node.js not found'", 
    "npm --version || echo 'npm not found'",
    "echo '📦 Installing dependencies...'",
    "npm install",
    "echo '🚀 Starting application...'",
    "pkill -f 'node app.js' || echo 'No existing process'",
    "nohup node app.js > app.log 2>&1 &",
    "sleep 2",
    "echo '📊 Process check:'",
    "ps aux | grep 'node app.js' | grep -v grep",
    "echo '✅ Deployment completed!'",
    "echo '🌐 App URL: https://11seconds.de:3011'",
    "echo '📝 Check logs: tail -f app.log'"
)

# Create SSH command script
$sshScript = $commands -join "; "

Write-Host "🔄 Executing commands on server..." -ForegroundColor Yellow

try {
    # Method 1: Try using ssh with password automation
    if (Get-Command ssh -ErrorAction SilentlyContinue) {
        Write-Host "Using SSH client..." -ForegroundColor Green
        
        # Create expect-like behavior with PowerShell
        $process = New-Object System.Diagnostics.Process
        $process.StartInfo.FileName = "ssh"
        $process.StartInfo.Arguments = "$sshUser@$sshHost"
        $process.StartInfo.UseShellExecute = $false
        $process.StartInfo.RedirectStandardInput = $true
        $process.StartInfo.RedirectStandardOutput = $true
        $process.StartInfo.RedirectStandardError = $true
        $process.StartInfo.CreateNoWindow = $true
        
        $process.Start() | Out-Null
        
        # Send password and commands
        $process.StandardInput.WriteLine($sshPass)
        Start-Sleep -Seconds 2
        
        foreach ($cmd in $commands) {
            Write-Host "Executing: $cmd" -ForegroundColor Gray
            $process.StandardInput.WriteLine($cmd)
            Start-Sleep -Milliseconds 500
        }
        
        $process.StandardInput.WriteLine("exit")
        $process.WaitForExit(30000)  # 30 second timeout
        
        $output = $process.StandardOutput.ReadToEnd()
        $error = $process.StandardError.ReadToEnd()
        
        Write-Host "📤 Command Output:" -ForegroundColor Green
        Write-Host $output -ForegroundColor White
        
        if ($error) {
            Write-Host "⚠️ Errors/Warnings:" -ForegroundColor Yellow
            Write-Host $error -ForegroundColor Yellow
        }
        
    } else {
        Write-Host "❌ SSH client not found" -ForegroundColor Red
        
        # Method 2: Try PowerShell SSH module
        if (Get-Module -ListAvailable -Name Posh-SSH) {
            Write-Host "Using Posh-SSH module..." -ForegroundColor Green
            Import-Module Posh-SSH
            
            $credential = New-Object System.Management.Automation.PSCredential($sshUser, (ConvertTo-SecureString $sshPass -AsPlainText -Force))
            $session = New-SSHSession -ComputerName $sshHost -Credential $credential
            
            foreach ($cmd in $commands) {
                Write-Host "Executing: $cmd" -ForegroundColor Gray
                $result = Invoke-SSHCommand -SessionId $session.SessionId -Command $cmd
                Write-Host $result.Output -ForegroundColor White
            }
            
            Remove-SSHSession -SessionId $session.SessionId
        } else {
            Write-Host "Installing Posh-SSH module..." -ForegroundColor Yellow
            Install-Module -Name Posh-SSH -Force -Scope CurrentUser
            
            Write-Host "Please run the script again after module installation." -ForegroundColor Yellow
        }
    }
} catch {
    Write-Host "❌ SSH automation failed: $($_.Exception.Message)" -ForegroundColor Red
    
    Write-Host "`n💡 Manual SSH command:" -ForegroundColor Yellow
    Write-Host "ssh $sshUser@$sshHost" -ForegroundColor White
    Write-Host "Password: $sshPass" -ForegroundColor White
    Write-Host "`nThen execute:" -ForegroundColor Yellow
    foreach ($cmd in $commands) {
        Write-Host "  $cmd" -ForegroundColor White
    }
}

Write-Host "`n🎉 SSH Deployment script completed!" -ForegroundColor Green
