# FTP Credential Test für 11seconds.de
Write-Host "=== FTP Credential Test ===" -ForegroundColor Cyan

$FTP_HOST = "ftp.11seconds.de"
$FTP_USER = "k302164_11s"
$FTP_PASS = "hallo.411S"

Write-Host "Host: $FTP_HOST" -ForegroundColor Yellow
Write-Host "User: $FTP_USER" -ForegroundColor Yellow
Write-Host "Pass: $('*' * $FTP_PASS.Length)" -ForegroundColor Yellow

try {
    Write-Host "`nTesting connection..." -ForegroundColor Blue
    
    # Test 1: List Directory
    $ftpUri = "ftp://$FTP_HOST/"
    $request = [System.Net.FtpWebRequest]::Create($ftpUri)
    $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $request.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
    $request.UsePassive = $true
    $request.Timeout = 15000
    
    Write-Host "  → Connecting to server..." -ForegroundColor Gray
    $response = $request.GetResponse()
    $responseStream = $response.GetResponseStream()
    $reader = New-Object System.IO.StreamReader($responseStream)
    $content = $reader.ReadToEnd()
    $reader.Close()
    $response.Close()
    
    Write-Host "✓ Connection successful!" -ForegroundColor Green
    Write-Host "`nDirectory listing:" -ForegroundColor Blue
    
    if ($content.Trim()) {
        $content.Split("`n") | ForEach-Object {
            if ($_.Trim()) {
                Write-Host "  $($_.Trim())" -ForegroundColor Gray
            }
        }
    } else {
        Write-Host "  (Empty directory or no readable files)" -ForegroundColor Gray
    }
    
    # Test 2: Upload a small test file
    Write-Host "`nTesting file upload..." -ForegroundColor Blue
    $testContent = "11Seconds FTP Test - $(Get-Date)"
    $testFileName = "ftp-test-$(Get-Date -Format 'yyyyMMdd-HHmmss').txt"
    
    $uploadUri = "ftp://$FTP_HOST/$testFileName"
    $uploadRequest = [System.Net.FtpWebRequest]::Create($uploadUri)
    $uploadRequest.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
    $uploadRequest.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
    $uploadRequest.UsePassive = $true
    
    $testBytes = [System.Text.Encoding]::UTF8.GetBytes($testContent)
    $uploadRequest.ContentLength = $testBytes.Length
    
    $uploadStream = $uploadRequest.GetRequestStream()
    $uploadStream.Write($testBytes, 0, $testBytes.Length)
    $uploadStream.Close()
    
    $uploadResponse = $uploadRequest.GetResponse()
    $uploadResponse.Close()
    
    Write-Host "✓ File upload successful!" -ForegroundColor Green
    Write-Host "  Test file: $testFileName" -ForegroundColor Gray
    
    # Test 3: Delete test file
    try {
        $deleteUri = "ftp://$FTP_HOST/$testFileName"
        $deleteRequest = [System.Net.FtpWebRequest]::Create($deleteUri)
        $deleteRequest.Method = [System.Net.WebRequestMethods+Ftp]::DeleteFile
        $deleteRequest.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
        
        $deleteResponse = $deleteRequest.GetResponse()
        $deleteResponse.Close()
        
        Write-Host "✓ Test file cleanup successful" -ForegroundColor Green
    } catch {
        Write-Host "⚠ Test file cleanup failed (but upload worked)" -ForegroundColor Yellow
    }
    
    Write-Host "`n🎉 FTP Credentials are VALID and working!" -ForegroundColor Green
    Write-Host "✓ Ready for deployment" -ForegroundColor Green
    
} catch {
    Write-Host "✗ FTP Connection failed!" -ForegroundColor Red
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
    
    if ($_.Exception.Message -like "*530*") {
        Write-Host "`n💡 Error 530 = Login incorrect" -ForegroundColor Yellow
        Write-Host "   → Check username and password" -ForegroundColor Gray
    } elseif ($_.Exception.Message -like "*550*") {
        Write-Host "`n💡 Error 550 = Permission denied" -ForegroundColor Yellow
        Write-Host "   → Check directory permissions" -ForegroundColor Gray
    } else {
        Write-Host "`n💡 Possible issues:" -ForegroundColor Yellow
        Write-Host "   → Network connectivity" -ForegroundColor Gray
        Write-Host "   → FTP server temporarily unavailable" -ForegroundColor Gray
        Write-Host "   → Firewall blocking FTP" -ForegroundColor Gray
    }
    
    exit 1
}
