# PowerShell Native FTP Upload Script for Netcup
# Uses System.Net.FtpWebRequest for direct FTP operations

param(
    [string]$Action = "deploy"
)

# Load environment variables from .env.netcup
function Load-NetcupCredentials {
    $envFile = ".\.env.netcup"
    if (Test-Path $envFile) {
        Get-Content $envFile | ForEach-Object {
            if ($_ -match "^([^#][^=]+)=(.*)$") {
                $name = $matches[1].Trim()
                $value = $matches[2].Trim()
                [Environment]::SetEnvironmentVariable($name, $value, "Process")
            }
        }
        Write-Host "✅ Netcup credentials loaded successfully" -ForegroundColor Green
    } else {
        Write-Host "❌ .env.netcup file not found!" -ForegroundColor Red
        exit 1
    }
}

# Test FTP connection
function Test-FTPConnection {
    $ftpHost = [Environment]::GetEnvironmentVariable("NETCUP_FTP_HOST")
    $ftpUser = [Environment]::GetEnvironmentVariable("NETCUP_FTP_USER")
    $ftpPass = [Environment]::GetEnvironmentVariable("NETCUP_FTP_PASSWORD")
    
    Write-Host "🔄 Testing FTP connection to $ftpHost..." -ForegroundColor Yellow
    
    try {
        $ftpUri = "ftp://$ftpHost/"
        $request = [System.Net.FtpWebRequest]::Create($ftpUri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
        $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $request.UseBinary = $true
        $request.UsePassive = $true
        $request.KeepAlive = $false
        $request.Timeout = 30000
        
        $response = $request.GetResponse()
        $stream = $response.GetResponseStream()
        $reader = New-Object System.IO.StreamReader($stream)
        $listing = $reader.ReadToEnd()
        
        $reader.Close()
        $response.Close()
        
        Write-Host "✅ FTP connection successful!" -ForegroundColor Green
        Write-Host "📁 Directory listing:" -ForegroundColor Cyan
        $listing.Split("`n") | ForEach-Object { if ($_.Trim()) { Write-Host "   $_" } }
        return $true
    }
    catch {
        Write-Host "❌ FTP connection failed: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

# Upload single file via FTP
function Upload-FileToFTP {
    param(
        [string]$LocalFilePath,
        [string]$RemoteFilePath
    )
    
    $ftpHost = [Environment]::GetEnvironmentVariable("NETCUP_FTP_HOST")
    $ftpUser = [Environment]::GetEnvironmentVariable("NETCUP_FTP_USER")
    $ftpPass = [Environment]::GetEnvironmentVariable("NETCUP_FTP_PASSWORD")
    
    try {
        $ftpUri = "ftp://$ftpHost/$RemoteFilePath"
        $request = [System.Net.FtpWebRequest]::Create($ftpUri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $request.UseBinary = $true
        $request.UsePassive = $true
        $request.KeepAlive = $false
        
        # Read file content
        $fileBytes = [System.IO.File]::ReadAllBytes($LocalFilePath)
        $request.ContentLength = $fileBytes.Length
        
        # Upload file
        $requestStream = $request.GetRequestStream()
        $requestStream.Write($fileBytes, 0, $fileBytes.Length)
        $requestStream.Close()
        
        $response = $request.GetResponse()
        $response.Close()
        
        return $true
    }
    catch {
        Write-Host "❌ Failed to upload $LocalFilePath : $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

# Create directory on FTP server
function Create-FTPDirectory {
    param([string]$RemoteDirPath)
    
    $ftpHost = [Environment]::GetEnvironmentVariable("NETCUP_FTP_HOST")
    $ftpUser = [Environment]::GetEnvironmentVariable("NETCUP_FTP_USER")
    $ftpPass = [Environment]::GetEnvironmentVariable("NETCUP_FTP_PASSWORD")
    
    try {
        $ftpUri = "ftp://$ftpHost/$RemoteDirPath"
        $request = [System.Net.FtpWebRequest]::Create($ftpUri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        
        $response = $request.GetResponse()
        $response.Close()
        return $true
    }
    catch {
        # Directory might already exist
        return $false
    }
}

# Upload entire deployment package
function Deploy-ToNetcup {
    Write-Host "🚀 Starting deployment to Netcup..." -ForegroundColor Green
    
    $deployDir = ".\deploy-netcup-auto"
    if (-not (Test-Path $deployDir)) {
        Write-Host "❌ Deployment directory not found: $deployDir" -ForegroundColor Red
        return $false
    }
    
    # Test FTP connection first
    if (-not (Test-FTPConnection)) {
        return $false
    }
    
    Write-Host "📦 Uploading deployment package..." -ForegroundColor Yellow
    
    # Create remote directories
    Create-FTPDirectory "api" | Out-Null
    Create-FTPDirectory "api/middleware" | Out-Null
    Create-FTPDirectory "api/routes" | Out-Null
    Create-FTPDirectory "httpdocs" | Out-Null
    
    $uploadCount = 0
    $failCount = 0
    
    # Upload files
    $filesToUpload = @(
        @{ Local = "$deployDir\app.js"; Remote = "app.js" },
        @{ Local = "$deployDir\package.json"; Remote = "package.json" },
        @{ Local = "$deployDir\.env"; Remote = ".env" },
        @{ Local = "$deployDir\api\db-switcher.js"; Remote = "api/db-switcher.js" },
        @{ Local = "$deployDir\api\middleware\auth.js"; Remote = "api/middleware/auth.js" },
        @{ Local = "$deployDir\api\routes\auth.js"; Remote = "api/routes/auth.js" },
        @{ Local = "$deployDir\api\routes\game.js"; Remote = "api/routes/game.js" },
        @{ Local = "$deployDir\httpdocs\index.html"; Remote = "httpdocs/index.html" }
    )
    
    foreach ($file in $filesToUpload) {
        if (Test-Path $file.Local) {
            Write-Host "📤 Uploading: $($file.Remote)..." -ForegroundColor Cyan
            
            if (Upload-FileToFTP -LocalFilePath $file.Local -RemoteFilePath $file.Remote) {
                Write-Host "  ✅ Success" -ForegroundColor Green
                $uploadCount++
            } else {
                Write-Host "  ❌ Failed" -ForegroundColor Red
                $failCount++
            }
        } else {
            Write-Host "  ⚠️  File not found: $($file.Local)" -ForegroundColor Yellow
            $failCount++
        }
    }
    
    Write-Host "" -ForegroundColor White
    Write-Host "📊 Deployment Summary:" -ForegroundColor Green
    Write-Host "  ✅ Uploaded: $uploadCount files" -ForegroundColor Green
    Write-Host "  ❌ Failed: $failCount files" -ForegroundColor Red
    
    if ($failCount -eq 0) {
        Write-Host "🎉 Deployment completed successfully!" -ForegroundColor Green
        Write-Host "🌐 Your app should be available at: https://11seconds.de:3011" -ForegroundColor Cyan
        return $true
    } else {
        Write-Host "⚠️  Deployment completed with errors" -ForegroundColor Yellow
        return $false
    }
}

# Main script execution
Write-Host "🤖 AI Native FTP Deployment Script" -ForegroundColor Magenta
Write-Host "===================================" -ForegroundColor Magenta

# Load credentials
Load-NetcupCredentials

switch ($Action.ToLower()) {
    "test-ftp" {
        Test-FTPConnection
    }
    "deploy" {
        Deploy-ToNetcup
    }
    default {
        Write-Host "Available actions: test-ftp, deploy" -ForegroundColor Yellow
    }
}
