param(
    [string]$Out = "dist\admin-package.zip"
)

# Creates a ZIP archive of the admin center files for manual upload.
# Excludes config/.env to avoid leaking secrets. Run locally and upload via hosting control panel.

Write-Host "Packaging admin files -> $Out"

$temp = Join-Path $env:TEMP "11s_admin_package_$(Get-Random)"
if (Test-Path $temp) { Remove-Item -Recurse -Force $temp }
New-Item -ItemType Directory -Path $temp | Out-Null

# Copy admin files
Copy-Item -Path "admin\*" -Destination $temp -Recurse -Force

# Also include root index.html if present
if (Test-Path "index.html") { Copy-Item -Path "index.html" -Destination $temp -Force }

# Ensure output dir
$outDir = Split-Path $Out -Parent
if (-not (Test-Path $outDir)) { New-Item -ItemType Directory -Path $outDir | Out-Null }

if (Test-Path $Out) { Remove-Item $Out -Force }

Compress-Archive -Path (Join-Path $temp '*') -DestinationPath $Out -Force

Write-Host "Created package: $Out"

# Cleanup
Remove-Item -Recurse -Force $temp

Write-Host "Note: config/.env is NOT included. Add your config/.env via hosting panel at /httpdocs/config/.env after upload."
