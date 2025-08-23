Write-Host "=== FTP Test ==="
$FTP_HOST = "ftp.11seconds.de"
$FTP_USER = "k302164_11s" 
$FTP_PASS = "hallo.411S"

try {
    Write-Host "Testing: $FTP_HOST"
    $ftpUri = "ftp://$FTP_HOST/"
    $request = [System.Net.FtpWebRequest]::Create($ftpUri)
    $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $request.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
    $request.UsePassive = $true
    $request.Timeout = 10000
    
    $response = $request.GetResponse()
    Write-Host "SUCCESS: FTP connection works!"
    $response.Close()
    
} catch {
    Write-Host "FAILED: $($_.Exception.Message)"
}
