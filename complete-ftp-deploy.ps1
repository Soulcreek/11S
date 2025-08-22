# Complete FTP Deploy Script - Lädt alle React Build-Dateien hoch
$logFile = "complete-ftp-deploy.log"
$timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"

function Write-Log {
    param([string]$Message, [string]$Color = "White")
    $logMessage = "[$timestamp] $Message"
    Write-Host $logMessage -ForegroundColor $Color
    Add-Content -Path $logFile -Value $logMessage
}

Write-Log "🚀 Starting Complete FTP Deployment to Netcup" "Green"

# FTP Settings
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

# Upload directory recursively
function Upload-Directory {
    param([string]$LocalDir, [string]$RemoteDir)
    
    if (-not (Test-Path $LocalDir)) {
        Write-Log "⚠️ Directory not found: $LocalDir" "Yellow"
        return
    }
    
    # Create remote directory
    Create-Directory $RemoteDir
    
    # Upload all files in directory
    $files = Get-ChildItem -Path $LocalDir -File
    foreach ($file in $files) {
        $remotePath = "$RemoteDir/$($file.Name)"
        Upload-File -LocalPath $file.FullName -RemotePath $remotePath
    }
    
    # Recursively upload subdirectories
    $subdirs = Get-ChildItem -Path $LocalDir -Directory
    foreach ($subdir in $subdirs) {
        $remoteSubDir = "$RemoteDir/$($subdir.Name)"
        Upload-Directory -LocalDir $subdir.FullName -RemoteDir $remoteSubDir
    }
}

Write-Log "📦 Creating remote directories..." "Yellow"
Create-Directory "api"
Create-Directory "api/middleware" 
Create-Directory "api/routes"
Create-Directory "httpdocs"
Create-Directory "httpdocs/static"

Write-Log "📤 Uploading backend files..." "Yellow"

$deployPath = ".\deploy-netcup-auto"
$uploadStats = @{ Success = 0; Failed = 0 }

# Upload backend files
$backendFiles = @(
    @{ Local = "$deployPath\app.js"; Remote = "app.js" },
    @{ Local = "$deployPath\package.json"; Remote = "package.json" },
    @{ Local = "$deployPath\.env"; Remote = ".env" },
    @{ Local = "$deployPath\api\db-switcher.js"; Remote = "api/db-switcher.js" },
    @{ Local = "$deployPath\api\middleware\auth.js"; Remote = "api/middleware/auth.js" },
    @{ Local = "$deployPath\api\routes\auth.js"; Remote = "api/routes/auth.js" },
    @{ Local = "$deployPath\api\routes\game.js"; Remote = "api/routes/game.js" }
)

foreach ($file in $backendFiles) {
    if (Upload-File -LocalPath $file.Local -RemotePath $file.Remote) {
        $uploadStats.Success++
    } else {
        $uploadStats.Failed++
    }
}

Write-Log "📤 Uploading frontend files..." "Yellow"

# Upload all frontend files from web/build to httpdocs
$buildPath = ".\web\build"
if (Test-Path $buildPath) {
    # Upload root files
    $rootFiles = Get-ChildItem -Path $buildPath -File
    foreach ($file in $rootFiles) {
        $remotePath = "httpdocs/$($file.Name)"
        if (Upload-File -LocalPath $file.FullName -RemotePath $remotePath) {
            $uploadStats.Success++
        } else {
            $uploadStats.Failed++
        }
    }
    
    # Upload static directory recursively
    $staticPath = "$buildPath\static"
    if (Test-Path $staticPath) {
        Upload-Directory -LocalDir $staticPath -RemoteDir "httpdocs/static"
        $staticFiles = Get-ChildItem -Path $staticPath -Recurse -File
        $uploadStats.Success += $staticFiles.Count
    }
} else {
    Write-Log "❌ Build directory not found: $buildPath" "Red"
    Write-Log "Please run 'npm run build' in the web directory first!" "Yellow"
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

Write-Log "Full log saved to: $logFile" "White"
