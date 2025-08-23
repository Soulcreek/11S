# Quick HTML Verification Check

Write-Host "=== HTML VERIFICATION CHECK ===" -ForegroundColor Cyan

$htmlFile = "verify-20250823-141554-23c032ce.html"
$ftpServer = "ftp.11seconds.de"
$username = "k302164_11s"
$password = "hallo.411S"

Write-Host "`n1. Checking FTP server..." -ForegroundColor Yellow

try {
    $ftpurl = "ftp://$ftpServer/$htmlFile"
    $request = [Net.FtpWebRequest]::Create($ftpurl)
    $request.Method = [Net.WebRequestMethods+Ftp]::GetFileSize
    $request.Credentials = New-Object Net.NetworkCredential($username, $password)
    $response = $request.GetResponse()
    
    Write-Host "HTML file exists on FTP: $($response.ContentLength) bytes" -ForegroundColor Green
    $response.Close()
} catch {
    Write-Host "FTP check failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n2. Testing web accessibility..." -ForegroundColor Yellow

try {
    $webUrl = "http://11seconds.de/$htmlFile"
    $webRequest = Invoke-WebRequest -Uri $webUrl -Method Get -TimeoutSec 10
    Write-Host "Web access SUCCESS: Status $($webRequest.StatusCode)" -ForegroundColor Green
    Write-Host "Content length: $($webRequest.Content.Length) characters"
    Write-Host "First 100 chars: $($webRequest.Content.Substring(0, [Math]::Min(100, $webRequest.Content.Length)))"
} catch {
    Write-Host "Web access FAILED: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.Exception.Response) {
        Write-Host "HTTP Status: $($_.Exception.Response.StatusCode)" -ForegroundColor Red
    }
}

Write-Host "`n3. Testing main site for comparison..." -ForegroundColor Yellow
try {
    $mainRequest = Invoke-WebRequest -Uri "http://11seconds.de" -Method Get -TimeoutSec 10
    Write-Host "Main site works: Status $($mainRequest.StatusCode)" -ForegroundColor Green
} catch {
    Write-Host "Main site failed: $($_.Exception.Message)" -ForegroundColor Red
}
