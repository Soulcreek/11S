# Simple FTP Test
Write-Host "=== FTP Test ===" -ForegroundColor Cyan

$FTP_HOST = "ftp.11seconds.de"
$FTP_USER = "k302164_11s"
$FTP_PASS = "hallo.411S"

try {
    Write-Host "Testing: $FTP_HOST with user $FTP_USER" -ForegroundColor Yellow
    
    $ftpUri = "ftp://$FTP_HOST/"
    $request = [System.Net.FtpWebRequest]::Create($ftpUri)
    $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $request.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
    $request.UsePassive = $true
    $request.Timeout = 10000
    
    $response = $request.GetResponse()
    Write-Host "✓ Connection successful!" -ForegroundColor Green
    $response.Close()
    
} catch {
    Write-Host "✗ Connection failed: $($_.Exception.Message)" -ForegroundColor Red
}
