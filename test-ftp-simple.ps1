# Simple FTP Test Script
Write-Host "🤖 Testing FTP Connection with new credentials..." -ForegroundColor Cyan

# New FTP credentials from .env.netcup
$ftpHost = "ftp.11seconds.de"
$ftpUser = "hk302164_11s"
$ftpPass = "hallo.411S"

Write-Host "🔄 Connecting to: $ftpHost as $ftpUser" -ForegroundColor Yellow

try {
    # Create FTP request
    $ftpUri = "ftp://$ftpHost/"
    $request = [System.Net.FtpWebRequest]::Create($ftpUri)
    $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
    $request.UseBinary = $true
    $request.UsePassive = $true
    $request.KeepAlive = $false
    $request.Timeout = 30000
    
    Write-Host "📡 Sending FTP request..." -ForegroundColor Yellow
    
    # Get response
    $response = $request.GetResponse()
    $stream = $response.GetResponseStream()
    $reader = New-Object System.IO.StreamReader($stream)
    $listing = $reader.ReadToEnd()
    
    # Clean up
    $reader.Close()
    $response.Close()
    
    Write-Host "✅ FTP CONNECTION SUCCESSFUL!" -ForegroundColor Green
    Write-Host "📁 Remote directory contents:" -ForegroundColor Cyan
    
    $listing.Split("`n") | ForEach-Object { 
        if ($_.Trim()) { 
            Write-Host "   📄 $_" -ForegroundColor White
        } 
    }
    
    Write-Host "`n🚀 Ready for deployment!" -ForegroundColor Green
}
catch {
    Write-Host "❌ FTP CONNECTION FAILED!" -ForegroundColor Red
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
    
    if ($_.Exception.Message -like "*530*") {
        Write-Host "💡 Hint: 530 = Login incorrect - check username/password" -ForegroundColor Yellow
    }
    elseif ($_.Exception.Message -like "*timeout*") {
        Write-Host "💡 Hint: Connection timeout - check host/firewall" -ForegroundColor Yellow
    }
}
