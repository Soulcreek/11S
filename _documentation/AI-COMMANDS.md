# 🤖 AI Chat Commands für 11Seconds Quiz Game Deployment
# Diese Datei definiert Befehle, die du an die AI geben kannst

## 📋 Verfügbare AI-Befehle:

### **Deployment Befehle:**
- `deploy now` - Sofortiges Deployment zu Netcup
- `deploy force` - Deployment trotz lokaler Änderungen
- `deploy test` - Deployment mit Tests
- `build package` - Nur Package erstellen
- `check status` - Deployment Status prüfen
- `run tests` - Pre-Deployment Tests
- `sync files` - FTP Sync zu Server

### **Server Management:**
- `start server` - Lokalen Server starten
- `stop server` - Lokalen Server stoppen
- `restart server` - Server neu starten
- `show logs` - Server Logs anzeigen

### **Datenbank Management:**
- `setup database` - Datenbank initialisieren
- `add questions` - Fragen zur DB hinzufügen
- `test database` - DB Verbindung testen
- `reset database` - Datenbank zurücksetzen

### **Code Management:**
- `check changes` - Git Status prüfen
- `commit changes` - Auto-Commit mit AI Message
- `push code` - Code zu GitHub pushen
- `create backup` - Backup erstellen

## 🔧 Beispiel-Verwendung:

**Du sagst:** "deploy now"
**AI macht:** 
1. Führt Pre-Tests aus
2. Erstellt Production Package
3. Startet Deployment zu Netcup
4. Zeigt Status und Logs

**Du sagst:** "check status"
**AI macht:**
1. Prüft lokale Änderungen
2. Zeigt letztes Deployment
3. Testet Server Status
4. Zeigt Git Status

**Du sagst:** "setup database"
**AI macht:**
1. Testet DB Verbindung
2. Erstellt Tabellen
3. Fügt Sample-Fragen hinzu
4. Bestätigt Setup

## ⚙️ Konfiguration:

Die AI verwendet diese Umgebungsvariablen:
- `NETCUP_FTP_HOST` - FTP Server
- `NETCUP_FTP_USER` - FTP Benutzername  
- `NETCUP_FTP_PASSWORD` - FTP Passwort
- `NETCUP_DB_HOST` - MySQL Host
- `NETCUP_DB_USER` - MySQL User
- `NETCUP_DB_PASS` - MySQL Passwort

## 🤖 AI Automation Features:

1. **Smart Deployment**: Erkennt Änderungen und deployt automatisch
2. **Error Handling**: Bei Fehlern automatische Rollback-Versuche
3. **Status Monitoring**: Kontinuierliche Überwachung
4. **Log Analysis**: Automatische Fehleranalyse
5. **Backup Creation**: Automatische Backups vor Deployment

## 📝 Logging:

Alle AI-Aktionen werden geloggt in:
- `.deployment-info.json` - Deployment History
- `ai-deployment.log` - Detaillierte Logs
- VS Code Terminal - Live Output

---

**💡 Tipp:** Sage einfach natürlich was du willst, z.B.:
- "Bitte deploye das Spiel zu Netcup"
- "Kannst du den Status checken?"
- "Starte den Server und teste die API"
- "Erstelle ein Backup und deploye dann"
