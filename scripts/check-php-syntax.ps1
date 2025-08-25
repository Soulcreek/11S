param(
  [string]$Root = (Split-Path -Parent $MyInvocation.MyCommand.Path)
)

$ErrorActionPreference = 'Stop'
function Write-Info($m){ Write-Host "[INFO] $m" -ForegroundColor Cyan }
function Write-Err($m){ Write-Host "[ERR]  $m" -ForegroundColor Red }
function Write-Ok($m){ Write-Host "[OK]   $m" -ForegroundColor Green }

$php = Get-Command php -ErrorAction SilentlyContinue
if (-not $php) {
  Write-Err "PHP CLI not found. Install PHP or add it to PATH to run syntax checks."
  exit 2
}

# Workspace root is parent of scripts folder
$workspace = Split-Path -Parent $Root
Write-Info "Scanning for PHP files under $workspace"

$excludeDirs = @('\.git\','node_modules\','_archive\','.vscode\','web\\dist\\','web\\build\\')
$phpFiles = Get-ChildItem -Path $workspace -Recurse -Include *.php -File |
  Where-Object { $p = $_.FullName; -not ($excludeDirs | Where-Object { $p -like "*$_*" }) }

if (-not $phpFiles) { Write-Info 'No PHP files found'; exit 0 }

$fail = 0
foreach ($f in $phpFiles) {
  $res = & php -l -- $f.FullName 2>&1
  if ($LASTEXITCODE -ne 0 -or ($res -match 'Errors parsing')) {
    Write-Err ("PHP syntax error: {0}`n{1}" -f $f.FullName, $res)
    $fail++
  }
}

if ($fail -gt 0) {
  Write-Err ("PHP syntax check failed in $fail file(s)")
  exit 1
}
Write-Ok 'PHP syntax check passed'
exit 0
