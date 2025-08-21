# Direct FTP Check Script - Überprüfe ob Dateien auf Netcup Server sind
Write-Host "🔍 Checking files on Netcup server..." -ForegroundColor Cyan

# FTP credentials from .env.netcup
$ftpHost = "ftp.11seconds.de"
$ftpUser = "hk302164_11s"
$ftpPass = "hallo.411S"

function List-FTPDirectory($remotePath = "") {
    try {
        $uri = "ftp://$ftpHost/$remotePath"
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectoryDetails
        $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $request.UseBinary = $true
        $request.UsePassive = $true
        
        $response = $request.GetResponse()
        $stream = $response.GetResponseStream()
        $reader = New-Object System.IO.StreamReader($stream)
        $listing = $reader.ReadToEnd()
        
        $reader.Close()
        $response.Close()
        
        return $listing
    }
    catch {
        Write-Host "❌ Error listing directory '$remotePath': $($_.Exception.Message)" -ForegroundColor Red
        return $null
    }
}

Write-Host "📡 Connecting to $ftpHost as $ftpUser..." -ForegroundColor Yellow

# Check root directory
Write-Host "`n📁 ROOT DIRECTORY:" -ForegroundColor Green
$rootListing = List-FTPDirectory ""
if ($rootListing) {
    $rootListing.Split("`n") | ForEach-Object { 
        if ($_.Trim()) { 
            Write-Host "  $_" -ForegroundColor White
        } 
    }
} else {
    Write-Host "  ❌ Could not list root directory" -ForegroundColor Red
}

# Check if our files exist
Write-Host "`n🔍 CHECKING FOR OUR FILES:" -ForegroundColor Green
$expectedFiles = @("app.js", "package.json", ".env", "api", "httpdocs")

foreach ($file in $expectedFiles) {
    try {
        $uri = "ftp://$ftpHost/$file"
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::GetFileSize
        $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        
        $response = $request.GetResponse()
        $size = $response.ContentLength
        $response.Close()
        
        Write-Host "  ✅ $file (${size} bytes)" -ForegroundColor Green
    }
    catch {
        Write-Host "  ❌ $file - NOT FOUND" -ForegroundColor Red
    }
}

# Check api directory
Write-Host "`n📁 API DIRECTORY:" -ForegroundColor Green
$apiListing = List-FTPDirectory "api"
if ($apiListing) {
    $apiListing.Split("`n") | ForEach-Object { 
        if ($_.Trim()) { 
            Write-Host "  $_" -ForegroundColor White
        } 
    }
} else {
    Write-Host "  ❌ API directory not found or empty" -ForegroundColor Red
}

# Check httpdocs directory
Write-Host "`n📁 HTTPDOCS DIRECTORY:" -ForegroundColor Green
$httpdocsListing = List-FTPDirectory "httpdocs"
if ($httpdocsListing) {
    $httpdocsListing.Split("`n") | ForEach-Object { 
        if ($_.Trim()) { 
            Write-Host "  $_" -ForegroundColor White
        } 
    }
} else {
    Write-Host "  ❌ HTTPDOCS directory not found or empty" -ForegroundColor Red
}

Write-Host "`n📊 ANALYSIS:" -ForegroundColor Magenta
Write-Host "If you don't see our files above, the FTP upload failed." -ForegroundColor Yellow
Write-Host "Let's try uploading again..." -ForegroundColor Yellow
