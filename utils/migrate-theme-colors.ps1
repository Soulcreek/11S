# Theme Color Migration Script
# This script replaces all old purple theme colors with the new light green theme

Write-Host "🎨 Starting Theme Color Migration..." -ForegroundColor Cyan
Write-Host "Converting from Purple (#667eea, #764ba2) to Light Green (#10b981, #059669)" -ForegroundColor Yellow

$oldColors = @{
    '#667eea' = '#10b981'
    '#764ba2' = '#059669'
    '667eea' = '10b981'
    '764ba2' = '059669'
}

$extensions = @('*.php', '*.js', '*.jsx', '*.css', '*.html')
$excludePaths = @('node_modules', '.git', 'web/httpdocs/static', 'web/web/build', '_backup_api_backend')

function Update-ThemeColors {
    param(
        [string]$FilePath
    )
    
    $content = Get-Content $FilePath -Raw -ErrorAction SilentlyContinue
    if (-not $content) { return $false }
    
    $changed = $false
    
    foreach ($oldColor in $oldColors.Keys) {
        if ($content -match [regex]::Escape($oldColor)) {
            $content = $content -replace [regex]::Escape($oldColor), $oldColors[$oldColor]
            $changed = $true
        }
    }
    
    if ($changed) {
        Set-Content -Path $FilePath -Value $content -NoNewline
        Write-Host "✓ Updated: $FilePath" -ForegroundColor Green
        return $true
    }
    
    return $false
}

$totalFiles = 0
$updatedFiles = 0

# Process all relevant files
foreach ($ext in $extensions) {
    $files = Get-ChildItem -Path "." -Filter $ext -Recurse | Where-Object {
        $skip = $false
        foreach ($excludePath in $excludePaths) {
            if ($_.FullName -like "*$excludePath*") {
                $skip = $true
                break
            }
        }
        return -not $skip
    }
    
    foreach ($file in $files) {
        $totalFiles++
        if (Update-ThemeColors -FilePath $file.FullName) {
            $updatedFiles++
        }
    }
}

Write-Host "`n📊 Migration Summary:" -ForegroundColor Cyan
Write-Host "  Total files checked: $totalFiles" -ForegroundColor White
Write-Host "  Files updated: $updatedFiles" -ForegroundColor Green
Write-Host "  Theme migration completed!" -ForegroundColor Green
