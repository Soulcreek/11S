# Simple Complete FTP Deploy Script
$logFile = "deploy.log"

function Write-Log {
    param([string]$Message)
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] $Message"
    Write-Host $logMessage
    Add-Content -Path $logFile -Value $logMessage
}

Write-Log "Starting Complete FTP Deployment to Netcup"

# FTP Settings
$ftpHost = "ftp.11seconds.de"
$ftpUser = "hk302164_11s"
$ftpPass = "hallo.411S"

Write-Log "Connecting to: $ftpHost as $ftpUser"

# Upload function
function Upload-File {
    param([string]$LocalPath, [string]$RemotePath)
    
    try {
        if (-not (Test-Path $LocalPath)) {
            Write-Log "File not found: $LocalPath"
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
        
        Write-Log "Uploaded: $RemotePath"
        return $true
    }
    catch {
        Write-Log "Upload failed for $RemotePath : $($_.Exception.Message)"
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
        Write-Log "Created directory: $RemotePath"
    }
    catch {
        # Directory might already exist, ignore error
    }
}

Write-Log "Creating remote directories..."
Create-Directory "api"
Create-Directory "api/middleware" 
Create-Directory "api/routes"
Create-Directory "httpdocs"
Create-Directory "httpdocs/static"
Create-Directory "httpdocs/static/css"
Create-Directory "httpdocs/static/js"

Write-Log "Uploading backend files..."

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

Write-Log "Uploading frontend files..."

# Upload frontend files from deploy-netcup-auto/httpdocs
$httpdocsPath = "$deployPath\httpdocs"
if (Test-Path $httpdocsPath) {
    # Upload root files
    $rootFiles = Get-ChildItem -Path $httpdocsPath -File
    foreach ($file in $rootFiles) {
        $remotePath = "httpdocs/$($file.Name)"
        if (Upload-File -LocalPath $file.FullName -RemotePath $remotePath) {
            $uploadStats.Success++
        } else {
            $uploadStats.Failed++
        }
    }
    
    # Upload static/css files
    $cssPath = "$httpdocsPath\static\css"
    if (Test-Path $cssPath) {
        $cssFiles = Get-ChildItem -Path $cssPath -File
        foreach ($file in $cssFiles) {
            $remotePath = "httpdocs/static/css/$($file.Name)"
            if (Upload-File -LocalPath $file.FullName -RemotePath $remotePath) {
                $uploadStats.Success++
            } else {
                $uploadStats.Failed++
            }
        }
    }
    
    # Upload static/js files
    $jsPath = "$httpdocsPath\static\js"
    if (Test-Path $jsPath) {
        $jsFiles = Get-ChildItem -Path $jsPath -File
        foreach ($file in $jsFiles) {
            $remotePath = "httpdocs/static/js/$($file.Name)"
            if (Upload-File -LocalPath $file.FullName -RemotePath $remotePath) {
                $uploadStats.Success++
            } else {
                $uploadStats.Failed++
            }
        }
    }
} else {
    Write-Log "httpdocs directory not found: $httpdocsPath"
}

Write-Log "Deployment Complete!"
Write-Log "Successful uploads: $($uploadStats.Success)"
Write-Log "Failed uploads: $($uploadStats.Failed)"

if ($uploadStats.Failed -eq 0) {
    Write-Log "All files uploaded successfully!"
    Write-Log "App should be available at: https://11seconds.de:3011"
} else {
    Write-Log "Some files failed to upload. Check the log above."
}

Write-Log "Full log saved to: $logFile"
