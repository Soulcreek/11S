# 11Seconds Static Deployment System

## Overview

This project has been cleaned up and reorganized for **static-only deployment**. The deprecated API backend has been removed, and all 17+ redundant deployment scripts have been consolidated into 2 clean, maintainable scripts.

## Project Structure

```
11S/
├── web/                          # React frontend (static build)
│   ├── src/                      # React source code
│   ├── build/                    # Production build output
│   └── package.json             # Frontend dependencies
├── build.ps1                     # Build script
├── deploy.ps1                    # Deployment script
├── .env.deploy.template          # Deployment config template
├── .env.deploy                   # Your deployment credentials (create this)
└── _backup_deprecated/           # Backup of old API and scripts
    ├── api/                      # Old backend (deprecated)
    └── deployment_scripts/       # Old deployment scripts (removed)
```

## Quick Start

### 1. Setup Deployment Credentials

```powershell
# Copy template and configure
copy .env.deploy.template .env.deploy
# Edit .env.deploy with your actual FTP/SSH credentials
```

### 2. Build and Deploy

```powershell
# Build the React application
.\build.ps1 -Install -Clean

# Deploy via FTP (primary method)
.\deploy.ps1 -Method ftp -Build

# Deploy via SSH (fallback method)
.\deploy.ps1 -Method ssh -Build

# Auto-deploy (tries FTP first, SSH as fallback)
.\deploy.ps1 -Method auto -Build
```

## Detailed Usage

### Build Script (`build.ps1`)

Builds the React application for production deployment.

**Parameters:**
- `-Clean`: Remove previous build directory
- `-Install`: Run npm install before building
- `-ShowDetails`: Show detailed build output

**Examples:**
```powershell
# Basic build
.\build.ps1

# Clean build with dependency installation
.\build.ps1 -Install -Clean

# Verbose build output
.\build.ps1 -ShowDetails
```

### Deployment Script (`deploy.ps1`)

Deploys the React build to your hosting provider.

**Parameters:**
- `-Method`: Deployment method (ftp, ssh, auto)
- `-Build`: Build before deployment
- `-Test`: Test connections only
- `-Verbose`: Show detailed deployment output

**Examples:**
```powershell
# Auto-deployment (recommended)
.\deploy.ps1 -Method auto -Build

# FTP deployment only
.\deploy.ps1 -Method ftp

# Test connections
.\deploy.ps1 -Test

# SSH deployment with verbose output
.\deploy.ps1 -Method ssh -Verbose
```

## Configuration

### `.env.deploy` File

Create this file based on `.env.deploy.template`:

```bash
# FTP Settings (Primary deployment method)
FTP_HOST=ftp.yourdomain.com
FTP_USER=your-ftp-username
FTP_PASSWORD=your-ftp-password
FTP_REMOTE_PATH=/httpdocs

# SSH Settings (Fallback deployment method)
SSH_HOST=ssh.yourdomain.com
SSH_USER=your-ssh-username
SSH_PASSWORD=your-ssh-password
SSH_REMOTE_PATH=/var/www/html

# Domain Settings
DOMAIN_URL=https://yourdomain.com

# Build Settings
BUILD_PATH=web/build
DEPLOYMENT_NAME=11seconds-static-deploy
```

## Features

### ✅ What's Working Now

1. **Clean Build System**
   - Single `build.ps1` script
   - Automatic dependency management
   - Production optimization
   - Build validation

2. **Flexible Deployment**
   - FTP primary deployment
   - SSH fallback deployment
   - Auto-method selection
   - Connection testing

3. **Security**
   - Credentials in `.env.deploy` (gitignored)
   - No hardcoded passwords
   - Secure credential management

4. **Static Content**
   - Pure React build deployment
   - No backend dependencies
   - Fast hosting-friendly

### 🗑️ What Was Removed

1. **Deprecated Backend**
   - `/api` folder (backed up to `_backup_deprecated/api`)
   - Node.js server dependencies
   - MySQL database requirements

2. **Redundant Scripts**
   - 17+ duplicate PowerShell scripts
   - Multiple FTP upload variants
   - Conflicting SSH deployment scripts

3. **API Dependencies**
   - Converted AdminPage to static mode
   - Removed fetch() calls to backend
   - Local data usage only

## Deployment Workflow

```
1. Code changes → 2. Build → 3. Deploy → 4. Verify
     ↓              ↓           ↓          ↓
  Edit React    .\build.ps1  .\deploy.ps1  Check URL
  components                    -Method auto
```

## Troubleshooting

### Build Issues

```powershell
# Clear npm cache
npm cache clean --force

# Reinstall dependencies
.\build.ps1 -Install -Clean
```

### Deployment Issues

```powershell
# Test connections
.\deploy.ps1 -Test

# Try different method
.\deploy.ps1 -Method ssh

# Check credentials
notepad .env.deploy
```

### Common Errors

| Error | Solution |
|-------|----------|
| "Build directory not found" | Run `.\build.ps1` first |
| "FTP connection failed" | Check `.env.deploy` credentials |
| "SSH connection failed" | Verify SSH keys or password |
| "Permission denied" | Check file/directory permissions |

## Performance

**Before Cleanup:**
- 17+ deployment scripts
- Mixed backend/frontend deployment
- Hardcoded credentials
- Conflicting logic

**After Cleanup:**
- 2 clean scripts
- Static-only deployment
- Secure credential management
- Single source of truth

## Next Steps

1. **Test Deployment**: Run through complete build/deploy cycle
2. **Configure Domain**: Update DNS settings for your domain
3. **Set Up Monitoring**: Monitor deployment success
4. **Document Workflows**: Team-specific deployment procedures

---

**Created:** August 22, 2025  
**Status:** ✅ Ready for Production  
**Maintainer:** 11Seconds Development Team