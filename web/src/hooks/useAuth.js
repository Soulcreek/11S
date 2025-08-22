import React, { useState, useEffect } from 'react';

const AuthManager = () => {
  const [authMode, setAuthMode] = useState('guest'); // 'guest', 'login', 'register', 'verify'
  const [user, setUser] = useState(null);
  const [guestUser, setGuestUser] = useState(null);
  const [registrationData, setRegistrationData] = useState({});
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  // Initialize authentication
  useEffect(() => {
    checkExistingAuth();
    initGoogleAuth();
  }, []);

  const checkExistingAuth = () => {
    const storedUser = localStorage.getItem('11s_user');
    const storedGuest = localStorage.getItem('11s_guest');
    
    if (storedUser) {
      const userData = JSON.parse(storedUser);
      if (userData.expires_at && userData.expires_at > Date.now()) {
        setUser(userData);
        setAuthMode('authenticated');
      } else {
        localStorage.removeItem('11s_user');
      }
    } else if (storedGuest) {
      const guestData = JSON.parse(storedGuest);
      if (guestData.expires_at && guestData.expires_at > Date.now()) {
        setGuestUser(guestData);
        setAuthMode('guest_active');
      } else {
        localStorage.removeItem('11s_guest');
      }
    }
  };

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

  const startGuestMode = async () => {
    setLoading(true);
    setError('');
    
    try {
      const response = await fetch('/api/auth/guest', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          ip: await getClientIP(),
          user_agent: navigator.userAgent
        })
      });
      
      const data = await response.json();
      
      if (data.success) {
        const guestData = {
          ...data.user,
          expires_at: Date.now() + (data.user.expires_at - data.user.created_at) * 1000
        };
        
        setGuestUser(guestData);
        localStorage.setItem('11s_guest', JSON.stringify(guestData));
        setAuthMode('guest_active');
        setSuccess(`Willkommen ${data.user.username}! Du spielst als Gast.`);
      } else {
        setError(data.error || 'Fehler beim Erstellen des Gastkontos');
      }
    } catch (err) {
      setError('Verbindungsfehler beim Erstellen des Gastkontos');
    }
    
    setLoading(false);
  };

  const handleRegister = async (formData) => {
    setLoading(true);
    setError('');
    
    try {
      const response = await fetch('/api/auth/register', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          ...formData,
          guest_data: guestUser, // Preserve guest progress if converting
          ip: await getClientIP()
        })
      });
      
      const data = await response.json();
      
      if (data.success) {
        setRegistrationData({ ...formData, user_id: data.user_id });
        setAuthMode('verify');
        setSuccess(data.message);
      } else {
        setError(data.error);
      }
    } catch (err) {
      setError('Verbindungsfehler bei der Registrierung');
    }
    
    setLoading(false);
  };

  const handleVerification = async (code) => {
    setLoading(true);
    setError('');
    
    try {
      const response = await fetch('/api/auth/verify', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          user_id: registrationData.user_id,
          code: code
        })
      });
      
      const data = await response.json();
      
      if (data.success) {
        // Auto-login after verification
        const loginResult = await handleLogin({
          identifier: registrationData.email || registrationData.phone,
          password: registrationData.password
        });
        
        if (loginResult.success) {
          setSuccess('E-Mail bestätigt und erfolgreich angemeldet!');
        }
      } else {
        setError(data.error);
      }
    } catch (err) {
      setError('Verbindungsfehler bei der Bestätigung');
    }
    
    setLoading(false);
  };

  const handleLogin = async (credentials) => {
    setLoading(true);
    setError('');
    
    try {
      const response = await fetch('/api/auth/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(credentials)
      });
      
      const data = await response.json();
      
      if (data.success) {
        const userData = {
          ...data.user,
          session_token: data.session_token,
          expires_at: Date.now() + (3600 * 1000) // 1 hour
        };
        
        setUser(userData);
        localStorage.setItem('11s_user', JSON.stringify(userData));
        localStorage.removeItem('11s_guest'); // Remove guest data after login
        setAuthMode('authenticated');
        setSuccess('Erfolgreich angemeldet!');
        return { success: true };
      } else {
        setError(data.error);
        return { success: false, error: data.error };
      }
    } catch (err) {
      setError('Verbindungsfehler bei der Anmeldung');
      return { success: false, error: 'Verbindungsfehler' };
    } finally {
      setLoading(false);
    }
  };

  const handleGoogleSignIn = async (credentialResponse) => {
    setLoading(true);
    setError('');
    
    try {
      // Send to PHP file directly (static hosting compatible)
      const formData = new FormData();
      formData.append('google_credential', credentialResponse.credential);
      if (guestUser) {
        formData.append('guest_data', JSON.stringify(guestUser));
      }
      
      const response = await fetch('/auth-google.php', {
        method: 'POST',
        body: formData
      });
      
      const data = await response.json();
      
      if (data.success) {
        const userData = {
          ...data.user,
          session_token: data.session_token,
          expires_at: Date.now() + (3600 * 1000) // 1 hour
        };
        
        setUser(userData);
        localStorage.setItem('11s_user', JSON.stringify(userData));
        localStorage.removeItem('11s_guest'); // Remove guest data after login
        setAuthMode('authenticated');
        setSuccess('Google Anmeldung erfolgreich!');
      } else {
        setError(data.error);
      }
    } catch (err) {
      setError('Fehler bei der Google Anmeldung');
    }
    
    setLoading(false);
  };

  const logout = () => {
    setUser(null);
    setGuestUser(null);
    localStorage.removeItem('11s_user');
    localStorage.removeItem('11s_guest');
    setAuthMode('guest');
    setError('');
    setSuccess('');
  };

  const convertGuestToUser = () => {
    setAuthMode('register');
    setSuccess('Registriere dich, um deinen Fortschritt zu speichern!');
  };

  const getClientIP = async () => {
    try {
      const response = await fetch('https://api.ipify.org?format=json');
      const data = await response.json();
      return data.ip;
    } catch {
      return 'unknown';
    }
  };

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
