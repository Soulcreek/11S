# One-click live deployment for 11Seconds
# This script deploys the web project to production with full verification

param(
    [switch]$Force,
    [switch]$Verbose
)

$ErrorActionPreference = "Stop"

Write-Host "🚀 11Seconds Live Deployment" -ForegroundColor Magenta
Write-Host "==============================" -ForegroundColor Magenta

# Change to deployment system directory
$deploymentDir = Join-Path $PSScriptRoot "_deployment-system"
if (-not (Test-Path $deploymentDir)) {
    Write-Error "Deployment system directory not found: $deploymentDir"
    exit 1
}

Push-Location $deploymentDir

try {
    # Install dependencies if needed
    if (-not (Test-Path "node_modules")) {
        Write-Host "Installing deployment dependencies..." -ForegroundColor Yellow
        npm install
    }

    # Build arguments
    $deployArgs = @(
        "deploy-cli.js",
        "--project", "web",
        "--target", "production", 
        "--parts", "default",
        "--build"
    )
    
    if ($Force) { $deployArgs += "--force" }
    if ($Verbose) { $deployArgs += "--verbose" }
    
    # Run deployment
    Write-Host "Starting live deployment..." -ForegroundColor Green
    node @deployArgs
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "`n✅ Live deployment completed successfully!" -ForegroundColor Green
        Write-Host "Your site should be updated at: https://11seconds.de" -ForegroundColor Cyan
    } else {
        Write-Error "Deployment failed with exit code: $LASTEXITCODE"
        exit $LASTEXITCODE
    }
    
} catch {
    Write-Error "Deployment error: $($_.Exception.Message)"
    exit 1
} finally {
    Pop-Location
}

# Keep window open if run directly (not from terminal)
if ($Host.Name -eq "ConsoleHost" -and [Environment]::UserInteractive) {
    Write-Host "`nPress any key to close..." -ForegroundColor Gray
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
}
