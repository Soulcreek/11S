# 🚀 MANUELLE SERVER REPARATUR - NETCUP

## SSH VERBINDUNG
```bash
ssh hosting223936@hosting223936.ae94b.netcup.net
# Passwort: hallo.4Netcup
```

## SCHNELLE DIAGNOSE
Kopiere diese Befehle **einzeln** in die SSH-Konsole:

### 1. BASIS-CHECK
```bash
cd 11seconds.de
pwd && ls -la
```

### 2. NODE.JS VERFÜGBARKEIT
```bash
node --version || echo "Node.js NICHT installiert!"
npm --version || echo "NPM NICHT installiert!"
```

### 3. PROZESS-STATUS
```bash
ps aux | grep "node app.js" | grep -v grep
```

### 4. LOG-CHECK
```bash
tail -20 app.log 2>/dev/null || echo "Keine Logs gefunden"
```

### 5. PORT-STATUS
```bash
netstat -tlnp | grep :3011 || echo "Port 3011 NICHT belegt"
```

## REPARATUR-COMMANDS

### Alte Prozesse beenden
```bash
pkill -f "node app.js"
```

### Dependencies installieren
```bash
npm install
```

### App starten
```bash
nohup node app.js > app.log 2>&1 &
```

### Status prüfen
```bash
sleep 3 && ps aux | grep "node app.js" | grep -v grep
tail -5 app.log
```

## MÖGLICHE PROBLEME

1. **Node.js fehlt** → Hosting Support kontaktieren
2. **Permissions** → `chmod +x app.js`
3. **Dependencies** → `npm install` ausführen
4. **Port blockiert** → `pkill -f node` dann neustart
5. **Webroot falsch** → Dateien in `/httpdocs/` verschieben

## ERFOLG-TEST
```
curl -I http://localhost:3011
```

Sollte zeigen: **HTTP/1.1 200 OK**

---
**Hinweis:** Falls du keinen direkten SSH-Zugang hast, nutze das Netcup Control Panel → SSH Terminal
