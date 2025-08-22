# 📦 11Seconds Quiz Game - Upload Checkliste

## 📁 Ordnerstruktur für Netcup Webhosting:

Lade diese Dateien in dein Webhosting-Root-Verzeichnis hoch:

```
📁 / (Root-Verzeichnis deines Webhostings)
├── 📄 app.js                    ← Haupt-Server-Datei
├── 📄 package.json              ← package-production.json umbenennen
├── 📄 .env                     ← .env-template kopieren & anpassen
├── 📄 setup-extended-questions.js ← Datenbank mit Fragen füllen
├── 📁 api/                     ← Backend API (kompletter Ordner)
│   ├── 📁 routes/
│   │   ├── 📄 auth.js
│   │   └── 📄 game.js  
│   ├── 📁 middleware/
│   │   └── 📄 auth.js
│   ├── 📄 db-switcher.js        ← Smart Database Switcher
│   └── 📄 db-local.js           ← SQLite Fallback
└── 📁 httpdocs/                ← Frontend (kompletter Ordner)
    ├── 📄 index.html
    ├── 📄 game.html
    ├── 📁 js/
    └── 📁 css/
```

## 🚀 Upload-Methoden:

### **Option A: FTP (FileZilla)**
```
Host: ftp.deine-domain.de
Username: dein_ftp_username
Password: dein_ftp_password
Port: 21
```

### **Option B: Netcup Dateimanager**
- Im Kundencenter → Webhosting → Dateimanager
- Dateien per Drag & Drop hochladen

### **Option C: SSH (falls verfügbar)**
```bash
ssh username@deine-domain.de
# Dann Git Repository klonen oder Dateien übertragen
```

## ⚠️ Wichtige Hinweise:

1. **Alle Dateien in das ROOT-Verzeichnis**, nicht in einen Unterordner
2. **package-production.json zu package.json umbenennen**
3. **.env-template zu .env umbenennen und MySQL-Daten eintragen**
4. **Ordnerstruktur beibehalten** (api/ und httpdocs/ als Unterordner)

## 🔧 Nach dem Upload:

1. SSH/Terminal öffnen
2. `cd` in dein Webhosting-Verzeichnis
3. `npm install` ausführen
4. `.env` konfigurieren
5. `node app.js` starten
