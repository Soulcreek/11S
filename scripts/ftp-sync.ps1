# 🔄 FTP Sync Script für Netcup Webhosting
# Synchronisiert Dateien automatisch via FTP

param(
    [Parameter(Mandatory=$false)]
    [string]$FtpHost = "",
    
    [Parameter(Mandatory=$false)]
    [string]$FtpUser = "",
    
    [Parameter(Mandatory=$false)]
    [string]$FtpPassword = "",
    
    [Parameter(Mandatory=$false)]
    [string]$RemotePath = "/",
    
    [switch]$DryRun = $false
)

Write-Host "🔄 FTP Sync to Netcup Webhosting" -ForegroundColor Green

# FTP Credentials aus Environment oder Parameter
if (-not $FtpHost) { $FtpHost = $env:NETCUP_FTP_HOST }
if (-not $FtpUser) { $FtpUser = $env:NETCUP_FTP_USER }
if (-not $FtpPassword) { $FtpPassword = $env:NETCUP_FTP_PASSWORD }

# Validierung
if (-not $FtpHost -or -not $FtpUser -or -not $FtpPassword) {
    Write-Host "❌ FTP credentials missing!" -ForegroundColor Red
    Write-Host "💡 Set environment variables or use parameters:" -ForegroundColor Yellow
    Write-Host "   NETCUP_FTP_HOST, NETCUP_FTP_USER, NETCUP_FTP_PASSWORD" -ForegroundColor Gray
    Write-Host "   OR use: -FtpHost host -FtpUser user -FtpPassword pass" -ForegroundColor Gray
    exit 1
}

$ProjectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $ProjectRoot

# Dateien zum Upload
$FilesToUpload = @(
    @{Local = "app.js"; Remote = "app.js"},
    @{Local = "package-production.json"; Remote = "package.json"},
    @{Local = "setup-extended-questions.js"; Remote = "setup-extended-questions.js"}
)

$DirsToUpload = @("api", "httpdocs")

Write-Host "🌐 Connecting to: $FtpHost" -ForegroundColor Cyan
Write-Host "👤 User: $FtpUser" -ForegroundColor Cyan

if ($DryRun) {
    Write-Host "🧪 DRY RUN MODE - No files will be uploaded" -ForegroundColor Yellow
}

try {
    # WinSCP verwenden (falls verfügbar)
    if (Get-Module -ListAvailable -Name WinSCP) {
        Import-Module WinSCP
        
        $sessionOptions = New-Object WinSCP.SessionOptions -Property @{
            Protocol = [WinSCP.Protocol]::Ftp
            HostName = $FtpHost
            UserName = $FtpUser
            Password = $FtpPassword
            Timeout = 30
        }
        
        if (-not $DryRun) {
            $session = New-Object WinSCP.Session
            $session.Open($sessionOptions)
            
            $transferOptions = New-Object WinSCP.TransferOptions
            $transferOptions.TransferMode = [WinSCP.TransferMode]::Binary
            $transferOptions.ResumeSupport = $true
        }
        
        # Einzelne Dateien
        foreach ($file in $FilesToUpload) {
            if (Test-Path $file.Local) {
                $remotePath = "$RemotePath/$($file.Remote)"
                Write-Host "📄 $($file.Local) → $remotePath" -ForegroundColor Green
                
                if (-not $DryRun) {
                    $session.PutFiles($file.Local, $remotePath, $False, $transferOptions)
                }
            } else {
                Write-Host "⚠️  Missing: $($file.Local)" -ForegroundColor Yellow
            }
        }
        
        # Verzeichnisse
        foreach ($dir in $DirsToUpload) {
            if (Test-Path $dir) {
                $remotePath = "$RemotePath/$dir/"
                Write-Host "📁 $dir/ → $remotePath" -ForegroundColor Green
                
                if (-not $DryRun) {
                    $session.PutFiles("$dir\*", $remotePath, $True, $transferOptions)
                }
            } else {
                Write-Host "⚠️  Missing directory: $dir" -ForegroundColor Yellow
            }
        }
        
        if (-not $DryRun) {
            $session.Close()
        }
        
        Write-Host "✅ FTP sync completed successfully!" -ForegroundColor Green
        
    } else {
        Write-Host "❌ WinSCP PowerShell module not found!" -ForegroundColor Red
        Write-Host "💡 Install with: Install-Module -Name WinSCP" -ForegroundColor Yellow
        Write-Host "💡 Or use alternative FTP client" -ForegroundColor Yellow
        
        # Alternative: Native PowerShell FTP (basic)
        Write-Host "`n🔄 Attempting native PowerShell FTP..." -ForegroundColor Yellow
        
        $ftpRequest = [System.Net.FtpWebRequest]::Create("ftp://$FtpHost/")
        $ftpRequest.Credentials = New-Object System.Net.NetworkCredential($FtpUser, $FtpPassword)
        $ftpRequest.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
        
        if (-not $DryRun) {
            $response = $ftpRequest.GetResponse()
            Write-Host "✅ FTP connection successful!" -ForegroundColor Green
            $response.Close()
        }
        
        Write-Host "⚠️  Native FTP upload not implemented. Use WinSCP or manual upload." -ForegroundColor Yellow
    }
    
} catch {
    Write-Host "❌ FTP sync failed: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

if ($DryRun) {
    Write-Host "`n✨ Dry run completed. Use without -DryRun to actually upload." -ForegroundColor Cyan
} else {
    Write-Host "`n🎉 Files uploaded to Netcup successfully!" -ForegroundColor Green
    Write-Host "🌐 Don't forget to:" -ForegroundColor Yellow
    Write-Host "   1. Configure .env on the server" -ForegroundColor White
    Write-Host "   2. Run: npm install" -ForegroundColor White
    Write-Host "   3. Run: node app.js" -ForegroundColor White
}
