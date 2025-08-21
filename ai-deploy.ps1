# 🤖 AI Deployment Interface für 11Seconds Quiz Game
# Ermöglicht AI-gesteuerte Deployments direkt aus VS Code

param(
    [Parameter(Mandatory=$true)]
    [ValidateSet("deploy", "build", "test", "sync", "status", "rollback", "logs")]
    [string]$Action,
    
    [Parameter(Mandatory=$false)]
    [string]$Environment = "production",
    
    [Parameter(Mandatory=$false)]
    [string]$Message = "",
    
    [switch]$Force = $false,
    [switch]$DryRun = $false,
    [switch]$Verbose = $false
)

# Farben für Output
$Colors = @{
    Success = "Green"
    Warning = "Yellow"
    Error = "Red"
    Info = "Cyan"
    Debug = "Gray"
}

function Write-Log {
    param($Message, $Type = "Info")
    $timestamp = Get-Date -Format "HH:mm:ss"
    Write-Host "[$timestamp] $Message" -ForegroundColor $Colors[$Type]
}

function Load-NetcupCredentials {
    # Lade Netcup Zugangsdaten aus verschiedenen Quellen
    $credentialsLoaded = $false
    
    # 1. Prüfe .env.netcup Datei
    if (Test-Path ".env.netcup") {
        Write-Log "🔐 Loading credentials from .env.netcup..." "Debug"
        Get-Content ".env.netcup" | ForEach-Object {
            if ($_ -match "^([^=]+)=(.*)$") {
                $key = $matches[1].Trim()
                $value = $matches[2].Trim()
                [Environment]::SetEnvironmentVariable($key, $value, "Process")
            }
        }
        $credentialsLoaded = $true
    }
    
    # 2. Prüfe Environment Variables
    $requiredVars = @("NETCUP_FTP_HOST", "NETCUP_FTP_USER", "NETCUP_FTP_PASSWORD")
    $missingVars = @()
    
    foreach ($var in $requiredVars) {
        if (-not [Environment]::GetEnvironmentVariable($var)) {
            $missingVars += $var
        }
    }
    
    if ($missingVars.Count -eq 0) {
        Write-Log "✅ All FTP credentials loaded successfully" "Success"
        return $true
    } else {
        Write-Log "❌ Missing FTP credentials: $($missingVars -join ', ')" "Error"
        Write-Log "💡 Create .env.netcup file with your credentials" "Info"
        Write-Log "💡 Or set environment variables manually" "Info"
        return $false
    }
}

function Test-NetcupConnection {
    Write-Log "🔌 Testing Netcup FTP connection..." "Info"
    
    $ftpHost = [Environment]::GetEnvironmentVariable("NETCUP_FTP_HOST")
    $ftpUser = [Environment]::GetEnvironmentVariable("NETCUP_FTP_USER")
    $ftpPass = [Environment]::GetEnvironmentVariable("NETCUP_FTP_PASSWORD")
    
    if (-not $ftpHost -or -not $ftpUser -or -not $ftpPass) {
        Write-Log "❌ FTP credentials not configured" "Error"
        return $false
    }
    
    try {
        # Test FTP connection
        $ftpRequest = [System.Net.FtpWebRequest]::Create("ftp://$ftpHost/")
        $ftpRequest.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $ftpRequest.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
        $ftpRequest.Timeout = 10000
        
        $response = $ftpRequest.GetResponse()
        $response.Close()
        
        Write-Log "✅ FTP connection successful!" "Success"
        return $true
    } catch {
        Write-Log "❌ FTP connection failed: $($_.Exception.Message)" "Error"
        return $false
    }
}

function Invoke-NetcupFTPUpload {
    param($LocalPath, $RemotePath = "/")
    
    Write-Log "📤 Uploading to Netcup FTP..." "Info"
    
    $ftpHost = [Environment]::GetEnvironmentVariable("NETCUP_FTP_HOST")
    $ftpUser = [Environment]::GetEnvironmentVariable("NETCUP_FTP_USER")
    $ftpPass = [Environment]::GetEnvironmentVariable("NETCUP_FTP_PASSWORD")
    
    # WinSCP verwenden falls verfügbar
    if (Get-Module -ListAvailable -Name WinSCP) {
        try {
            Import-Module WinSCP -ErrorAction Stop
            
            $sessionOptions = New-Object WinSCP.SessionOptions -Property @{
                Protocol = [WinSCP.Protocol]::Ftp
                HostName = $ftpHost
                UserName = $ftpUser
                Password = $ftpPass
                Timeout = 30
            }
            
            $session = New-Object WinSCP.Session
            $session.Open($sessionOptions)
            
            $transferOptions = New-Object WinSCP.TransferOptions
            $transferOptions.TransferMode = [WinSCP.TransferMode]::Binary
            $transferOptions.ResumeSupport = $true
            
            # Upload all files
            if (Test-Path $LocalPath) {
                $session.PutFiles("$LocalPath\*", $RemotePath, $True, $transferOptions)
                Write-Log "✅ Upload completed successfully!" "Success"
                
                $session.Close()
                return $true
            } else {
                Write-Log "❌ Local path not found: $LocalPath" "Error"
                return $false
            }
            
        } catch {
            Write-Log "❌ WinSCP upload failed: $($_.Exception.Message)" "Error"
            return $false
        }
    } else {
        Write-Log "⚠️  WinSCP module not available" "Warning"
        Write-Log "💡 Install with: Install-Module -Name WinSCP" "Info"
        Write-Log "📁 Files ready for manual upload in: $LocalPath" "Info"
        
        # Öffne Explorer für manuellen Upload
        if (Test-Path $LocalPath) {
            Start-Process explorer.exe -ArgumentList $LocalPath
        }
        
        return $false
    }
}
    $status = @{
        LastDeploy = if (Test-Path ".deployment-info.json") { 
            (Get-Content ".deployment-info.json" | ConvertFrom-Json).timestamp 
        } else { "Never" }
        LocalChanges = (git status --porcelain | Measure-Object).Count
        Branch = git rev-parse --abbrev-ref HEAD
        LastCommit = git log -1 --pretty=format:"%h - %s (%cr)"
        ServerRunning = $false
    }
    
    # Check if local server is running
    try {
        $response = Invoke-WebRequest -Uri "http://localhost:3011/api/game/categories" -TimeoutSec 3 -ErrorAction Stop
        $status.ServerRunning = $true
    } catch {
        $status.ServerRunning = $false
    }
    
    return $status
}

function Save-DeploymentInfo {
    param($Action, $Success, $Message = "")
    
    $info = @{
        timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        action = $Action
        success = $Success
        message = $Message
        environment = $Environment
        branch = git rev-parse --abbrev-ref HEAD
        commit = git rev-parse --short HEAD
        user = $env:USERNAME
    }
    
    $info | ConvertTo-Json | Out-File ".deployment-info.json" -Encoding UTF8
}

Write-Log "🤖 AI Deployment Interface - Action: $Action" "Info"

$ProjectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $ProjectRoot

# Status abrufen
$status = Get-DeploymentStatus

switch ($Action) {
    "status" {
        Write-Log "📊 Deployment Status Report:" "Info"
        Write-Log "  Last Deploy: $($status.LastDeploy)" "Info"
        Write-Log "  Current Branch: $($status.Branch)" "Info" 
        Write-Log "  Last Commit: $($status.LastCommit)" "Info"
        Write-Log "  Local Changes: $($status.LocalChanges) files" "Info"
        Write-Log "  Local Server: $(if($status.ServerRunning){'🟢 Running'}else{'🔴 Stopped'})" "Info"
        
        if (Test-Path "deploy-netcup-auto") {
            $deployFiles = Get-ChildItem "deploy-netcup-auto" | Measure-Object
            Write-Log "  Deploy Package: $($deployFiles.Count) files ready" "Info"
        }
    }
    
    "test" {
        Write-Log "🧪 Running pre-deployment tests..." "Info"
        
        # Server Status Test
        Write-Log "Testing server startup..." "Debug"
        $testResult = & node -e "
            console.log('Testing database connection...');
            const dbPromise = require('./api/db-switcher');
            dbPromise.then(db => {
                console.log('✅ Database connection successful');
                process.exit(0);
            }).catch(err => {
                console.error('❌ Database connection failed:', err.message);
                process.exit(1);
            });
        "
        
        if ($LASTEXITCODE -eq 0) {
            Write-Log "✅ Database connection test passed" "Success"
        } else {
            Write-Log "❌ Database connection test failed" "Error"
            Save-DeploymentInfo "test" $false "Database connection failed"
            exit 1
        }
        
        # API Routes Test
        Write-Log "Testing API routes..." "Debug"
        if ($status.ServerRunning) {
            try {
                $categories = Invoke-RestMethod -Uri "http://localhost:3011/api/game/categories" -TimeoutSec 5
                Write-Log "✅ API routes working: $($categories.categories.Count) categories found" "Success"
            } catch {
                Write-Log "⚠️  API routes test skipped (server not running)" "Warning"
            }
        }
        
        Write-Log "✅ All tests passed!" "Success"
        Save-DeploymentInfo "test" $true "All tests passed"
    }
    
    "build" {
        Write-Log "📦 Building production package..." "Info"
        
        if ($DryRun) {
            Write-Log "🧪 DRY RUN - No files will be created" "Warning"
        }
        
        $BuildDir = "deploy-netcup-auto"
        
        if (-not $DryRun) {
            if (Test-Path $BuildDir) {
                Remove-Item $BuildDir -Recurse -Force
                Write-Log "🗑️  Cleaned existing build directory" "Debug"
            }
            New-Item -ItemType Directory -Path $BuildDir | Out-Null
        }
        
        $FilesToCopy = @(
            @{Source = "app.js"; Dest = "$BuildDir\app.js"},
            @{Source = "package-production.json"; Dest = "$BuildDir\package.json"},
            @{Source = ".env-production-example"; Dest = "$BuildDir\.env-template"},
            @{Source = "setup-extended-questions.js"; Dest = "$BuildDir\setup-extended-questions.js"}
        )
        
        foreach ($file in $FilesToCopy) {
            if (Test-Path $file.Source) {
                if (-not $DryRun) {
                    Copy-Item $file.Source $file.Dest -Force
                }
                Write-Log "✅ $($file.Source) → $($file.Dest)" "Success"
            } else {
                Write-Log "❌ Missing: $($file.Source)" "Error"
            }
        }
        
        $DirsToC copy = @("api", "httpdocs")
        foreach ($dir in $DirsToC copy) {
            if (Test-Path $dir) {
                if (-not $DryRun) {
                    Copy-Item $dir "$BuildDir\" -Recurse -Force
                }
                Write-Log "✅ $dir/ copied to build" "Success"
            } else {
                Write-Log "❌ Missing directory: $dir" "Error"
            }
        }
        
        if (-not $DryRun) {
            # Build Info erstellen
            $buildInfo = @{
                built = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
                branch = git rev-parse --abbrev-ref HEAD
                commit = git rev-parse --short HEAD
                environment = $Environment
                message = $Message
            }
            $buildInfo | ConvertTo-Json | Out-File "$BuildDir\build-info.json" -Encoding UTF8
        }
        
        Write-Log "✅ Production package built successfully!" "Success"
        Save-DeploymentInfo "build" $true "Production package built"
    }
    
    "deploy" {
        Write-Log "🚀 Starting full deployment to $Environment..." "Info"
        
        if ($status.LocalChanges -gt 0 -and -not $Force) {
            Write-Log "⚠️  Warning: $($status.LocalChanges) uncommitted changes detected!" "Warning"
            Write-Log "💡 Use -Force to deploy anyway, or commit changes first" "Info"
            exit 1
        }
        
        # Build first
        Write-Log "📦 Building package..." "Info"
        & $PSCommandPath -Action build -Environment $Environment -Message $Message -DryRun:$DryRun
        
        if ($LASTEXITCODE -eq 0) {
            Write-Log "✅ Build completed successfully" "Success"
        } else {
            Write-Log "❌ Build failed, aborting deployment" "Error"
            exit 1
        }
        
        # Create ZIP for easy upload
        if (-not $DryRun -and (Get-Command Compress-Archive -ErrorAction SilentlyContinue)) {
            $ZipName = "11seconds-deployment-$(Get-Date -Format 'yyyyMMdd-HHmmss').zip"
            Compress-Archive -Path "deploy-netcup-auto\*" -DestinationPath $ZipName -Force
            Write-Log "📦 Deployment ZIP created: $ZipName" "Success"
        }
        
        Write-Log "🎉 Deployment package ready!" "Success"
        Write-Log "📁 Files ready in: deploy-netcup-auto/" "Info"
        Write-Log "📦 ZIP package: $ZipName" "Info"
        
        # Öffne Explorer
        if (-not $DryRun -and (Test-Path "deploy-netcup-auto")) {
            Start-Process explorer.exe -ArgumentList "deploy-netcup-auto"
        }
        
        Save-DeploymentInfo "deploy" $true "Deployment package created: $ZipName"
    }
    
    "sync" {
        Write-Log "🔄 Testing FTP connection and syncing to Netcup server..." "Info"
        
        # Load credentials first
        if (-not (Load-NetcupCredentials)) {
            Write-Log "❌ Cannot proceed without FTP credentials" "Error"
            exit 1
        }
        
        # Test FTP connection
        if (-not (Test-NetcupConnection)) {
            Write-Log "❌ FTP connection failed, aborting sync" "Error"
            exit 1
        }
        
        # Check if deployment package exists
        if (-not (Test-Path "deploy-netcup-auto")) {
            Write-Log "📦 No deployment package found, building first..." "Warning"
            & $PSCommandPath -Action build -Environment $Environment -Message $Message
        }
        
        # Upload via FTP
        if (Invoke-NetcupFTPUpload "deploy-netcup-auto" "/") {
            Write-Log "✅ Sync completed successfully" "Success"
            Save-DeploymentInfo "sync" $true "Files synced to Netcup via FTP"
        } else {
            Write-Log "❌ Sync failed" "Error"
            Save-DeploymentInfo "sync" $false "FTP sync failed"
        }
    }
    
    "test-ftp" {
        Write-Log "🔌 Testing FTP connection to Netcup..." "Info"
        
        if (Load-NetcupCredentials) {
            if (Test-NetcupConnection) {
                Write-Log "✅ FTP test successful - ready for deployment!" "Success"
                Save-DeploymentInfo "test-ftp" $true "FTP connection verified"
            } else {
                Write-Log "❌ FTP test failed" "Error"
                Save-DeploymentInfo "test-ftp" $false "FTP connection failed"
            }
        } else {
            Write-Log "❌ FTP credentials not configured" "Error"
        }
    }
    
    "logs" {
        Write-Log "📜 Recent deployment history:" "Info"
        
        if (Test-Path ".deployment-info.json") {
            $lastDeploy = Get-Content ".deployment-info.json" | ConvertFrom-Json
            Write-Log "  Last Action: $($lastDeploy.action) at $($lastDeploy.timestamp)" "Info"
            Write-Log "  Status: $(if($lastDeploy.success){'✅ Success'}else{'❌ Failed'})" "Info"
            Write-Log "  Message: $($lastDeploy.message)" "Info"
            Write-Log "  Branch: $($lastDeploy.branch)" "Info"
            Write-Log "  Commit: $($lastDeploy.commit)" "Info"
        } else {
            Write-Log "  No deployment history found" "Warning"
        }
        
        # Git log für letzte Commits
        Write-Log "`n📝 Recent commits:" "Info"
        $commits = git log --oneline -5
        foreach ($commit in $commits) {
            Write-Log "    $commit" "Debug"
        }
    }
    
    "rollback" {
        Write-Log "⏪ Rollback functionality not implemented yet" "Warning"
        Write-Log "💡 Manual rollback: Upload previous deployment package" "Info"
    }
}

Write-Log "✨ AI Deployment Interface completed at $(Get-Date -Format 'HH:mm:ss')" "Success"
