# Quick upload cache-clear.html
$FTP_HOST = "ftp.11seconds.de"
$FTP_USER = "k302164_11s"  
$FTP_PASS = "hallo.411S"

try {
    $ftpUri = "ftp://$FTP_HOST/cache-clear.html"
    $request = [System.Net.FtpWebRequest]::Create($ftpUri)
    $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
    $request.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
    $request.UsePassive = $true
    $request.UseBinary = $true
    
    $content = [System.IO.File]::ReadAllBytes("httpdocs\cache-clear.html")
    $request.ContentLength = $content.Length
    
    $stream = $request.GetRequestStream()
    $stream.Write($content, 0, $content.Length)
    $stream.Close()
    
    $response = $request.GetResponse()
    $response.Close()
    
    Write-Host "Cache-Clear Seite hochgeladen!" -ForegroundColor Green
    Write-Host "Verfügbar unter: http://11seconds.de/cache-clear.html" -ForegroundColor Yellow
    
} catch {
    Write-Host "Fehler: $($_.Exception.Message)" -ForegroundColor Red
}
