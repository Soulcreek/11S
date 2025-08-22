# 11Seconds Quiz Game - Professional Admin Center

## 📋 Übersicht

Das Admin Center ist eine professionelle, webbasierte Verwaltungsoberfläche für das 11Seconds Quiz Game mit erweiterten Sicherheitsfunktionen, Benutzerauthentifizierung und Echtzeit-Monitoring.

## 🚀 Erweiterte Features (NEU)

### � Professionelles Authentifizierungssystem

- **Multi-Modal Authentication:** Benutzername/E-Mail + Passwort, Google OAuth, Gast-Accounts
- **Verifizierungssysteme:** E-Mail-Verifizierung mit professionellen Templates, SMS via Twilio
- **Passwort-Sicherheit:** PBKDF2-Hashing mit 10.000 Iterationen + Salt
- **Session-Management:** Sichere Token-basierte Sessions mit konfigurierbaren Timeouts
- **Rate Limiting:** Schutz vor Brute-Force-Angriffen und Spam

### 👥 Erweiterte Benutzerverwaltung (`user-management-enhanced.php`)

- **Professionelle Oberfläche:** Erweiterte Filterung, Paginierung, Bulk-Operationen
- **Echtzeit-Statistiken:** Aktive Benutzer, Registrierungstrends, Sicherheitsmetriken
- **Sicherheits-Monitoring:** Fehlgeschlagene Login-Versuche, verdächtige Aktivitäten
- **Benutzeroperationen:** Erstellen, bearbeiten, löschen mit vollständigem Audit-Trail

### 🛡️ Security Dashboard (`security-dashboard.php`)

- **Bedrohungs-Monitoring:** Echtzeit-Sicherheitswarnungen und verdächtige Aktivitätserkennung
- **Angriffsprävention:** Rate Limiting, Brute-Force-Schutz, Session-Management
- **Audit-Logging:** Umfassende Aktivitätsverfolgung und forensische Analyse
- **Alert-Management:** Automatische Bedrohungserkennung mit Lösungsworkflows

### 🎯 Anti-Cheat-System

- **Score-Validierung:** Multi-Layer-Validierung mit Zeit-/Muster-/Session-Checks
- **Betrugserkennung:** Automatisierte Erkennung verdächtiger Aktivitäten
- **Session-Integrität:** Schutz vor Session-Manipulation
- **Pattern-Analyse:** Erkennung unnatürlicher Antwortmuster

### 🤖 KI-gestützte Features

- **Question Generator:** Automatische Fragenerstellung mit Google Gemini AI
- **Smart Analytics:** Benutzerverhalten-Analyse und Performance-Insights
- **Content-Management:** Bulk-Import/Export von Fragen mit Qualitätsbewertung

## 🏗️ Technische Architektur

### Core-Authentifizierung (`includes/`)

- **`AuthManager.php`:** Zentrale Authentifizierungslogik mit vollständiger Sicherheit
- **`GoogleAuth.php`:** Google OAuth-Integration mit JWT-Verifizierung

### Datenstruktur

```
admin/
├── includes/                   # Authentifizierungssystem
│   ├── AuthManager.php        # Kern-Authentifizierung
│   └── GoogleAuth.php         # Google OAuth Integration
├── data/                      # Sichere Datenspeicherung
│   ├── auth-config.json       # Sicherheitskonfiguration
│   ├── users.json             # Benutzerdaten (automatisch erstellt)
│   └── questions.json         # Fragendatenbank
├── dashboard.php              # Haupt-Dashboard
├── user-management-enhanced.php # Professionelle Benutzerverwaltung
├── security-dashboard.php     # Sicherheits-Monitoring
├── question-management.php    # Fragenverwaltung
├── question-generator.php     # KI-Fragengenerator
├── settings.php               # Systemeinstellungen
└── uploads/                   # Datei-Uploads
```

## 🛠 Installation & Setup

### Automatische Deployment

Das Admin Center wird automatisch mit dem Haupt-Deployment-Script installiert:

```powershell
# Komplette System-Deployment (inkl. Admin Center)
powershell -ExecutionPolicy Bypass -File .\deploy.ps1 -Method ftp -Build
```

### Manuelle Installation

1. Kopieren Sie das `/admin/` Verzeichnis auf Ihren Webserver
2. Stellen Sie sicher, dass PHP 7.4+ installiert ist
3. Vergeben Sie Schreibrechte für `/admin/data/` Verzeichnis
4. Öffnen Sie `https://yourdomain.com/admin/` im Browser

### Erste Anmeldung

- **Benutzername:** `admin`
- **Passwort:** `admin123`

⚠️ **KRITISCH:** Ändern Sie diese Anmeldedaten SOFORT nach der ersten Anmeldung!

## 🔧 Erweiterte Konfiguration

### Externe Dienste konfigurieren

Nach der Installation konfigurieren Sie diese Services im Admin Panel:

#### 1. E-Mail-Verifizierung (SMTP)

```json
{
  "smtp": {
    "host": "smtp.your-provider.com",
    "port": 587,
    "username": "your-email@domain.com",
    "password": "your-app-password",
    "encryption": "tls"
  }
}
```

#### 2. SMS-Verifizierung (Twilio)

```json
{
  "twilio": {
    "account_sid": "your-account-sid",
    "auth_token": "your-auth-token",
    "phone_number": "+1234567890"
  }
}
```

#### 3. Google OAuth

```json
{
  "google_oauth": {
    "client_id": "your-client-id.googleusercontent.com",
    "client_secret": "your-client-secret"
  }
}
```

### Sicherheitseinstellungen

Konfigurieren Sie diese Sicherheitsparameter im Admin Panel:

#### Passwort-Richtlinien

- **Mindestlänge:** 8 Zeichen (empfohlen: 12+)
- **Komplexität:** Groß-, Kleinbuchstaben, Zahlen
- **Sonderzeichen:** Optional aktivierbar
- **Passwort-Historie:** Verhindert Wiederverwendung

#### Rate Limiting

- **Login-Versuche:** 5 pro 15 Minuten
- **Registrierungen:** 3 pro Stunde pro IP
- **Score-Einreichungen:** 10 pro Minute
- **API-Aufrufe:** 100 pro Minute

#### Session-Management

- **Session-Timeout:** 1 Stunde (konfigurierbar)
- **Sichere Cookies:** HTTPS-only in Produktion
- **Cross-Site-Schutz:** CSRF-Token für alle Formulare

## 🔐 Professionelle Sicherheit

### Erweiterte Anti-Cheat-Maßnahmen

- **Zeit-Validierung:** Verhindert unmögliche Completion-Zeiten
- **Score-Verifizierung:** Server-seitige Punkteberechnung
- **Pattern-Erkennung:** Identifizierung verdächtiger Antwortmuster
- **Session-Integrität:** Schutz vor Session-Manipulation

### Monitoring & Alerts

- **Echtzeit-Dashboard:** Live-Bedrohungserkennung
- **Automatische Alerts:** E-Mail/SMS-Benachrichtigungen bei Sicherheitsereignissen
- **Forensische Analyse:** Detaillierte Logs für Incident Response
- **Compliance-Reporting:** Audit-Trails für Sicherheitsaudits

## 🎯 Verwendung der neuen Features

### Security Dashboard

1. Navigieren Sie zu **Sicherheits-Dashboard**
2. Überwachen Sie Echtzeit-Sicherheitsstatistiken
3. Überprüfen Sie verdächtige Aktivitäten
4. Lösen Sie Sicherheitswarnungen auf
5. Exportieren Sie Security-Reports

### Erweiterte Benutzerverwaltung

1. Nutzen Sie **Erweiterte Benutzerverwaltung**
2. Filtern Sie Benutzer nach Status, Registrierungsdatum, Aktivität
3. Führen Sie Bulk-Operationen durch
4. Überwachen Sie Benutzerstatistiken in Echtzeit
5. Verwalten Sie Benutzerberechtigungen

### Authentifizierungs-Features

- **Guest-zu-User-Konvertierung:** Nahtlose Umwandlung von Gast-Accounts
- **Multi-Channel-Verifizierung:** E-Mail ODER SMS-Verifizierung
- **Social Login:** Google OAuth mit automatischer Kontoverknüpfung
- **Session-Management:** Erweiterte Session-Kontrolle mit Timeout-Konfiguration

## 🔧 Konfiguration (Legacy)

### Google Gemini API

Für die KI-basierte Fragenerstellung:

1. Besuchen Sie [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Erstellen Sie einen kostenlosen API-Schlüssel
3. Geben Sie den Schlüssel in den Admin-Einstellungen ein

### Datenspeicherung

- Alle Daten werden im `/admin/data/` Verzeichnis in JSON-Dateien gespeichert
- `users.json` - Benutzerdaten
- `questions.json` - Fragendatenbank
- `config.json` - System-Konfiguration

## 🔐 Sicherheit

- Session-basierte Authentifizierung
- Passwörter werden gehasht gespeichert
- API-Schlüssel werden verschlüsselt gespeichert
- CSRF-Schutz für alle Formulare

## 📁 Dateistruktur

```
admin/
├── index.php              # Login-Seite
├── dashboard.php          # Dashboard
├── user-management.php    # Benutzerverwaltung
├── question-management.php # Fragenverwaltung
├── question-generator.php  # KI-Generator
├── backup.php             # Backup-Funktionen
├── settings.php           # Einstellungen
└── data/                  # Datenverzeichnis
    ├── users.json         # Benutzerdaten
    ├── questions.json     # Fragen
    └── config.json        # Konfiguration
```

## 🎯 Verwendung

### Dashboard

- Zentrale Übersicht über alle wichtigen Statistiken
- Schnellzugriff auf alle Verwaltungsfunktionen

### Benutzerverwaltung

- Alle registrierten Benutzer einsehen
- Spielstatistiken verfolgen
- Neue Benutzer manuell hinzufügen

### Fragen verwalten

- Neue Fragen manuell hinzufügen
- Bestehende Fragen durchsuchen und filtern
- Kategorien verwalten

### KI-Generator

- Automatische Fragenerstellung
- Verschiedene Kategorien und Schwierigkeitsgrade
- Spezifische Themen angeben
- Vorschau vor dem Speichern

## 🔄 Backup

Erstellen Sie regelmäßig Backups:

- **Täglich:** Für produktive Systeme
- **Wöchentlich:** Für Testsysteme
- **Vor Updates:** Immer ein Backup erstellen

## 🐛 Fehlerbehebung

### Häufige Probleme

**Login funktioniert nicht:**

- Überprüfen Sie die Session-Konfiguration in PHP
- Stellen Sie sicher, dass Cookies aktiviert sind

**Daten werden nicht gespeichert:**

- Überprüfen Sie die Schreibrechte für `/admin/data/`
- PHP muss Dateien erstellen und ändern können

**KI-Generator funktioniert nicht:**

- Überprüfen Sie den Google Gemini API-Schlüssel
- Stellen Sie sicher, dass der Server Internetzugang hat

## 📞 Support

Bei Problemen oder Fragen wenden Sie sich an das Entwicklungsteam.

## 📄 Lizenz

Dieses Admin Center ist Teil des 11Seconds Quiz Game Projekts.
