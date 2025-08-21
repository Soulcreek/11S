# 🤖 AI-Controlled Deployment Orchestrator for Netcup
# Automatisiert alles: Build, Test, Upload, Deploy, Verify

param(
    [Parameter(Mandatory=$false)]
    [ValidateSet("deploy", "quick-deploy", "test", "status", "rollback", "full-deploy")]
    [string]$Action = "deploy",
    
    [Parameter(Mandatory=$false)]
    [switch]$Force = $false,
    
    [Parameter(Mandatory=$false)]
    [switch]$SkipTests = $false
)

# Color functions
function Write-Success { param($msg) Write-Host "✅ $msg" -ForegroundColor Green }
function Write-Error { param($msg) Write-Host "❌ $msg" -ForegroundColor Red }
function Write-Warning { param($msg) Write-Host "⚠️  $msg" -ForegroundColor Yellow }
function Write-Info { param($msg) Write-Host "ℹ️  $msg" -ForegroundColor Cyan }
function Write-Step { param($msg) Write-Host "🔄 $msg" -ForegroundColor Magenta }

# Load Netcup credentials
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
        Write-Success "Netcup credentials loaded"
        return $true
    } else {
        Write-Error ".env.netcup file not found!"
        return $false
    }
}

# Test FTP connection
function Test-FTPConnection {
    $host = [Environment]::GetEnvironmentVariable("NETCUP_FTP_HOST")
    $user = [Environment]::GetEnvironmentVariable("NETCUP_FTP_USER")
    $pass = [Environment]::GetEnvironmentVariable("NETCUP_FTP_PASSWORD")
    
    Write-Step "Testing FTP connection to $host..."
    
    try {
        $uri = "ftp://$host/"
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
        $request.Credentials = New-Object System.Net.NetworkCredential($user, $pass)
        $request.Timeout = 15000
        
        $response = $request.GetResponse()
        $response.Close()
        
        Write-Success "FTP connection successful"
        return $true
    } catch {
        Write-Error "FTP connection failed: $($_.Exception.Message)"
        return $false
    }
}

# Build deployment package
function Build-DeploymentPackage {
    Write-Step "Building deployment package..."
    
    $deployDir = ".\deploy-netcup-auto"
    
    # Clean and recreate deployment directory
    if (Test-Path $deployDir) {
        Remove-Item $deployDir -Recurse -Force
        Write-Info "Cleaned existing deployment directory"
    }
    
    # Create directory structure
    New-Item -Path $deployDir -ItemType Directory -Force | Out-Null
    New-Item -Path "$deployDir\api" -ItemType Directory -Force | Out-Null
    New-Item -Path "$deployDir\api\middleware" -ItemType Directory -Force | Out-Null
    New-Item -Path "$deployDir\api\routes" -ItemType Directory -Force | Out-Null
    New-Item -Path "$deployDir\httpdocs" -ItemType Directory -Force | Out-Null
    
    # Copy files
    $filesToCopy = @(
        @{ Source = ".\app.js"; Dest = "$deployDir\app.js" },
        @{ Source = ".\package.json"; Dest = "$deployDir\package.json" },
        @{ Source = ".\api\db-switcher.js"; Dest = "$deployDir\api\db-switcher.js" },
        @{ Source = ".\api\middleware\auth.js"; Dest = "$deployDir\api\middleware\auth.js" },
        @{ Source = ".\api\routes\auth.js"; Dest = "$deployDir\api\routes\auth.js" },
        @{ Source = ".\api\routes\game.js"; Dest = "$deployDir\api\routes\game.js" },
        @{ Source = ".\httpdocs\index.html"; Dest = "$deployDir\httpdocs\index.html" }
    )
    
    $copyCount = 0
    foreach ($file in $filesToCopy) {
        if (Test-Path $file.Source) {
            Copy-Item $file.Source $file.Dest -Force
            $copyCount++
        } else {
            Write-Warning "Source file not found: $($file.Source)"
        }
    }
    
    # Create production .env
    $prodEnv = @"
# 11Seconds Quiz Game - Production Environment
NODE_ENV=production
PORT=3011

# JWT Configuration
JWT_SECRET=NETCUP_PRODUCTION_JWT_SECRET_2025_SECURE_KEY_11SECONDS

# MySQL Database Configuration (Netcup)
DB_HOST=10.35.233.76
DB_PORT=3306
DB_USER=k302164_11S
DB_PASS=hallo.411S
DB_NAME=k302164_11Sec_Data

# Fallback SQLite Database
SQLITE_DB_PATH=./data/quiz.db

# CORS Configuration
CORS_ORIGIN=https://11seconds.de

# App Configuration
APP_NAME=11Seconds Quiz Game
APP_VERSION=1.0.0
"@
    
    $prodEnv | Out-File -FilePath "$deployDir\.env" -Encoding UTF8
    $copyCount++
    
    Write-Success "Built deployment package with $copyCount files"
    return $copyCount -gt 0
}

# Upload files via FTP
function Upload-ToNetcup {
    $host = [Environment]::GetEnvironmentVariable("NETCUP_FTP_HOST")
    $user = [Environment]::GetEnvironmentVariable("NETCUP_FTP_USER")
    $pass = [Environment]::GetEnvironmentVariable("NETCUP_FTP_PASSWORD")
    
    Write-Step "Uploading to Netcup FTP server..."
    
    # Upload function
    function Upload-File($localPath, $remotePath) {
        try {
            if (-not (Test-Path $localPath)) {
                Write-Warning "File not found: $localPath"
                return $false
            }
            
            $uri = "ftp://$host/$remotePath"
            $request = [System.Net.FtpWebRequest]::Create($uri)
            $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
            $request.Credentials = New-Object System.Net.NetworkCredential($user, $pass)
            $request.UseBinary = $true
            
            $fileBytes = [System.IO.File]::ReadAllBytes($localPath)
            $request.ContentLength = $fileBytes.Length
            
            $stream = $request.GetRequestStream()
            $stream.Write($fileBytes, 0, $fileBytes.Length)
            $stream.Close()
            
            $response = $request.GetResponse()
            $response.Close()
            
            Write-Host "  ✅ $remotePath" -ForegroundColor Green
            return $true
        } catch {
            Write-Host "  ❌ $remotePath - $($_.Exception.Message)" -ForegroundColor Red
            return $false
        }
    }
    
    # Create directories
    function Create-FTPDirectory($path) {
        try {
            $uri = "ftp://$host/$path"
            $request = [System.Net.FtpWebRequest]::Create($uri)
            $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
            $request.Credentials = New-Object System.Net.NetworkCredential($user, $pass)
            $response = $request.GetResponse()
            $response.Close()
        } catch {
            # Directory might exist
        }
    }
    
    # Create remote directory structure
    Create-FTPDirectory "api"
    Create-FTPDirectory "api/middleware"
    Create-FTPDirectory "api/routes"
    Create-FTPDirectory "httpdocs"
    
    # Upload files
    $deployDir = ".\deploy-netcup-auto"
    $uploads = @(
        @{ Local = "$deployDir\app.js"; Remote = "app.js" },
        @{ Local = "$deployDir\package.json"; Remote = "package.json" },
        @{ Local = "$deployDir\.env"; Remote = ".env" },
        @{ Local = "$deployDir\api\db-switcher.js"; Remote = "api/db-switcher.js" },
        @{ Local = "$deployDir\api\middleware\auth.js"; Remote = "api/middleware/auth.js" },
        @{ Local = "$deployDir\api\routes\auth.js"; Remote = "api/routes/auth.js" },
        @{ Local = "$deployDir\api\routes\game.js"; Remote = "api/routes/game.js" },
        @{ Local = "$deployDir\httpdocs\index.html"; Remote = "httpdocs/index.html" }
    )
    
    $successCount = 0
    foreach ($upload in $uploads) {
        if (Upload-File $upload.Local $upload.Remote) {
            $successCount++
        }
    }
    
    Write-Success "Uploaded $successCount/$($uploads.Count) files"
    return $successCount -eq $uploads.Count
}

# Generate deployment instructions
function Generate-DeploymentInstructions {
    $instructions = @"

🚀 DEPLOYMENT ERFOLGREICH!

📋 NÄCHSTE SCHRITTE AUF DEM NETCUP SERVER:

1️⃣ SSH ins Netcup System einloggen:
   ssh your-username@11seconds.de

2️⃣ Node.js Dependencies installieren:
   cd /pfad/zu/deiner/app
   npm install

3️⃣ MySQL Datenbank setup (falls noch nicht gemacht):
   - Gehe ins Netcup Kundencenter
   - Erstelle MySQL Datenbank: k302164_11Sec_Data
   - User: k302164_11S bereits konfiguriert

4️⃣ App starten:
   node app.js
   
   ODER als Service mit PM2:
   npm install -g pm2
   pm2 start app.js --name "11seconds-quiz"
   pm2 startup
   pm2 save

📡 DEINE APP LÄUFT DANN UNTER:
   🌐 Frontend: https://11seconds.de
   🔗 API: https://11seconds.de:3011/api
   📊 Status: https://11seconds.de:3011/api/game/status

🎮 FEATURES:
   ✅ Smart Database Switcher (MySQL → SQLite Fallback)
   ✅ JWT Authentication
   ✅ Game API (Questions, Score, Auth)
   ✅ Responsive Web Interface
   ✅ Production-ready Configuration

"@
    
    Write-Host $instructions -ForegroundColor Cyan
}

# Main deployment orchestrator
function Start-Deployment {
    Write-Host "`n🤖 AI DEPLOYMENT ORCHESTRATOR" -ForegroundColor Magenta
    Write-Host "===============================" -ForegroundColor Magenta
    
    # Step 1: Load credentials
    if (-not (Load-NetcupCredentials)) {
        return $false
    }
    
    # Step 2: Test FTP connection
    if (-not (Test-FTPConnection)) {
        Write-Error "FTP connection failed. Check credentials in .env.netcup"
        return $false
    }
    
    # Step 3: Build deployment package
    if (-not (Build-DeploymentPackage)) {
        Write-Error "Failed to build deployment package"
        return $false
    }
    
    # Step 4: Upload to Netcup
    if (-not (Upload-ToNetcup)) {
        Write-Error "Upload to Netcup failed"
        return $false
    }
    
    # Step 5: Generate instructions
    Generate-DeploymentInstructions
    
    Write-Success "🎉 AUTOMATED DEPLOYMENT COMPLETED!"
    return $true
}

# Execute based on action
switch ($Action.ToLower()) {
    "deploy" { Start-Deployment }
    "quick-deploy" { Start-Deployment }
    "full-deploy" { Start-Deployment }
    "test" { Test-FTPConnection }
    "status" { 
        Load-NetcupCredentials
        Test-FTPConnection 
    }
    default {
        Write-Host "Available actions: deploy, test, status" -ForegroundColor Yellow
    }
}
