Deployment Refactor Plan (PlanGPT)

Purpose
- Provide a clear, minimal, reusable, and testable deployment pattern for this repository and future projects.
- Reduce complexity, improve reliability, and make deployment configurable per-project and per-part.

Requirements checklist
- Auto-build when a build project exists (e.g., CRA -> `web/build`).
- FTP upload from local project build directory.
- Verification immediately before and after upload (FTP + HTTP checks).
- Configurable project parts (e.g., web, admin, mobile, or public/editors/admins + assets) so we only upload selected parts.
- Exclude `node_modules`, `.git`, and other globs by default.
- Early mapping check: local build-path <> server remote-path validation.
- Parameterizable: project, target, method, parts, dry-run, force.

High-level recommendation
- Implement a small Node.js CLI tool under `_deployment-system/` (`deploy-cli.js`) plus a thin PowerShell shim `deploy.ps1` for backward compatibility.
- Rationale: Node.js already used in the repo, cross-platform, better packages for FTP/SFTP, easier config parsing and testing. If a PowerShell-only solution is mandatory, we can port the same modular design to PowerShell.

Design overview
- Config file: `_deployment-system/deployment-config.yaml` (versioned, readable) describing targets, credentials references, and projects (localBuild, source, parts, remotePath, verify rules).
- CLI: commands: build, dry-run, upload, verify, deploy (composed: build + pre-verify + upload + post-verify).
- Shim: `deploy.ps1` simply calls `node deploy-cli.js` with forwarded args.
- Utilities: build runner, FTP uploader (using `basic-ftp`), optional SFTP (ssh2-sftp-client), HTTP verifier (node-fetch), exclude globs (fast-glob or minimatch), manifest generator.
- Manifest & logs: save `_deployment-system/manifests/<timestamp>-manifest.json` with hashes, filecounts, verification results.

Config schema (example)
```yaml
version: 1
targets:
  production:
    method: ftp
    ftp:
      host: ftp.11seconds.de
      user: $FTP_USER
      password: $FTP_PASSWORD
    remoteRoot: /11seconds.de/httpdocs
    domain: https://11seconds.de
projects:
  web:
    localBuild: web/build
    localSource: web
    parts:
      default:
        include: ["index.html","static/**","manifest.json","sw.js"]
      admin:
        include: ["admin/**"]
    remotePath: /11seconds.de/httpdocs
    verify:
      url: "{domain}/deploy-verify-{timestamp}.html"
      fileInBuild: "deploy-verify-{timestamp}.html"
defaults:
  excludes:
    - node_modules/**
    - .git/**
    - **/*.map
```

Contract (inputs / outputs / error modes)
- Inputs: config file + CLI flags (project, target, parts, method) + secrets via environment.
- Outputs: console logs, manifest file, exit codes (0 success, >0 failure).
- Errors: missing build, missing credentials, FTP timeouts, verification failures, mapping mismatch.

Edge cases
- Server returns HTTP 500 despite file present (host-side issue) — surface and request server logs.
- Partial uploads — tool records failed files and optionally retries.
- FTP passive/active differences and timeouts — configurable.
- Service worker cache causing stale client — update sw.js cache name during local copy or bump manifest.

Implementation plan (phased)
Phase 0 — Plan approval (this file). No code edits.
Phase 1 — Scaffold (2–3h):
  - Add `_deployment-system/deployment-config.yaml` (example content)
  - Add `_deployment-system/deploy-cli.js` scaffold: config loader, resolve build path, dry-run mapping output
  - Add `_deployment-system/package.json` with deps: `yaml`, `basic-ftp`, `node-fetch`, `fast-glob`
  - Add `deploy.ps1` shim and `_deployment-system/README.md`
Phase 2 — Core: build + uploader + verifier (2–4h):
  - Implement build runner (npm ci && npm run build) if `build` requested
  - Implement FTP upload respecting exclude globs
  - Implement pre/post verification (FTP SIZE/LIST and HTTP GET)
  - Create manifest file
Phase 3 — Parts & mapping & tests (2–3h):
  - Implement parts include/exclude logic
  - Implement mapping checker and fail early if mismatch
  - Add small unit tests for config and mapping logic
Phase 4 — Hardening & docs (1–2h):
  - Add retries, timeouts, log levels
  - Document CI usage and rollback steps

Migration steps
1. Add new tool and config; do not delete the current `deploy.ps1` yet.
2. Add shim `deploy.ps1` that calls new CLI so existing workflows continue.
3. Validate with dry-run on local machine; iterate until upload + verification pass.
4. After 1–2 successful deploys with the new tool, deprecate legacy functions in `_deployment-system/deploy.ps1`.

Verification strategy
- Pre-upload: ensure local build contains `fileInBuild` `deploy-verify-{timestamp}.html` (create if missing). Confirm local files count and a sample main JS/CSS present.
- Mapping check: confirm configured `localBuild` exists and `remotePath` is set; test FTP LIST on `remotePath` parent directory.
- Post-upload: check FTP SIZE/LIST for verification file; HTTP GET the verification URL and assert timestamp or manifest fingerprint appears.

Configurable options to expose
- project(s), target, method (ftp|ssh), parts, dry-run, force, excludes, timeouts, retries

Safety & rollback
- No destructive operations by default. Provide `--confirm`/`--force` to enable destructive cleanup.
- Optional backup of remote index.html (rename to `.bak`) before overwrite.

Example runs
- Dry-run mapping + upload list:
  ```powershell
  cd _deployment-system
  node deploy-cli.js --project web --target production --parts default --dry-run
  ```
- Full deploy (build + verify):
  ```powershell
  node deploy-cli.js --project web --target production --parts default --method ftp
  ```

Deliverables on implementation
- `_deployment-system/deployment-config.yaml` (example)
- `_deployment-system/deploy-cli.js` (Node.js CLI)
- `_deployment-system/package.json`
- `deploy.ps1` (thin shim)
- `_deployment-system/README.md` and `_deployment-system/manifests/` usage
- Unit tests for mapping logic

Risks & open questions
- Credentials must be provided via environment or CI secrets — do not commit secrets.
- If host responds 500 despite files being present, we need server logs or host admin to investigate.
- If you require PowerShell-only tooling, I will port the same architecture but development and testing will be slower and less portable.

Estimates
- MVP (scaffold + build + ftp + verify + docs): 7–12 hours.
- Additional polish (parts selection, tests, CI examples): 3–6 hours.

Next steps
- Confirm you want the Node.js CLI approach or require PowerShell-only.
- On confirm, I will create the scaffold files in `_deployment-system` and run a dry-run (no credentials required) and show the generated mapping output.

---

Generated on 2025-08-23 by PlanGPT
