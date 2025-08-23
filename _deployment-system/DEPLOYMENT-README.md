# Universal Deployment System

A robust, one-click deployment system that can be easily adapted to any web project. Built with Node.js CLI + PowerShell wrappers for maximum compatibility and ease of use.

## Quick Setup for New Projects

### 1. Copy Deployment System

Copy the entire `_deployment-system/` folder to your new project root.

### 2. Install Dependencies

```bash
cd _deployment-system
npm install
```

### 3. Configure for Your Project

Edit `_deployment-system/deployment-config.yaml`:

```yaml
version: 1
targets:
  production:
    method: ftp
    ftp:
      host: your-ftp-host.com
      user: ${FTP_USER}
      password: ${FTP_PASSWORD}
      timeout: 30000
    remoteRoot: /public_html
    domain: https://your-domain.com

  local:
    method: copy
    localPath: ./httpdocs-local
    domain: http://localhost:5000

projects:
  web:
    localSource: . # For single-page projects
    localBuild: dist # React: build, Vue: dist, Angular: dist
    buildCommand: npm run build # Your build command
    parts:
      default:
        include:
          - "index.html"
          - "assets/**" # Vue/Vite: assets/, React: static/
          - "*.js"
          - "*.css"
          - "favicon.ico"
        remotePath: "/"
    verify:
      enabled: true
      fileTemplate: "deploy-verify-{timestamp}.html"
      urlTemplate: "{domain}/{file}"

defaults:
  excludes:
    - "node_modules/**"
    - ".git/**"
    - "**/*.map"
    - "src/**" # Don't deploy source files
    - "*.config.js"
    - ".env*"
```

### 4. Set Environment Variables

Create these environment variables (or add to `.env`):

```bash
FTP_USER=your_username
FTP_PASSWORD=your_password
```

### 5. Create One-Click Scripts

Copy `deploy-live.ps1` and `deploy-local.ps1` to your project root and update the project path if needed.

## Framework-Specific Configurations

### React (Create React App)

```yaml
projects:
  web:
    localSource: .
    localBuild: build
    buildCommand: npm run build
    parts:
      default:
        include:
          - "index.html"
          - "static/**"
          - "manifest.json"
          - "favicon.ico"
```

### Vue 3 + Vite

```yaml
projects:
  web:
    localSource: .
    localBuild: dist
    buildCommand: npm run build
    parts:
      default:
        include:
          - "index.html"
          - "assets/**"
          - "favicon.ico"
```

### Angular

```yaml
projects:
  web:
    localSource: .
    localBuild: dist/your-app-name
    buildCommand: ng build --prod
    parts:
      default:
        include:
          - "index.html"
          - "*.js"
          - "*.css"
          - "assets/**"
```

### Next.js (Static Export)

```yaml
projects:
  web:
    localSource: .
    localBuild: out
    buildCommand: npm run build && npm run export
    parts:
      default:
        include:
          - "**/*.html"
          - "_next/**"
          - "*.ico"
```

### Nuxt.js (Static)

```yaml
projects:
  web:
    localSource: .
    localBuild: dist
    buildCommand: npm run generate
    parts:
      default:
        include:
          - "**/*.html"
          - "_nuxt/**"
          - "*.ico"
```

## Multi-Part Deployment Examples

### Blog with Admin Panel

```yaml
projects:
  blog:
    localSource: .
    localBuild: dist
    buildCommand: npm run build
    parts:
      public:
        include: ["index.html", "posts/**", "assets/**"]
        remotePath: "/"
      admin:
        include: ["admin/**"]
        remotePath: "/admin"
      api:
        include: ["api/**"]
        remotePath: "/api"
```

Usage:

```powershell
# Deploy only public site
node deploy-cli.js --project blog --parts public

# Deploy admin panel only
node deploy-cli.js --project blog --parts admin

# Deploy everything
node deploy-cli.js --project blog --parts "public,admin,api"
```

### E-commerce Site

```yaml
projects:
  shop:
    localSource: .
    localBuild: build
    buildCommand: npm run build
    parts:
      storefront:
        include: ["index.html", "products/**", "static/**"]
        remotePath: "/"
      dashboard:
        include: ["dashboard/**"]
        remotePath: "/dashboard"
      assets:
        include: ["images/**", "uploads/**"]
        remotePath: "/assets"
```

## Hosting Provider Examples

### Shared Hosting (cPanel/Plesk)

```yaml
targets:
  production:
    method: ftp
    ftp:
      host: ftp.yourdomain.com
      user: ${FTP_USER}
      password: ${FTP_PASSWORD}
    remoteRoot: /public_html # Common for shared hosting
    domain: https://yourdomain.com
```

### VPS/Dedicated Server

```yaml
targets:
  production:
    method: ftp
    ftp:
      host: your-server-ip
      user: ${FTP_USER}
      password: ${FTP_PASSWORD}
    remoteRoot: /var/www/html # Common for Apache/Nginx
    domain: https://yourdomain.com
```

### Netcup/1&1/Strato

```yaml
targets:
  production:
    method: ftp
    ftp:
      host: ftp.netcup.de
      user: ${FTP_USER}
      password: ${FTP_PASSWORD}
    remoteRoot: / # Root FTP access
    domain: https://yourdomain.de
```

## Usage Commands

### One-Click Deployment (Recommended)

```powershell
# Deploy to production
.\deploy-live.ps1

# Deploy locally for testing
.\deploy-local.ps1
```

### Manual CLI Usage

```powershell
cd _deployment-system

# Basic deployment
node deploy-cli.js --project web --target production --parts default --build

# Advanced options
node deploy-cli.js --project web --target production --parts default --build --force --verbose

# Dry run (see what would be deployed)
node deploy-cli.js --project web --target production --parts default --dry-run

# Deploy specific parts only
node deploy-cli.js --project web --target production --parts "admin,api" --build
```

## Troubleshooting

### Common Issues

**"Local source path not found"**

- Check that your `localSource` path in config points to correct directory
- Ensure the directory exists relative to your project root

**"Build failed"**

- Verify your `buildCommand` is correct for your framework
- Check that all dependencies are installed (`npm install`)
- Try building manually first: `npm run build`

**"FTP connection failed"**

- Verify `FTP_USER` and `FTP_PASSWORD` environment variables
- Check FTP host, port, and credentials with your hosting provider
- Some hosts require FTPS or different ports

**"No files found to deploy"**

- Check your `include` patterns in the config
- Verify the `localBuild` path contains built files
- Use `--verbose` to see which files are being matched

**"Verification failed (HTTP 500)"**

- Files uploaded successfully but server has issues
- Check server logs or contact hosting provider
- The verification URL will show exactly what failed

### Debug Mode

```powershell
# Enable verbose logging
.\deploy-live.ps1 -Verbose

# Or with CLI
node deploy-cli.js --project web --target production --parts default --build --verbose
```

### Check Deployment History

```powershell
# View recent deployments
Get-ChildItem _deployment-system\manifests\ | Sort-Object LastWriteTime | Select-Object -Last 5

# View deployment details
Get-Content _deployment-system\manifests\deploy-latest.json | ConvertFrom-Json
```

## Features

### ✅ Supported Features

- **Auto-build**: Runs your build command automatically
- **One-click deployment**: Simple PowerShell wrappers
- **Local testing**: Deploy to local folder + HTTP server
- **Selective deployment**: Deploy only specific parts
- **Pre/post verification**: Ensures deployment success
- **Path validation**: Checks mappings before upload
- **Comprehensive logging**: Detailed logs and manifests
- **Cross-platform**: Works on Windows, Mac, Linux
- **Framework agnostic**: Works with any static site generator

### 🚀 Advanced Features

- **Multi-part deployment**: Deploy different sections independently
- **Rollback capability**: Keep deployment history
- **Dry-run mode**: Preview deployment without uploading
- **Environment variables**: Secure credential handling
- **Custom exclude patterns**: Flexible file filtering
- **Verification cleanup**: Auto-removes test files
- **Manifest generation**: Detailed deployment records

## File Structure

```
your-project/
├── deploy-live.ps1              # One-click live deployment
├── deploy-local.ps1             # One-click local deployment
├── _deployment-system/
│   ├── deploy-cli.js            # Core deployment CLI
│   ├── deployment-config.yaml   # Your configuration
│   ├── package.json             # Node.js dependencies
│   ├── README-NEW.md            # Detailed documentation
│   └── manifests/               # Deployment history
│       └── deploy-*.json        # Timestamped records
└── your-build-files...
```

## Migration from Other Systems

### From Manual FTP

1. Copy your FTP credentials to environment variables
2. Configure `remoteRoot` to match your current upload path
3. Set `include` patterns to match files you currently upload
4. Test with `--dry-run` first

### From Other Deployment Tools

1. Compare your current build output with `localBuild` setting
2. Match your current file patterns with `include/exclude`
3. Use same target paths in `remotePath`
4. Test locally first with `deploy-local.ps1`

## Support & Customization

### Adding New Deployment Methods

The system supports plugins for new deployment methods (SSH, SFTP, cloud storage, etc.). Modify `deploy-cli.js` to add new methods in the deployment switch statement.

### Custom Verification

Modify the verification settings in your config:

```yaml
verify:
  enabled: true
  fileTemplate: "custom-verify-{timestamp}.json"
  urlTemplate: "{domain}/api/verify/{file}"
```

### Integration with CI/CD

```yaml
# GitHub Actions example
- name: Deploy to production
  run: |
    cd _deployment-system
    node deploy-cli.js --project web --target production --parts default --build
  env:
    FTP_USER: ${{ secrets.FTP_USER }}
    FTP_PASSWORD: ${{ secrets.FTP_PASSWORD }}
```

---

## Quick Start Checklist

1. [ ] Copy `_deployment-system/` folder to your project
2. [ ] Run `npm install` in `_deployment-system/`
3. [ ] Edit `deployment-config.yaml` with your settings
4. [ ] Set `FTP_USER` and `FTP_PASSWORD` environment variables
5. [ ] Copy `deploy-live.ps1` and `deploy-local.ps1` to project root
6. [ ] Test with `.\deploy-local.ps1` first
7. [ ] Deploy live with `.\deploy-live.ps1`

**That's it! Your project now has professional deployment capabilities.**

---

_Universal Deployment System v1.0 - Works with React, Vue, Angular, Next.js, Nuxt.js, and any static site_
