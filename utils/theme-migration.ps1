Write-Host "🎨 Starting Theme Color Migration..." -ForegroundColor Cyan
Write-Host "Converting from Purple to Light Green theme" -ForegroundColor Yellow

# Define color mappings
$colorMappings = @{
    '#667eea' = '#10b981'
    '#764ba2' = '#059669'
    '667eea' = '10b981'
    '764ba2' = '059669'
}

$updatedFiles = 0
$totalFiles = 0

# Find and update PHP files in admin directory
Get-ChildItem -Path "./admin" -Filter "*.php" -Recurse | ForEach-Object {
    $filePath = $_.FullName
    $content = Get-Content $filePath -Raw
    $originalContent = $content
    $totalFiles++
    
    foreach ($oldColor in $colorMappings.Keys) {
        $newColor = $colorMappings[$oldColor]
        $content = $content -replace [regex]::Escape($oldColor), $newColor
    }
    
    if ($content -ne $originalContent) {
        Set-Content -Path $filePath -Value $content -NoNewline
        Write-Host "✓ Updated: $($_.Name)" -ForegroundColor Green
        $updatedFiles++
    }
}

# Find and update React JS files
Get-ChildItem -Path "./web/src" -Filter "*.js" -Recurse | ForEach-Object {
    $filePath = $_.FullName
    $content = Get-Content $filePath -Raw
    $originalContent = $content
    $totalFiles++
    
    foreach ($oldColor in $colorMappings.Keys) {
        $newColor = $colorMappings[$oldColor]
        $content = $content -replace [regex]::Escape($oldColor), $newColor
    }
    
    if ($content -ne $originalContent) {
        Set-Content -Path $filePath -Value $content -NoNewline
        Write-Host "✓ Updated: $($_.Name)" -ForegroundColor Green
        $updatedFiles++
    }
}

Write-Host "`n📊 Migration Summary:" -ForegroundColor Cyan
Write-Host "  Total files checked: $totalFiles" -ForegroundColor White
Write-Host "  Files updated: $updatedFiles" -ForegroundColor Green
