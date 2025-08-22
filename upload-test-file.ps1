# Upload Testdatei zu Netcup FTP
Write-Host "Uploading test file to verify deployment..." -ForegroundColor Cyan

# FTP-Einstellungen
$ftpHost = "ftp.11seconds.de"
$ftpUser = "k302164_11s"
$ftpPass = "hallo.411S"

$localFile = ".\deploy-netcup-auto\httpdocs\deployment-test.html"
$remotePath = "httpdocs/deployment-test.html"

# FTP Upload Funktion
function Upload-TestFile($localFile, $remotePath) {
    Write-Host "Uploading: $localFile -> $remotePath" -ForegroundColor Cyan
    
    if (-not (Test-Path $localFile)) {
        Write-Host "ERROR: Local file not found: $localFile" -ForegroundColor Red
        return $false
    }
    
    try {
        # FTP-Request erstellen
        $uri = "ftp://$ftpHost/$remotePath"
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $request.UseBinary = $true
        $request.UsePassive = $true
        $request.KeepAlive = $false
        $request.Timeout = 30000
        
        # Datei lesen und hochladen
        $fileContent = [System.IO.File]::ReadAllBytes($localFile)
        $request.ContentLength = $fileContent.Length
        
        $requestStream = $request.GetRequestStream()
        $requestStream.Write($fileContent, 0, $fileContent.Length)
        $requestStream.Close()
        
        # Antwort erhalten
        $response = $request.GetResponse()
        Write-Host "SUCCESS - Test file uploaded: $($response.StatusDescription)" -ForegroundColor Green
        $response.Close()
        
        return $true
    }
    catch {
        Write-Host "FAILED: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

# Testdatei hochladen
$result = Upload-TestFile $localFile $remotePath

if ($result) {
    Write-Host "`nTest file successfully uploaded!" -ForegroundColor Green
    Write-Host "You can verify it at: https://11seconds.de/deployment-test.html" -ForegroundColor Cyan
} else {
    Write-Host "`nTest file upload failed." -ForegroundColor Red
}
