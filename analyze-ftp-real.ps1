# FTP Server Analysis - What's really there?
$FTP_HOST = "ftp.11seconds.de"
$FTP_USER = "k302164_11s"  
$FTP_PASS = "hallo.411S"

Write-Host "=== ANALYZING REAL FTP SERVER CONTENT ===" -ForegroundColor Red

try {
    # List root directory
    Write-Host "[1] Root directory listing:" -ForegroundColor Yellow
    $request = [System.Net.FtpWebRequest]::Create("ftp://$FTP_HOST/")
    $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectoryDetails
    $request.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
    $request.UsePassive = $true
    
    $response = $request.GetResponse()
    $stream = $response.GetResponseStream()
    $reader = New-Object System.IO.StreamReader($stream)
    $content = $reader.ReadToEnd()
    $reader.Close()
    $response.Close()
    
    $lines = $content.Split("`n") | Where-Object {$_.Trim()}
    foreach ($line in $lines) {
        Write-Host "  $($line.Trim())" -ForegroundColor Gray
    }
    
    # Check for our specific verification file
    Write-Host "`n[2] Checking for verification file:" -ForegroundColor Yellow
    $verificationFile = "build-verification-20250823-140525-c02ddfe7.json"
    
    try {
        $fileRequest = [System.Net.FtpWebRequest]::Create("ftp://$FTP_HOST/$verificationFile")
        $fileRequest.Method = [System.Net.WebRequestMethods+Ftp]::GetFileSize
        $fileRequest.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
        
        $fileResponse = $fileRequest.GetResponse()
        $fileSize = $fileResponse.ContentLength
        $fileResponse.Close()
        
        Write-Host "  FOUND: $verificationFile ($fileSize bytes)" -ForegroundColor Green
        
        # Try to download it
        $downloadRequest = [System.Net.FtpWebRequest]::Create("ftp://$FTP_HOST/$verificationFile")
        $downloadRequest.Method = [System.Net.WebRequestMethods+Ftp]::DownloadFile
        $downloadRequest.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
        
        $downloadResponse = $downloadRequest.GetResponse()
        $downloadStream = $downloadResponse.GetResponseStream()
        $downloadReader = New-Object System.IO.StreamReader($downloadStream)
        $downloadContent = $downloadReader.ReadToEnd()
        $downloadReader.Close()
        $downloadResponse.Close()
        
        Write-Host "  CONTENT: $downloadContent" -ForegroundColor Green
        
    } catch {
        Write-Host "  NOT FOUND: $verificationFile" -ForegroundColor Red
        Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
    }
    
    # Check index.html timestamp
    Write-Host "`n[3] Checking index.html:" -ForegroundColor Yellow
    try {
        $indexRequest = [System.Net.FtpWebRequest]::Create("ftp://$FTP_HOST/index.html")
        $indexRequest.Method = [System.Net.WebRequestMethods+Ftp]::GetDateTimestamp
        $indexRequest.Credentials = New-Object System.Net.NetworkCredential($FTP_USER, $FTP_PASS)
        
        $indexResponse = $indexRequest.GetResponse()
        Write-Host "  index.html timestamp: $($indexResponse.LastModified)" -ForegroundColor Green
        $indexResponse.Close()
    } catch {
        Write-Host "  index.html: ERROR - $($_.Exception.Message)" -ForegroundColor Red
    }
    
    # Check what files start with "build-"
    Write-Host "`n[4] Searching for build-* files:" -ForegroundColor Yellow
    $allFiles = $content.Split("`n") | Where-Object {$_.Trim()}
    $buildFiles = $allFiles | Where-Object {$_ -like "*build-*"}
    
    if ($buildFiles) {
        foreach ($file in $buildFiles) {
            Write-Host "  FOUND: $($file.Trim())" -ForegroundColor Green
        }
    } else {
        Write-Host "  NO build-* files found" -ForegroundColor Red
    }
    
} catch {
    Write-Host "FTP Analysis failed: $($_.Exception.Message)" -ForegroundColor Red
}

# Also check what we have locally in httpdocs
Write-Host "`n=== LOCAL HTTPDOCS CONTENT ===" -ForegroundColor Yellow
if (Test-Path "httpdocs") {
    $localFiles = Get-ChildItem "httpdocs" -File | Select-Object Name, Length, LastWriteTime
    foreach ($file in $localFiles) {
        Write-Host "  $($file.Name) ($($file.Length) bytes, $($file.LastWriteTime))" -ForegroundColor Gray
        
        if ($file.Name -like "*build-verification*") {
            Write-Host "    >>> VERIFICATION FILE FOUND LOCALLY <<<" -ForegroundColor Green
            $content = Get-Content "httpdocs\$($file.Name)" -Raw
            Write-Host "    Content: $content" -ForegroundColor Green
        }
    }
} else {
    Write-Host "  httpdocs folder not found" -ForegroundColor Red
}
