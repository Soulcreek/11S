# 🔌 FTP-Verbindungstest für Netcup

# Lade Zugangsdaten
if (Test-Path ".env.netcup") {
    Get-Content ".env.netcup" | ForEach-Object {
        if ($_ -match "^([^=]+)=(.*)$") {
            $key = $matches[1].Trim()
            $value = $matches[2].Trim()
            [Environment]::SetEnvironmentVariable($key, $value, "Process")
        }
    }
    Write-Host "✅ Zugangsdaten geladen" -ForegroundColor Green
} else {
    Write-Host "❌ .env.netcup nicht gefunden!" -ForegroundColor Red
    exit 1
}

$ftpHost = $env:NETCUP_FTP_HOST
$ftpUser = $env:NETCUP_FTP_USER
$ftpPass = $env:NETCUP_FTP_PASSWORD

Write-Host "🔌 Teste FTP-Verbindung zu: $ftpHost" -ForegroundColor Cyan
Write-Host "👤 Benutzer: $ftpUser" -ForegroundColor Gray

try {
    $ftpRequest = [System.Net.FtpWebRequest]::Create("ftp://$ftpHost/")
    $ftpRequest.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
    $ftpRequest.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $ftpRequest.Timeout = 15000
    
    Write-Host "⏳ Verbinde..." -ForegroundColor Yellow
    $response = $ftpRequest.GetResponse()
    
    Write-Host "✅ FTP-Verbindung erfolgreich!" -ForegroundColor Green
    Write-Host "🎉 Bereit für automatisches Deployment!" -ForegroundColor Green
    
    $response.Close()
} catch {
    Write-Host "❌ FTP-Verbindung fehlgeschlagen!" -ForegroundColor Red
    Write-Host "Fehler: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "" -ForegroundColor White
    Write-Host "💡 Prüfe folgende Punkte:" -ForegroundColor Yellow
    Write-Host "  1. FTP-Host korrekt? (ftp.deine-domain.de)" -ForegroundColor White
    Write-Host "  2. Benutzername richtig geschrieben?" -ForegroundColor White
    Write-Host "  3. Passwort korrekt eingegeben?" -ForegroundColor White
    Write-Host "  4. FTP im Netcup Kundencenter aktiviert?" -ForegroundColor White
}
