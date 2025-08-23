# Check FTP Server Contents
$FTP_HOST = "ftp.11seconds.de"
$FTP_USER = "k302164_11s"  
$FTP_PASS = "hallo.411S"

Write-Host "=== Checking FTP Server Contents ===" -ForegroundColor Cyan

try {
    $request = [System.Net.FtpWebRequest]::Create("ftp://$FTP_HOST/")
    $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $request.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
    $request.UsePassive = $true
    
    $response = $request.GetResponse()
    $stream = $response.GetResponseStream()
    $reader = New-Object System.IO.StreamReader($stream)
    $content = $reader.ReadToEnd()
    $reader.Close()
    $response.Close()
    
    Write-Host "FTP Directory Contents:" -ForegroundColor Yellow
    $files = $content.Split("`n") | Where-Object {$_.Trim()}
    foreach ($file in $files) {
        Write-Host "  $($file.Trim())" -ForegroundColor Gray
    }
    
    Write-Host "`nChecking specific files:" -ForegroundColor Yellow
    
    # Check index.html timestamp
    try {
        $indexRequest = [System.Net.FtpWebRequest]::Create("ftp://$FTP_HOST/index.html")
        $indexRequest.Method = [System.Net.WebRequestMethods+Ftp]::GetDateTimestamp
        $indexRequest.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
        $indexResponse = $indexRequest.GetResponse()
        Write-Host "  index.html timestamp: $($indexResponse.LastModified)" -ForegroundColor Green
        $indexResponse.Close()
    } catch {
        Write-Host "  index.html: NOT FOUND" -ForegroundColor Red
    }
    
    # Check cache-clear.html
    try {
        $cacheRequest = [System.Net.FtpWebRequest]::Create("ftp://$FTP_HOST/cache-clear.html")
        $cacheRequest.Method = [System.Net.WebRequestMethods+Ftp]::GetDateTimestamp
        $cacheRequest.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
        $cacheResponse = $cacheRequest.GetResponse()
        Write-Host "  cache-clear.html timestamp: $($cacheResponse.LastModified)" -ForegroundColor Green
        $cacheResponse.Close()
    } catch {
        Write-Host "  cache-clear.html: NOT FOUND" -ForegroundColor Red
    }
    
} catch {
    Write-Host "FTP Error: $($_.Exception.Message)" -ForegroundColor Red
}
