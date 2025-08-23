# FTP Connection Test - Fixed Version
# ASCII encoding, no special characters

Write-Host "=== FTP Test for 11seconds.de ===" -ForegroundColor Cyan

$FTP_HOST = "ftp.11seconds.de"
$FTP_USER = "k302164_11s"
$FTP_PASS = "hallo.411S"

Write-Host "Host: $FTP_HOST" -ForegroundColor Yellow
Write-Host "User: $FTP_USER" -ForegroundColor Yellow

try {
    Write-Host "`nTesting connection..." -ForegroundColor Blue
    
    # Test directory listing
    $ftpUri = "ftp://$FTP_HOST/"
    $request = [System.Net.FtpWebRequest]::Create($ftpUri)
    $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $request.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
    $request.UsePassive = $true
    $request.Timeout = 15000
    
    Write-Host "Connecting..." -ForegroundColor Gray
    $response = $request.GetResponse()
    $stream = $response.GetResponseStream()
    $reader = New-Object System.IO.StreamReader($stream)
    $content = $reader.ReadToEnd()
    $reader.Close()
    $response.Close()
    
    Write-Host "SUCCESS: FTP connection works!" -ForegroundColor Green
    
    Write-Host "`nDirectory contents:" -ForegroundColor Blue
    if ($content.Trim()) {
        $content.Split("`n") | ForEach-Object {
            if ($_.Trim()) {
                Write-Host "  $($_.Trim())" -ForegroundColor Gray
            }
        }
    } else {
        Write-Host "  (Empty or no readable files)" -ForegroundColor Gray
    }
    
    # Test file upload
    Write-Host "`nTesting file upload..." -ForegroundColor Blue
    $testContent = "FTP Test $(Get-Date)"
    $testFile = "test-$(Get-Date -Format 'yyyyMMdd-HHmmss').txt"
    
    $uploadUri = "ftp://$FTP_HOST/$testFile"
    $uploadRequest = [System.Net.FtpWebRequest]::Create($uploadUri)
    $uploadRequest.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
    $uploadRequest.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
    $uploadRequest.UsePassive = $true
    
    $bytes = [System.Text.Encoding]::UTF8.GetBytes($testContent)
    $uploadRequest.ContentLength = $bytes.Length
    
    $uploadStream = $uploadRequest.GetRequestStream()
    $uploadStream.Write($bytes, 0, $bytes.Length)
    $uploadStream.Close()
    
    $uploadResponse = $uploadRequest.GetResponse()
    $uploadResponse.Close()
    
    Write-Host "SUCCESS: File upload works!" -ForegroundColor Green
    Write-Host "Test file: $testFile" -ForegroundColor Gray
    
    # Clean up test file
    try {
        $deleteUri = "ftp://$FTP_HOST/$testFile"
        $deleteRequest = [System.Net.FtpWebRequest]::Create($deleteUri)
        $deleteRequest.Method = [System.Net.WebRequestMethods+Ftp]::DeleteFile
        $deleteRequest.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
        
        $deleteResponse = $deleteRequest.GetResponse()
        $deleteResponse.Close()
        
        Write-Host "Test file cleaned up" -ForegroundColor Green
    } catch {
        Write-Host "Could not delete test file (but upload worked)" -ForegroundColor Yellow
    }
    
    Write-Host "`nRESULT: FTP credentials are VALID!" -ForegroundColor Green
    Write-Host "Ready for deployment." -ForegroundColor Green
    
} catch {
    Write-Host "ERROR: FTP connection failed!" -ForegroundColor Red
    Write-Host "Details: $($_.Exception.Message)" -ForegroundColor Red
    
    # Error analysis
    if ($_.Exception.Message -like "*530*") {
        Write-Host "`nLikely cause: Wrong username/password" -ForegroundColor Yellow
    } elseif ($_.Exception.Message -like "*550*") {
        Write-Host "`nLikely cause: Permission denied" -ForegroundColor Yellow
    } elseif ($_.Exception.Message -like "*timeout*") {
        Write-Host "`nLikely cause: Network timeout" -ForegroundColor Yellow
    } else {
        Write-Host "`nPossible causes:" -ForegroundColor Yellow
        Write-Host "- Network connectivity issues" -ForegroundColor Gray
        Write-Host "- FTP server unavailable" -ForegroundColor Gray
        Write-Host "- Firewall blocking FTP" -ForegroundColor Gray
    }
    
    exit 1
}
