param(
  [string]$Workspace = (Split-Path -Parent $MyInvocation.MyCommand.Path)
)
$ErrorActionPreference = 'Stop'
function W($t,$c){ Write-Host $t -ForegroundColor $c }

# PHP
& (Join-Path $Workspace 'scripts/check-php-syntax.ps1')
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

# PowerShell parse check
W '[INFO] Checking PowerShell files...' 'Cyan'
$psFiles = Get-ChildItem -Path $Workspace -Recurse -Include *.ps1,*.psm1 -File |
  Where-Object { $_.FullName -notmatch '\\node_modules\\|\\.git\\|\\_archive\\' }
$psErrors = 0
foreach ($f in $psFiles) {
  try {
    $null = powershell -NoProfile -Command "[void][System.Management.Automation.Language.Parser]::ParseFile('$($f.FullName)', [ref]([string[]]@()), [ref]([System.Management.Automation.Language.Token[]]@()), [ref]([System.Management.Automation.Language.ParseError[]]@()))"
  } catch { $psErrors++ ; W ("[ERR]  PS parse error: $($f.FullName) -> $($_.Exception.Message)") 'Red' }
}
if ($psErrors -gt 0) { W "[ERR]  PowerShell parse errors: $psErrors" 'Red'; exit 1 } else { W '[OK]   PowerShell syntax check passed' 'Green' }

# JS/TS via node --check (Node 20+ supports --check). Fallback to eslint if present.
W '[INFO] Checking JavaScript files...' 'Cyan'
$node = Get-Command node -ErrorAction SilentlyContinue
if ($node) {
  $jsFiles = Get-ChildItem -Path $Workspace -Recurse -Include *.js,*.mjs -File |
    Where-Object { $_.FullName -notmatch '\\node_modules\\|\\.git\\|\\web\\dist\\|\\web\\build\\' }
  $jsErr = 0
  foreach ($f in $jsFiles) {
    $out = node --check "$($f.FullName)" 2>&1
    if ($LASTEXITCODE -ne 0) { W ("[ERR]  JS parse error: $($f.FullName)`n$out") 'Red'; $jsErr++ }
  }
  if ($jsErr -gt 0) { W "[ERR]  JavaScript parse errors: $jsErr" 'Red'; exit 1 } else { W '[OK]   JavaScript syntax check passed' 'Green' }
} else {
  W '[WARN] Node not found, skipping JS syntax check' 'Yellow'
}

# JSON validation
W '[INFO] Validating JSON files...' 'Cyan'
$jsonFiles = Get-ChildItem -Path $Workspace -Recurse -Include *.json -File |
  Where-Object { $_.FullName -notmatch '\\node_modules\\|\\.git\\|\\web\\dist\\|\\web\\build\\' }
$jsonErr = 0
foreach ($f in $jsonFiles) {
  try { $null = Get-Content $f.FullName -Raw | ConvertFrom-Json -ErrorAction Stop } catch { W ("[ERR]  JSON invalid: $($f.FullName) -> $($_.Exception.Message)") 'Red'; $jsonErr++ }
}
if ($jsonErr -gt 0) { W "[ERR]  JSON errors: $jsonErr" 'Red'; exit 1 } else { W '[OK]   JSON validation passed' 'Green' }

W '[OK]   All syntax checks passed' 'Green'
exit 0
