param(
  [Parameter(Mandatory=$true)]
  [ValidateSet('deploy','autodeploy','status','test')]
  [string]$Action,

  [ValidateSet('production','local')]
  [string]$Environment = 'production',

  [string]$FtpUser,
  [string]$FtpPassword
)

Write-Host "NOTICE: This script enforces remote admin path = /httpdocs/admin" -ForegroundColor Cyan

$ErrorActionPreference = 'Stop'

function Import-FtpCredentials {
  if ($FtpUser -and $FtpPassword) {
    $env:FTP_USER = $FtpUser
    $env:FTP_PASSWORD = $FtpPassword
    Write-Host "[OK] Using FTP credentials from parameters"
    return
  }
  if ($env:FTP_USER -and $env:FTP_PASSWORD) {
    Write-Host "[OK] Using FTP credentials from environment"
    return
  }
  Write-Host "[WARN] No FTP credentials found in parameters or environment"
}

switch ($Action) {
  'status' {
    Write-Host 'Running status check (simple-deploy.js status)'
    node simple-deploy.js status
    exit $LASTEXITCODE
  }

  'test' {
    Write-Host 'Running connectivity test (no upload)'
    Import-FtpCredentials
    node AUTO-DEPLOY-MK/mk_deploy-cli.js --project web --target production --dry-run --verbose
    exit $LASTEXITCODE
  }

  'autodeploy' {
    Write-Host 'Running auto-deploy (AUTO-DEPLOY-MK)'
    Import-FtpCredentials
    node AUTO-DEPLOY-MK/mk_deploy-cli.js --project web --target production --parts admin --verbose
    exit $LASTEXITCODE
  }

  'deploy' {
    Write-Host 'Running simple deploy (simple-deploy.js deploy)'
    Import-FtpCredentials
    node simple-deploy.js deploy
    exit $LASTEXITCODE
  }
}
param(
  [Parameter(Mandatory = $true)]
  [ValidateSet('deploy','autodeploy','status','test','build')]
  [string]$Action,

  [ValidateSet('production','local')]
  [string]$Environment = 'production',

  [string]$FtpUser,
  [string]$FtpPassword
)

Write-Host "NOTICE: ai-deploy wrapper - enforcing admin path /httpdocs/admin" -ForegroundColor Cyan

function Ensure-Credentials {
  if ($FtpUser -and $FtpPassword) { $env:FTP_USER = $FtpUser; $env:FTP_PASSWORD = $FtpPassword; return }
  if (-not $env:FTP_USER -or -not $env:FTP_PASSWORD) {
    Write-Host "WARN: FTP credentials missing. Set environment variables or pass -FtpUser/-FtpPassword" -ForegroundColor Yellow
  } else {
    Write-Host "INFO: FTP credentials found in environment" -ForegroundColor Green
  }
}

switch ($Action) {
  'status' {
    Write-Host "INFO: Running status check via simple-deploy.js"
    node simple-deploy.js status
    exit $LASTEXITCODE
  }

  'test' {
    Write-Host "INFO: Running connectivity test (dry-run)"
    Ensure-Credentials
    node AUTO-DEPLOY-MK/tmp-ftp-check.js
    exit $LASTEXITCODE
  }

  'build' {
    Write-Host "INFO: Building web project"
    Push-Location web
    npm ci
    npm run build
    Pop-Location
    exit $LASTEXITCODE
  }

  'deploy' {
    Write-Host "INFO: Running normal deploy (uses simple-deploy.js -> enforces /httpdocs/admin)"
    Ensure-Credentials
    node simple-deploy.js deploy
    exit $LASTEXITCODE
  }

  'autodeploy' {
    Write-Host "INFO: Running auto-deploy (mk_deploy-cli.js) - enforces /httpdocs/admin via config"
    Ensure-Credentials
    node AUTO-DEPLOY-MK/mk_deploy-cli.js --project web --target production --parts admin,app-httpdocs --verbose
    exit $LASTEXITCODE
  }

  default {
    Write-Host "Unknown action: $Action" -ForegroundColor Red
    exit 2
  }
}
param(
  [Parameter(Mandatory = $true)][ValidateSet('deploy','status','test','build','cleanup','purge-admin')]
  [string]$Action,

  [ValidateSet('production','local')]
  [string]$Environment = 'production',

  [switch]$VerboseOutput,

  [string]$FtpUser,
  [string]$FtpPassword
)

# DEPLOYMENT POLICY NOTE
# All admin files MUST be deployed to /httpdocs/admin on the remote FTP server.
# This script and the repository's deployment helpers enforce that path.
# Do NOT upload package.json into /httpdocs as it may trigger Node/Passenger runtime.
Write-Host "NOTICE: Enforcing remote admin path = /httpdocs/admin" -ForegroundColor Cyan

$ErrorActionPreference = 'Stop'

function Write-Info($msg){ Write-Host "[INFO] $msg" -ForegroundColor Cyan }
function Write-Ok($msg){ Write-Host "[OK]   $msg" -ForegroundColor Green }
function Write-Warn($msg){ Write-Host "[WARN] $msg" -ForegroundColor Yellow }
function Write-Err($msg){ Write-Host "[ERR]  $msg" -ForegroundColor Red }

# Location helpers
$RepoRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$AutoDir  = Join-Path $RepoRoot '_archive/AUTO-DEPLOY-MK'

# Ensure deploy CLI can save manifests
$Manifests = Join-Path $AutoDir 'manifests'
if (-not (Test-Path $Manifests)) { New-Item -ItemType Directory -Path $Manifests | Out-Null }

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
    throw "No credentials found. Run 'Setup FTP Credentials' task or pass -FtpUser and -FtpPassword."
  }
  try {
    $data = Import-Clixml -Path $credFile
    if ((-not $data.Username) -or (-not $data.SecurePassword)) { throw 'Invalid credential file.' }
    $plain = [Runtime.InteropServices.Marshal]::PtrToStringUni([Runtime.InteropServices.Marshal]::SecureStringToBSTR($data.SecurePassword))
    $env:FTP_USER = $data.Username
    $env:FTP_PASSWORD = $plain
    Write-Ok "Loaded FTP credentials for user '$($data.Username)'"
  } catch {
    throw "Failed to load credentials: $($_.Exception.Message)"
  }
}

switch ($Action) {
  'status' {
    Write-Info 'Checking Green Glass Admin Center status (https://11seconds.de)'
    try {
      & node 'simple-deploy.js' status
      exit 0
    } catch {
      Write-Err $_.Exception.Message; exit 1
    }
  }

  'build' {
    Write-Info 'Building React app (web)'
    Push-Location (Join-Path $RepoRoot 'web')
    try {
      if ((Test-Path 'package-lock.json') -or (Test-Path 'package.json')) { npm ci | Out-Host }
      npm run build | Out-Host
      Write-Ok 'Build complete'
      exit 0
    } catch {
      Write-Err $_.Exception.Message; exit 1
    } finally {
      Pop-Location
    }
  }

  'test' {
    Write-Info 'Testing FTP connectivity to ftp.11seconds.de'
    Import-FtpCredentials
    try {
      $checkScript = Join-Path $AutoDir 'tmp-ftp-check.js'
      node $checkScript | Out-Host
      Write-Ok 'FTP connectivity test attempted (see output above)'
      exit 0
    } catch {
      Write-Err $_.Exception.Message; exit 1
    }
  }

  'cleanup' {
    Write-Info 'Cleaning up diagnostic files on server (/httpdocs/admin/phpinfo.php, /httpdocs/admin/pdo-check.php)'
    Import-FtpCredentials
    try {
      $script = Join-Path $AutoDir 'cleanup-admin-diagnostics.js'
      Push-Location $RepoRoot
      & node $script
      $exit = $LASTEXITCODE
      if ($exit -ne 0) { throw "Cleanup script exited with code $exit" }
      Write-Ok 'Cleanup completed'
      exit 0
    } catch {
      Write-Err $_.Exception.Message; exit 1
    } finally {
      Pop-Location
    }
  }

  'purge-admin' {
    Write-Info 'Purging remote /admin (preserving /admin/data and .htaccess)'
    Import-FtpCredentials
    try {
      $script = Join-Path $AutoDir 'purge-admin.js'
      Push-Location $RepoRoot
      & node $script
      $exit = $LASTEXITCODE
      if ($exit -ne 0) { throw "Purge script exited with code $exit" }
      Write-Ok 'Purge completed'
      exit 0
    } catch {
      Write-Err $_.Exception.Message; exit 1
    } finally {
      Pop-Location
    }
  }

  'deploy' {
    Write-Info "Deploying NEW Green Glass Admin Center (environment: $Environment)"
    Import-FtpCredentials
    try {
      Write-Info 'Starting deployment with simple-deploy.js'
      Push-Location $RepoRoot
      & node 'simple-deploy.js' deploy
      $exit = $LASTEXITCODE
      if ($exit -ne 0) { throw "Simple deployment exited with code $exit" }
      Write-Ok 'Green Glass Admin Center deployment completed!'

      Write-Info 'Verifying deployment...'
      try {
        $root = Invoke-WebRequest -Uri 'https://11seconds.de/' -UseBasicParsing -Headers @{ 'Cache-Control'='no-cache' } -TimeoutSec 15
        Write-Ok "Root: HTTP $($root.StatusCode)"
      } catch {
        Write-Warn "Root fetch failed: $($_.Exception.Message)"
      }
      try {
        $admin = Invoke-WebRequest -Uri 'https://11seconds.de/admin/login.php' -UseBasicParsing -Headers @{ 'Cache-Control'='no-cache' } -TimeoutSec 15
        Write-Ok "Admin Login: HTTP $($admin.StatusCode)"
        if ($admin.Content -match 'Green Glass|11Seconds Admin') {
          Write-Ok "NEW GREEN GLASS DESIGN IS LIVE!"
        } else {
          Write-Warn "Old design still showing - may need cache clear"
        }
      } catch {
        Write-Warn "Admin login fetch failed: $($_.Exception.Message)"
      }

      exit 0
    } catch {
      Write-Err $_.Exception.Message; exit 1
    } finally {
      Pop-Location
    }
  }
}
param(
  [Parameter(Mandatory = $true)][ValidateSet('deploy', 'status', 'test', 'build', 'cleanup', 'purge-admin')]
  [string]$Action,
  [ValidateSet('production', 'local')]
  [string]$Environment = 'production',
  [switch]$VerboseOutput,
  [string]$FtpUser,
  [string]$FtpPassword
)

# DEPLOYMENT POLICY NOTE
# All admin files MUST be deployed to /httpdocs/admin on the remote FTP server.
# This script and the repository's deployment helpers enforce that path.
# Do NOT upload package.json into /httpdocs as it may trigger Node/Passenger runtime.
Write-Host "NOTICE: Enforcing remote admin path = /httpdocs/admin" -ForegroundColor Cyan

$ErrorActionPreference = 'Stop'

function Write-Info($msg){ Write-Host "[INFO] $msg" -ForegroundColor Cyan }
function Write-Ok($msg){ Write-Host "[OK]   $msg" -ForegroundColor Green }
function Write-Warn($msg){ Write-Host "[WARN] $msg" -ForegroundColor Yellow }
function Write-Err($msg){ Write-Host "[ERR]  $msg" -ForegroundColor Red }

# Location helpers
$RepoRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$AutoDir  = Join-Path $RepoRoot '_archive/AUTO-DEPLOY-MK'

# Ensure deploy CLI can save manifests
$Manifests = Join-Path $AutoDir 'manifests'
if (-not (Test-Path $Manifests)) { New-Item -ItemType Directory -Path $Manifests | Out-Null }

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
    throw "No credentials found. Run 'Setup FTP Credentials' task or pass -FtpUser and -FtpPassword."
  param(
      param(
        [Parameter(Mandatory = $true)][ValidateSet('deploy','status','test','build','cleanup','purge-admin')]
        [string]$Action,

        [ValidateSet('production','local')]
        [string]$Environment = 'production',

        [switch]$VerboseOutput,

        [string]$FtpUser,
        [string]$FtpPassword
      )

      # DEPLOYMENT POLICY NOTE
      # All admin files MUST be deployed to /httpdocs/admin on the remote FTP server.
      # This script and the repository's deployment helpers enforce that path.
      # Do NOT upload package.json into /httpdocs as it may trigger Node/Passenger runtime.
      Write-Host "NOTICE: Enforcing remote admin path = /httpdocs/admin" -ForegroundColor Cyan

      $ErrorActionPreference = 'Stop'

      function Write-Info($msg){ Write-Host "[INFO] $msg" -ForegroundColor Cyan }
      function Write-Ok($msg){ Write-Host "[OK]   $msg" -ForegroundColor Green }
      function Write-Warn($msg){ Write-Host "[WARN] $msg" -ForegroundColor Yellow }
      function Write-Err($msg){ Write-Host "[ERR]  $msg" -ForegroundColor Red }

      # Location helpers
      $RepoRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
      $AutoDir  = Join-Path $RepoRoot '_archive/AUTO-DEPLOY-MK'

      # Ensure deploy CLI can save manifests
      $Manifests = Join-Path $AutoDir 'manifests'
      if (-not (Test-Path $Manifests)) { New-Item -ItemType Directory -Path $Manifests | Out-Null }

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
          throw "No credentials found. Run 'Setup FTP Credentials' task or pass -FtpUser and -FtpPassword."
        }
        try {
          $data = Import-Clixml -Path $credFile
          if ((-not $data.Username) -or (-not $data.SecurePassword)) { throw 'Invalid credential file.' }
          $plain = [Runtime.InteropServices.Marshal]::PtrToStringUni([Runtime.InteropServices.Marshal]::SecureStringToBSTR($data.SecurePassword))
          $env:FTP_USER = $data.Username
          $env:FTP_PASSWORD = $plain
          Write-Ok "Loaded FTP credentials for user '$($data.Username)'"
        } catch {
          throw "Failed to load credentials: $($_.Exception.Message)"
        }
      }

      switch ($Action) {
        'status' {
          Write-Info 'Checking Green Glass Admin Center status (https://11seconds.de)'
          try {
            & node 'simple-deploy.js' status
            exit 0
          } catch {
            Write-Err $_.Exception.Message; exit 1
          }
        }

        'build' {
          Write-Info 'Building React app (web)'
          Push-Location (Join-Path $RepoRoot 'web')
          try {
            if ((Test-Path 'package-lock.json') -or (Test-Path 'package.json')) { npm ci | Out-Host }
            npm run build | Out-Host
            Write-Ok 'Build complete'
            exit 0
          } catch {
            Write-Err $_.Exception.Message; exit 1
          } finally {
            Pop-Location
          }
        }

        'test' {
          Write-Info 'Testing FTP connectivity to ftp.11seconds.de'
          Import-FtpCredentials
          try {
            $checkScript = Join-Path $AutoDir 'tmp-ftp-check.js'
            node $checkScript | Out-Host
            Write-Ok 'FTP connectivity test attempted (see output above)'
            exit 0
          } catch {
            Write-Err $_.Exception.Message; exit 1
          }
        }

        'cleanup' {
          Write-Info 'Cleaning up diagnostic files on server (/httpdocs/admin/phpinfo.php, /httpdocs/admin/pdo-check.php)'
          Import-FtpCredentials
          try {
            $script = Join-Path $AutoDir 'cleanup-admin-diagnostics.js'
            Push-Location $RepoRoot
            & node $script
            $exit = $LASTEXITCODE
            if ($exit -ne 0) { throw "Cleanup script exited with code $exit" }
            Write-Ok 'Cleanup completed'
            exit 0
          } catch {
            Write-Err $_.Exception.Message; exit 1
          } finally {
            Pop-Location
          }
        }

        'purge-admin' {
          Write-Info 'Purging remote /admin (preserving /admin/data and .htaccess)'
          Import-FtpCredentials
          try {
            $script = Join-Path $AutoDir 'purge-admin.js'
            Push-Location $RepoRoot
            & node $script
            $exit = $LASTEXITCODE
            if ($exit -ne 0) { throw "Purge script exited with code $exit" }
            Write-Ok 'Purge completed'
            exit 0
          } catch {
            Write-Err $_.Exception.Message; exit 1
          } finally {
            Pop-Location
          }
        }

        'deploy' {
          Write-Info "Deploying NEW Green Glass Admin Center (environment: $Environment)"
          Import-FtpCredentials
          try {
            Write-Info 'Starting deployment with simple-deploy.js'
            Push-Location $RepoRoot
            & node 'simple-deploy.js' deploy
            $exit = $LASTEXITCODE
            if ($exit -ne 0) { throw "Simple deployment exited with code $exit" }
            Write-Ok 'Green Glass Admin Center deployment completed!'

            Write-Info 'Verifying deployment...'
            try {
              $root = Invoke-WebRequest -Uri 'https://11seconds.de/' -UseBasicParsing -Headers @{ 'Cache-Control'='no-cache' } -TimeoutSec 15
              Write-Ok "Root: HTTP $($root.StatusCode)"
            } catch {
              Write-Warn "Root fetch failed: $($_.Exception.Message)"
            }
            try {
              $admin = Invoke-WebRequest -Uri 'https://11seconds.de/admin/login.php' -UseBasicParsing -Headers @{ 'Cache-Control'='no-cache' } -TimeoutSec 15
              Write-Ok "Admin Login: HTTP $($admin.StatusCode)"
              if ($admin.Content -match 'Green Glass|11Seconds Admin') {
                Write-Ok "NEW GREEN GLASS DESIGN IS LIVE!"
              } else {
                Write-Warn "Old design still showing - may need cache clear"
              }
            } catch {
              Write-Warn "Admin login fetch failed: $($_.Exception.Message)"
            }

            exit 0
          } catch {
            Write-Err $_.Exception.Message; exit 1
          } finally {
            Pop-Location
          }
        }
      }
