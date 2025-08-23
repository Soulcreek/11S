// 🔴 STATISCHE APP - VOLLSTÄNDIG IM BROWSER
//
// Diese App läuft ohne Server:
// ✅ Alle Daten über localStorage
// ✅ Keine Datenbank-Verbindungen  
// ✅ Keine API-Endpunkte erforderlich
// ✅ Deployment: Nur statische Dateien
//
// LEGACY API-Aufrufe (führen zu 404):
// - /api/auth/guest
// - /api/auth/register  
// - /api/auth/verify
// - /api/auth/login
// - /api/auth/google.php
//
// Die App ignoriert API-Fehler und verwendet localStorage-Fallback

import React from 'react';
import ReactDOM from 'react-dom/client';
import './index.css';
import App from './App';

const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);
