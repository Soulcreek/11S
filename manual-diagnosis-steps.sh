# MANUELLE SERVER-DIAGNOSE CHECKLISTE
# Kopiere diese Kommandos einzeln in deine SSH-Sitzung

echo "🔍 === SCHRITT 1: VERBINDUNG TESTEN ==="
whoami
pwd

echo "🔍 === SCHRITT 2: ZUM WEB ROOT ==="
cd 11seconds.de
pwd
ls -la

echo "🔍 === SCHRITT 3: DATEIEN PRÜFEN ==="
ls -la app.js
ls -la package.json
ls -la .env
ls -la api/
ls -la httpdocs/

echo "🔍 === SCHRITT 4: NODE.JS PRÜFEN ==="
node --version
npm --version

echo "🔍 === SCHRITT 5: PROZESSE PRÜFEN ==="
ps aux | grep node
ps aux | grep app.js

echo "🔍 === SCHRITT 6: LOGS PRÜFEN ==="
ls -la *.log
tail -20 app.log

echo "🔍 === SCHRITT 7: PORTS PRÜFEN ==="
netstat -tlnp | grep :3011

echo "🛠️ === SCHRITT 8: REPARATUR VERSUCHEN ==="
# Alte Prozesse beenden
pkill -f "node app.js"

# Dependencies installieren
npm install

# App starten
nohup node app.js > app.log 2>&1 &

# Warten
sleep 3

echo "✅ === SCHRITT 9: FINAL CHECK ==="
ps aux | grep "node app.js" | grep -v grep
tail -10 app.log
netstat -tlnp | grep :3011

echo "🌐 URL: https://11seconds.de:3011"
