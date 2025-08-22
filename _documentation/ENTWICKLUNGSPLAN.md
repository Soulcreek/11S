# Entwicklungsplan: 11Seconds Quiz Game MVP

## 🎯 Ziel: Funktionsfähiger Solo-Spielmodus

### Aktuelle Situation (✅ Abgeschlossen)

- ✅ Frontend Login-Seite mit localStorage-basierter Authentifizierung
- ✅ GamePage mit 5 Fragen und 11-Sekunden Timer implementiert
- ✅ Score-Berechnung mit Schwierigkeits-Multiplikatoren
- ✅ HighscorePage mit lokalen und globalen Rankings
- ✅ Navigation zwischen allen Seiten funktioniert
- ✅ Fragen-Datenbank (extraQuestions.js) mit ~400 Fragen
- ✅ Grundlegende Projektstruktur für Static Deployment

---

## 📋 Phase 1: Solo-Spielmodus (Priorität: HOCH)

### 1.1 GamePage Frontend erstellen

**Datei:** `web/src/pages/GamePage.js`

**Features:**

- Fragen-Anzeige (1 von 5)
- Eingabefeld für Schätzung
- 11-Sekunden Timer mit Countdown
- Fortschrittsanzeige
- "Weiter" Button

**Komponenten:**

```jsx
// Struktur:
- QuestionDisplay (Frage anzeigen)
- AnswerInput (Eingabefeld)
- Timer (Countdown von 11s)
- ProgressBar (1/5, 2/5, etc.)
- ScoreDisplay (am Ende)
```

### 1.2 Game Logic Implementation

**Dateien zu bearbeiten:**

- `web/src/pages/GamePage.js`
- `web/src/App.js` (Routing hinzufügen)

**Funktionen:**

```javascript
// Game State Management:
-loadQuestions() - // API Call
  submitAnswer(questionId, userAnswer) -
  calculateScore(userAnswer, correctAnswer) -
  nextQuestion() -
  finishGame();
```

### 1.3 Score-Berechnung

**Formel für Punkte:**

```javascript
// Beispiel: Je näher an der korrekten Antwort, desto mehr Punkte
const calculateQuestionScore = (userAnswer, correctAnswer) => {
  const difference = Math.abs(userAnswer - correctAnswer);
  const percentageDiff = (difference / correctAnswer) * 100;

  if (percentageDiff <= 5) return 100; // Perfekt
  if (percentageDiff <= 10) return 80; // Sehr gut
  if (percentageDiff <= 20) return 60; // Gut
  if (percentageDiff <= 50) return 40; // OK
  return 20; // Mindestpunkte
};
```

---

## 📋 Phase 2: Navigation & Routing

### 2.1 React Router implementieren

**Installation:**

```bash
npm install react-router-dom --workspace=web
```

**Routen:**

- `/` - Login/Register
- `/game` - Solo-Spielmodus
- `/highscore` - Highscore-Listen
- `/profile` - Benutzerprofil (später)

### 2.2 Geschützte Routen

- Token-basierte Authentifizierung
- Automatische Weiterleitung bei fehlendem Token
- Token in localStorage speichern

---

## 📋 Phase 3: Highscore-System

### 3.1 Highscore-Seite

**Datei:** `web/src/pages/HighscorePage.js`

**Features:**

- Globale Top 10
- Persönlicher Rekord
- Letzte Spiele
- Filter-Optionen (täglich, wöchentlich, alle Zeit)

### 3.2 Backend-Erweiterungen

**Neue Endpoints:**

```javascript
// api/routes/game.js
GET / api / game / highscores / global; // Top 10 global
GET / api / game / highscores / personal; // Persönliche Statistiken
GET / api / game / recent - games; // Letzte Spiele
```

---

## 📋 Phase 4: Fragen-Datenbank erweitern

### 4.1 Fragen-Kategorien

**Kategorien:**

- Geografie (Höhe von Bergen, Einwohner von Städten)
- Geschichte (Jahreszahlen, Dauer von Ereignissen)
- Sport (Rekorde, Geschwindigkeiten)
- Wissenschaft (Temperaturen, Entfernungen)
- Allgemeinwissen (Preise, Mengen)

### 4.2 Fragen-Pool erweitern

**Mindestens 100 Fragen** für abwechslungsreiche Spiele

---

## 🚀 Konkrete nächste Schritte (in dieser Reihenfolge)

### Schritt 1: GamePage Grundgerüst

1. `GamePage.js` erstellen
2. Basis-Layout mit Mock-Daten
3. Navigation von LoginPage zu GamePage

### Schritt 2: API-Integration

1. Fragen von Backend laden
2. Timer implementieren
3. Score-Berechnung

### Schritt 3: Spiel-Ende

1. Score an Backend senden
2. Ergebnis-Anzeige
3. Zurück zu Highscore/Neues Spiel

### Schritt 4: Highscore-Anzeige

1. HighscorePage erstellen
2. Backend-Endpoints für Highscores
3. Navigation zwischen allen Seiten

---

## 🧪 Testing & Debug

### Frontend Testing

```bash
# Web Frontend starten
npm run start:web
# Läuft auf http://localhost:3000
```

### Backend Testing

```bash
# API Server starten
npm run start:api
# Läuft auf http://localhost:3011
```

### API Testing (mit curl/Postman)

```bash
# Login testen
curl -X POST http://localhost:3011/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"test123"}'

# Fragen abrufen (mit Token)
curl -X GET http://localhost:3011/api/game/questions \
  -H "x-auth-token: YOUR_JWT_TOKEN"
```

---

## 📝 Dateien die erstellt/bearbeitet werden müssen

### Zu erstellen:

- `web/src/pages/GamePage.js`
- `web/src/pages/HighscorePage.js`
- `web/src/components/Timer.js`
- `web/src/components/ProgressBar.js`

### Zu bearbeiten:

- `web/src/App.js` (Routing hinzufügen)
- `web/package.json` (react-router-dom dependency)
- `api/routes/game.js` (zusätzliche Endpoints)

---

## 🎯 Erfolgskriterien MVP

### Must-Have:

- ✅ Login/Registrierung funktioniert (localStorage-basiert)
- ✅ 5 Fragen nacheinander beantworten
- ✅ 11-Sekunden Timer pro Frage mit visueller Anzeige
- ✅ Score-Berechnung mit Schwierigkeit und Zeit-Bonus
- ✅ Highscore-Anzeige (lokal und global)
- ✅ Responsive Navigation zwischen allen Seiten

### Nice-to-Have (Nächste Verbesserungen):

- 🔄 Erweiterte Animationen und Transitions
- 🔄 Sound-Effekte für Timer und Antworten
- 🔄 Kategorien-Filter im Spiel
- 🔄 Statistiken und Performance-Tracking
- 🔄 Progressive Web App (PWA) Features
- 🔄 React Native Mobile App Vorbereitung

---

**Geschätzte Entwicklungszeit MVP: 8-12 Stunden**

_Plan erstellt am: 21. August 2025_
