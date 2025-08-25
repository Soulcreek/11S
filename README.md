# 🚀 11Seconds Quiz - ONE-PAGE PROJECT OVERVIEW

## 📍 **Was ist das?**

Ein **React-Quiz-Spiel mit PHP-Admin-Panel** - deployed als statische Website.

## 🏗️ **Projekt-Struktur (EINFACH)**

```
11S/
├── web/                    # React Quiz Game
│   ├── src/               # React Source
│   ├── httpdocs/          # Build Output (AUTO-GENERATED)
│   └── package.json       # Dependencies
├── admin/                 # PHP Admin Center
│   ├── admin-center.php   # MAIN ENTRY POINT ⭐
│   ├── pages/             # Modular Content
│   └── includes/          # Styles & Config
├── api/                   # Simple PHP APIs
├── package.json           # Root Dependencies
└── deploy.ps1             # ONE DEPLOYMENT SCRIPT ⭐
```

## 🚀 **Development Workflow**

### 1. **Entwickeln**

```bash
cd web
npm install
npm start                  # React dev server
```

### 2. **Builden**

```bash
cd web
npm run build             # Output: web/httpdocs/
```

### 3. **Deployen**

```powershell
.\deploy.ps1              # FTP Upload to 11seconds.de
```

## 🌐 **Live URLs**

- **Main Site:** https://11seconds.de
- **Admin Center:** https://11seconds.de/admin/admin-center.php ⭐
- **API:** https://11seconds.de/api/

## 🔧 **Tech Stack**

- **Frontend:** React 18 + Modern CSS
- **Backend:** PHP 8+ (stateless, file-based)
- **Hosting:** Static Files + PHP (Netcup)
- **Database:** JSON Files (simple)
- **Deployment:** PowerShell + FTP

## ⚠️ **WICHTIG**

- **Kein Node.js Server** im Production
- **Nur statische Files + PHP**
- **Admin-Center = einziger Entry-Point**
- **Alles andere ist Legacy/Backup**

IMPORTANT DEPLOYMENT POLICY:
- Admin files MUST always be uploaded to the remote path `/httpdocs/admin` on the FTP host.
- Never upload `package.json` into `/httpdocs` — keep it in the repository root.

## 🔑 **Quick Start**

1. `git clone`
2. `cd web && npm install && npm start`
3. Open http://localhost:3010
	- Note: Primary local server port changed to 3010. Secondary/testing services may use 3011.
4. Admin: https://11seconds.de/admin/admin-center.php

## 📞 **Bei Problemen**

- **FTP-Fehler:** Environment vars setzen
- **Build-Fehler:** `cd web && npm ci`
- **Admin 404:** admin-center.php prüfen

### Fallback: manual admin package

If FTP fails due to server-side passive data channel issues, create a ZIP package of the `admin/` files locally and upload it via the hosting control panel. A helper script is available:

PowerShell (Windows):

```powershell
.
scripts\package-admin.ps1 -Out dist\admin-package.zip
```

Then upload `dist\admin-package.zip` to your host control panel and extract into `/httpdocs/admin`. Remember to upload `config/.env` separately to `/httpdocs/config/.env`.

---

**Das war's! 🎯 KISS Prinzip: Keep It Simple, Stupid!**
