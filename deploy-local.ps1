# One-click local test deployment for 11Seconds
# This script deploys the web project locally for testing

param(
    [switch]$Force,
    [switch]$Verbose,
    [int]$Port = 5000
)

$ErrorActionPreference = "Stop"

Write-Host "🧪 11Seconds Local Test Deployment" -ForegroundColor Cyan
Write-Host "====================================" -ForegroundColor Cyan

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

    # Build arguments for local deployment
    $deployArgs = @(
        "deploy-cli.js",
        "--project", "web",
        "--target", "local", 
        "--parts", "default",
        "--build"
    )
    
    if ($Force) { $deployArgs += "--force" }
    if ($Verbose) { $deployArgs += "--verbose" }
    
    # Run local deployment
    Write-Host "Starting local test deployment..." -ForegroundColor Green
    node @deployArgs
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "`n✅ Local deployment completed successfully!" -ForegroundColor Green
        
        # Start local server for testing
        $localPath = Join-Path (Get-Location) "..\httpdocs-local"
        if (Test-Path $localPath) {
            Write-Host "Starting local test server on port $Port..." -ForegroundColor Yellow
            
            # Check if npx is available for serve
            try {
                $serveInstalled = npx --yes serve --version 2>$null
                if ($serveInstalled) {
                    Write-Host "Local server running at: http://localhost:$Port" -ForegroundColor Cyan
                    Write-Host "Press Ctrl+C to stop the server" -ForegroundColor Gray
                    npx --yes serve -s $localPath -p $Port
                } else {
                    Write-Warning "Could not start local server. Install 'serve' globally: npm install -g serve"
                    Write-Host "Files deployed to: $localPath" -ForegroundColor Cyan
                }
            } catch {
                Write-Warning "Could not start local server: $($_.Exception.Message)"
                Write-Host "Files deployed to: $localPath" -ForegroundColor Cyan
            }
        } else {
            Write-Warning "Local deployment path not found: $localPath"
        }
    } else {
        Write-Error "Local deployment failed with exit code: $LASTEXITCODE"
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
