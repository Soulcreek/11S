# 🚀 Deployment Dokumentation - Glass Theme 2025

## 📋 Übersicht

Diese Dokumentation beschreibt das aktuelle Deployment-System für das 11Seconds Quiz-Spiel mit dem neuen **Modern Glass Design**.

### 🎨 Design-System

- **Hauptfarben**: Light Green Theme (#22c55e, #16a34a, #34d399)
- **Design**: Modern Glass Design mit backdrop-filter, transparenten Hintergründen
- **Schrift**: Inter (Google Fonts)
- **Effekte**: Glasmorphismus, Hover-Animationen, Shimmer-Effekte

## 🏗️ System-Architektur

### Frontend (React SPA)

- **Location**: `web/` Ordner
- **Build Output**: `web/httpdocs/`
- **Theme**: Vollständig auf Light Green Glass Design umgestellt
- **Components**: Alle React-Komponenten verwenden das neue Farbschema

### Backend (PHP Admin)

- **Location**: `admin/` Ordner
- **Glass Design Files**:
  - `admin/includes/modern-glass-style.php` (Zentrale Design-Datei)
  - `admin/media-management-glass.php` (Medien & Branding)
  - `admin/security-dashboard-glass.php` (Security Dashboard)
  - `admin/user-management-enhanced-glass.php` (Benutzer-Management)

### API

- **Location**: `api/` Ordner
- **Datenbankverbindung**: MySQL/MariaDB über PDO

## 🚀 Deployment-System

### AUTO-DEPLOY-MK System

Das Projekt verwendet ein fortschrittliches deployment system mit folgenden Features:

#### Konfigurationsdatei

- **File**: `AUTO-DEPLOY-MK/mk_deployment-config.yaml`
- **Target**: Netcup FTP (ftp.11seconds.de)
- **Remote Path**: `/httpdocs`

#### Deployment Parts

1. **app-httpdocs**: React SPA Build (Root `/`)
2. **admin**: Admin PHP Files (`/admin`)
3. **api**: Backend API (`/api`)
4. **static**: Statische Assets (`/static`)
5. **root-php**: Root-Level PHP Files
6. **root-assets**: Service Worker, robots.txt, etc.

### 🔧 Verfügbare Tasks

#### VS Code Tasks (tasks.json)

```bash
# 🤖 AI Deploy to Netcup
powershell -ExecutionPolicy Bypass -File ai-deploy.ps1 -Action deploy -Environment production -Verbose

# 📊 AI Deployment Status
powershell -ExecutionPolicy Bypass -File ai-deploy.ps1 -Action status

# 🧪 AI Pre-Deploy Test
powershell -ExecutionPolicy Bypass -File ai-deploy.ps1 -Action test

# 📦 AI Build Package
powershell -ExecutionPolicy Bypass -File ai-deploy.ps1 -Action build

# 🔐 Setup FTP Credentials
powershell -ExecutionPolicy Bypass -File setup-ftp-credentials.ps1
```

#### NPM Scripts (package.json)

```bash
# Standard Deployment (Empfohlen)
npm run deploy-live

# Test Deployment (Dry Run)
npm run deploy-test

# Local Development
npm start
npm run dev
```

### 📋 Deployment Checklist

#### Pre-Deployment

- [ ] Theme-Migration abgeschlossen (Purple → Light Green)
- [ ] Alle Admin-Files auf Glass Design umgestellt
- [ ] React Build erfolgreich mit neuer Theme
- [ ] FTP Credentials konfiguriert

#### Deployment Steps

1. **Build Package**: `ai-deploy.ps1 -Action build`
2. **Test Deployment**: `npm run deploy-test`
3. **Live Deployment**: `npm run deploy-live`
4. **Status Check**: `ai-deploy.ps1 -Action status`

#### Post-Deployment

- [ ] Website-Funktionalität testen
- [ ] Admin-Center überprüfen
- [ ] Design-Konsistenz validieren
- [ ] Performance-Check

## 🎯 Glass Design Implementation

### CSS Custom Properties

```css
:root {
  --primary-green: #22c55e; /* Hauptfarbe */
  --secondary-green: #16a34a; /* Sekundär */
  --accent-green: #34d399; /* Akzent */
  --dark-green: #065f46; /* Dunkler Text */
}
```

### Glass Effects

```css
.glass-card {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 16px;
}
```

### Animation System

- **Hover Effects**: translateY(-4px), scale transforms
- **Loading States**: Shimmer effects, pulse animations
- **Transitions**: smooth 0.3s ease curves
- **Drop Shadows**: rgba(34, 197, 94, 0.3) green glow

## 📁 File Structure

```
11S/
├── admin/                          # PHP Admin Interface
│   ├── includes/
│   │   └── modern-glass-style.php # Central Glass Design
│   ├── media-management-glass.php # Media & Branding
│   ├── security-dashboard-glass.php # Security
│   └── user-management-enhanced-glass.php # Users
├── web/                           # React Frontend
│   ├── src/
│   │   ├── components/           # React Components (Updated)
│   │   └── pages/               # React Pages (Updated)
│   └── httpdocs/               # Build Output
├── api/                          # Backend API
├── static/                       # Static Assets
├── AUTO-DEPLOY-MK/              # Deployment System
│   ├── mk_deployment-config.yaml # Deploy Config
│   └── mk_deploy-cli.js         # Deploy CLI
├── ai-deploy.ps1               # AI Deployment Script
└── setup-ftp-credentials.ps1   # FTP Setup
```

## 🔒 Security Features

### FTP Credentials Storage

- **Location**: `%USERPROFILE%/.11s/ftp-credentials.xml`
- **Encryption**: SecureString (Windows DPAPI)
- **Environment**: `$env:FTP_USER`, `$env:FTP_PASSWORD`

### Admin Purge System

- **Pre-Deploy**: Automatische Bereinigung alter Admin-Files
- **Verification**: HTML-basierte Deployment-Verifizierung
- **Rollback**: Manifest-basierte Rollback-Möglichkeit

## 🌍 Live URLs

### Production URLs

- **Website**: https://11seconds.de/
- **Admin Center**: https://11seconds.de/admin/
- **API Endpoint**: https://11seconds.de/api/

### Development URLs

- **Local Server**: http://localhost:3010 (React Dev - primary)
- **Local API**: http://localhost:3011/api/ (auxiliary / testing)

Note: Default development ports were changed: primary server now uses 3010; use 3011 for auxiliary services or local API testing.

## 🔄 Update Process

### Design Updates

1. **Zentrale Styles**: Update `admin/includes/modern-glass-style.php`
2. **React Theme**: Update CSS custom properties in components
3. **Build & Deploy**: Run full deployment cycle

### Content Updates

1. **Questions**: Use Admin Center → Question Management
2. **Media**: Use Admin Center → Media & Branding
3. **Users**: Use Admin Center → User Management

## 📊 Monitoring

### Deployment Status

- **Command**: `ai-deploy.ps1 -Action status`
- **Logs**: `AUTO-DEPLOY-MK/manifests/`
- **Verification**: Automated HTML verification files

### Performance

- **Lighthouse**: Regelmäßige Performance-Audits
- **Bundle Size**: Überwachung der Build-Größe
- **Load Times**: Frontend Performance Monitoring

## 🛠️ Troubleshooting

### Common Issues

1. **FTP Timeout**: Increase timeout in `mk_deployment-config.yaml`
2. **Build Errors**: Check Node.js version compatibility
3. **Theme Issues**: Verify CSS custom properties loading
4. **Admin Access**: Check PHP session configuration

### Debug Commands

```bash
# Test FTP Connection
node AUTO-DEPLOY-MK/mk_deploy-cli.js --project web --target production --dry-run

# Check Build Output
dir web\httpdocs

# Validate Glass Theme
Start-Process "admin/media-management-glass.php"
```

---

**Created**: 2025-08-24  
**Version**: Glass Theme v1.0  
**Status**: ✅ Ready for Production Deployment
