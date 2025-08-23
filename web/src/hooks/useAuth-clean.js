/*
 * 🔴 ARCHITEKTUR-HINWEIS: STATISCHE APP
 * Diese App läuft vollständig im Browser ohne Server!
 * - KEINE echten API-Endpunkte (/api/* führt zu 404)
 * - Alle Daten über localStorage
 * - Vollständig client-seitige Authentifizierung
 * 
 * ✅ SAUBERE STATISCHE VERSION - Keine API-Aufrufe mehr!
 */

import React, { useState, useEffect } from 'react';

const AuthManager = () => {
  const [authMode, setAuthMode] = useState('guest'); // 'guest', 'login', 'register', 'verify'
  const [user, setUser] = useState(null);
  const [guestUser, setGuestUser] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [registrationData, setRegistrationData] = useState({});

  // Initialisierung - Prüfe localStorage
  useEffect(() => {
    const storedUser = localStorage.getItem('11s_user');
    const storedGuest = localStorage.getItem('11s_guest');
    
    if (storedUser) {
      try {
        const userData = JSON.parse(storedUser);
        // Prüfe ob Session abgelaufen ist
        if (userData.expires_at && userData.expires_at < Date.now()) {
          localStorage.removeItem('11s_user');
        } else {
          setUser(userData);
          setAuthMode('authenticated');
        }
      } catch (e) {
        localStorage.removeItem('11s_user');
      }
    } else if (storedGuest) {
      try {
        const guestData = JSON.parse(storedGuest);
        // Prüfe ob Gast-Session abgelaufen ist
        if (guestData.expires_at && guestData.expires_at < Date.now()) {
          localStorage.removeItem('11s_guest');
        } else {
          setGuestUser(guestData);
          setAuthMode('guest_active');
        }
      } catch (e) {
        localStorage.removeItem('11s_guest');
      }
    }
  }, []);

  // Google Auth Initialisierung
  const initGoogleAuth = () => {
    if (typeof window !== 'undefined' && window.google) {
      window.google.accounts.id.initialize({
        client_id: process.env.REACT_APP_GOOGLE_CLIENT_ID,
        callback: handleGoogleSignIn,
        auto_select: false,
        cancel_on_tap_outside: true
      });
    }
  };

  // 🔄 STATISCHE VERSION - Gastmodus starten
  const startGuestMode = async () => {
    setLoading(true);
    setError('');
    
    try {
      const guestUsername = `Gast_${Math.random().toString(36).substr(2, 6)}`;
      const clientIP = await getClientIP();
      
      const guestData = {
        id: `guest_${Date.now()}`,
        username: guestUsername,
        email: null,
        created_at: Date.now(),
        expires_at: Date.now() + (24 * 60 * 60 * 1000), // 24h expires
        user_agent: navigator.userAgent,
        ip: clientIP,
        is_guest: true
      };
      
      // Direkt in localStorage speichern - kein Server erforderlich
      setGuestUser(guestData);
      localStorage.setItem('11s_guest', JSON.stringify(guestData));
      setAuthMode('guest_active');
      setSuccess(`Willkommen ${guestData.username}! Du spielst als Gast.`);
      
    } catch (err) {
      setError('Fehler beim Erstellen des Gastkontos');
    }
    
    setLoading(false);
  };

  // 🔄 STATISCHE VERSION - Benutzer registrieren
  const handleRegister = async (formData) => {
    setLoading(true);
    setError('');
    
    try {
      // Validierung
      if (!formData.email && !formData.phone) {
        throw new Error('E-Mail oder Telefon erforderlich');
      }
      if (!formData.password || formData.password.length < 6) {
        throw new Error('Passwort muss mindestens 6 Zeichen haben');
      }
      
      // Prüfe ob Benutzer bereits existiert
      const existingUsers = JSON.parse(localStorage.getItem('registeredUsers') || '{}');
      const identifier = formData.email || formData.phone;
      
      if (existingUsers[identifier]) {
        throw new Error('Benutzer existiert bereits');
      }
      
      // Erstelle neuen Benutzer
      const userId = `user_${Date.now()}`;
      const userData = {
        id: userId,
        username: formData.username || formData.name,
        email: formData.email,
        phone: formData.phone,
        password: formData.password, // In echter App würde man das hashen
        created_at: Date.now(),
        verified: true, // Für statische App direkt verifiziert
        guest_data: guestUser || null
      };
      
      // Speichere in localStorage
      existingUsers[identifier] = userData;
      localStorage.setItem('registeredUsers', JSON.stringify(existingUsers));
      
      // Automatisch einloggen nach Registrierung
      setUser(userData);
      localStorage.setItem('11s_user', JSON.stringify(userData));
      localStorage.removeItem('11s_guest'); // Entferne Guest-Daten
      
      setAuthMode('authenticated');
      setSuccess('Registrierung erfolgreich! Du bist jetzt eingeloggt.');
      
    } catch (err) {
      setError(err.message || 'Fehler bei der Registrierung');
    }
    
    setLoading(false);
  };

  // 🔄 STATISCHE VERSION - Keine Verifizierung erforderlich
  const handleVerification = async (code) => {
    // In statischer App nicht erforderlich - direkt eingeloggt nach Registrierung
    setSuccess('Verifizierung nicht erforderlich - du bist bereits eingeloggt!');
    setAuthMode('authenticated');
  };

  // 🔄 STATISCHE VERSION - Benutzer einloggen
  const handleLogin = async (credentials) => {
    setLoading(true);
    setError('');
    
    try {
      if (!credentials.identifier || !credentials.password) {
        throw new Error('E-Mail/Telefon und Passwort erforderlich');
      }
      
      // Suche Benutzer in localStorage
      const registeredUsers = JSON.parse(localStorage.getItem('registeredUsers') || '{}');
      const userData = registeredUsers[credentials.identifier];
      
      if (!userData) {
        throw new Error('Benutzer nicht gefunden');
      }
      
      if (userData.password !== credentials.password) {
        throw new Error('Falsches Passwort');
      }
      
      // Login erfolgreich
      setUser(userData);
      localStorage.setItem('11s_user', JSON.stringify(userData));
      localStorage.removeItem('11s_guest'); // Entferne Guest-Daten nach Login
      
      setAuthMode('authenticated');
      setSuccess(`Willkommen zurück, ${userData.username}!`);
      
      return { success: true };
      
    } catch (err) {
      setError(err.message || 'Fehler beim Anmelden');
      return { success: false };
    } finally {
      setLoading(false);
    }
  };

  // 🔄 STATISCHE VERSION - Google-Login simulieren
  const handleGoogleSignIn = async (credentialResponse) => {
    setLoading(true);
    setError('');
    
    try {
      // Für Demo: Erstelle Mock-Benutzer basierend auf Google-Credential
      // In einer echten Implementierung würde man hier den JWT-Token dekodieren
      
      const mockGoogleUser = {
        id: `google_${Date.now()}`,
        username: `Google_User_${Math.random().toString(36).substr(2, 4)}`,
        email: 'google-user@example.com', // In echter App aus JWT extrahieren
        provider: 'google',
        created_at: Date.now(),
        expires_at: Date.now() + (24 * 60 * 60 * 1000), // 24h
        google_credential: credentialResponse.credential
      };
      
      // Übertrage Guest-Daten falls vorhanden
      if (guestUser) {
        mockGoogleUser.guest_data = guestUser;
      }
      
      setUser(mockGoogleUser);
      localStorage.setItem('11s_user', JSON.stringify(mockGoogleUser));
      localStorage.removeItem('11s_guest');
      
      setAuthMode('authenticated');
      setSuccess('Google Anmeldung erfolgreich!');
      
    } catch (err) {
      setError('Fehler bei der Google Anmeldung');
    }
    
    setLoading(false);
  };

  // Logout
  const logout = () => {
    setUser(null);
    setGuestUser(null);
    localStorage.removeItem('11s_user');
    localStorage.removeItem('11s_guest');
    setAuthMode('guest');
    setError('');
    setSuccess('');
  };

  // Guest zu User konvertieren
  const convertGuestToUser = () => {
    setAuthMode('register');
    setSuccess('Registriere dich, um deinen Fortschritt zu speichern!');
  };

  // Client IP ermitteln (externe API)
  const getClientIP = async () => {
    try {
      const response = await fetch('https://api.ipify.org?format=json');
      const data = await response.json();
      return data.ip;
    } catch {
      return 'unknown';
    }
  };

  // Utility Funktionen
  const getCurrentUser = () => {
    return user || guestUser;
  };

  const isAuthenticated = () => {
    return user !== null;
  };

  const isGuest = () => {
    return guestUser !== null && user === null;
  };

  return {
    // State
    authMode,
    user,
    guestUser,
    loading,
    error,
    success,
    
    // Actions
    setAuthMode,
    setError,
    setSuccess,
    startGuestMode,
    handleRegister,
    handleVerification,
    handleLogin,
    handleGoogleSignIn,
    logout,
    convertGuestToUser,
    
    // Utilities
    getCurrentUser,
    isAuthenticated,
    isGuest,
    initGoogleAuth
  };
};

export default AuthManager;
