# 🚀 11Seconds Quiz Game - Netcup Webhosting 4000 Deployment

## Schritt-für-Schritt Anleitung

### **1. Dateien vorbereiten**

**Dateien die du auf Netcup hochladen musst:**
```
📁 Dein Webhosting Root/
├── 📄 app.js                    (Haupt-Server)
├── 📄 package-production.json   (Abhängigkeiten) 
├── 📄 .env                     (Datenbank-Config)
├── 📁 api/                     (Backend API)
│   ├── 📁 routes/
│   ├── 📁 middleware/
│   ├── 📄 db-switcher.js
│   └── 📄 db-local.js
├── 📁 httpdocs/               (Frontend)
│   ├── 📄 index.html
│   ├── 📄 game.html  
│   ├── 📁 js/
│   └── 📁 css/
└── 📄 setup-extended-questions.js (DB Setup)
```

### **2. Netcup Webhosting vorbereiten**

#### **MySQL Datenbank erstellen:**
1. **Netcup Kundencenter** öffnen
2. **Webhosting** → **Datenbanken** 
3. **Neue MySQL Datenbank** erstellen
4. **Datenbankname, Username, Passwort** notieren
5. **Host bleibt:** `10.35.233.76`

#### **SSH/FTP Zugang:**
- **FTP:** Über FileZilla oder WinSCP
- **SSH:** Falls verfügbar über Terminal

### **3. Dateien hochladen**

#### **Via FTP (FileZilla):**
```bash
# Verbindung:
Host: ftp.deine-domain.de
Username: dein_ftp_username  
Password: dein_ftp_password
Port: 21

# Alle Dateien ins Root-Verzeichnis hochladen
```

#### **Via SSH (falls verfügbar):**
```bash
# Mit SSH verbinden
ssh username@deine-domain.de

# Git Repository klonen (falls Git verfügbar)
git clone https://github.com/Soulcreek/11S.git
cd 11S
```

### **4. Environment konfigurieren**

**Erstelle `.env` Datei mit deinen Netcup MySQL Daten:**
```env
# MySQL Datenbank (deine echten Werte eintragen!)
DB_HOST=10.35.233.76
DB_PORT=3306
DB_USER=dein_mysql_username
DB_PASS=dein_mysql_passwort  
DB_NAME=dein_datenbankname

# Sicherheit (generiere einen sicheren Schlüssel!)
JWT_SECRET=dein_super_sicherer_jwt_schluessel_mindestens_32_zeichen

# Server
PORT=3011
NODE_ENV=production
```

### **5. Dependencies installieren**

```bash
# Package.json umbenennen
mv package-production.json package.json

# Dependencies installieren
npm install

# Falls npm nicht verfügbar ist, kontaktiere Netcup Support
```

### **6. Datenbank einrichten**

```bash
# Automatische Tabellenerstellung durch ersten Start
node app.js

# Oder manuell Fragen hinzufügen:
node setup-extended-questions.js
```

### **7. Server starten**

#### **Für Testing:**
```bash
# Server im Vordergrund starten
node app.js
```

#### **Für Production (im Hintergrund):**
```bash
# Mit nohup (bleibt nach SSH-Trennung aktiv)
nohup node app.js > server.log 2>&1 &

# Log-Datei überwachen
tail -f server.log
```

### **8. Domain/Subdomain konfigurieren**

#### **Netcup Domain-Setup:**
1. **Netcup Kundencenter** → **Domains**
2. **DNS-Zone bearbeiten**
3. **A-Record** auf deine Webhosting-IP setzen
4. **Port-Weiterleitung** auf 3011 konfigurieren

#### **Alternative: Subdomain**
```
# Beispiel: quiz.deine-domain.de
A-Record: quiz → deine-webhosting-ip
Port: 3011
```

### **9. Zugriff testen**

```bash
# Server Status prüfen
curl http://deine-domain.de:3011/api/game/categories

# Frontend öffnen
http://deine-domain.de:3011
```

### **10. Monitoring & Wartung**

#### **Server Status prüfen:**
```bash
# Läuft der Server?
ps aux | grep node

# Log-Datei anzeigen
tail -f server.log

# Server neu starten
pkill node
nohup node app.js > server.log 2>&1 &
```

#### **Datenbank überwachen:**
```bash
# MySQL Verbindung testen
node -e "
const mysql = require('mysql2');
const db = mysql.createConnection({
  host: '10.35.233.76',
  user: 'dein_username',
  password: 'dein_password',
  database: 'dein_dbname'
});
db.query('SELECT COUNT(*) FROM questions', console.log);
"
```

## 🆘 **Problemlösung**

### **Port 3011 nicht erreichbar:**
- Netcup Support kontaktieren für Port-Freischaltung
- Alternative: Port 80/443 verwenden (Reverse Proxy)

### **MySQL Verbindung fehlschlägt:**
- DB-Credentials in `.env` prüfen  
- Netcup MySQL Service Status prüfen
- Fallback: System nutzt automatisch SQLite

### **npm install schlägt fehl:**
- Node.js Version prüfen (`node --version`)
- Netcup Support nach Node.js/npm fragen

## 📞 **Support Kontakte**

- **Netcup Support:** https://www.netcup.de/kontakt/
- **Entwickler:** Marcel (GitHub: Soulcreek)

---

## 🎮 **Nach erfolgreichem Deployment:**

**Dein 11Seconds Quiz Game läuft unter:**
```
🌐 Frontend: http://deine-domain.de:3011
🔗 API: http://deine-domain.de:3011/api
📊 Kategorien: http://deine-domain.de:3011/api/game/categories
🏆 Highscores: http://deine-domain.de:3011/api/game/highscores
```

**Das Spiel unterstützt:**
- ✅ Benutzer-Registration/Login
- ✅ Solo-Spiel mit 11-Sekunden Timer
- ✅ 7 Kategorien + 3 Schwierigkeitsgrade  
- ✅ Highscore-System
- ✅ 60+ Fragen in der Datenbank
- ✅ Responsive Design für Mobile

Viel Erfolg beim Deployment! 🚀
