#!/usr/bin/env pwsh
<#
.SYNOPSIS
One-click deployment to production environment
.DESCRIPTION
This script deploys the project to the live production environment using the Node.js CLI.
.PARAMETER Parts
Comma-separated list of parts to deploy (default: "default")
.PARAMETER Verbose
Enable verbose output
.PARAMETER WhatIf
Show what would be deployed without actually deploying
.EXAMPLE
.\deploy-live.ps1
.EXAMPLE
.\deploy-live.ps1 -Parts "admin,default"
.EXAMPLE
.\deploy-live.ps1 -Verbose -WhatIf
#>

param(
    [string]$Parts = "default",
    [switch]$Verbose,
    [switch]$WhatIf
)

# Script configuration
$Project = "web"
$Target = "production"

# Colors for output
$ErrorColor = "Red"
$SuccessColor = "Green"
$InfoColor = "Cyan"
$WarningColor = "Yellow"

function Write-ColorOutput {
    param([string]$Message, [string]$Color = "White")
    Write-Host $Message -ForegroundColor $Color
}

function Test-NodeInstallation {
    try {
        $nodeVersion = node --version 2>$null
        if ($LASTEXITCODE -eq 0) {
            Write-ColorOutput "✓ Node.js found: $nodeVersion" $SuccessColor
            return $true
        }
    } catch {
        Write-ColorOutput "✗ Node.js not found or not accessible" $ErrorColor
        return $false
    }
    return $false
}

function Test-DeploymentSystem {
    $cliScript = "deploy-cli.js"
    if (-not (Test-Path $cliScript)) {
        Write-ColorOutput "✗ CLI script not found: $cliScript" $ErrorColor
        return $false
    }
    
    $configFile = "deployment-config.yaml"
    if (-not (Test-Path $configFile)) {
        Write-ColorOutput "✗ Configuration file not found: $configFile" $ErrorColor
        return $false
    }
    
    Write-ColorOutput "✓ Deployment system validated" $SuccessColor
    return $true
}

function Install-Dependencies {
    Write-ColorOutput "Checking npm dependencies..." $InfoColor
    
    if (-not (Test-Path "package.json")) {
        Write-ColorOutput "✗ package.json not found" $ErrorColor
        return $false
    }
    
    if (-not (Test-Path "node_modules")) {
        Write-ColorOutput "Installing npm dependencies..." $WarningColor
        try {
            npm install
            if ($LASTEXITCODE -ne 0) {
                throw "npm install failed"
            }
            Write-ColorOutput "✓ Dependencies installed successfully" $SuccessColor
        } catch {
            Write-ColorOutput "✗ Failed to install dependencies: $_" $ErrorColor
            return $false
        }
    } else {
        Write-ColorOutput "✓ Dependencies already installed" $SuccessColor
    }
    
    return $true
}

function Test-FtpCredentials {
    if (-not $env:FTP_USER -or -not $env:FTP_PASSWORD) {
        Write-ColorOutput "WARNING: FTP credentials not found in environment variables" $WarningColor
        Write-ColorOutput "Please set FTP_USER and FTP_PASSWORD environment variables" $WarningColor
        Write-ColorOutput "Example:" $InfoColor
        Write-ColorOutput "  `$env:FTP_USER = 'your-ftp-username'" $InfoColor
        Write-ColorOutput "  `$env:FTP_PASSWORD = 'your-ftp-password'" $InfoColor
        return $false
    }
    
    Write-ColorOutput "✓ FTP credentials found in environment" $SuccessColor
    return $true
}

# Main execution
Write-ColorOutput "🚀 Starting Live Deployment" $InfoColor
Write-ColorOutput "Project: $Project | Target: $Target | Parts: $Parts" $InfoColor

# Pre-flight checks
Write-ColorOutput "`n📋 Pre-flight checks..." $InfoColor

if (-not (Test-NodeInstallation)) {
    Write-ColorOutput "Please install Node.js and try again." $ErrorColor
    exit 1
}

if (-not (Test-DeploymentSystem)) {
    Write-ColorOutput "Deployment system validation failed." $ErrorColor
    exit 1
}

if (-not (Install-Dependencies)) {
    Write-ColorOutput "Dependency installation failed." $ErrorColor
    exit 1
}

if (-not (Test-FtpCredentials)) {
    if (-not $WhatIf) {
        Write-ColorOutput "FTP credentials required for live deployment." $ErrorColor
        exit 1
    }
}

# Build deployment arguments
$deployArgs = @(
    "deploy-cli.js",
    "--project", $Project,
    "--target", $Target,
    "--parts", $Parts
)

if ($Verbose) {
    $deployArgs += "--verbose"
}

if ($WhatIf) {
    $deployArgs += "--dry-run"
    Write-ColorOutput "`n🔍 DRY RUN MODE - No actual deployment will occur" $WarningColor
}

# Execute deployment
Write-ColorOutput "`n🚀 Executing deployment..." $InfoColor
Write-ColorOutput "Command: node $($deployArgs -join ' ')" $InfoColor

try {
    & node @deployArgs
    $exitCode = $LASTEXITCODE
    
    if ($exitCode -eq 0) {
        if ($WhatIf) {
            Write-ColorOutput "`n✅ Dry run completed successfully!" $SuccessColor
            Write-ColorOutput "Ready for live deployment. Run without -WhatIf to deploy." $InfoColor
        } else {
            Write-ColorOutput "`n🎉 Live deployment completed successfully!" $SuccessColor
        }
    } else {
        Write-ColorOutput "`n❌ Deployment failed with exit code: $exitCode" $ErrorColor
        exit $exitCode
    }
} catch {
    Write-ColorOutput "`n❌ Deployment error: $_" $ErrorColor
    exit 1
}

Write-ColorOutput "`n✨ Done!" $SuccessColor
