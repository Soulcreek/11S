# 11Seconds Deployment Script - Zentrales Deployment System
# Version: 2.0.0
# Datum: 2025-08-23

param(
    [Parameter(Mandatory=$false)]
    [ValidateSet("web", "api", "all")]
    [string]$Component = "all",
    
    [Parameter(Mandatory=$false)]
    [switch]$BuildOnly,
    
    [Parameter(Mandatory=$false)]
    [switch]$UploadOnly,
    
    [Parameter(Mandatory=$false)]
    [switch]$Force
)

# =====================================================
# DEPLOYMENT KONFIGURATION - BASIEREND AUF NODE.JS CONFIG
# =====================================================
# Node.js-Version: 22.18.0
# Package Manager: npm
# Dokumentenstamm: /11seconds.de/httpdocs
# Anwendungsstamm: /11seconds.de
# Anwendungsstartdatei: app.js
# URL: http://11seconds.de

$Config = @{
    # Lokale Pfade
    LocalPaths = @{
        Root = "c:\Users\Marcel\Documents\GitHub\11S"
        WebSource = "c:\Users\Marcel\Documents\GitHub\11S\web"
    # Use CRA default build output directory
    WebBuild = "c:\Users\Marcel\Documents\GitHub\11S\web\build"
        ApiSource = "c:\Users\Marcel\Documents\GitHub\11S\api"
        LocalDeploy = "c:\Users\Marcel\Documents\GitHub\11S\httpdocs"
    }
    
    # Remote Server Struktur (EXAKT nach Node.js Konfiguration)
    RemotePaths = @{
        AppRoot = "/11seconds.de"                    # Anwendungsstamm
        DocumentRoot = "/11seconds.de/httpdocs"      # Dokumentenstamm - hier kommen React Build Files hin
        ApiFiles = "/11seconds.de"                   # API Files (app.js etc.) in Anwendungsstamm
        StaticFiles = "/11seconds.de/httpdocs/static" # Static Assets (JS/CSS)
    }
    
    # Build Konfiguration
    Build = @{
        NodeVersion = "22.18.0"
        PackageManager = "npm"
        ProductionMode = $true
    }
}

# =====================================================
# HILFSFUNKTIONEN
# =====================================================

function Write-DeployLog {
    param([string]$Message, [string]$Level = "INFO")
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $color = switch ($Level) {
        "ERROR" { "Red" }
        "WARN" { "Yellow" }
        "SUCCESS" { "Green" }
        default { "White" }
    }
    Write-Host "[$timestamp] [$Level] $Message" -ForegroundColor $color
}

function Test-Prerequisites {
    Write-DeployLog "Checking prerequisites..."
    
    # Check Node.js
    try {
        $nodeVersion = node --version
        Write-DeployLog "Node.js version: $nodeVersion"
        if (-not $nodeVersion.StartsWith("v22")) {
            Write-DeployLog "Warning: Expected Node.js v22.x, found $nodeVersion" -Level "WARN"
        }
    } catch {
        Write-DeployLog "Node.js not found! Please install Node.js 22.18.0" -Level "ERROR"
        return $false
    }
    
    # Check npm
    try {
        $npmVersion = npm --version
        Write-DeployLog "npm version: $npmVersion"
    } catch {
        Write-DeployLog "npm not found!" -Level "ERROR"
        return $false
    }
    
    # Check paths
    if (-not (Test-Path $Config.LocalPaths.Root)) {
        Write-DeployLog "Project root not found: $($Config.LocalPaths.Root)" -Level "ERROR"
        return $false
    }
    
    return $true
}

function Build-WebApplication {
    Write-DeployLog "Building web application..."
    
    $webPath = $Config.LocalPaths.WebSource
    if (-not (Test-Path $webPath)) {
        Write-DeployLog "Web source directory not found: $webPath" -Level "ERROR"
        return $false
    }
    
    Push-Location $webPath
    try {
        # Clean previous build
        if (Test-Path "httpdocs") {
            Remove-Item -Recurse -Force "httpdocs"
            Write-DeployLog "Cleaned previous build"
        }
        
        # Install dependencies if needed
        if (-not (Test-Path "node_modules") -or $Force) {
            Write-DeployLog "Installing dependencies..."
            npm install
            if ($LASTEXITCODE -ne 0) {
                throw "npm install failed"
            }
        }
        
        # Build application
        Write-DeployLog "Building React application..."
        $env:NODE_ENV = "production"
        npm run build
        if ($LASTEXITCODE -ne 0) {
            throw "Build failed"
        }
        
        # Verify build output
        $buildPath = Join-Path $webPath "httpdocs"
        if (-not (Test-Path $buildPath)) {
            throw "Build output not found at $buildPath"
        }
        
        $indexFile = Join-Path $buildPath "index.html"
        if (-not (Test-Path $indexFile)) {
            throw "index.html not found in build output"
        }
        
        # Show build stats
        $buildSize = (Get-ChildItem -Recurse $buildPath | Measure-Object -Property Length -Sum).Sum / 1MB
        Write-DeployLog "Build size: $([math]::Round($buildSize, 2)) MB"
        
        Write-DeployLog "Web application built successfully" -Level "SUCCESS"
        return $true
        
    } catch {
        Write-DeployLog "Build failed: $($_.Exception.Message)" -Level "ERROR"
        return $false
    } finally {
        Pop-Location
    }
}

function Deploy-LocalFiles {
    Write-DeployLog "Deploying files locally..."
    
    $sourcePath = $Config.LocalPaths.WebBuild
    $targetPath = $Config.LocalPaths.LocalDeploy
    
    if (-not (Test-Path $sourcePath)) {
        Write-DeployLog "Source build not found: $sourcePath" -Level "ERROR"
        return $false
    }
    
    try {
        # Clean target directory
        if (Test-Path $targetPath) {
            Remove-Item -Recurse -Force "$targetPath\*"
            Write-DeployLog "Cleaned local deployment directory"
        } else {
            New-Item -ItemType Directory -Path $targetPath -Force | Out-Null
        }
        
        # Copy files
        Write-DeployLog "Copying build files to local deployment directory..."
        Copy-Item -Recurse -Force "$sourcePath\*" $targetPath
        
        # Update Service Worker with new cache version to force browser refresh
        $swFile = Join-Path $targetPath "sw.js"
        if (Test-Path $swFile) {
            $timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
            (Get-Content $swFile) -replace "11seconds-v[\d\.\-\w]+", "11seconds-v2.3.0-$timestamp" | Set-Content $swFile
            Write-DeployLog "Updated Service Worker cache version to force browser refresh"
        }
        
        # Create deployment manifest
        $manifest = @{
            DeploymentTime = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
            Component = $Component
            BuildHash = (Get-FileHash -Path (Join-Path $targetPath "index.html")).Hash.Substring(0,8)
            Files = (Get-ChildItem -Recurse $targetPath | Measure-Object).Count
        }
        $manifest | ConvertTo-Json | Out-File -FilePath (Join-Path $targetPath "deployment-manifest.json")
        
        Write-DeployLog "Local deployment completed successfully" -Level "SUCCESS"
        return $true
        
    } catch {
        Write-DeployLog "Local deployment failed: $($_.Exception.Message)" -Level "ERROR"
        return $false
    }
}

function Test-Deployment {
    Write-DeployLog "Testing deployment..."
    
    # Check local files
    $requiredFiles = @("index.html", "static", "manifest.json", "sw.js")
    $deployPath = $Config.LocalPaths.LocalDeploy
    
    foreach ($file in $requiredFiles) {
        $filePath = Join-Path $deployPath $file
        if (-not (Test-Path $filePath)) {
            Write-DeployLog "Required file missing: $file" -Level "ERROR"
            return $false
        }
    }
    
    # Check for main JS and CSS files
    $staticPath = Join-Path $deployPath "static"
    $jsFiles = Get-ChildItem -Path (Join-Path $staticPath "js") -Filter "main.*.js" -ErrorAction SilentlyContinue
    $cssFiles = Get-ChildItem -Path (Join-Path $staticPath "css") -Filter "main.*.css" -ErrorAction SilentlyContinue
    
    if ($jsFiles.Count -eq 0) {
        Write-DeployLog "No main JS file found in build" -Level "ERROR"
        return $false
    }
    
    if ($cssFiles.Count -eq 0) {
        Write-DeployLog "No main CSS file found in build" -Level "ERROR"
        return $false
    }
    
    Write-DeployLog "Found JS: $($jsFiles[0].Name), CSS: $($cssFiles[0].Name)"
    Write-DeployLog "Deployment test passed" -Level "SUCCESS"
    return $true
}

function Show-UploadInstructions {
    Write-DeployLog "=== UPLOAD INSTRUCTIONS ===" -Level "SUCCESS"
    Write-DeployLog "Your files are ready for upload in: $($Config.LocalPaths.LocalDeploy)" -Level "SUCCESS"
    Write-DeployLog ""
    Write-DeployLog "MAPPING FOR NODE.JS HOSTING:" -Level "SUCCESS"
    Write-DeployLog "Local Path -> Remote Path (Node.js Config)" -Level "SUCCESS"
    Write-DeployLog "httpdocs/* -> /11seconds.de/httpdocs/ (Dokumentenstamm)" -Level "SUCCESS"
    Write-DeployLog "api/* -> /11seconds.de/ (Anwendungsstamm - für app.js)" -Level "SUCCESS"
    Write-DeployLog ""
    Write-DeployLog "CRITICAL: Upload ALL contents of httpdocs folder to /11seconds.de/httpdocs/" -Level "WARN"
    Write-DeployLog "This includes: index.html, static/js/, static/css/, manifest.json, sw.js" -Level "WARN"
    Write-DeployLog ""
    Write-DeployLog "After upload, test at: http://11seconds.de" -Level "SUCCESS"
}

# =====================================================
# HAUPTLOGIK
# =====================================================

# Color functions for better output
function Write-Success { param($msg) Write-Host "[SUCCESS] $msg" -ForegroundColor Green }
function Write-Error { param($msg) Write-Host "[ERROR] $msg" -ForegroundColor Red }
function Write-Warning { param($msg) Write-Host "[WARNING] $msg" -ForegroundColor Yellow }
function Write-Info { param($msg) Write-Host "[INFO] $msg" -ForegroundColor Cyan }
function Write-Step { param($msg) Write-Host "[STEP] $msg" -ForegroundColor Magenta }

# Load deployment configuration
function Load-DeployConfig {
    $configFile = Join-Path $PSScriptRoot $ConfigFile
    if (-not (Test-Path $configFile)) {
        Write-Error "Deployment config not found: $configFile"
        Write-Info "Copy deployment-config-template.env to deployment-config.env and configure your settings"
        return $false
    }
    
    Get-Content $configFile | ForEach-Object {
        if ($_ -match "^([^#][^=]+)=(.*)$") {
            $name = $matches[1].Trim()
            $value = $matches[2].Trim().Trim('"')
            [Environment]::SetEnvironmentVariable($name, $value, "Process")
            if ($ShowDetails) { Write-Host "  $name = $value" -ForegroundColor DarkGray }
        }
    }
    
    Write-Success "Deployment configuration loaded from $configFile"
    return $true
}

# Build React app
function Build-ReactApp {
    Write-Step "Building React application..."
    
    if (-not (Test-Path "web")) {
        Write-Error "Web directory not found"
        return $false
    }
    
    Push-Location "web"
    try {
        # Set a build-time timestamp for the banner
        $env:REACT_APP_BUILD_TIME = (Get-Date).ToString('yyyy-MM-dd HH:mm:ss')
        Write-Info "REACT_APP_BUILD_TIME=$($env:REACT_APP_BUILD_TIME)"
        Write-Info "Running npm run build..."
        npm run build
        if ($LASTEXITCODE -ne 0) {
            Write-Error "React build failed"
            return $false
        }
        
        if (-not (Test-Path "build")) {
            Write-Error "Build directory not created"
            return $false
        }
        
        $buildFiles = Get-ChildItem "build" -Recurse -File | Measure-Object
        Write-Success "React build completed - $($buildFiles.Count) files generated"
        return $true
    }
    finally {
        Pop-Location
    }
}

# Test FTP connection
function Test-FTPConnection {
    $ftpHost = [Environment]::GetEnvironmentVariable("FTP_HOST")
    $user = [Environment]::GetEnvironmentVariable("FTP_USER")
    $pass = [Environment]::GetEnvironmentVariable("FTP_PASSWORD")
    
    Write-Step "Testing FTP connection to $ftpHost..."
    
    try {
        $uri = "ftp://$ftpHost/"
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
        $request.Credentials = New-Object System.Net.NetworkCredential($user, $pass)
        $request.Timeout = 10000
        
        $response = $request.GetResponse()
        $response.Close()
        
        Write-Success "FTP connection successful"
        return $true
    }
    catch {
        Write-Error "FTP connection failed: $($_.Exception.Message)"
        return $false
    }
}

# Upload file via FTP
function Upload-FileViaFTP {
    param([string]$LocalPath, [string]$RemotePath)
    
    $ftpHost = [Environment]::GetEnvironmentVariable("FTP_HOST")
    $user = [Environment]::GetEnvironmentVariable("FTP_USER")
    $pass = [Environment]::GetEnvironmentVariable("FTP_PASSWORD")
    
    try {
        if (-not (Test-Path $LocalPath)) {
            Write-Warning "File not found: $LocalPath"
            return $false
        }
        
        $uri = "ftp://$ftpHost/$RemotePath"
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $request.Credentials = New-Object System.Net.NetworkCredential($user, $pass)
        $request.UseBinary = $true
        $request.Timeout = 30000
        
        $fileBytes = [System.IO.File]::ReadAllBytes($LocalPath)
        $request.ContentLength = $fileBytes.Length
        
        $stream = $request.GetRequestStream()
        $stream.Write($fileBytes, 0, $fileBytes.Length)
        $stream.Close()
        
        $response = $request.GetResponse()
        $response.Close()
        
        if ($ShowDetails) { Write-Host "  [OK] $RemotePath" -ForegroundColor Green }
        return $true
    }
    catch {
        Write-Host "  [FAIL] $RemotePath - $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

# Create FTP directory
function Create-FTPDirectory {
    param([string]$RemotePath)
    
    $ftpHost = [Environment]::GetEnvironmentVariable("FTP_HOST")
    $user = [Environment]::GetEnvironmentVariable("FTP_USER")
    $pass = [Environment]::GetEnvironmentVariable("FTP_PASSWORD")
    
    try {
        $uri = "ftp://$ftpHost/$RemotePath"
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $request.Credentials = New-Object System.Net.NetworkCredential($user, $pass)
        $request.Timeout = 15000
        
        $response = $request.GetResponse()
        $response.Close()
        
        if ($ShowDetails) { Write-Host "  [DIR] Created: $RemotePath" -ForegroundColor Cyan }
        return $true
    }
    catch {
        # Directory might already exist - ignore error
        return $true
    }
}

# Deploy via FTP (frontend + optional backend)
function Deploy-ViaFTP {
    # Use resolved BUILD_PATH if present in process env, otherwise expect Start-Deployment to set $global:ResolvedBuildPath
    $buildPath = [Environment]::GetEnvironmentVariable("BUILD_PATH")
    if (-not $buildPath -and (Get-Variable -Name ResolvedBuildPath -Scope Global -ErrorAction SilentlyContinue)) {
        $buildPath = $global:ResolvedBuildPath
    }
    $remotePath = [Environment]::GetEnvironmentVariable("FTP_REMOTE_PATH")
    $uploadBackend = [Environment]::GetEnvironmentVariable("FTP_UPLOAD_BACKEND")
    if (-not $uploadBackend) { $uploadBackend = "true" }
    
    if (-not (Test-Path $buildPath)) {
    Write-Error "Build directory not found: $buildPath"
        return $false
    }
    
    Write-Step "Deploying via FTP..."
    
    $stats = @{ Success = 0; Failed = 0 }
    
    # Create remote directory structure
    Create-FTPDirectory "$remotePath"
    Create-FTPDirectory "$remotePath/static"
    Create-FTPDirectory "$remotePath/static/css"
    Create-FTPDirectory "$remotePath/static/js"
    Create-FTPDirectory "$remotePath/static/media"
    Create-FTPDirectory "$remotePath/admin"
    Create-FTPDirectory "$remotePath/admin/data"
    Create-FTPDirectory "$remotePath/admin/includes"
    Create-FTPDirectory "$remotePath/admin/uploads"
    
    # Upload all files recursively
    function Upload-Directory($localDir, $remoteDir) {
        $files = Get-ChildItem -Path $localDir -File
        foreach ($file in $files) {
            $remoteFilePath = "$remoteDir/$($file.Name)"
            if (Upload-FileViaFTP -LocalPath $file.FullName -RemotePath $remoteFilePath) {
                $stats.Success++
            }
            else {
                $stats.Failed++
            }
        }
        
        $subdirs = Get-ChildItem -Path $localDir -Directory
        foreach ($subdir in $subdirs) {
            $remoteSubDir = "$remoteDir/$($subdir.Name)"
            Create-FTPDirectory $remoteSubDir
            Upload-Directory -localDir $subdir.FullName -remoteDir $remoteSubDir
        }
    }
    
    # 1) Upload frontend build to httpdocs
    Upload-Directory -localDir $buildPath -remoteDir $remotePath

    # 1.2) Auto-replace index.html with working-app.html if it exists
    $workingAppPath = Join-Path $buildPath "working-app.html"
    if (Test-Path $workingAppPath) {
        Write-Step "Replacing index.html with working-app.html..."
        $tempIndexPath = Join-Path $buildPath "index.html.backup"
        $originalIndexPath = Join-Path $buildPath "index.html"
        
        # Backup original index.html
        if (Test-Path $originalIndexPath) {
            Copy-Item $originalIndexPath $tempIndexPath -Force
        }
        
        # Copy working-app.html to index.html
        Copy-Item $workingAppPath $originalIndexPath -Force
        
        # Upload the new index.html
        if (Upload-FileViaFTP -LocalPath $originalIndexPath -RemotePath "$remotePath/index.html") {
            $stats.Success++
            Write-Success "Successfully replaced index.html with working-app.html"
        }
        else {
            $stats.Failed++
            Write-Error "Failed to upload new index.html"
        }
        
        # Restore original index.html
        if (Test-Path $tempIndexPath) {
            Copy-Item $tempIndexPath $originalIndexPath -Force
            Remove-Item $tempIndexPath -Force
        }
    }
    else {
        Write-Info "working-app.html not found - using default index.html"
    }

    # 1.5) Upload admin center to httpdocs/admin
    $adminPath = [Environment]::GetEnvironmentVariable("ADMIN_PATH")
    if (-not $adminPath) { $adminPath = "../admin" }
    
    if (Test-Path $adminPath) {
        Write-Step "Uploading admin center via FTP..."
        Upload-Directory -localDir (Resolve-Path $adminPath).Path -remoteDir "$remotePath/admin"
        
        # Ensure admin data directory has proper permissions (informational)
        Write-Info "Ensure the following directory has write permissions on your server:"
        Write-Info "  $remotePath/admin/data/ (PHP must be able to create/modify JSON files)"
        
        Write-Success "Admin center uploaded successfully"
    }
    else {
        Write-Warning "Admin directory not found: $adminPath - skipping admin center upload"
    }

    # 2) Optionally upload backend files to /11seconds.de root (sibling of httpdocs)
    if ($uploadBackend -eq "true") {
        $rootRemote = "/"  # Netcup FTP root defaults to domain root; httpdocs is already handled above
        Write-Step "Uploading backend files to root via FTP..."
        Create-FTPDirectory "$rootRemote/api"
        $backendItems = @("app.js", "package.json", "package-lock.json")
        foreach ($item in $backendItems) {
            if (Test-Path $item) {
                if (Upload-FileViaFTP -LocalPath $item -RemotePath "$rootRemote/$item") { $stats.Success++ } else { $stats.Failed++ }
            }
            else {
                if ($ShowDetails) { Write-Host "  [SKIP] $item missing locally" -ForegroundColor DarkGray }
            }
        }
        if (Test-Path "api") {
            # upload api folder recursively
            function Upload-ApiDir($localDir, $remoteDir) {
                Create-FTPDirectory $remoteDir
                $files = Get-ChildItem -Path $localDir -File
                foreach ($file in $files) {
                    $remoteFilePath = "$remoteDir/$($file.Name)"
                    if (Upload-FileViaFTP -LocalPath $file.FullName -RemotePath $remoteFilePath) { $stats.Success++ } else { $stats.Failed++ }
                }
                $subdirs = Get-ChildItem -Path $localDir -Directory
                foreach ($subdir in $subdirs) {
                    Upload-ApiDir -localDir $subdir.FullName -remoteDir "$remoteDir/$($subdir.Name)"
                }
            }
            Upload-ApiDir -localDir (Resolve-Path "api").Path -remoteDir "$rootRemote/api"
        }
        else {
            if ($ShowDetails) { Write-Host "  [SKIP] api directory missing locally" -ForegroundColor DarkGray }
        }
    }
    else {
        Write-Info "Skipping backend upload - Static deployment only (FTP_UPLOAD_BACKEND=$uploadBackend)"
    }
    
    Write-Success "FTP deployment completed"
    Write-Info "✅ Successful uploads: $($stats.Success)"
    Write-Info "Failed uploads: $($stats.Failed)"
    
    return $stats.Failed -eq 0
}

# Test SSH connection
function Test-SSHConnection {
    $sshHost = [Environment]::GetEnvironmentVariable("SSH_HOST")
    $user = [Environment]::GetEnvironmentVariable("SSH_USER")
    
    Write-Step "Testing SSH connection to $sshHost..."
    Write-Warning "SSH deployment requires manual password entry"
    
    try {
        $sshTarget = "$user@$sshHost"
        $result = ssh -o ConnectTimeout=10 -o BatchMode=yes $sshTarget "echo 'SSH connection test successful'" 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Success "SSH connection successful"
            return $true
        }
        else {
            Write-Error "SSH connection failed: $result"
            return $false
        }
    }
    catch {
        Write-Error "SSH test failed: $($_.Exception.Message)"
        return $false
    }
}

# Deploy via SSH
function Deploy-ViaSSH {
    $buildPath = [Environment]::GetEnvironmentVariable("BUILD_PATH")
    if (-not $buildPath -and (Get-Variable -Name ResolvedBuildPath -Scope Global -ErrorAction SilentlyContinue)) {
        $buildPath = $global:ResolvedBuildPath
    }
    $sshHost = [Environment]::GetEnvironmentVariable("SSH_HOST")
    $user = [Environment]::GetEnvironmentVariable("SSH_USER")
    $remotePath = [Environment]::GetEnvironmentVariable("SSH_REMOTE_PATH")
    
    Write-Step "Deploying via SSH/SCP..."
    Write-Warning "You will be prompted for SSH password"
    
    try {
        # Use scp to copy entire build directory
        Write-Info "Copying files via SCP..."
        $scpTarget = "$user@$sshHost" + ":" + $remotePath + "/"
        $result = scp -r "$buildPath/*" $scpTarget 2>&1
        
        if ($LASTEXITCODE -eq 0) {
            Write-Success "SSH deployment completed"
            return $true
        }
        else {
            Write-Error "SCP failed: $result"
            return $false
        }
    }
    catch {
        Write-Error "SSH deployment failed: $($_.Exception.Message)"
        return $false
    }
}

# Main deployment function
function Start-Deployment {
    Write-Host "`n11SECONDS STATIC DEPLOYMENT" -ForegroundColor Magenta
    Write-Host "===============================" -ForegroundColor Magenta
    
    # Load configuration
    if (-not (Load-DeployConfig)) {
        return $false
    }
    
    # Build if requested
    if ($Build) {
        if (-not (Build-ReactApp)) {
            Write-Error "Build failed - aborting deployment"
            return $false
        }
    }

    # Resolve BUILD_PATH intelligently: prefer web/build, then web/httpdocs, then repo/httpdocs
    function Resolve-BuildPath {
        $envPath = [Environment]::GetEnvironmentVariable("BUILD_PATH")
        if ($envPath -and (Test-Path $envPath)) { return (Resolve-Path $envPath).Path }

        $candidates = @("web\build", "web\httpdocs", "httpdocs")
        foreach ($cand in $candidates) {
            if (Test-Path (Join-Path $PSScriptRoot "..\$cand")) {
                return (Resolve-Path (Join-Path $PSScriptRoot "..\$cand")).Path
            }
            if (Test-Path $cand) { return (Resolve-Path $cand).Path }
        }

        return $null
    }

    $buildPath = Resolve-BuildPath
    if ($buildPath) {
        $global:ResolvedBuildPath = $buildPath
    }
    Write-Info "Resolved BUILD_PATH = $buildPath"
    if (-not $buildPath) {
        Write-Error "Build directory not found. Checked BUILD_PATH env and common locations (web/build, web/httpdocs, httpdocs)."
        Write-Info "Run with -Build flag or manually run 'npm run build' in web directory"
        return $false
    }
    
    # Test mode - just check connections
    if ($Test) {
        Write-Step "Testing deployment connections..."
        $ftpOk = Test-FTPConnection
        $sshOk = Test-SSHConnection
        
        if ($ftpOk -and $sshOk) {
            Write-Success "All deployment methods available"
        }
        elseif ($ftpOk) {
            Write-Warning "Only FTP deployment available"
        }
        elseif ($sshOk) {
            Write-Warning "Only SSH deployment available"
        }
        else {
            Write-Error "No deployment methods available"
            return $false
        }
        return $true
    }
    
    # Deploy based on method
    $success = $false
    switch ($Method.ToLower()) {
        "ftp" {
            $success = Deploy-ViaFTP
        }
        "ssh" {
            $success = Deploy-ViaSSH
        }
        "auto" {
            Write-Step "Auto-selecting deployment method..."
            if (Test-FTPConnection) {
                Write-Info "Using FTP deployment (primary)"
                $success = Deploy-ViaFTP
            }
            elseif (Test-SSHConnection) {
                Write-Info "Using SSH deployment (fallback)"
                $success = Deploy-ViaSSH
            }
            else {
                Write-Error "No deployment methods available"
                return $false
            }
        }
    }
    
    if ($success) {
        $domain = [Environment]::GetEnvironmentVariable("DOMAIN_URL")
        Write-Success "Deployment completed successfully!"
        Write-Info "Your app should be available at: $domain"
        
        # Post-deployment verification info
        Write-Host "`nPOST-DEPLOYMENT CHECKLIST:" -ForegroundColor Yellow
        Write-Host "□ Frontend: $domain" -ForegroundColor Cyan
        Write-Host "□ Admin Center: $domain/admin/ (admin/admin123)" -ForegroundColor Cyan
        Write-Host "□ Change admin password immediately!" -ForegroundColor Red
        Write-Host "□ Configure SMTP/SMS/Google OAuth in admin settings" -ForegroundColor Yellow
        Write-Host "□ Set write permissions for /admin/data/ directory" -ForegroundColor Yellow
        Write-Host "`nNOTE: Static deployment - no Node.js server required!" -ForegroundColor Green
    }
    else {
        Write-Error "Deployment failed"
    }
    
    return $success
}

# Execute deployment
Start-Deployment