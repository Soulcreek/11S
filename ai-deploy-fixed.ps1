param(
    [Parameter(Mandatory = $true)][ValidateSet('deploy', 'status', 'test', 'build', 'cleanup')]
    [string]$Action,
    [ValidateSet('production', 'local')]
    [string]$Environment = 'production',
    [switch]$VerboseOutput,
    [string]$FtpUser,
    [string]$FtpPassword
)

$ErrorActionPreference = 'Stop'

function Write-Info($msg) { Write-Host "[INFO] $msg" -ForegroundColor Cyan }
function Write-Ok($msg) { Write-Host "[OK]   $msg" -ForegroundColor Green }
function Write-Warn($msg) { Write-Host "[WARN] $msg" -ForegroundColor Yellow }
function Write-Err($msg) { Write-Host "[ERR]  $msg" -ForegroundColor Red }

# Location helpers
$RepoRoot = Split-Path -Parent $MyInvocation.MyCommand.Path

function Import-FtpCredentials {
    if ($FtpUser -and $FtpPassword) {
        $env:FTP_USER = $FtpUser
        $env:FTP_PASSWORD = $FtpPassword
        Write-Ok "Using FTP credentials from parameters"
        return
    }

    if ($env:FTP_USER -and $env:FTP_PASSWORD) {
        Write-Ok "Using FTP credentials from environment"
        return
    }

    $storeDir = Join-Path $env:USERPROFILE '.11s'
    $credFile = Join-Path $storeDir 'ftp-credentials.xml'
    if (-not (Test-Path $credFile)) {
        throw "No credentials found. Run 'Setup FTP Credentials' task or set FTP_USER/FTP_PASSWORD env vars."
    }
    try {
        $data = Import-Clixml -Path $credFile
        if ((-not $data.Username) -or (-not $data.SecurePassword)) { 
            throw 'Invalid credential file.' 
        }
        $plain = [Runtime.InteropServices.Marshal]::PtrToStringUni([Runtime.InteropServices.Marshal]::SecureStringToBSTR($data.SecurePassword))
        $env:FTP_USER = $data.Username
        $env:FTP_PASSWORD = $plain
        Write-Ok "Loaded FTP credentials for user '$($data.Username)'"
    }
    catch {
        throw "Failed to load credentials: $($_.Exception.Message)"
    }
}

switch ($Action) {
    'status' {
        Write-Info 'Checking Green Glass Admin Center status'
        try {
            & node 'simple-deploy.js' status
            exit 0
        }
        catch {
            Write-Err $_.Exception.Message
            exit 1
        }
    }
  
    'deploy' {
        Write-Info "🚀 Deploying NEW Green Glass Admin Center (environment: $Environment)"
        Import-FtpCredentials
    
        try {
            Write-Info 'Starting deployment with simple-deploy.js'
            Push-Location $RepoRoot
            & node 'simple-deploy.js' deploy
            $exit = $LASTEXITCODE
            if ($exit -ne 0) { 
                throw "Simple deployment exited with code $exit" 
            }
            Write-Ok '🎉 Green Glass Admin Center deployment completed!'
      
            # Additional verification
            Write-Info 'Additional verification...'
            Start-Sleep -Seconds 3
      
            try {
                $admin = Invoke-WebRequest -Uri 'https://11seconds.de/admin/login.php' -UseBasicParsing -Headers @{ 'Cache-Control' = 'no-cache' } -TimeoutSec 15
                Write-Ok "✅ Admin Login: HTTP $($admin.StatusCode)"
        
                if ($admin.Content -match 'Green Glass') {
                    Write-Ok "🎉 NEW GREEN GLASS DESIGN IS LIVE!"
                }
                else {
                    Write-Warn "⚠️  Green Glass design not detected - may need cache clear"
                }
            }
            catch {
                Write-Warn "Could not verify admin page: $($_.Exception.Message)"
            }
      
            exit 0
        }
        catch {
            Write-Err $_.Exception.Message
            exit 1
        }
        finally {
            Pop-Location
        }
    }
  
    'test' {
        Write-Info 'Testing FTP connectivity'
        Import-FtpCredentials
        try {
            # Simple FTP test using Node.js
            $testScript = @"
const ftp = require('basic-ftp');
async function test() {
  const client = new ftp.Client();
  try {
    await client.access({
      host: 'ftp.11seconds.de',
      user: process.env.FTP_USER,
      password: process.env.FTP_PASSWORD
    });
    console.log('✅ FTP connection successful');
    client.close();
  } catch (error) {
    console.error('❌ FTP connection failed:', error.message);
    process.exit(1);
  }
}
test();
"@
            $testScript | Out-File -FilePath 'ftp-test.js' -Encoding UTF8
            & node 'ftp-test.js'
            Remove-Item 'ftp-test.js' -Force
            Write-Ok 'FTP test completed'
            exit 0
        }
        catch {
            Write-Err $_.Exception.Message
            exit 1
        }
    }
  
    'build' {
        Write-Info 'No build step needed for new admin system'
        Write-Ok 'Admin files are ready for deployment'
        exit 0
    }
  
    'cleanup' {
        Write-Info 'Cleaning up temporary files'
        try {
            @('ftp-test.js', 'temp-deploy.log') | ForEach-Object {
                if (Test-Path $_) {
                    Remove-Item $_ -Force
                    Write-Ok "Removed $_"
                }
            }
            Write-Ok 'Cleanup completed'
            exit 0
        }
        catch {
            Write-Err $_.Exception.Message
            exit 1
        }
    }
}
