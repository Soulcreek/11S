# 11Seconds Deployment System v2.0.0

## Übersicht

Zentrales Deployment-System für die 11Seconds Quiz-App, optimiert für Node.js 22.18.0 Hosting.

## Node.js Hosting Konfiguration

```
Node.js-Version: 22.18.0
Package Manager: npm
Dokumentenstamm: /11seconds.de/httpdocs
Anwendungsstamm: /11seconds.de
Anwendungsstartdatei: app.js
URL: http://11seconds.de
```

## Ordnerstruktur & Pfad-Mapping

### Lokale Struktur

```
c:\Users\Marcel\Documents\GitHub\11S\
├── web\                    # React Source Code
├── api\                    # Node.js Backend
├── httpdocs\              # Deployment-bereite Files (generiert)
└── deploy-new.ps1         # Deployment Script
```

### Remote Server Struktur

```
/11seconds.de/
├── httpdocs/              # Dokumentenstamm (React Build Files)
│   ├── index.html
│   ├── static/js/
│   ├── static/css/
│   ├── manifest.json
│   └── sw.js
├── api/                   # API Files (optional)
├── app.js                 # Node.js Startdatei
└── package.json
```

## Deployment Script Verwendung

### Kommandos

```powershell
# Kompletter Build & Deploy (empfohlen)
.\deploy-new.ps1 -Component web

# Nur Build (ohne Upload-Instruktionen)
.\deploy-new.ps1 -Component web -BuildOnly

# Nur Upload-Instruktionen anzeigen
.\deploy-new.ps1 -Component web -UploadOnly

# Force Rebuild (clean install)
.\deploy-new.ps1 -Component web -Force
```

### Parameter

- `-Component`: web | api | all
- `-BuildOnly`: Nur Build, keine Upload-Instruktionen
- `-UploadOnly`: Nur Upload-Instruktionen, kein Build
- `-Force`: Clean install & rebuild

## Deployment Prozess

### 1. Build Phase

1. **Prerequisites Check**: Node.js 22.18.0, npm
2. **Clean Build**: Löscht alte Build-Dateien
3. **Dependencies**: npm install (bei Bedarf)
4. **React Build**: npm run build (production)
5. **Local Deploy**: Kopiert nach httpdocs/
6. **Service Worker Update**: Neue Cache-Version für Browser-Refresh
7. **Validation**: Prüft erforderliche Dateien

### 2. Upload Phase

Das Script zeigt detaillierte Upload-Instruktionen an:

```
MAPPING FOR NODE.JS HOSTING:
Local Path -> Remote Path
httpdocs/* -> /11seconds.de/httpdocs/ (Dokumentenstamm)
api/* -> /11seconds.de/ (Anwendungsstamm)
```

### 3. Kritische Upload-Punkte

⚠️ **WICHTIG**: Alle Inhalte von `httpdocs/*` müssen nach `/11seconds.de/httpdocs/` hochgeladen werden:

- index.html
- static/js/main.\*.js
- static/css/main.\*.css
- manifest.json
- sw.js

## Features

### ✅ Implementiert

- Zentrales Deployment-Script
- Automatischer React Build
- Service Worker Cache-Invalidation
- Pfad-Mapping Validation
- Deployment Manifest
- Build-Statistiken
- Fehlerbehandlung

### 🔄 Cache-Management

- Automatische Service Worker Versionierung
- Timestamp-basierte Cache-Keys
- Browser-Refresh erzwingen

### 📊 Deployment Manifest

Erstellt `deployment-manifest.json` mit:

```json
{
  "DeploymentTime": "2025-08-23 11:35:28",
  "Component": "web",
  "BuildHash": "a1b2c3d4",
  "Files": 42
}
```

## Troubleshooting

### Build Fehler

```powershell
# Clean rebuild mit Force-Parameter
.\deploy-new.ps1 -Component web -Force
```

### Cache Probleme

```powershell
# Service Worker wird automatisch aktualisiert
# Browser: Strg+Shift+R (Hard Refresh)
# Oder: Developer Tools > Application > Clear Storage
```

### Upload Probleme

1. Prüfe Pfad-Mapping: `httpdocs/*` → `/11seconds.de/httpdocs/`
2. Alle Dateien müssen hochgeladen werden
3. Verzeichnisstruktur beibehalten

## Erweiterungen für die Zukunft

### Geplant

- [ ] Automatischer FTP Upload
- [ ] SSH/rsync Support
- [ ] Rollback-Funktionalität
- [ ] Health Checks nach Deploy
- [ ] Multi-Environment Support

### API Deployment

```powershell
# Wenn API-Deployment benötigt wird
.\deploy-new.ps1 -Component api
# oder
.\deploy-new.ps1 -Component all
```

## Changelog

### v2.0.0 (2025-08-23)

- ✅ Komplette Neuimplementierung
- ✅ Node.js 22.18.0 Kompatibilität
- ✅ Automatische Service Worker Updates
- ✅ Pfad-Mapping Validation
- ✅ Deployment Manifest
- ✅ Zentrale Konfiguration
- ✅ Robuste Fehlerbehandlung

### v1.x (deprecated)

- Alte FTP/SSH Implementierung
- Inkonsistente Pfad-Mappings
- Manuelle Cache-Verwaltung
