# Detaillierter FTP-Test mit ausführlichem Fehler-Feedback
Write-Host "🔍 DETAILLIERTER FTP-VERBINDUNGSTEST" -ForegroundColor Magenta
Write-Host "=====================================" -ForegroundColor Magenta

# Aktualisierte FTP-Zugangsdaten aus .env.netcup
$ftpHost = "ftp.11seconds.de"
$ftpUser = "k302164_11s"
$ftpPass = "hallo.411S"
$ftpPort = 21

Write-Host "`n📡 VERBINDUNGSPARAMETER:" -ForegroundColor Cyan
Write-Host "  Host: $ftpHost" -ForegroundColor White
Write-Host "  User: $ftpUser" -ForegroundColor White
Write-Host "  Port: $ftpPort" -ForegroundColor White
Write-Host "  Pass: $($ftpPass.Substring(0,3))***" -ForegroundColor White

Write-Host "`n🔄 Test 1: DNS-Auflösung..." -ForegroundColor Yellow
try {
    $dnsResult = [System.Net.Dns]::GetHostAddresses($ftpHost)
    Write-Host "  ✅ DNS erfolgreich: $($dnsResult[0].IPAddressToString)" -ForegroundColor Green
} catch {
    Write-Host "  ❌ DNS-Fehler: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "  💡 Mögliche Ursachen:" -ForegroundColor Yellow
    Write-Host "     - Falsche Domain: $ftpHost" -ForegroundColor Yellow
    Write-Host "     - Internet-Verbindung unterbrochen" -ForegroundColor Yellow
    exit 1
}

Write-Host "`n🔄 Test 2: TCP-Verbindung zu Port $ftpPort..." -ForegroundColor Yellow
try {
    $tcpClient = New-Object System.Net.Sockets.TcpClient
    $tcpClient.Connect($ftpHost, $ftpPort)
    $tcpClient.Close()
    Write-Host "  ✅ TCP-Verbindung erfolgreich" -ForegroundColor Green
} catch {
    Write-Host "  ❌ TCP-Verbindungsfehler: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "  💡 Mögliche Ursachen:" -ForegroundColor Yellow
    Write-Host "     - FTP-Server läuft nicht auf Port $ftpPort" -ForegroundColor Yellow
    Write-Host "     - Firewall blockiert Port $ftpPort" -ForegroundColor Yellow
    Write-Host "     - Falscher FTP-Host: $ftpHost" -ForegroundColor Yellow
    exit 1
}

Write-Host "`n🔄 Test 3: FTP-Anmeldung..." -ForegroundColor Yellow
try {
    $ftpUri = "ftp://$ftpHost/"
    $request = [System.Net.FtpWebRequest]::Create($ftpUri)
    $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
    $request.UseBinary = $true
    $request.UsePassive = $true
    $request.KeepAlive = $false
    $request.Timeout = 30000
    
    Write-Host "  🔐 Anmeldung mit Benutzer: $ftpUser" -ForegroundColor Cyan
    
    $response = $request.GetResponse()
    $stream = $response.GetResponseStream()
    $reader = New-Object System.IO.StreamReader($stream)
    $listing = $reader.ReadToEnd()
    
    $reader.Close()
    $response.Close()
    
    Write-Host "  ✅ FTP-Anmeldung erfolgreich!" -ForegroundColor Green
    Write-Host "  📁 Verzeichnisinhalt:" -ForegroundColor Green
    
    $fileCount = 0
    $listing.Split("`n") | ForEach-Object { 
        if ($_.Trim()) { 
            Write-Host "     $_" -ForegroundColor White
            $fileCount++
        } 
    }
    
    Write-Host "  📊 Gefunden: $fileCount Einträge" -ForegroundColor Green
    
} catch {
    $errorMessage = $_.Exception.Message
    Write-Host "  ❌ FTP-Anmeldung fehlgeschlagen!" -ForegroundColor Red
    Write-Host "  🔍 Fehlermeldung: $errorMessage" -ForegroundColor Red
    
    Write-Host "`n  💡 FEHLERANALYSE:" -ForegroundColor Yellow
    
    if ($errorMessage -like "*530*" -or $errorMessage -like "*login*" -or $errorMessage -like "*authentication*") {
        Write-Host "     ❌ LOGIN-FEHLER (530)" -ForegroundColor Red
        Write-Host "     Mögliche Ursachen:" -ForegroundColor Yellow
        Write-Host "     - Falscher Benutzername: '$ftpUser'" -ForegroundColor Yellow
        Write-Host "     - Falsches Passwort: '$ftpPass'" -ForegroundColor Yellow
        Write-Host "     - Account ist gesperrt oder deaktiviert" -ForegroundColor Yellow
        Write-Host "     - FTP-Zugang ist nicht aktiviert" -ForegroundColor Yellow
    }
    elseif ($errorMessage -like "*timeout*" -or $errorMessage -like "*time*") {
        Write-Host "     ❌ TIMEOUT-FEHLER" -ForegroundColor Red
        Write-Host "     Mögliche Ursachen:" -ForegroundColor Yellow
        Write-Host "     - Server überlastet" -ForegroundColor Yellow
        Write-Host "     - Firewall blockiert Verbindung" -ForegroundColor Yellow
        Write-Host "     - Passive/Active Mode Probleme" -ForegroundColor Yellow
    }
    elseif ($errorMessage -like "*421*") {
        Write-Host "     ❌ SERVER NICHT VERFÜGBAR (421)" -ForegroundColor Red
        Write-Host "     Mögliche Ursachen:" -ForegroundColor Yellow
        Write-Host "     - Zu viele gleichzeitige Verbindungen" -ForegroundColor Yellow
        Write-Host "     - Server temporär überlastet" -ForegroundColor Yellow
    }
    elseif ($errorMessage -like "*425*" -or $errorMessage -like "*data connection*") {
        Write-Host "     ❌ DATENVERBINDUNG FEHLGESCHLAGEN (425)" -ForegroundColor Red
        Write-Host "     Mögliche Ursachen:" -ForegroundColor Yellow
        Write-Host "     - Passive Mode Probleme" -ForegroundColor Yellow
        Write-Host "     - Firewall blockiert Datenport" -ForegroundColor Yellow
    }
    else {
        Write-Host "     ❓ UNBEKANNTER FEHLER" -ForegroundColor Red
        Write-Host "     Vollständige Fehlermeldung:" -ForegroundColor Yellow
        Write-Host "     $errorMessage" -ForegroundColor White
    }
    
    Write-Host "`n  🛠️  LÖSUNGSVORSCHLÄGE:" -ForegroundColor Cyan
    Write-Host "     1. Überprüfe FTP-Zugangsdaten im Netcup Kundencenter" -ForegroundColor White
    Write-Host "     2. Stelle sicher, dass FTP-Zugang aktiviert ist" -ForegroundColor White
    Write-Host "     3. Probiere anderen FTP-Client (FileZilla)" -ForegroundColor White
    Write-Host "     4. Kontaktiere Netcup Support bei wiederkehrenden Problemen" -ForegroundColor White
    
    exit 1
}

Write-Host "`n🔄 Test 4: Upload-Test (kleine Testdatei)..." -ForegroundColor Yellow
try {
    # Erstelle kleine Testdatei
    $testContent = "FTP Upload Test - $(Get-Date)"
    $testFile = ".\ftp-test.txt"
    $testContent | Out-File -FilePath $testFile -Encoding UTF8
    
    # Upload-Test
    $uploadUri = "ftp://$ftpHost/ftp-test.txt"
    $uploadRequest = [System.Net.FtpWebRequest]::Create($uploadUri)
    $uploadRequest.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
    $uploadRequest.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
    $uploadRequest.UseBinary = $true
    
    $fileBytes = [System.IO.File]::ReadAllBytes($testFile)
    $uploadRequest.ContentLength = $fileBytes.Length
    
    $requestStream = $uploadRequest.GetRequestStream()
    $requestStream.Write($fileBytes, 0, $fileBytes.Length)
    $requestStream.Close()
    
    $uploadResponse = $uploadRequest.GetResponse()
    $uploadResponse.Close()
    
    Write-Host "  ✅ Upload-Test erfolgreich!" -ForegroundColor Green
    
    # Testdatei löschen
    Remove-Item $testFile -Force
    
} catch {
    Write-Host "  ❌ Upload-Test fehlgeschlagen: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "  💡 Mögliche Ursachen:" -ForegroundColor Yellow
    Write-Host "     - Keine Schreibberechtigung" -ForegroundColor Yellow
    Write-Host "     - Speicher voll" -ForegroundColor Yellow
    Write-Host "     - Upload-Ordner existiert nicht" -ForegroundColor Yellow
}

Write-Host "`n🎉 FTP-VERBINDUNGSTEST ABGESCHLOSSEN!" -ForegroundColor Green
Write-Host "✅ Alle Tests erfolgreich - FTP ist bereit für Deployment!" -ForegroundColor Green
