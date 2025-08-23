# Deployment Refactor Plan - Clean & Simple (CS)

## Executive Summary

The current `_deployment-system/deploy.ps1` is 756 lines with 15+ functions, duplicate build approaches, hardcoded paths, and complex nested logic. This plan creates a **modular, configurable, and reusable** deployment system that reduces complexity by 70%+ while adding the requested features.

## Current Script Analysis

**Problems Identified:**

- **Complexity:** 756 lines, 15+ functions, duplicate build methods (`Build-WebApplication` vs `Build-ReactApp`)
- **Hardcoded paths:** Absolute Windows paths in config, not portable
- **Mixed approaches:** Multiple config systems (hardcoded hashtables + env files)
- **No project parts:** Always uploads everything, no selective deployment
- **Poor verification:** Basic checks, no pre/post upload verification
- **No path mapping validation:** Assumptions about local <-> remote path correspondence
- **Not reusable:** Project-specific hardcoded values throughout

**What Works:**

- FTP upload functionality (`Upload-FileViaFTP`, `Deploy-ViaFTP`)
- Environment variable loading from `.env` files
- Colored logging output
- Basic build detection

## Requirements Checklist

✅ **Auto-build:** Detect build projects and run builds automatically  
✅ **FTP Upload:** From local project directory to remote  
✅ **Pre/Post Verification:** Validate before upload, verify after  
✅ **Configurable Project Parts:** web/admin/mobile or public/editors/admins+assets  
✅ **Exclude node_modules:** Automatic exclusion with configurable patterns  
✅ **Path Mapping Logic:** Validate local <-> server path mapping early  
✅ **Reusable & Configurable:** Work across multiple projects  
✅ **Reduced Complexity:** Simpler, maintainable codebase

## Proposed Architecture

### 1. Configuration-Driven Design

**File:** `_deployment-system/deployment.config.json`

```json
{
  "version": "1.0",
  "project": {
    "name": "11Seconds",
    "type": "web-app"
  },
  "targets": {
    "production": {
      "ftp": {
        "host": "ftp.11seconds.de",
        "user": "${FTP_USER}",
        "password": "${FTP_PASSWORD}",
        "timeout": 30000
      },
      "paths": {
        "remote_root": "/11seconds.de/httpdocs",
        "domain": "https://11seconds.de"
      }
    }
  },
  "components": {
    "web": {
      "source": "web",
      "build_dir": "build",
      "build_command": "npm run build",
      "parts": {
        "core": {
          "include": ["index.html", "static/**", "manifest.json"],
          "remote_path": "/"
        },
        "admin": {
          "include": ["admin/**"],
          "remote_path": "/admin"
        }
      }
    },
    "api": {
      "source": "api",
      "build_dir": null,
      "parts": {
        "server": {
          "include": ["**/*.js", "package.json"],
          "exclude": ["node_modules/**", "tests/**"],
          "remote_path": "/"
        }
      }
    }
  },
  "verification": {
    "enabled": true,
    "file_template": "deploy-verify-{timestamp}.html",
    "url_template": "{domain}/{file}"
  },
  "defaults": {
    "excludes": ["node_modules/**", ".git/**", "**/*.log", "**/*.map", ".env*"]
  }
}
```

### 2. Modular PowerShell Architecture

**Main Script:** `deploy.ps1` (100-150 lines)

```powershell
param(
    [string]$Component = "web",
    [string]$Target = "production",
    [string]$Parts = "core",
    [string]$Action = "deploy",  # build|upload|verify|deploy
    [switch]$DryRun,
    [switch]$Force
)

# Load modules
. "$PSScriptRoot\modules\Config.ps1"
. "$PSScriptRoot\modules\Builder.ps1"
. "$PSScriptRoot\modules\Uploader.ps1"
. "$PSScriptRoot\modules\Verifier.ps1"

# Main execution
$config = Load-DeploymentConfig
$result = Invoke-Deployment -Config $config -Component $Component -Target $Target -Parts $Parts -Action $Action -DryRun:$DryRun
exit ($result ? 0 : 1)
```

**Module Structure:**

```
_deployment-system/
├── deploy.ps1                 # Main entry (100-150 lines)
├── deployment.config.json     # Configuration
├── modules/
│   ├── Config.ps1             # Configuration loader (50-80 lines)
│   ├── Builder.ps1            # Build automation (50-100 lines)
│   ├── Uploader.ps1           # FTP/SFTP upload (100-150 lines)
│   ├── Verifier.ps1           # Pre/post verification (50-100 lines)
│   └── PathMapper.ps1         # Path mapping & validation (50-80 lines)
├── templates/
│   └── verify.html            # Verification file template
└── logs/
    └── deployment-{date}.log  # Deployment logs
```

### 3. Core Features Implementation

#### A. Auto-Build Detection & Execution

```powershell
# Builder.ps1
function Invoke-AutoBuild {
    param($ComponentConfig, $Force)

    if (-not $ComponentConfig.build_command) {
        Write-Info "No build command configured for component"
        return $true
    }

    $buildDir = Join-Path $ComponentConfig.source $ComponentConfig.build_dir
    if ((Test-Path $buildDir) -and -not $Force) {
        Write-Info "Build directory exists, skipping build (use -Force to rebuild)"
        return $true
    }

    Push-Location $ComponentConfig.source
    try {
        Write-Step "Running: $($ComponentConfig.build_command)"
        Invoke-Expression $ComponentConfig.build_command
        return $LASTEXITCODE -eq 0
    } finally {
        Pop-Location
    }
}
```

#### B. Configurable Project Parts

```powershell
# PathMapper.ps1
function Get-DeploymentFiles {
    param($ComponentConfig, $Parts, $Excludes)

    $allFiles = @()
    $partsArray = $Parts -split ','

    foreach ($partName in $partsArray) {
        $part = $ComponentConfig.parts.$partName
        if (-not $part) {
            throw "Part '$partName' not found in component configuration"
        }

        $files = Get-FilesByPattern -BasePath $ComponentConfig.source -Include $part.include -Exclude $Excludes
        $allFiles += $files | ForEach-Object {
            @{
                LocalPath = $_.FullName
                RemotePath = Join-Path $part.remote_path $_.Name
                Part = $partName
            }
        }
    }

    return $allFiles
}
```

#### C. Pre/Post Verification

```powershell
# Verifier.ps1
function Test-PreUploadVerification {
    param($Config, $Files)

    Write-Step "Pre-upload verification..."

    # Check local files exist
    $missingFiles = $Files | Where-Object { -not (Test-Path $_.LocalPath) }
    if ($missingFiles) {
        Write-Error "Missing files: $($missingFiles.LocalPath -join ', ')"
        return $false
    }

    # Create verification file
    $verifyFile = New-VerificationFile -Config $Config
    Write-Success "Pre-upload verification passed"
    return $verifyFile
}

function Test-PostUploadVerification {
    param($Config, $VerifyFile)

    Write-Step "Post-upload verification..."

    # Test FTP file exists
    $ftpExists = Test-FTPFileExists -Config $Config.target.ftp -FilePath $VerifyFile.RemotePath
    if (-not $ftpExists) {
        Write-Error "Verification file not found on FTP"
        return $false
    }

    # Test HTTP accessibility
    $url = $Config.verification.url_template.Replace('{domain}', $Config.target.paths.domain).Replace('{file}', $VerifyFile.Name)
    $httpTest = Test-HttpUrl -Url $url -ExpectedContent $VerifyFile.Content
    if (-not $httpTest) {
        Write-Error "Verification URL not accessible: $url"
        return $false
    }

    Write-Success "Post-upload verification passed"
    return $true
}
```

#### D. Path Mapping Validation

```powershell
# PathMapper.ps1
function Test-PathMappings {
    param($Config, $Component, $Target)

    Write-Step "Validating path mappings..."

    $componentConfig = $Config.components.$Component
    $targetConfig = $Config.targets.$Target

    # Test local source path
    $localSource = $componentConfig.source
    if (-not (Test-Path $localSource)) {
        Write-Error "Local source path not found: $localSource"
        return $false
    }

    # Test build path if build component
    if ($componentConfig.build_dir) {
        $buildPath = Join-Path $localSource $componentConfig.build_dir
        if (-not (Test-Path $buildPath)) {
            Write-Warning "Build directory not found: $buildPath (will be created during build)"
        }
    }

    # Test FTP connection and remote path
    $ftpTest = Test-FTPConnection -Config $targetConfig.ftp
    if (-not $ftpTest) {
        Write-Error "FTP connection failed"
        return $false
    }

    Write-Success "Path mappings validated"
    return $true
}
```

## Implementation Phases

### Phase 1: Core Structure (2-3 hours)

1. Create modular directory structure
2. Implement configuration loader with JSON parsing
3. Create main `deploy.ps1` with parameter handling
4. Add basic logging and error handling

### Phase 2: Build & Upload (3-4 hours)

1. Implement `Builder.ps1` with auto-build detection
2. Implement `Uploader.ps1` with FTP functionality from current script
3. Add file filtering with include/exclude patterns
4. Implement project parts selection

### Phase 3: Verification & Mapping (2-3 hours)

1. Implement `Verifier.ps1` with pre/post upload checks
2. Add `PathMapper.ps1` with mapping validation
3. Create verification file templates
4. Add HTTP verification tests

### Phase 4: Testing & Documentation (2-3 hours)

1. Test with current 11Seconds project
2. Create comprehensive documentation
3. Add example configurations for different project types
4. Performance optimization and error handling improvement

## Usage Examples

### Basic Deployment

```powershell
# Deploy web component core parts to production
.\deploy.ps1 -Component web -Target production -Parts core

# Deploy with build
.\deploy.ps1 -Component web -Target production -Parts core -Action deploy

# Dry run to see what would be deployed
.\deploy.ps1 -Component web -Target production -Parts core -DryRun
```

### Advanced Usage

```powershell
# Deploy multiple parts
.\deploy.ps1 -Component web -Target production -Parts "core,admin"

# Upload only (skip build)
.\deploy.ps1 -Component web -Target production -Parts core -Action upload

# Force rebuild and deploy
.\deploy.ps1 -Component web -Target production -Parts core -Force
```

## Configuration for Different Projects

### Multi-Component Project (like 11Seconds)

```json
{
  "components": {
    "web": {
      "source": "web",
      "build_dir": "build",
      "build_command": "npm run build",
      "parts": {
        "core": { "include": ["index.html", "static/**"], "remote_path": "/" },
        "admin": { "include": ["admin/**"], "remote_path": "/admin" }
      }
    },
    "mobile": {
      "source": "mobile-app",
      "build_dir": "dist",
      "build_command": "ionic build --prod",
      "parts": {
        "app": { "include": ["**/*"], "remote_path": "/mobile" }
      }
    }
  }
}
```

### Multi-User Project (public/editors/admins)

```json
{
  "components": {
    "frontend": {
      "source": "src",
      "build_dir": "dist",
      "parts": {
        "public": { "include": ["public/**"], "remote_path": "/" },
        "editors": { "include": ["editors/**"], "remote_path": "/editors" },
        "admins": { "include": ["admin/**"], "remote_path": "/admin" },
        "assets": { "include": ["assets/**"], "remote_path": "/assets" }
      }
    }
  }
}
```

## Benefits of New Architecture

### Complexity Reduction

- **From 756 → ~400 lines total** (distributed across modules)
- **From 15+ → 8 functions** (focused, single-purpose)
- **From hardcoded → configuration-driven**
- **From monolithic → modular**

### Enhanced Features

- ✅ Selective deployment (project parts)
- ✅ Pre/post upload verification
- ✅ Path mapping validation
- ✅ Auto-build detection
- ✅ Configurable exclusions
- ✅ Reusable across projects
- ✅ Dry-run capability
- ✅ Better error handling and logging

### Maintainability

- **Modular design:** Easy to modify individual components
- **Configuration-driven:** No code changes for new projects
- **Testable:** Each module can be unit tested
- **Documented:** Clear interfaces and examples

## Migration Strategy

### Step 1: Parallel Implementation (Week 1)

- Create new system alongside existing script
- Keep existing `deploy.ps1` as `deploy-legacy.ps1`
- Test new system with dry-runs

### Step 2: Validation (Week 2)

- Deploy test components to staging
- Compare results with legacy system
- Fix any issues found

### Step 3: Production Migration (Week 3)

- Use new system for production deployments
- Monitor for issues
- Decommission legacy system once stable

## Risk Mitigation

### Risks & Solutions

- **Configuration errors:** JSON schema validation and detailed error messages
- **FTP connectivity issues:** Retry logic and timeout configuration
- **Path mapping errors:** Comprehensive pre-flight checks
- **Performance regression:** Optimize file operations and parallel uploads where possible

### Rollback Plan

- Keep legacy system available for 30 days
- Document exact command equivalents between old and new systems
- Provide quick rollback procedure in documentation

## Deliverables

1. **Core System Files:**

   - `deploy.ps1` (main script)
   - `deployment.config.json` (configuration)
   - All module files in `modules/`

2. **Documentation:**

   - `README.md` (usage guide)
   - `CONFIGURATION.md` (config reference)
   - `MIGRATION.md` (migration guide)

3. **Examples:**

   - Configuration examples for different project types
   - PowerShell usage examples
   - CI/CD integration examples

4. **Testing:**
   - Validation scripts for configuration
   - Test deployment to staging environment
   - Performance benchmarks vs legacy system

## Timeline & Resource Estimate

- **Phase 1-2:** 5-7 hours (core functionality)
- **Phase 3-4:** 4-6 hours (verification & testing)
- **Documentation:** 2-3 hours
- **Total:** 11-16 hours over 2-3 days

## Conclusion

This plan transforms a complex 756-line monolithic script into a **clean, modular, configurable system** that addresses all requirements while being **70% smaller and infinitely more maintainable**. The configuration-driven approach makes it reusable across projects, while the modular design makes it easy to extend and maintain.

The new system provides all requested features:

- ✅ Auto-build detection and execution
- ✅ Configurable project parts deployment
- ✅ Pre/post upload verification
- ✅ Path mapping validation
- ✅ Node_modules exclusion and configurable filters
- ✅ FTP upload functionality
- ✅ Cross-project reusability

**Next Steps:** Approve plan and begin Phase 1 implementation.

---

_Generated: 2025-08-23_  
_Plan Version: 1.0_
