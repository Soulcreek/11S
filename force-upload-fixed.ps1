# Robustes FTP Upload mit .NET FtpWebRequest
Write-Host "ROBUST FTP UPLOAD TO NETCUP" -ForegroundColor Green
Write-Host "===========================" -ForegroundColor Green

# Load environment variables from .env.netcup
if (Test-Path ".env.netcup") {
    Write-Host "Loading .env.netcup configuration..." -ForegroundColor Yellow
    Get-Content ".env.netcup" | Where-Object { $_ -match "^[^#].*=" } | ForEach-Object {
        $parts = $_ -split "=", 2
        if ($parts.Length -eq 2) {
            $name = $parts[0].Trim()
            $value = $parts[1].Trim().Trim('"')
            Set-Variable -Name $name -Value $value
        }
    }
} else {
    Write-Host "ERROR: .env.netcup file not found!" -ForegroundColor Red
    exit 1
}

$ftpHost = $NETCUP_FTP_HOST -replace "ftp://", ""
$ftpUser = $NETCUP_FTP_USER
$ftpPass = $NETCUP_FTP_PASSWORD

Write-Host "FTP Host: $ftpHost" -ForegroundColor Cyan
Write-Host "FTP User: $ftpUser" -ForegroundColor Cyan

function Force-Upload-File($localFile, $remotePath) {
    Write-Host "Uploading: $localFile -> $remotePath" -ForegroundColor Cyan
    
    if (-not (Test-Path $localFile)) {
        Write-Host "  ERROR: Local file not found: $localFile" -ForegroundColor Red
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
        
        $requestStream = $request.GetRequestStream()
        $requestStream.Write($fileContent, 0, $fileContent.Length)
        $requestStream.Close()
        
        # Get response
        $response = $request.GetResponse()
        Write-Host "  SUCCESS - $($fileContent.Length) bytes - $($response.StatusDescription)" -ForegroundColor Green
        $response.Close()
        
        return $true
    }
    catch {
        Write-Host "  FAILED: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

function Force-Create-Directory($remotePath) {
    Write-Host "Creating directory: $remotePath" -ForegroundColor Yellow
    
    try {
        $uri = "ftp://$ftpHost/$remotePath"
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $request.Timeout = 15000
        
        $response = $request.GetResponse()
        Write-Host "  Directory created: $remotePath" -ForegroundColor Green
        $response.Close()
        return $true
    }
    catch {
        Write-Host "  Directory might already exist: $remotePath" -ForegroundColor Yellow
        return $false
    }
}

# Test connection first
Write-Host "Testing FTP connection..." -ForegroundColor Yellow
try {
    $testUri = "ftp://$ftpHost/"
    $testRequest = [System.Net.FtpWebRequest]::Create($testUri)
    $testRequest.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $testRequest.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
    $testRequest.Timeout = 15000
    
    $testResponse = $testRequest.GetResponse()
    Write-Host "FTP connection successful!" -ForegroundColor Green
    $testResponse.Close()
}
catch {
    Write-Host "FTP connection failed: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Create directories
Write-Host "`nCreating remote directories..." -ForegroundColor Magenta
Force-Create-Directory "api"
Force-Create-Directory "api/middleware"
Force-Create-Directory "api/routes" 
Force-Create-Directory "api/data"
Force-Create-Directory "httpdocs"
Force-Create-Directory "httpdocs/static"
Force-Create-Directory "httpdocs/static/css"
Force-Create-Directory "httpdocs/static/js"

# Check if build exists
if (!(Test-Path "web/build")) {
    Write-Host "ERROR: web/build directory not found! The React build is missing." -ForegroundColor Red
    exit 1
}

Write-Host "`nUploading files..." -ForegroundColor Magenta

# Define files to upload
$uploads = @{
    # Backend files
    "app.js" = "app.js"
    "package.json" = "package.json"
    ".env.production" = ".env"
    
    # API files
    "api/db.js" = "api/db.js"
    "api/db-switcher.js" = "api/db-switcher.js"
    "api/routes/auth.js" = "api/routes/auth.js"
    "api/routes/game.js" = "api/routes/game.js"
    "api/routes/admin.js" = "api/routes/admin.js"
    "api/middleware/auth.js" = "api/middleware/auth.js"
    "api/middleware/admin.js" = "api/middleware/admin.js"
    "api/data/insert_extra_questions.sql" = "api/data/insert_extra_questions.sql"
    
    # React build files (in httpdocs for web access)
    "web/build/index.html" = "httpdocs/index.html"
    "web/build/manifest.json" = "httpdocs/manifest.json"
    "web/build/robots.txt" = "httpdocs/robots.txt"
    "web/build/favicon.ico" = "httpdocs/favicon.ico"
}

# Upload CSS files
Get-ChildItem "web/build/static/css/*.css" | ForEach-Object {
    $uploads[$_.FullName] = "httpdocs/static/css/$($_.Name)"
}

# Upload JS files  
Get-ChildItem "web/build/static/js/*.js" | ForEach-Object {
    $uploads[$_.FullName] = "httpdocs/static/js/$($_.Name)"
}

$successCount = 0
$totalCount = $uploads.Count

foreach ($upload in $uploads.GetEnumerator()) {
    if (Force-Upload-File $upload.Key $upload.Value) {
        $successCount++
    }
    Start-Sleep -Milliseconds 200  # Small delay between uploads
}

Write-Host "`nUPLOAD SUMMARY:" -ForegroundColor Green
Write-Host "Successful: $successCount/$totalCount files" -ForegroundColor Green

if ($successCount -eq $totalCount) {
    Write-Host "`nALL FILES UPLOADED SUCCESSFULLY!" -ForegroundColor Green
    Write-Host "Backend and React build are now on the server!" -ForegroundColor Cyan
    Write-Host "`nNEXT STEPS:" -ForegroundColor Yellow
    Write-Host "1. SSH to hosting223936@hosting223936.ae94b.netcup.net" -ForegroundColor White
    Write-Host "2. Password: hallo.4Netcup" -ForegroundColor White
    Write-Host "3. Run: cd 11seconds.de" -ForegroundColor White
    Write-Host "4. Run: npm install --production" -ForegroundColor White 
    Write-Host "5. Run: pkill -f 'node app.js' || true" -ForegroundColor White
    Write-Host "6. Run: nohup node app.js > app.log 2>&1 &" -ForegroundColor White
    Write-Host "7. Check: ps aux | grep node" -ForegroundColor White
} else {
    Write-Host "`nSome files failed to upload. Check the errors above." -ForegroundColor Yellow
}
