# 🚀 Quick AI Deployment Commands
# Kurze Scripte für schnelle AI-Befehle

# AI Deploy
function Invoke-AIDeploy {
    param([string]$Action = "deploy", [string]$Message = "")
    & "ai-deploy.ps1" -Action $Action -Message $Message -Verbose
}

# Aliases für häufige Befehle
Set-Alias -Name "ai-deploy" -Value "Invoke-AIDeploy"
Set-Alias -Name "ai-status" -Value { & "ai-deploy.ps1" -Action "status" }
Set-Alias -Name "ai-test" -Value { & "ai-deploy.ps1" -Action "test" }
Set-Alias -Name "ai-build" -Value { & "ai-deploy.ps1" -Action "build" }

# Export für PowerShell Profile
Export-ModuleMember -Function Invoke-AIDeploy
Export-ModuleMember -Alias ai-deploy, ai-status, ai-test, ai-build

Write-Host "🤖 AI Deployment Commands loaded!" -ForegroundColor Green
Write-Host "Available commands:" -ForegroundColor Cyan
Write-Host "  ai-deploy     - Full deployment" -ForegroundColor White
Write-Host "  ai-status     - Check status" -ForegroundColor White  
Write-Host "  ai-test       - Run tests" -ForegroundColor White
Write-Host "  ai-build      - Build package" -ForegroundColor White
