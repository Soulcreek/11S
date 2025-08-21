# Direct FTP Test
try {
    Write-Host "🔌 Testing FTP connection to ftp.11seconds.de..." -ForegroundColor Cyan
    Write-Host "👤 User: hosting223936" -ForegroundColor Gray
    
    $ftpRequest = [System.Net.FtpWebRequest]::Create("ftp://ftp.11seconds.de/")
    $ftpRequest.Credentials = New-Object System.Net.NetworkCredential("hosting223936", "hallo.4Netcup")
    $ftpRequest.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $ftpRequest.Timeout = 15000
    
    Write-Host "⏳ Connecting..." -ForegroundColor Yellow
    $response = $ftpRequest.GetResponse()
    
    Write-Host "✅ FTP connection successful!" -ForegroundColor Green
    Write-Host "🎉 Ready for automatic deployment!" -ForegroundColor Green
    
    # List directory contents
    $responseStream = $response.GetResponseStream()
    $reader = New-Object System.IO.StreamReader($responseStream)
    $dirList = $reader.ReadToEnd()
    
    Write-Host "`n📁 Directory contents:" -ForegroundColor Cyan
    Write-Host $dirList -ForegroundColor White
    
    $reader.Close()
    $response.Close()
    
    Write-Host "✅ FTP test completed successfully!" -ForegroundColor Green
    
} catch {
    Write-Host "❌ FTP connection failed!" -ForegroundColor Red
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
    
    Write-Host "`n💡 Troubleshooting tips:" -ForegroundColor Yellow
    Write-Host "  1. Check FTP host: ftp.11seconds.de" -ForegroundColor White
    Write-Host "  2. Verify username: hosting223936" -ForegroundColor White
    Write-Host "  3. Check password in Netcup control panel" -ForegroundColor White
    Write-Host "  4. Ensure FTP is enabled in Netcup" -ForegroundColor White
}
