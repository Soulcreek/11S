// File: web/src/pages/LoginPage.js
// Description: The login and registration page for the web app with API calls.


import React, { useState, useRef, useEffect } from 'react';
import { userManager, utils } from '../utils/localStorage';


const LoginPage = ({ onLoginSuccess }) => {
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [isRegisterMode, setIsRegisterMode] = useState(false);
  const [notification, setNotification] = useState({ message: '', type: '' });
  const inputRef = useRef(null);

  useEffect(() => {
    // autofocus input on mount
    inputRef.current?.focus();
  }, []);

  const clearNotification = () => setNotification({ message: '', type: '' });

  const handleLogin = async (event) => {
    event?.preventDefault();
    const name = (username || '').trim();
    
    // Validation
    const usernameValidation = utils.validateUsername(name);
    if (!usernameValidation.valid) {
      setNotification({ message: usernameValidation.error, type: 'error' });
      setTimeout(clearNotification, 3000);
      return;
    }

    const emailValidation = utils.validateEmail(email);
    if (!emailValidation.valid) {
      setNotification({ message: emailValidation.error, type: 'error' });
      setTimeout(clearNotification, 3000);
      return;
    }

    if (isRegisterMode) {
      // Register new user locally
      const result = userManager.registerUser({
        username: name,
        email: email.trim()
      });

      if (result.success) {
        setNotification({ message: `Willkommen, ${name}! Account erstellt.`, type: 'success' });
        setTimeout(() => {
          if (onLoginSuccess) onLoginSuccess();
        }, 1000);
      } else {
        setNotification({ message: result.error, type: 'error' });
        setTimeout(clearNotification, 3000);
      }
      return;
    }

    // Try to login existing user
    const result = userManager.loginUser(name);
    if (result.success) {
      setNotification({ message: `Willkommen zurück, ${name}!`, type: 'success' });
      setTimeout(() => {
        if (onLoginSuccess) onLoginSuccess();
      }, 1000);
    } else {
      // User doesn't exist, suggest registration
      setNotification({ 
        message: 'Benutzer nicht gefunden. Möchtest du dich registrieren?', 
        type: 'error' 
      });
      setTimeout(() => {
        setIsRegisterMode(true);
        clearNotification();
      }, 2000);
    }
  };

  const handleGuestLogin = () => {
    const guest = 'Gast';
    localStorage.setItem('username', guest);
    setUsername(guest);
    setNotification({ message: 'Weiter als Gast', type: 'success' });
    if (onLoginSuccess) onLoginSuccess();
  };

  return (
    <div style={styles.container}>
      <div style={styles.formContainer}>
        <h1 style={styles.title}>11seconds</h1>
        <h2 style={styles.subtitle}>Enter your name to start</h2>
        {notification.message && (
          <div style={{ ...styles.notification, backgroundColor: notification.type === 'success' ? '#4CAF50' : '#f44336' }}>{notification.message}</div>
        )}
        <form onSubmit={handleLogin}>
          <div style={styles.inputGroup}>
            <label htmlFor="username" style={styles.label}>
              {isRegisterMode ? 'Username' : 'Name'}
            </label>
            <input 
              ref={inputRef} 
              autoComplete="off" 
              type="text" 
              id="username" 
              value={username} 
              onChange={(e) => setUsername(e.target.value)} 
              style={styles.input} 
              required 
              placeholder={isRegisterMode ? 'Choose a username (3-20 chars)' : 'Enter your name'}
            />
          </div>

          {isRegisterMode && (
            <div style={styles.inputGroup}>
              <label htmlFor="email" style={styles.label}>E-Mail (optional)</label>
              <input 
                type="email" 
                id="email" 
                value={email} 
                onChange={(e) => setEmail(e.target.value)} 
                style={styles.input}
                placeholder="your@email.com"
              />
            </div>
          )}

          <div style={{ display: 'flex', gap: '10px' }}>
            <button type="submit" style={styles.button}>
              {isRegisterMode ? 'Registrieren' : 'Start'}
            </button>
            {!isRegisterMode && (
              <button type="button" onClick={handleGuestLogin} style={styles.guestButton}>
                Gast
              </button>
            )}
          </div>

          <div style={styles.switchText}>
            {isRegisterMode ? (
              <span>
                Schon registriert?{' '}
                <a 
                  href="#" 
                  style={styles.link} 
                  onClick={(e) => {
                    e.preventDefault();
                    setIsRegisterMode(false);
                    setEmail('');
                    clearNotification();
                  }}
                >
                  Anmelden
                </a>
              </span>
            ) : (
              <span>
                Neuer Spieler?{' '}
                <a 
                  href="#" 
                  style={styles.link} 
                  onClick={(e) => {
                    e.preventDefault();
                    setIsRegisterMode(true);
                    clearNotification();
                  }}
                >
                  Registrieren
                </a>
              </span>
            )}
          </div>
        </form>
      </div>
    </div>
  );
};

// --- Styles (unchanged, but added notification style) ---
const styles = {
  container: { display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100vh', backgroundColor: '#f0f2f5', fontFamily: 'Arial, sans-serif' },
  formContainer: { padding: '40px', borderRadius: '8px', boxShadow: '0 4px 12px rgba(0, 0, 0, 0.1)', backgroundColor: 'white', width: '100%', maxWidth: '400px', textAlign: 'center' },
  title: { fontSize: '32px', fontWeight: 'bold', color: '#1c1e21', marginBottom: '10px' },
  subtitle: { fontSize: '24px', color: '#333', marginBottom: '20px' },
  inputGroup: { marginBottom: '15px', textAlign: 'left' },
  label: { display: 'block', marginBottom: '5px', color: '#606770', fontSize: '14px' },
  input: { width: '100%', padding: '12px', border: '1px solid #dddfe2', borderRadius: '6px', fontSize: '16px', boxSizing: 'border-box' },
  button: { flex: 1, padding: '12px', border: 'none', borderRadius: '6px', backgroundColor: '#1877f2', color: 'white', fontSize: '18px', fontWeight: 'bold', cursor: 'pointer', marginTop: '10px' },
  guestButton: { padding: '12px', border: 'none', borderRadius: '6px', backgroundColor: '#6c757d', color: 'white', fontSize: '18px', cursor: 'pointer', marginTop: '10px' },
  switchText: { marginTop: '20px', fontSize: '14px', color: '#606770' },
  link: { color: '#1877f2', textDecoration: 'none' },
  notification: { padding: '10px', color: 'white', borderRadius: '6px', marginBottom: '20px', fontSize: '14px' },
};

export default LoginPage;
