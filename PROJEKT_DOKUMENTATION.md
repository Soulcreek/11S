# 11Seconds Quiz Game - Projektdokumentation

## Projekt-Übersicht

**Name:** 11Seconds Quiz Game  
**Typ:** Rundenbasiertes Schätz-Quiz  
**Plattformen:** Web (React), Mobile (React Native), Backend (Node.js + MySQL)  
**Repository:** https://github.com/Soulcreek/11S  

### Spiel-Konzept
- **Rundenbasiertes Ratespiel** mit Schätzfragen
- **5 Fragen pro Runde**, jeweils 11 Sekunden Antwortzeit
- **Scoring basierend auf Abweichung** vom korrekten Wert
- **Modi:** Solo, Duell, Gruppe (asynchron)
- **Features:** Highscore-Listen, Nutzer-Login, globale Rankings

### MVP-Ziele
1. ✅ Nutzer-Login/Registrierung
2. 🔄 Solo-Spielmodus (5 Fragen, 11s Timer)
3. 📊 Highscore-System
4. 🎯 Globale Rangliste

---

## Aktuelle Projektstruktur

```
11S/
├── 📁 api/                     # Backend API
│   ├── db.js                   # MySQL Datenbankverbindung
│   ├── package.json           # API Dependencies
│   ├── 📁 routes/
│   │   ├── auth.js            # Login/Registrierung
│   │   └── game.js            # Spiel-Logik
│   └── 📁 middleware/
│       └── auth.js            # JWT Token Verification
├── 📁 web/                     # React Web Frontend
│   ├── package.json           # Web Dependencies
│   └── 📁 src/
│       ├── App.js             # Haupt-App Component
│       └── 📁 pages/
│           └── LoginPage.js   # Login/Register Seite
├── 📁 httpdocs/               # Static Files (unused)
├── app.js                     # Express Server Hauptdatei
├── package.json              # Monorepo Configuration
└── .env                      # Umgebungsvariablen
```

---

## Backend API (Node.js + Express)

### Status: ✅ Funktionsfähig

**Port:** 3011  
**Basis-URL:** `http://localhost:3011/api`

### Verfügbare Endpoints:

#### Authentifizierung (`/api/auth`)
- ✅ `POST /register` - Nutzer registrieren
- ✅ `POST /login` - Nutzer anmelden (gibt JWT Token zurück)

#### Spiel (`/api/game`) 🔒 *Authentifizierung erforderlich*
- ✅ `GET /questions` - 5 zufällige Fragen abrufen
- ✅ `POST /submit-solo` - Solo-Spiel Ergebnis einreichen

### Datenbankstruktur (MySQL)

**Server:** 10.35.233.76:3306  
**Datenbank:** k302164_11Sec_Data

#### Tabellen (Annahme basierend auf Code):
```sql
-- Benutzer
users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100) UNIQUE,
    password_hash VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)

-- Fragen
questions (
    question_id INT PRIMARY KEY AUTO_INCREMENT,
    question_text TEXT,
    correct_answer DECIMAL(10,2),
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)

-- Solo-Spiele
solo_games (
    game_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    final_score INT,
    played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
)

-- Highscores
highscores (
    user_id INT PRIMARY KEY,
    score INT,
    achieved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
)
```

---

## Frontend Web (React)

### Status: ✅ Grundfunktionen vorhanden

**Port:** 3000 (Standard React)  
**Framework:** React 18.2.0

### Verfügbare Komponenten:
- ✅ `LoginPage.js` - Login/Registrierung mit API-Integration
- ✅ `App.js` - Hauptkomponent

### Benötigte Komponenten:
- 🔄 `GamePage.js` - Solo-Spielmodus
- 🔄 `HighscorePage.js` - Highscore-Anzeige
- 🔄 Router für Navigation zwischen Seiten

---

## Gefundene Probleme und Lösungen

### 🐛 Problem 1: Login-Route fehlte
**Status:** ✅ BEHOBEN
- **Problem:** `POST /api/auth/login` Route war nicht implementiert
- **Lösung:** Login-Route mit JWT-Token-Generierung hinzugefügt

### 🐛 Problem 2: Falsche API-URL im Frontend
**Status:** ✅ BEHOBEN
- **Problem:** Frontend verwies auf Port 3001 statt 3011
- **Lösung:** API_URL in `LoginPage.js` korrigiert

### 🐛 Problem 3: PowerShell Execution Policy
**Status:** ✅ BEHOBEN
- **Problem:** npm-Befehle funktionierten nicht
- **Lösung:** `Set-ExecutionPolicy RemoteSigned` ausgeführt

---

## Technologie-Stack

### Backend
- **Runtime:** Node.js
- **Framework:** Express.js
- **Datenbank:** MySQL 2 (mysql2 Package)
- **Authentifizierung:** JWT (jsonwebtoken)
- **Passwort-Hashing:** bcryptjs
- **CORS:** Aktiviert für Frontend-Requests

### Frontend Web
- **Framework:** React 18.2.0
- **HTTP Client:** Axios
- **Styling:** Inline-Styles (aktuell)

### Geplant Mobile
- **Framework:** React Native (noch nicht implementiert)

---

## Nächste Schritte (MVP)

### Phase 1: Solo-Spielmodus vervollständigen
1. 🔄 Spiel-Seite erstellen (`GamePage.js`)
2. 🔄 Timer-Implementierung (11 Sekunden)
3. 🔄 Score-Berechnung basierend auf Abweichung
4. 🔄 Navigation zwischen Login und Spiel

### Phase 2: Highscore-System
1. 🔄 Highscore-Anzeige Seite
2. 🔄 Globale Rangliste
3. 🔄 Persönliche Statistiken

### Phase 3: UI/UX Verbesserungen
1. 🔄 Responsive Design
2. 🔄 CSS Framework (z.B. Material-UI)
3. 🔄 Animationen und Transitions

---

## Entwicklungsumgebung

### Server starten:
```bash
# API Server (Port 3011)
npm run start:api

# Web Frontend (Port 3000)
npm run start:web
```

### Entwicklung mit Auto-Reload:
```bash
# API Development Mode
npm run dev:api
```

---

## Deployment-Informationen

**Hosting:** Netcup Webhosting 4000  
**Domain:** (noch nicht konfiguriert)  
**MySQL:** ✅ Bereits konfiguriert und verbunden

---

*Dokumentation erstellt am: 21. August 2025*  
*Letztes Update: Login-Funktionalität implementiert*
