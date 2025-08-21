Write-Host "🔍 DIREKTER FTP TEST" -ForegroundColor Magenta

# Aktualisierte Zugangsdaten
$host = "ftp.11seconds.de"
$user = "k302164_11s"  # Korrigiert von hk302164_11s
$pass = "hallo.411S"

Write-Host "Testing: $user@$host" -ForegroundColor Cyan

try {
    $uri = "ftp://$host/"
    $req = [System.Net.FtpWebRequest]::Create($uri)
    $req.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $req.Credentials = New-Object System.Net.NetworkCredential($user, $pass)
    
    $resp = $req.GetResponse()
    Write-Host "✅ SUCCESS: FTP connection works!" -ForegroundColor Green
    $resp.Close()
} catch {
    $err = $_.Exception.Message
    Write-Host "❌ FAILED: $err" -ForegroundColor Red
    
    if ($err -like "*530*") {
        Write-Host "💡 ERROR 530 = Wrong username/password" -ForegroundColor Yellow
        Write-Host "   Check: Is '$user' the correct username?" -ForegroundColor Yellow
        Write-Host "   Check: Is password correct?" -ForegroundColor Yellow
    }
}

# Test upload immediately if connection works
Write-Host "`nTesting upload..." -ForegroundColor Yellow
try {
    $testContent = "Test upload $(Get-Date)"
    $testFile = "test-upload.txt" 
    $testContent | Out-File $testFile
    
    $upUri = "ftp://$host/test-upload.txt"
    $upReq = [System.Net.FtpWebRequest]::Create($upUri)
    $upReq.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
    $upReq.Credentials = New-Object System.Net.NetworkCredential($user, $pass)
    
    $bytes = [System.IO.File]::ReadAllBytes($testFile)
    $upReq.ContentLength = $bytes.Length
    $stream = $upReq.GetRequestStream()
    $stream.Write($bytes, 0, $bytes.Length)
    $stream.Close()
    $upResp = $upReq.GetResponse()
    $upResp.Close()
    
    Write-Host "✅ Upload test successful!" -ForegroundColor Green
    Remove-Item $testFile
} catch {
    Write-Host "❌ Upload failed: $($_.Exception.Message)" -ForegroundColor Red
}
