param(
  [Parameter(Mandatory = $true)]
  [ValidateSet('deploy','status','test','build','cleanup','purge-admin','autodeploy')]
  [string]$Action,

  [ValidateSet('production','local')]
  [string]$Environment = 'production',

  [string]$FtpUser,
  [string]$FtpPassword
)

# IMPORTANT: Never deploy Node tooling into /httpdocs; admin files must live under /httpdocs/admin
Write-Host "NOTICE: Enforcing remote admin path = /httpdocs/admin" -ForegroundColor Cyan
$ErrorActionPreference = 'Stop'

function Write-Info($msg){ Write-Host "[INFO] $msg" -ForegroundColor Cyan }
function Write-Ok($msg){ Write-Host "[OK]   $msg" -ForegroundColor Green }
function Write-Warn($msg){ Write-Host "[WARN] $msg" -ForegroundColor Yellow }
function Write-Err($msg){ Write-Host "[ERR]  $msg" -ForegroundColor Red }

$RepoRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$AutoDir  = Join-Path $RepoRoot 'AUTO-DEPLOY-MK'

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
    Write-Warn "No saved FTP credentials at $credFile. Use the 'Setup FTP Credentials' task or pass -FtpUser/-FtpPassword."
    return
  }
  try {
    $data = Import-Clixml -Path $credFile
    if ((-not $data.Username) -or (-not $data.SecurePassword)) { throw 'Invalid credential file.' }
    $plainPtr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($data.SecurePassword)
    try {
      $plain = [Runtime.InteropServices.Marshal]::PtrToStringUni($plainPtr)
    } finally {
      if ($plainPtr -ne [IntPtr]::Zero) { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($plainPtr) }
    }
    $env:FTP_USER = $data.Username
    $env:FTP_PASSWORD = $plain
    Write-Ok "Loaded FTP credentials for user '$($data.Username)'"
  } catch {
    throw "Failed to load credentials: $($_.Exception.Message)"
  }
}

function Invoke-Http {
  param(
    [string]$Url,
    [int]$TimeoutSec = 15,
    [int]$Retries = 1
  )
  $attempt = 0
  $headers = @{ 'Cache-Control' = 'no-cache' }
  do {
    try {
      return Invoke-WebRequest -Uri $Url -UseBasicParsing -Headers $headers -TimeoutSec $TimeoutSec
    } catch {
      $attempt++
      if ($attempt -gt $Retries) { throw }
      Start-Sleep -Seconds 2
    }
  } while ($true)
}

switch ($Action) {
  'status' {
    Write-Info 'Checking Green Glass Admin Center status (simple-deploy.js status)'
    try {
      Push-Location $RepoRoot
      & node 'simple-deploy.js' status
      exit 0
    } catch {
      Write-Err $_.Exception.Message; exit 1
    } finally { Pop-Location }
  }

  'build' {
    $webDir = Join-Path $RepoRoot 'web'
    if (Test-Path $webDir) {
      Write-Info 'Building web frontend'
      try {
        Push-Location $webDir
        if (Test-Path 'package.json') {
          npm ci | Out-Host
          npm run build | Out-Host
        }
        Write-Ok 'Build complete'
        exit 0
      } catch {
        Write-Err $_.Exception.Message; exit 1
      } finally { Pop-Location }
    } else {
      Write-Warn "No 'web' directory found. Skipping build."
      exit 0
    }
  }

  'test' {
    Write-Info 'Testing connectivity (no changes)'
    try {
      Push-Location $RepoRoot
      & node 'simple-deploy.js' status
      exit 0
    } catch {
      Write-Err $_.Exception.Message; exit 1
    } finally { Pop-Location }
  }

  'cleanup' {
    Write-Info 'Cleanup not implemented in this script'
    exit 0
  }

  'purge-admin' {
    Write-Info 'Purge not implemented in this script'
    exit 0
  }

  'autodeploy' {
    Write-Info 'Running auto-deploy via AUTO-DEPLOY-MK if available'
    Import-FtpCredentials
    $cli = Join-Path $AutoDir 'mk_deploy-cli.js'
    if (-not (Test-Path $cli)) { Write-Warn "mk_deploy-cli.js not found at $cli"; exit 0 }
    try {
      Push-Location $RepoRoot
      & node $cli --project web --target $Environment --parts admin --verbose
      exit 0
    } catch {
      Write-Err $_.Exception.Message; exit 1
    } finally { Pop-Location }
  }

  'deploy' {
    Write-Info "Deploying Admin Center (environment: $Environment)"
    # Pre-flight: syntax checks
    try {
      $checkScript = Join-Path $RepoRoot 'scripts/check-syntax.ps1'
      if (Test-Path $checkScript) {
        Write-Info 'Running syntax checks (PHP/PS/JS/JSON)'
        & powershell -NoProfile -ExecutionPolicy Bypass -File $checkScript
        if ($LASTEXITCODE -ne 0) { throw "Syntax checks failed (exit $LASTEXITCODE). Aborting deploy." }
        Write-Ok 'Syntax checks passed'
      } else {
        Write-Warn 'check-syntax.ps1 not found. Skipping pre-deploy checks.'
      }
    } catch {
      Write-Err $_.Exception.Message; exit 1
    }

    Import-FtpCredentials
    try {
      Push-Location $RepoRoot
      & node 'simple-deploy.js' deploy
      $exit = $LASTEXITCODE
      if ($exit -ne 0) { throw "simple-deploy.js exited with code $exit" }

      Write-Ok 'Deployment finished'

      Write-Info 'Verifying endpoints...'
      try {
        $admin = Invoke-Http -Url 'https://11seconds.de/admin/login.php' -Retries 1
        Write-Ok "Admin Login: HTTP $($admin.StatusCode)"
      } catch { Write-Warn "Admin login check failed: $($_.Exception.Message)" }
      try {
        $api = Invoke-Http -Url 'https://11seconds.de/admin/api.php?action=test' -Retries 1
        Write-Ok "API test: HTTP $($api.StatusCode)"
      } catch { Write-Warn "API test failed: $($_.Exception.Message)" }
      try {
        $health = Invoke-Http -Url 'https://11seconds.de/admin/api.php?action=health' -Retries 1
        Write-Ok "API health: HTTP $($health.StatusCode)"
      } catch { Write-Warn "API health failed: $($_.Exception.Message)" }
      try {
        $lite = Invoke-Http -Url 'https://11seconds.de/admin/api.php?action=integrity-lite' -Retries 1
        Write-Ok "API integrity-lite: HTTP $($lite.StatusCode)"
      } catch { Write-Warn "API integrity-lite failed: $($_.Exception.Message)" }

      exit 0
    } catch {
      Write-Err $_.Exception.Message; exit 1
    } finally { Pop-Location }
  }

  default {
    Write-Err "Unknown action: $Action"; exit 2
  }
}
