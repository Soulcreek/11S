# 🔧 Netcup FTP Setup Assistant
# Hilft beim sicheren Einrichten der FTP-Zugangsdaten

Write-Host "🔧 Netcup FTP Setup Assistant" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan

$envFile = ".env.netcup"

Write-Host "`n📋 Ich helfe dir beim Einrichten der FTP-Zugangsdaten!" -ForegroundColor Yellow
Write-Host "Du findest diese Daten in deinem Netcup Kundencenter:" -ForegroundColor Gray
Write-Host "  → Webhosting → FTP-Zugang" -ForegroundColor Gray

# FTP Daten abfragen
Write-Host "`n🌐 FTP Zugangsdaten:" -ForegroundColor Cyan

$ftpHost = Read-Host "FTP Host (z.B. ftp.deine-domain.de)"
$ftpUser = Read-Host "FTP Benutzername"
$ftpPassword = Read-Host "FTP Passwort" -AsSecureString
$ftpPasswordPlain = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($ftpPassword))

# MySQL Daten abfragen  
Write-Host "`n🗄️  MySQL Datenbank (optional):" -ForegroundColor Cyan
Write-Host "Erstelle eine MySQL-Datenbank im Netcup Kundencenter:" -ForegroundColor Gray

$dbUser = Read-Host "MySQL Benutzername (leer lassen falls noch nicht erstellt)"
if ($dbUser) {
    $dbPassword = Read-Host "MySQL Passwort" -AsSecureString
    $dbPasswordPlain = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($dbPassword))
    $dbName = Read-Host "Datenbankname"
} else {
    $dbPasswordPlain = "your_mysql_password"
    $dbName = "your_database_name"
    $dbUser = "your_mysql_username"
}

# Domain abfragen
Write-Host "`n🌐 Domain Einstellungen:" -ForegroundColor Cyan
$domain = Read-Host "Deine Domain (z.B. meine-domain.de)"

# JWT Secret generieren
$jwtSecret = -join ((1..32) | ForEach {[char]((65..90) + (97..122) + (48..57) | Get-Random)})

# .env.netcup Datei erstellen
$envContent = @"
# 🔐 Netcup FTP & MySQL Zugangsdaten für AI Deployment
# Generiert am: $(Get-Date)

# === NETCUP FTP ZUGANGSDATEN ===
NETCUP_FTP_HOST=$ftpHost
NETCUP_FTP_USER=$ftpUser
NETCUP_FTP_PASSWORD=$ftpPasswordPlain
NETCUP_FTP_PORT=21

# === NETCUP MYSQL DATENBANK ===
NETCUP_DB_HOST=10.35.233.76
NETCUP_DB_PORT=3306
NETCUP_DB_USER=$dbUser
NETCUP_DB_PASS=$dbPasswordPlain
NETCUP_DB_NAME=$dbName

# === DOMAIN & SERVER ===
NETCUP_DOMAIN=$domain
NETCUP_APP_PORT=3011

# === SICHERHEIT ===
NETCUP_JWT_SECRET=$jwtSecret

# === AI DEPLOYMENT SETTINGS ===
AUTO_DEPLOY_ENABLED=true
BACKUP_BEFORE_DEPLOY=true
RUN_TESTS_BEFORE_DEPLOY=true
"@

$envContent | Out-File $envFile -Encoding UTF8

Write-Host "`n✅ Zugangsdaten erfolgreich gespeichert!" -ForegroundColor Green
Write-Host "📁 Datei erstellt: $envFile" -ForegroundColor Cyan

# Berechtigungen warnen
Write-Host "`n⚠️  WICHTIG - Sicherheitshinweise:" -ForegroundColor Yellow
Write-Host "1. Die Datei $envFile enthält deine Passwörter!" -ForegroundColor Red
Write-Host "2. Sie wird NICHT zu Git hinzugefügt (.gitignore)" -ForegroundColor Green
Write-Host "3. Teile diese Datei NIEMALS öffentlich!" -ForegroundColor Red

# FTP Verbindung testen
Write-Host "`n🔌 Teste FTP-Verbindung..." -ForegroundColor Yellow

try {
    $ftpRequest = [System.Net.FtpWebRequest]::Create("ftp://$ftpHost/")
    $ftpRequest.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPasswordPlain)
    $ftpRequest.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $ftpRequest.Timeout = 10000
    
    $response = $ftpRequest.GetResponse()
    $response.Close()
    
    Write-Host "✅ FTP-Verbindung erfolgreich!" -ForegroundColor Green
} catch {
    Write-Host "❌ FTP-Verbindung fehlgeschlagen: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "💡 Prüfe deine FTP-Zugangsdaten im Netcup Kundencenter" -ForegroundColor Yellow
}

Write-Host "`n🚀 Setup abgeschlossen!" -ForegroundColor Green
Write-Host "Du kannst jetzt AI-Deployments verwenden:" -ForegroundColor Cyan
Write-Host "  → Sage: 'deploy now'" -ForegroundColor White
Write-Host "  → Oder: Ctrl+Shift+P → 'AI Deploy to Netcup'" -ForegroundColor White

Write-Host "`n💡 Nächste Schritte:" -ForegroundColor Yellow
Write-Host "1. MySQL-Datenbank im Netcup Kundencenter erstellen" -ForegroundColor White
Write-Host "2. Sage: 'deploy now' für erstes Deployment" -ForegroundColor White
Write-Host "3. Sage: 'setup database' um Fragen hinzuzufügen" -ForegroundColor White

Read-Host "`nDrücke Enter zum Beenden"
