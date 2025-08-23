# 11 Seconds Quiz - Projekt-Dokumentation

## 🔴 FUNDAMENTALE VORAUSSETZUNG

**DIESE APP IST VOLLSTÄNDIG STATISCH - KEIN SERVER ERFORDERLICH!**

### Architektur:

- **Frontend**: React (läuft im Browser)
- **Daten**: localStorage (client-seitig)
- **Hosting**: Statische Dateien (HTML/CSS/JS)
- **Deployment**: FTP Upload von Build-Dateien

### Was NICHT verwendet wird:

- Node.js Server
- PHP Backend
- Datenbank (MySQL/PostgreSQL)
- API-Endpunkte
- Session-Management

### ⚠️ Legacy-Problem:

Code enthält noch API-Aufrufe zu `/api/*` die zu 404 führen, aber die App ignoriert diese Fehler und verwendet localStorage.

## 📁 Struktur

```
11S/
├── web/                    # React App (EINZIGE PRODUKTIVE KOMPONENTE)
│   ├── src/               # React Source Code
│   ├── httpdocs/          # Build Output (deployment-ready)
├── httpdocs/              # Lokale Kopie für Upload
├── deploy-new.ps1         # Deployment-Script für statische Dateien
├── api/                   # LEGACY - LEER/UNUSED
└── app.js                 # LEGACY - LEER
```

## 🚀 Deployment

```powershell
.\deploy-new.ps1 -Component web
```

Uploadet nur statische Dateien - kein Server-Setup erforderlich!

## ⚠️ Für zukünftige Entwickler:

**BITTE KEINE SERVER-KOMPONENTEN HINZUFÜGEN!**

- App muss statisch bleiben
- Alle Features über localStorage
- Kein Backend erforderlich
