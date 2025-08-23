# Deployment with Simple Verification
# Using simple filename to avoid server issues

Write-Host "=== 11Seconds Simple Verification Deployment ===" -ForegroundColor Cyan

$version = "v2.4.2-" + (Get-Date -Format "yyyyMMdd-HHmmss")
$timestamp = Get-Date -Format "dd.MM.yyyy HH:mm:ss"

Write-Host "Version: $version" -ForegroundColor Green  

# Create simple verification file
$verificationContent = @"
<!DOCTYPE html>
<html>
<head>
    <title>11Seconds Deployment Verification</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f0f8ff; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-size: 24px; margin-bottom: 20px; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .version { font-weight: bold; color: #007bff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="success">✅ Deployment Successful!</div>
        <div class="info">
            <strong>Version:</strong> <span class="version">$version</span><br>
            <strong>Deployed:</strong> $timestamp<br>
            <strong>Status:</strong> Live and Running
        </div>
        <p>This verification page confirms that the latest deployment of 11Seconds Quiz Game is successfully live on the server.</p>
        <p><a href="/" style="color: #007bff;">← Back to 11Seconds Game</a></p>
    </div>
</body>
</html>
"@

Write-Host "[1/6] Creating simple verification file..." -ForegroundColor Yellow

$verificationFile = "web\public\deployment.html"
$verificationContent | Out-File -FilePath $verificationFile -Encoding UTF8

Write-Host "Verification created: $verificationFile" -ForegroundColor Green

# Build React
Write-Host "[2/6] Building React..." -ForegroundColor Yellow
Set-Location web
npm run build
Set-Location ..

# Verify build contains verification
$buildVerification = "web\build\deployment.html"
if (Test-Path $buildVerification) {
    Write-Host "Build completed - verification file verified in build" -ForegroundColor Green
} else {
    Write-Host "⚠️  Warning: Verification file not found in build" -ForegroundColor Red
}

# Copy to httpdocs
Write-Host "[3/6] Copying to httpdocs..." -ForegroundColor Yellow
if (Test-Path "httpdocs") {
    Remove-Item -Path "httpdocs" -Recurse -Force
}
Copy-Item -Path "web\build" -Destination "httpdocs" -Recurse

if (Test-Path "httpdocs\deployment.html") {
    Write-Host "Verification file verified in httpdocs" -ForegroundColor Green
} else {
    Write-Host "⚠️  Warning: Verification file not found in httpdocs" -ForegroundColor Red
}

# Update Service Worker
Write-Host "[4/6] Updating Service Worker..." -ForegroundColor Yellow
$swPath = "httpdocs\sw.js"
if (Test-Path $swPath) {
    $swContent = Get-Content $swPath -Raw
    $newCacheName = "11seconds-cache-v$($version.Replace('.', '').Replace('-', ''))"
    $updatedContent = $swContent -replace "11seconds-cache-v\d+", $newCacheName
    $updatedContent = $updatedContent -replace "deployment\.html", "deployment.html"
    $updatedContent | Out-File -FilePath $swPath -Encoding UTF8
    Write-Host "Service Worker updated with verification file" -ForegroundColor Green
} else {
    Write-Host "⚠️  Service Worker not found" -ForegroundColor Yellow
}

# Test FTP
Write-Host "[5/6] Testing FTP..." -ForegroundColor Yellow
$ftpServer = "ftp.11seconds.de"
$username = "k302164_11s"
$password = "hallo.411S"

try {
    $testUrl = "ftp://$ftpServer/"
    $request = [Net.FtpWebRequest]::Create($testUrl)
    $request.Method = [Net.WebRequestMethods+Ftp]::ListDirectory
    $request.Credentials = New-Object Net.NetworkCredential($username, $password)
    $response = $request.GetResponse()
    Write-Host "FTP OK" -ForegroundColor Green
    $response.Close()
} catch {
    Write-Host "⚠️  FTP test failed: $($_.Exception.Message)" -ForegroundColor Red
}

# Upload files with simple verification
Write-Host "[6/6] Uploading with simple verification..." -ForegroundColor Yellow

$sourceDir = "httpdocs"
$uploaded = 0
$failed = 0
$verificationUploaded = $false

function Upload-FileToFTP($localFile, $remoteFile) {
    try {
        $ftpUrl = "ftp://$ftpServer/$remoteFile"
        $request = [Net.FtpWebRequest]::Create($ftpUrl)
        $request.Method = [Net.WebRequestMethods+Ftp]::UploadFile
        $request.Credentials = New-Object Net.NetworkCredential($username, $password)
        $request.UseBinary = $true
        
        $fileBytes = [System.IO.File]::ReadAllBytes($localFile)
        $request.ContentLength = $fileBytes.Length
        
        $requestStream = $request.GetRequestStream()
        $requestStream.Write($fileBytes, 0, $fileBytes.Length)
        $requestStream.Close()
        
        $response = $request.GetResponse()
        $response.Close()
        return $true
    } catch {
    Write-Host "  $remoteFile : $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

# Get all files recursively
$allFiles = Get-ChildItem -Path $sourceDir -Recurse -File
$total = $allFiles.Count

foreach ($file in $allFiles) {
    $relativePath = $file.FullName.Substring($sourceDir.Length + 1).Replace('\', '/')
    
    if (Upload-FileToFTP $file.FullName $relativePath) {
        Write-Host "  ✅ $relativePath" -ForegroundColor Green
        $uploaded++
        
        if ($relativePath -eq "deployment.html") {
            Write-Host "  >>> VERIFICATION UPLOADED! <<<" -ForegroundColor Magenta
            $verificationUploaded = $true
        }
    } else {
        $failed++
    }
}

# Final verification
Write-Host "`n=== SIMPLE VERIFICATION RESULTS ===" -ForegroundColor Cyan
Write-Host "Version: $version" -ForegroundColor Green

if ($verificationUploaded) {
    Write-Host "Verification Upload: SUCCESS" -ForegroundColor Green
} else {
    Write-Host "Verification Upload: FAILED" -ForegroundColor Red
}

if ($failed -eq 0) {
    Write-Host "Files: Uploaded $uploaded, Failed $failed" -ForegroundColor Green
} else {
    Write-Host "Files: Uploaded $uploaded, Failed $failed" -ForegroundColor Red
}

Write-Host "`n🌐 LIVE TEST URL:" -ForegroundColor Blue
Write-Host "   http://11seconds.de/deployment.html" -ForegroundColor Yellow

if ($verificationUploaded -and $failed -eq 0) {
    Write-Host "`n🎉 SIMPLE VERIFICATION DEPLOYMENT SUCCESS!" -ForegroundColor Green
    Write-Host "Open the URL above to verify the deployment is live!" -ForegroundColor Green
} else {
    Write-Host "`nDEPLOYMENT HAD ISSUES" -ForegroundColor Red
    Write-Host "Check the logs above for details." -ForegroundColor Red
}
