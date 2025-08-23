# 11Seconds Deployment Script - Zentrales Deployment System
# Version: 2.0.0
# Datum: 2025-08-23
#
# 🔴 KRITISCHE VORAUSSETZUNG: STATISCHES HOSTING ONLY!
# - KEINE Node.js Server - App läuft vollständig im Browser
# - KEINE API-Endpunkte - Alle Daten über localStorage  
# - NUR statische Dateien - HTML, CSS, JS, Assets
# - Deployment: Rein statische Dateien via FTP
#
# WICHTIG: Diese App benötigt KEINEN Server - läuft komplett client-seitig!

param(
    [Parameter(Mandatory=$false)]
    [ValidateSet("web", "all")]  # "api" entfernt - nicht mehr verfügbar
    [string]$Component = "web",  # Standard auf "web" - einzige verfügbare Komponente
    
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
    # Lokale Pfade - NUR WEB COMPONENT (statisch)
    LocalPaths = @{
        Root = "c:\Users\Marcel\Documents\GitHub\11S"
        WebSource = "c:\Users\Marcel\Documents\GitHub\11S\web"
        WebBuild = "c:\Users\Marcel\Documents\GitHub\11S\web\httpdocs"
        LocalDeploy = "c:\Users\Marcel\Documents\GitHub\11S\httpdocs"
        # ApiSource entfernt - nicht mehr verwendet
    }
    
    # Remote Server Struktur (STATISCHES HOSTING)
    # WICHTIG: Kein Node.js - nur statische Dateien!
    RemotePaths = @{
        DocumentRoot = "/httpdocs"                   # Statische Dateien (HTML, CSS, JS)
        StaticFiles = "/httpdocs/static"             # React Assets (JS/CSS)
        # KEINE API-Pfade - App läuft vollständig client-seitig!
    }
    
    # FTP Konfiguration (wird aus Umgebungsvariablen geladen)
    FTP = @{
        Server = $env:FTP_HOST
        Username = $env:FTP_USER
        Password = $env:FTP_PASSWORD
        Port = 21
        RemotePath = "/httpdocs"  # Pfad für Node.js Hosting
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

function Update-ServiceWorkerReferences {
    Write-DeployLog "Updating Service Worker references..."
    
    $swFile = Join-Path $Config.LocalPaths.LocalDeploy "sw.js"
    if (-not (Test-Path $swFile)) {
        Write-DeployLog "Service Worker file not found: $swFile" -Level "WARN"
        return $true
    }
    
    try {
        # Finde die aktuellen Build-Dateien
        $staticJsPath = Join-Path $Config.LocalPaths.LocalDeploy "static\js"
        $staticCssPath = Join-Path $Config.LocalPaths.LocalDeploy "static\css"
        
        $mainJsFiles = Get-ChildItem -Path $staticJsPath -Filter "main.*.js" -ErrorAction SilentlyContinue
        $mainCssFiles = Get-ChildItem -Path $staticCssPath -Filter "main.*.css" -ErrorAction SilentlyContinue
        
        if ($mainJsFiles.Count -eq 0 -or $mainCssFiles.Count -eq 0) {
            Write-DeployLog "Could not find main JS or CSS files for Service Worker update" -Level "WARN"
            return $true
        }
        
        $mainJsFile = $mainJsFiles[0].Name
        $mainCssFile = $mainCssFiles[0].Name
        
        # Lese Service Worker Inhalt
        $swContent = Get-Content $swFile -Raw
        
        # Ersetze veraltete Referenzen
        $swContent = $swContent -replace '/static/js/bundle\.js', "/static/js/$mainJsFile"
        $swContent = $swContent -replace '/static/css/main\.css', "/static/css/$mainCssFile"
        
        # Update Cache Version mit aktuellem Timestamp
        $timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
        $swContent = $swContent -replace "11seconds-v[\d\.\-\w]+", "11seconds-v2.3.0-$timestamp"
        
        # Schreibe zurück
        Set-Content -Path $swFile -Value $swContent -NoNewline
        
        Write-DeployLog "Service Worker updated: JS=$mainJsFile, CSS=$mainCssFile" -Level "SUCCESS"
        return $true
        
    } catch {
        Write-DeployLog "Failed to update Service Worker: $($_.Exception.Message)" -Level "ERROR"
        return $false
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
        
        # Update Service Worker references to match actual build files
        if (-not (Update-ServiceWorkerReferences)) {
            Write-DeployLog "Service Worker update failed" -Level "WARN"
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

# FTP Funktionen vom alten bewährten System
function Test-FTPConnection {
    if (-not $Config.FTP.Server -or -not $Config.FTP.Username -or -not $Config.FTP.Password) {
        Write-DeployLog "FTP credentials not configured. Set FTP_HOST, FTP_USER, FTP_PASSWORD environment variables" -Level "ERROR"
        return $false
    }
    
    Write-DeployLog "Testing FTP connection to $($Config.FTP.Server)..."
    
    try {
        $uri = "ftp://$($Config.FTP.Server)/"
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
        $request.Credentials = New-Object System.Net.NetworkCredential($Config.FTP.Username, $Config.FTP.Password)
        $request.Timeout = 10000
        
        $response = $request.GetResponse()
        $response.Close()
        
        Write-DeployLog "FTP connection successful" -Level "SUCCESS"
        return $true
    }
    catch {
        Write-DeployLog "FTP connection failed: $($_.Exception.Message)" -Level "ERROR"
        return $false
    }
}

function Upload-FileViaFTP {
    param([string]$LocalPath, [string]$RemotePath)
    
    try {
        if (-not (Test-Path $LocalPath)) {
            Write-DeployLog "File not found: $LocalPath" -Level "WARN"
            return $false
        }
        
        $uri = "ftp://$($Config.FTP.Server)$RemotePath"
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $request.Credentials = New-Object System.Net.NetworkCredential($Config.FTP.Username, $Config.FTP.Password)
        $request.UseBinary = $true
        $request.Timeout = 30000
        
        $fileBytes = [System.IO.File]::ReadAllBytes($LocalPath)
        $request.ContentLength = $fileBytes.Length
        
        $stream = $request.GetRequestStream()
        $stream.Write($fileBytes, 0, $fileBytes.Length)
        $stream.Close()
        
        $response = $request.GetResponse()
        $response.Close()
        
        Write-DeployLog "  ✓ $RemotePath" -Level "SUCCESS"
        return $true
    }
    catch {
        Write-DeployLog "  ✗ $RemotePath - $($_.Exception.Message)" -Level "ERROR"
        return $false
    }
}

function Create-FTPDirectory {
    param([string]$RemotePath)
    
    try {
        $uri = "ftp://$($Config.FTP.Server)$RemotePath"
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $request.Credentials = New-Object System.Net.NetworkCredential($Config.FTP.Username, $Config.FTP.Password)
        $request.Timeout = 15000
        
        $response = $request.GetResponse()
        $response.Close()
        
        Write-DeployLog "  📁 Created: $RemotePath" -Level "SUCCESS"
        return $true
    }
    catch {
        # Directory might already exist - ignore error
        return $true
    }
}

function Deploy-ViaFTP {
    Write-DeployLog "=== FTP UPLOAD PHASE ===" -Level "SUCCESS"
    
    if (-not (Test-FTPConnection)) {
        Write-DeployLog "FTP connection test failed" -Level "ERROR"
        return $false
    }
    
    $localPath = $Config.LocalPaths.LocalDeploy
    $remotePath = $Config.FTP.RemotePath
    
    if (-not (Test-Path $localPath)) {
        Write-DeployLog "Local deployment directory not found: $localPath" -Level "ERROR"
        return $false
    }
    
    $stats = @{ Success = 0; Failed = 0 }
    
    # Create remote directory structure for Node.js hosting
    Write-DeployLog "Creating remote directory structure..."
    Create-FTPDirectory "$remotePath"
    Create-FTPDirectory "$remotePath/static"
    Create-FTPDirectory "$remotePath/static/css"
    Create-FTPDirectory "$remotePath/static/js"
    Create-FTPDirectory "$remotePath/static/media"
    
    # Upload all files recursively
    function Upload-Directory($localDir, $remoteDir) {
        $files = Get-ChildItem -Path $localDir -File
        foreach ($file in $files) {
            $remoteFilePath = "$remoteDir/$($file.Name)"
            if (Upload-FileViaFTP -LocalPath $file.FullName -RemotePath $remoteFilePath) {
                $stats.Success++
            } else {
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
    
    # Upload frontend build to /httpdocs (Node.js DocumentRoot)
    Write-DeployLog "Uploading React build files to $remotePath..."
    Upload-Directory -localDir $localPath -remoteDir $remotePath
    
    # 🔴 API-Upload vollständig entfernt - App ist vollständig statisch!
    # Alle ehemaligen API-Funktionen laufen jetzt über localStorage im Browser
    Write-DeployLog "✅ Statische App deployment completed - no server required!" -Level "SUCCESS"
    
    Write-DeployLog "Upload completed: $($stats.Success) successful, $($stats.Failed) failed" -Level "SUCCESS"
    Write-DeployLog "Test your app at: http://11seconds.de" -Level "SUCCESS"
    
    return ($stats.Failed -eq 0)
}

function Show-UploadInstructions {
    Write-DeployLog "=== MANUAL UPLOAD INSTRUCTIONS ===" -Level "SUCCESS"
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

function Main {
    Write-DeployLog "Starting 11Seconds Deployment v2.0.0" -Level "SUCCESS"
    Write-DeployLog "Target: Node.js 22.18.0 hosting with DocumentRoot: /11seconds.de/httpdocs" -Level "SUCCESS"
    Write-DeployLog "Component: $Component, BuildOnly: $BuildOnly, UploadOnly: $UploadOnly, Force: $Force"
    
    # Prerequisites check
    if (-not (Test-Prerequisites)) {
        Write-DeployLog "Prerequisites check failed" -Level "ERROR"
        exit 1
    }
    
    # Build phase
    if (-not $UploadOnly) {
        Write-DeployLog "=== BUILD PHASE ===" -Level "SUCCESS"
        
        # Nur Web-Component (statisch)
        if ($Component -in @("web", "all")) {
            if (-not (Build-WebApplication)) {
                Write-DeployLog "Web build failed" -Level "ERROR"
                exit 1
            }
        } else {
            Write-DeployLog "⚠️  Nur 'web' Component verfügbar - API wurde entfernt" -Level "WARN"
        }
        
        if (-not (Deploy-LocalFiles)) {
            Write-DeployLog "Local deployment failed" -Level "ERROR"
            exit 1
        }
        
        if (-not (Test-Deployment)) {
            Write-DeployLog "Deployment test failed" -Level "ERROR"
            exit 1
        }
    }
    
    # Upload phase - Automatisches FTP oder manuelle Instruktionen
    if (-not $BuildOnly) {
        Write-DeployLog "=== UPLOAD PHASE ===" -Level "SUCCESS"
        
        # Versuche automatisches FTP, falls Credentials vorhanden
        if ($Config.FTP.Server -and $Config.FTP.Username -and $Config.FTP.Password) {
            Write-DeployLog "FTP credentials found - starting automatic upload..."
            if (Deploy-ViaFTP) {
                Write-DeployLog "Automatic FTP deployment completed successfully!" -Level "SUCCESS"
            } else {
                Write-DeployLog "Automatic FTP deployment failed - showing manual instructions" -Level "WARN"
                Show-UploadInstructions
            }
        } else {
            Write-DeployLog "No FTP credentials found - showing manual upload instructions" -Level "WARN"
            Write-DeployLog "Set FTP_HOST, FTP_USER, FTP_PASSWORD environment variables for automatic upload"
            Show-UploadInstructions
        }
    }
    
    Write-DeployLog "Deployment preparation completed successfully!" -Level "SUCCESS"
}

# Script ausführen
Main
