### **Deployment Refactor Plan**

**1. Executive Summary**

The goal is to replace the current complex, monolithic deployment script with a modern, modular, and configuration-driven system. This new system will be robust, reusable for other projects, and significantly easier to maintain. It will reduce deployment time by automating builds, uploads, and verification, while providing the flexibility to deploy specific project parts on demand.

**2. Requirements Checklist**

This plan is designed to meet all of your specified needs:

- [x] **Auto-build:** Automatically run build commands for projects that need it.
- [x] **FTP Upload:** Securely upload files from a local directory.
- [x] **Pre/Post Verification:** Run checks before and after upload to ensure success.
- [x] **Configurable Project Parts:** Deploy specific components like `web`, `admin`, or `assets` independently.
- [x] **Exclude `node_modules`:** Automatically ignore `node_modules` and other configured patterns.
- [x] **Path Mapping Validation:** Check local and remote path configurations at the start of a run.
- [x] **Reduced Complexity:** A simpler, more readable, and modular codebase.
- [x] **Reusable & Configurable:** Easily adapt to new projects with a simple configuration file.

**3. Proposed Architecture: Modular PowerShell**

We will create a new system within the `_deployment-system` folder, centered around a single configuration file and a set of clean, single-purpose PowerShell modules.

**File Structure:**

```
_deployment-system/
├── deploy.ps1                 # Main entry point (thin wrapper)
├── deployment.config.json     # ALL configuration lives here
└── modules/
    ├── Config.ps1             # Loads and validates configuration
    ├── Builder.ps1            # Handles the build process
    ├── Uploader.ps1           # Handles FTP operations
    ├── Verifier.ps1           # Handles pre/post-deployment checks
    └── PathMapper.ps1         # Resolves and validates file paths
```

**Configuration File (`deployment.config.json`):**

This file will define everything about your deployment targets and project structure. It's the only file you'll need to edit for new projects.

```json
{
  "targets": {
    "production": {
      "ftp": {
        "host": "ftp.11seconds.de",
        "user": "${env:FTP_USER}",
        "password": "${env:FTP_PASSWORD}"
      },
      "remote_root": "/11seconds.de/httpdocs",
      "domain": "https://11seconds.de"
    }
  },
  "components": {
    "web": {
      "source": "web",
      "build_dir": "build",
      "build_command": "npm run build",
      "parts": {
        "app": {
          "include": ["index.html", "static/**", "manifest.json"],
          "remote_path": "/"
        },
        "admin": {
          "include": ["admin/**"],
          "remote_path": "/admin/"
        }
      }
    },
    "api": {
      "source": "api",
      "parts": {
        "all": {
          "include": ["**/*"],
          "exclude": ["node_modules/**"],
          "remote_path": "/api/"
        }
      }
    }
  },
  "defaults": {
    "excludes": ["node_modules/**", ".git/**", "*.map"]
  }
}
```

**4. Core Feature Implementation**

- **Main Script (`deploy.ps1`):** This will be a simple script (under 100 lines) that parses command-line arguments (`-Component`, `-Parts`, `-Target`, `-DryRun`) and calls the appropriate modules.

- **Auto-Build (`Builder.ps1`):**

  - Checks if a `build_command` is defined for the component in `deployment.config.json`.
  - Checks if the `build_dir` already exists. If so, it skips the build unless `-Force` is used.
  - Runs the command from the component's `source` directory.

- **Configurable Parts (`PathMapper.ps1`):**

  - Takes the `-Parts` argument (e.g., `"app,admin"`).
  - For each part, it finds all files matching the `include` patterns within the `source` directory.
  - It applies the `exclude` patterns from both the part and the `defaults`.
  - It creates a final list of files to be uploaded, each with its calculated local and remote path.

- **Verification (`Verifier.ps1`):**

  - **Pre-upload:**
    1.  Checks that the local build directory and files actually exist.
    2.  Connects to FTP to ensure the target `remote_root` is accessible.
    3.  Creates a temporary `verification_{timestamp}.txt` file to be uploaded.
  - **Post-upload:**
    1.  Uses an FTP `LIST` command to confirm the `verification_{timestamp}.txt` file exists on the server.
    2.  (Optional) Makes an HTTP request to `https://your-domain.com/verification_{timestamp}.txt` to confirm it's publicly accessible.
    3.  Deletes the verification file from the server.

- **Path Mapping Check (`PathMapper.ps1`):**
  - This happens during the pre-upload verification step.
  - It resolves the absolute local path to the build directory.
  - It resolves the absolute remote path using the `remote_root` and the part's `remote_path`.
  - It will log a clear message: `INFO: Mapping local 'C:\path\to\project\web\build' to remote '/11seconds.de/httpdocs/'`.
  - The script will fail if the local source path doesn't exist.

**5. Implementation Phases**

1.  **Phase 1: Scaffolding & Config (2-3 hours)**

    - Create the new directory structure and empty module files.
    - Implement the `Config.ps1` module to load and parse `deployment.config.json`.
    - Create the main `deploy.ps1` entry point with parameter handling.

2.  **Phase 2: Build & Upload Logic (3-4 hours)**

    - Implement `Builder.ps1` and `Uploader.ps1`, reusing the robust FTP logic from the old script but adapting it to the new modular structure.
    - Implement the file gathering and filtering logic in `PathMapper.ps1`.

3.  **Phase 3: Verification & Polish (2-3 hours)**

    - Implement the pre/post verification logic in `Verifier.ps1`.
    - Add detailed logging, error handling, and a `-DryRun` mode.

4.  **Phase 4: Documentation & Migration (1-2 hours)**
    - Create a `README.md` explaining how to use the new system.
    - Rename the old script to `deploy-legacy.ps1` and officially switch over.

**6. Usage Example**

The new workflow will be simple and powerful.

```powershell
# Deploy the 'app' part of the 'web' component to production
.\_deployment-system\deploy.ps1 -Component web -Parts app -Target production

# See what would be deployed without actually uploading
.\_deployment-system\deploy.ps1 -Component web -Parts app -Target production -DryRun

# Deploy both the 'app' and 'admin' parts
.\_deployment-system\deploy.ps1 -Component web -Parts "app,admin" -Target production

# Deploy the entire 'api' component
.\_deployment-system\deploy.ps1 -Component api -Parts all -Target production
```

**7. Conclusion**

This plan directly addresses the need for a simpler, more robust deployment system. By moving to a modular, configuration-driven architecture, we will create a tool that is not only easier to use and maintain for this project but can be easily adapted for any future project by simply creating a new `deployment.config.json` file. This investment will save significant time and prevent future deployment headaches.
