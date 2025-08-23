# 11 Seconds Quiz - Architektur-Dokumentation

## 🔴 KRITISCHE VORAUSSETZUNGEN

### ✅ **STATISCHES HOSTING**

- **KEINE NODE.JS SERVER** - App läuft vollständig im Browser
- **KEINE BACKEND-API** - Alle Daten über localStorage
- **NUR STATISCHE DATEIEN** - HTML, CSS, JS, Assets
- **KEIN PHP/DATABASE** - Rein clientseitige Anwendung

### ⚠️ **DEPLOYMENT-ZIEL**

- **Statisches Webhosting** (wie GitHub Pages, Netlify, oder statische FTP)
- **Kein Server-Code** wird ausgeführt
- **Alle Funktionen** müssen im Browser funktionieren

## 📁 **AKTUELLE DATEI-STRUKTUR**

```
11S/
├── web/                    # React Frontend (EINZIGE PRODUKTIVE KOMPONENTE)
│   ├── src/               # React Source Code
│   ├── public/            # Statische Assets
│   └── httpdocs/          # React Build Output (DEPLOYMENT-BEREIT)
├── httpdocs/              # Lokale Kopie für Deployment
├── api/                   # LEGACY - NICHT VERWENDET
├── app.js                 # LEGACY - LEER
└── deploy-new.ps1         # Deployment Script für statische Dateien
```

## 🎯 **FUNKTIONALITÄTEN**

### ✅ **Was funktioniert (localStorage-basiert):**

- Benutzer-Registrierung/Login (lokale Speicherung)
- Gastmodus
- Quiz-Spiel mit 8 Kategorien
- Highscores
- Admin-Panel
- Einstellungen
- Achievements
- Mobile Responsive Design

### **Was NICHT existiert:**

- Server-basierte Authentifizierung
- Datenbank-Verbindungen
- API-Endpunkte
- Session-Management
- Server-seitiges Rendering

## 🔧 **DEPLOYMENT-PROZESS**

1. **React Build**: `npm run build` in `/web`
2. **Statische Dateien**: Output nach `/web/httpdocs`
3. **FTP Upload**: Alle Dateien auf statisches Hosting
4. **KEIN SERVER** erforderlich

## ⚠️ **LEGACY-PROBLEME (ZU KORRIGIEREN)**

### API-Aufrufe die entfernt werden müssen:

- `/api/auth/guest`
- `/api/auth/register`
- `/api/auth/verify`
- `/api/auth/login`
- `/api/auth/google.php`

Diese führen zu 404-Fehlern, aber App funktioniert trotzdem via localStorage-Fallback.

## 🎯 **TODO: API-CLEANUP**

Alle `fetch('/api/...')` Aufrufe müssen ersetzt werden durch:

- Direkte localStorage-Operationen
- Client-seitige Validierung
- Browser-basierte Logik

**WICHTIG**: Keine Server-Funktionalität hinzufügen - nur API-Aufrufe entfernen!
