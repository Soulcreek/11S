# 11Seconds Deployment System

A robust, one-click deployment system for the 11Seconds project with comprehensive verification and local testing capabilities.

## Quick Start (One-Click Deployment)

### Deploy Live

```powershell
.\deploy-live.ps1
```

**What it does:**

- Builds the React app (`npm run build`)
- Validates all paths and connections
- Uploads files to production FTP
- Creates and verifies a timestamped verification file
- Reports success/failure with verification URL

### Deploy Locally for Testing

```powershell
.\deploy-local.ps1
```

**What it does:**

- Builds the React app
- Copies files to `./httpdocs-local/`
- Starts a local server on http://localhost:5000
- Verifies the deployment locally

## Advanced Usage

### Manual CLI Usage

```powershell
cd _deployment-system
node deploy-cli.js --project web --target production --parts default --build
```

### Available Options

- `--project <name>` - Project to deploy (default: web)
- `--target <name>` - Target environment (production|local)
- `--parts <list>` - Parts to deploy, comma-separated (default|admin)
- `--build` - Run build before deployment
- `--force` - Force rebuild and overwrite
- `--dry-run` - Show what would be deployed without uploading
- `--verbose` - Detailed logging

### Deploy Specific Parts

```powershell
# Deploy only admin section
.\deploy-live.ps1 --parts admin

# Deploy both core and admin
node deploy-cli.js --project web --target production --parts "default,admin" --build
```

## Configuration

Main config file: `_deployment-system/deployment-config.yaml`

Key sections:

- **targets**: Production, local, staging environments
- **projects**: Web, API, mobile components
- **parts**: Selective deployment (core, admin, assets)
- **verification**: Automatic verification settings
- **excludes**: Files/patterns to ignore

## Environment Variables

Required for production deployment:

```
FTP_USER=your_ftp_username
FTP_PASSWORD=your_ftp_password
```

## Verification Process

The system automatically:

1. **Pre-upload**: Validates local files and FTP connection
2. **Upload**: Transfers files with progress tracking
3. **Post-upload**: Creates verification file and tests HTTP access
4. **Cleanup**: Removes verification file after successful test

## File Structure

```
_deployment-system/
├── deploy-cli.js              # Core Node.js CLI
├── deployment-config.yaml     # Configuration
├── package.json               # Dependencies
└── manifests/                 # Deployment logs
    └── deploy-*.json         # Timestamped deployment records
```

## Deployment Manifests

Each deployment creates a manifest in `_deployment-system/manifests/` with:

- Timestamp and target information
- List of deployed files with sizes
- Verification results
- Success/failure status

## Error Handling

Common issues and solutions:

**FTP Connection Failed**

- Check `FTP_USER` and `FTP_PASSWORD` environment variables
- Verify FTP host and port settings

**Build Failed**

- Ensure `npm` and Node.js are installed
- Check for errors in `web/` directory
- Try with `--force` to clean build

**Verification Failed (HTTP 500)**

- Files uploaded but server has issues
- Check server logs or contact hosting provider
- Verification file will show the exact URL that failed

**Path Not Found**

- Verify `web/build` directory exists after build
- Check that `web/` contains a React project

## Troubleshooting

### Enable Verbose Logging

```powershell
.\deploy-live.ps1 -Verbose
```

### Dry Run (See What Would Deploy)

```powershell
cd _deployment-system
node deploy-cli.js --dry-run --project web --target production --parts default
```

### Check Deployment History

```powershell
Get-ChildItem _deployment-system\manifests\ | Sort-Object LastWriteTime | Select-Object -Last 5
```

## Dependencies

The system uses these Node.js packages:

- `basic-ftp` - Robust FTP client
- `js-yaml` - YAML configuration parsing
- `node-fetch` - HTTP verification requests
- `fast-glob` - File pattern matching
- `commander` - CLI argument parsing

## Migration from Legacy System

The old `deploy.ps1` (756 lines) has been replaced with this modular system (300 lines + config).

Benefits:

- ✅ 70% less code complexity
- ✅ True one-click deployment
- ✅ Local testing capability
- ✅ Comprehensive verification
- ✅ Selective part deployment
- ✅ Cross-project reusability
- ✅ Detailed deployment manifests

---

_Generated: 2025-08-23_  
_Deployment System Version: 1.0_
