# Forced FTP Upload - Jede Datei einzeln mit Fehlerbehandlung
Write-Host "🚀 FORCED FTP UPLOAD TO NETCUP" -ForegroundColor Magenta
Write-Host "===============================" -ForegroundColor Magenta

$ftpHost = "ftp.11seconds.de"
$ftpUser = "hk302164_11s" 
$ftpPass = "hallo.411S"

function Force-Upload-File($localFile, $remotePath) {
    Write-Host "`n📤 Uploading: $localFile → $remotePath" -ForegroundColor Cyan
    
    if (-not (Test-Path $localFile)) {
        Write-Host "  ❌ Local file not found: $localFile" -ForegroundColor Red
        return $false
    }
    
    try {
        # Create FTP request
        $uri = "ftp://$ftpHost/$remotePath"
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $request.UseBinary = $true
        $request.UsePassive = $true
        $request.KeepAlive = $false
        $request.Timeout = 30000
        
        # Read file and upload
        $fileContent = [System.IO.File]::ReadAllBytes($localFile)
        $request.ContentLength = $fileContent.Length
        
        Write-Host "  📡 Uploading $($fileContent.Length) bytes..." -ForegroundColor Yellow
        
        $requestStream = $request.GetRequestStream()
        $requestStream.Write($fileContent, 0, $fileContent.Length)
        $requestStream.Close()
        
        # Get response
        $response = $request.GetResponse()
        Write-Host "  ✅ SUCCESS - Status: $($response.StatusDescription)" -ForegroundColor Green
        $response.Close()
        
        return $true
    }
    catch {
        Write-Host "  ❌ FAILED: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

function Force-Create-Directory($remotePath) {
    Write-Host "`n📁 Creating directory: $remotePath" -ForegroundColor Yellow
    
    try {
        $uri = "ftp://$ftpHost/$remotePath"
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        
        $response = $request.GetResponse()
        Write-Host "  ✅ Directory created: $remotePath" -ForegroundColor Green
        $response.Close()
        return $true
    }
    catch {
        Write-Host "  ⚠️  Directory might already exist: $remotePath" -ForegroundColor Yellow
        return $false
    }
}

# Test connection first
Write-Host "🔌 Testing FTP connection..." -ForegroundColor Yellow
try {
    $testUri = "ftp://$ftpHost/"
    $testRequest = [System.Net.FtpWebRequest]::Create($testUri)
    $testRequest.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $testRequest.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
    $testRequest.Timeout = 15000
    
    $testResponse = $testRequest.GetResponse()
    Write-Host "✅ FTP connection successful!" -ForegroundColor Green
    $testResponse.Close()
}
catch {
    Write-Host "❌ FTP connection failed: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Create directories
Write-Host "`n📁 Creating remote directories..." -ForegroundColor Magenta
Force-Create-Directory "api"
Force-Create-Directory "api/middleware"
Force-Create-Directory "api/routes"
Force-Create-Directory "httpdocs"

# Upload files one by one
Write-Host "`n📤 Uploading files..." -ForegroundColor Magenta

$deployDir = ".\deploy-netcup-auto"
$uploads = @{
    "$deployDir\app.js" = "app.js"
    "$deployDir\package.json" = "package.json"
    "$deployDir\.env" = ".env"
    "$deployDir\api\db-switcher.js" = "api/db-switcher.js"
    "$deployDir\api\middleware\auth.js" = "api/middleware/auth.js"
    "$deployDir\api\routes\auth.js" = "api/routes/auth.js"
    "$deployDir\api\routes\game.js" = "api/routes/game.js"
    "$deployDir\httpdocs\index.html" = "httpdocs/index.html"
    "$deployDir\server-setup.sh" = "server-setup.sh"
}

$successCount = 0
$totalCount = $uploads.Count

foreach ($upload in $uploads.GetEnumerator()) {
    if (Force-Upload-File $upload.Key $upload.Value) {
        $successCount++
    }
    Start-Sleep -Milliseconds 500  # Small delay between uploads
}

Write-Host "`n📊 UPLOAD SUMMARY:" -ForegroundColor Green
Write-Host "✅ Successful: $successCount/$totalCount files" -ForegroundColor Green

if ($successCount -eq $totalCount) {
    Write-Host "`n🎉 ALL FILES UPLOADED SUCCESSFULLY!" -ForegroundColor Green
    Write-Host "🌐 Your app files are now on the Netcup server!" -ForegroundColor Cyan
    Write-Host "`n📋 NEXT STEPS:" -ForegroundColor Yellow
    Write-Host "1. SSH to your Netcup server" -ForegroundColor White
    Write-Host "2. Run: chmod +x server-setup.sh && ./server-setup.sh" -ForegroundColor White
    Write-Host "3. Or manually: npm install && node app.js" -ForegroundColor White
} else {
    Write-Host "`n⚠️  Some files failed to upload. Check the errors above." -ForegroundColor Yellow
}
