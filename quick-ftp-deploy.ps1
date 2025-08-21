# Quick FTP Deploy Script with Logging
$logFile = "ftp-deploy.log"
$timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"

function Write-Log {
    param([string]$Message, [string]$Color = "White")
    $logMessage = "[$timestamp] $Message"
    Write-Host $logMessage -ForegroundColor $Color
    Add-Content -Path $logFile -Value $logMessage
}

Write-Log "🚀 Starting FTP Deployment to Netcup" "Green"

# FTP Settings from new credentials
$ftpHost = "ftp.11seconds.de"
$ftpUser = "hk302164_11s"
$ftpPass = "hallo.411S"

Write-Log "📡 Connecting to: $ftpHost as $ftpUser" "Yellow"

# Test connection first
try {
    $testUri = "ftp://$ftpHost/"
    $testRequest = [System.Net.FtpWebRequest]::Create($testUri)
    $testRequest.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $testRequest.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
    $testRequest.Timeout = 15000
    
    $testResponse = $testRequest.GetResponse()
    $testResponse.Close()
    
    Write-Log "✅ FTP Connection successful!" "Green"
}
catch {
    Write-Log "❌ FTP Connection failed: $($_.Exception.Message)" "Red"
    exit 1
}

# Upload function
function Upload-File {
    param([string]$LocalPath, [string]$RemotePath)
    
    try {
        if (-not (Test-Path $LocalPath)) {
            Write-Log "⚠️ File not found: $LocalPath" "Yellow"
            return $false
        }
        
        $uploadUri = "ftp://$ftpHost/$RemotePath"
        $uploadRequest = [System.Net.FtpWebRequest]::Create($uploadUri)
        $uploadRequest.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $uploadRequest.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $uploadRequest.UseBinary = $true
        
        $fileBytes = [System.IO.File]::ReadAllBytes($LocalPath)
        $uploadRequest.ContentLength = $fileBytes.Length
        
        $requestStream = $uploadRequest.GetRequestStream()
        $requestStream.Write($fileBytes, 0, $fileBytes.Length)
        $requestStream.Close()
        
        $uploadResponse = $uploadRequest.GetResponse()
        $uploadResponse.Close()
        
        Write-Log "✅ Uploaded: $RemotePath" "Green"
        return $true
    }
    catch {
        Write-Log "❌ Upload failed for $RemotePath : $($_.Exception.Message)" "Red"
        return $false
    }
}

# Create directory function
function Create-Directory {
    param([string]$RemotePath)
    try {
        $dirUri = "ftp://$ftpHost/$RemotePath"
        $dirRequest = [System.Net.FtpWebRequest]::Create($dirUri)
        $dirRequest.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $dirRequest.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        
        $dirResponse = $dirRequest.GetResponse()
        $dirResponse.Close()
        Write-Log "📁 Created directory: $RemotePath" "Cyan"
    }
    catch {
        # Directory might already exist, ignore error
    }
}

# Deploy files
Write-Log "📦 Creating remote directories..." "Yellow"
Create-Directory "api"
Create-Directory "api/middleware" 
Create-Directory "api/routes"
Create-Directory "httpdocs"

Write-Log "📤 Uploading files..." "Yellow"

$deployPath = ".\deploy-netcup-auto"
$uploadStats = @{ Success = 0; Failed = 0 }

# Upload each file
$files = @(
    @{ Local = "$deployPath\app.js"; Remote = "app.js" },
    @{ Local = "$deployPath\package.json"; Remote = "package.json" },
    @{ Local = "$deployPath\.env"; Remote = ".env" },
    @{ Local = "$deployPath\api\db-switcher.js"; Remote = "api/db-switcher.js" },
    @{ Local = "$deployPath\api\middleware\auth.js"; Remote = "api/middleware/auth.js" },
    @{ Local = "$deployPath\api\routes\auth.js"; Remote = "api/routes/auth.js" },
    @{ Local = "$deployPath\api\routes\game.js"; Remote = "api/routes/game.js" },
    @{ Local = "$deployPath\httpdocs\index.html"; Remote = "httpdocs/index.html" }
)

foreach ($file in $files) {
    if (Upload-File -LocalPath $file.Local -RemotePath $file.Remote) {
        $uploadStats.Success++
    } else {
        $uploadStats.Failed++
    }
}

Write-Log "📊 Deployment Complete!" "Green"
Write-Log "✅ Successful uploads: $($uploadStats.Success)" "Green"
Write-Log "❌ Failed uploads: $($uploadStats.Failed)" "Red"

if ($uploadStats.Failed -eq 0) {
    Write-Log "🎉 All files uploaded successfully!" "Green"
    Write-Log "🌐 App should be available at: https://11seconds.de:3011" "Cyan"
} else {
    Write-Log "⚠️ Some files failed to upload. Check the log above." "Yellow"
}

Write-Log "📝 Full log saved to: $logFile" "White"
