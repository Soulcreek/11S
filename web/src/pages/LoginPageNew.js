// File: web/src/pages/LoginPage.js
// Description: Enhanced login page with Google OAuth and detailed debugging

import React, { useState, useRef, useEffect } from 'react';
import { utils } from '../utils/localStorage';

const LoginPage = ({ onLoginSuccess }) => {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [email, setEmail] = useState('');
  const [isRegisterMode, setIsRegisterMode] = useState(false);
  const [notification, setNotification] = useState({ message: '', type: '' });
  const [showGuestOption, setShowGuestOption] = useState(false);
  const [debugInfo, setDebugInfo] = useState('');
  const inputRef = useRef(null);

  useEffect(() => {
    console.log('🔑 LoginPage: Component mounted');
    setDebugInfo('LoginPage loaded');
    
    // Initialize Google Sign-In
    const initializeGoogle = () => {
      if (window.google && window.google.accounts) {
        console.log('🔑 LoginPage: Google API found, initializing...');
        setDebugInfo('Google API found, initializing...');
        
        try {
          window.google.accounts.id.initialize({
            client_id: "532893965816-a3coqfs5hb48nr10lpme8pea3pqar2um.apps.googleusercontent.com",
            callback: handleGoogleResponse
          });

          // Wait for button container to exist
          setTimeout(() => {
            const buttonContainer = document.getElementById("google-signin-button");
            if (buttonContainer) {
              console.log('🔑 LoginPage: Rendering Google Sign-In button');
              setDebugInfo('Rendering Google Sign-In button');
              window.google.accounts.id.renderButton(buttonContainer, { 
                theme: "outline", 
                size: "large",
                width: 250,
                text: "signin_with",
                shape: "rectangular"
              });
              setDebugInfo('Google Sign-In button rendered successfully');
            } else {
              console.error('🔑 LoginPage: Google button container not found!');
              setDebugInfo('ERROR: Google button container not found!');
              setNotification({ message: 'Google Sign-In Button nicht gefunden', type: 'error' });
            }
          }, 100);
          
        } catch (error) {
          console.error('🔑 LoginPage: Google initialization error:', error);
          setDebugInfo('ERROR: Google initialization failed - ' + error.message);
          setNotification({ message: 'Google Sign-In Initialisierung fehlgeschlagen', type: 'error' });
        }
      } else {
        console.warn('🔑 LoginPage: Google API not loaded yet, retrying...');
        setDebugInfo('Google API not loaded yet, retrying...');
        // Retry after a short delay
        setTimeout(initializeGoogle, 500);
      }
    };
    
    // Start initialization
    initializeGoogle();

    // Auto-focus input on mount
    inputRef.current?.focus();
  }, []);

  const handleGoogleResponse = (response) => {
    console.log('🔑 LoginPage: Google response received');
    setDebugInfo('Google response received');
    
    try {
      // Decode the JWT token
      const base64Url = response.credential.split('.')[1];
      const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
      const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
      }).join(''));

      const userData = JSON.parse(jsonPayload);
      
      console.log('🔑 LoginPage: User data parsed:', userData);
      setDebugInfo('Google login successful: ' + userData.name);
      
      // Store user data
      const username = userData.name || userData.email.split('@')[0];
      localStorage.setItem('username', username);
      localStorage.setItem('userEmail', userData.email);
      localStorage.setItem('userPicture', userData.picture || '');
      localStorage.setItem('loginMethod', 'google');

      console.log('🔑 LoginPage: User data stored, calling onLoginSuccess');
      setDebugInfo('Calling onLoginSuccess...');
      
      // Call success callback
      onLoginSuccess();

    } catch (error) {
      console.error('🔑 LoginPage: Google login error:', error);
      setDebugInfo('ERROR: Google login failed - ' + error.message);
      setNotification({ message: 'Google Login fehlgeschlagen', type: 'error' });
      setTimeout(clearNotification, 3000);
    }
  };

  const clearNotification = () => setNotification({ message: '', type: '' });

  // Password validation
  const validatePassword = (password) => {
    if (password.length < 4) {
      return { valid: false, error: 'Passwort muss mindestens 4 Zeichen haben' };
    }
    return { valid: true };
  };

  // Simple password hashing
  const hashPassword = (password) => {
    let hash = 0;
    for (let i = 0; i < password.length; i++) {
      const char = password.charCodeAt(i);
      hash = ((hash << 5) - hash) + char;
      hash = hash & hash;
    }
    return Math.abs(hash).toString(36);
  };

  const handleLogin = async (event) => {
    event?.preventDefault();
    const name = (username || '').trim();
    const pass = password.trim();
    
    console.log('🔑 LoginPage: Manual login attempt:', { username: name, isRegister: isRegisterMode });
    
    // Validation
    const usernameValidation = utils.validateUsername(name);
    if (!usernameValidation.valid) {
      setNotification({ message: usernameValidation.error, type: 'error' });
      setTimeout(clearNotification, 3000);
      return;
    }

    if (!pass) {
      setNotification({ message: 'Bitte Passwort eingeben', type: 'error' });
      setTimeout(clearNotification, 3000);
      return;
    }

    try {
      const users = JSON.parse(localStorage.getItem('registeredUsers') || '{}');
      
      if (isRegisterMode) {
        // Registration logic...
        const emailValidation = utils.validateEmail(email);
        if (email && !emailValidation.valid) {
          setNotification({ message: emailValidation.error, type: 'error' });
          setTimeout(clearNotification, 3000);
          return;
        }

        const passwordValidation = validatePassword(pass);
        if (!passwordValidation.valid) {
          setNotification({ message: passwordValidation.error, type: 'error' });
          setTimeout(clearNotification, 3000);
          return;
        }

        if (users[name]) {
          setNotification({ message: 'Benutzername bereits vergeben', type: 'error' });
          setTimeout(clearNotification, 3000);
          return;
        }

        const hashedPassword = hashPassword(pass);
        users[name] = {
          username: name,
          password: hashedPassword,
          email: email,
          registeredAt: new Date().toISOString(),
          loginMethod: 'password'
        };
        
        localStorage.setItem('registeredUsers', JSON.stringify(users));
        setNotification({ message: 'Registrierung erfolgreich! Du kannst dich jetzt anmelden.', type: 'success' });
        setTimeout(() => {
          setIsRegisterMode(false);
          clearNotification();
        }, 2000);
        return;
      } else {
        // Login logic
        const user = users[name];
        if (!user) {
          setNotification({ message: 'Benutzername nicht gefunden', type: 'error' });
          setTimeout(clearNotification, 3000);
          return;
        }

        const hashedPassword = hashPassword(pass);
        if (user.password !== hashedPassword) {
          setNotification({ message: 'Falsches Passwort', type: 'error' });
          setTimeout(clearNotification, 3000);
          return;
        }

        // Login successful
        localStorage.setItem('username', name);
        localStorage.setItem('userEmail', user.email);
        localStorage.setItem('loginMethod', 'password');
        console.log('🔑 LoginPage: Manual login successful, calling onLoginSuccess');
        onLoginSuccess();
      }
    } catch (error) {
      console.error('🔑 LoginPage: Login/Registration error:', error);
      setNotification({ message: 'Ein Fehler ist aufgetreten', type: 'error' });
      setTimeout(clearNotification, 3000);
    }
  };

  const handleGuestLogin = () => {
    console.log('🔑 LoginPage: Guest login attempt');
    const guestName = 'Gast-' + Math.random().toString(36).substr(2, 6);
    localStorage.setItem('username', guestName);
    localStorage.setItem('loginMethod', 'guest');
    console.log('🔑 LoginPage: Guest login successful:', guestName);
    onLoginSuccess();
  };

  const styles = {
    container: {
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      justifyContent: 'center',
      minHeight: '100vh',
      background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
      fontFamily: '"Segoe UI", Tahoma, Geneva, Verdana, sans-serif',
      padding: '20px'
    },
    loginBox: {
      background: 'rgba(255, 255, 255, 0.95)',
      borderRadius: '20px',
      padding: '40px',
      boxShadow: '0 20px 40px rgba(0,0,0,0.1)',
      width: '100%',
      maxWidth: '400px',
      textAlign: 'center'
    },
    title: {
      fontSize: '2.5rem',
      fontWeight: 'bold',
      marginBottom: '10px',
      background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
      WebkitBackgroundClip: 'text',
      WebkitTextFillColor: 'transparent',
      backgroundClip: 'text'
    },
    subtitle: {
      fontSize: '1.2rem',
      color: '#666',
      marginBottom: '30px'
    },
    debugInfo: {
      background: 'rgba(16, 185, 129, 0.1)',
      border: '1px solid #10b981',
      borderRadius: '8px',
      padding: '8px',
      fontSize: '12px',
      color: '#059669',
      marginBottom: '15px',
      fontFamily: 'monospace'
    },
    inputGroup: {
      marginBottom: '20px',
      textAlign: 'left'
    },
    label: {
      display: 'block',
      marginBottom: '5px',
      color: '#333',
      fontWeight: '500'
    },
    input: {
      width: '100%',
      padding: '15px',
      fontSize: '16px',
      border: '2px solid #d1d5db',
      borderRadius: '10px',
      outline: 'none',
      transition: 'border-color 0.3s'
    },
    button: {
      width: '100%',
      padding: '15px',
      fontSize: '16px',
      fontWeight: 'bold',
      color: 'white',
      background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
      border: 'none',
      borderRadius: '10px',
      cursor: 'pointer',
      marginBottom: '15px',
      transition: 'transform 0.2s'
    },
    guestButton: {
      width: '100%',
      padding: '15px',
      fontSize: '16px',
      color: 'white',
      background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
      border: 'none',
      borderRadius: '10px',
      cursor: 'pointer',
      marginBottom: '10px'
    },
    toggleButton: {
      background: 'none',
      border: 'none',
      color: '#10b981',
      cursor: 'pointer',
      fontSize: '14px',
      textDecoration: 'underline'
    },
    notification: {
      padding: '10px',
      borderRadius: '8px',
      marginBottom: '20px',
      fontSize: '14px',
      textAlign: 'center'
    },
    googleContainer: {
      display: 'flex',
      justifyContent: 'center',
      alignItems: 'center',
      margin: '20px 0',
      width: '100%'
    },
    divider: {
      margin: '20px 0',
      position: 'relative',
      textAlign: 'center'
    },
    dividerLine: {
      borderTop: '1px solid #d1d5db',
      width: '100%'
    },
    dividerText: {
      position: 'absolute',
      top: '-10px',
      left: '50%',
      transform: 'translateX(-50%)',
      background: 'white',
      padding: '0 15px',
      color: '#666',
      fontSize: '14px'
    }
  };

  return (
    <div style={styles.container}>
      <div style={styles.loginBox}>
        <h1 style={styles.title}>11seconds</h1>
        <p style={styles.subtitle}>
          {isRegisterMode ? 'Account erstellen' : 'Melde dich an um zu starten'}
        </p>

        {/* Debug Info */}
        {debugInfo && (
          <div style={styles.debugInfo}>
            🔍 Debug: {debugInfo}
          </div>
        )}

        {/* Notification */}
        {notification.message && (
          <div style={{
            ...styles.notification,
            backgroundColor: notification.type === 'error' ? '#fef2f2' : '#f0fdf4',
            color: notification.type === 'error' ? '#dc2626' : '#16a34a',
            border: `1px solid ${notification.type === 'error' ? '#fca5a5' : '#a7f3d0'}`
          }}>
            {notification.message}
          </div>
        )}

        {/* Google Sign-In */}
        <div style={styles.googleContainer}>
          <div id="google-signin-button"></div>
        </div>

        <div style={styles.divider}>
          <div style={styles.dividerLine}></div>
          <span style={styles.dividerText}>oder</span>
        </div>

        {/* Login/Registration Form */}
        <form onSubmit={handleLogin}>
          <div style={styles.inputGroup}>
            <label style={styles.label}>Benutzername</label>
            <input
              ref={inputRef}
              style={styles.input}
              type="text"
              placeholder="Dein Benutzername"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              required
            />
          </div>

          {isRegisterMode && (
            <div style={styles.inputGroup}>
              <label style={styles.label}>E-Mail (optional)</label>
              <input
                style={styles.input}
                type="email"
                placeholder="deine@email.de"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
              />
            </div>
          )}

          <div style={styles.inputGroup}>
            <label style={styles.label}>Passwort</label>
            <input
              style={styles.input}
              type="password"
              placeholder="Dein Passwort"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
          </div>

          <button type="submit" style={styles.button}>
            {isRegisterMode ? 'Registrieren' : 'Anmelden'}
          </button>
        </form>

        {/* Toggle Login/Register */}
        <p style={{ margin: '15px 0' }}>
          {isRegisterMode ? 'Schon ein Account? ' : 'Noch kein Account? '}
          <button
            style={styles.toggleButton}
            onClick={() => {
              setIsRegisterMode(!isRegisterMode);
              clearNotification();
            }}
          >
            {isRegisterMode ? 'Anmelden' : 'Registrieren'}
          </button>
        </p>

        {/* Guest Option Toggle */}
        <button
          style={styles.toggleButton}
          onClick={() => setShowGuestOption(!showGuestOption)}
        >
          {showGuestOption ? 'Gast-Option ausblenden' : 'Als Gast spielen'}
        </button>

        {/* Guest Login */}
        {showGuestOption && (
          <div style={{ marginTop: '15px' }}>
            <button style={styles.guestButton} onClick={handleGuestLogin}>
              Als Gast starten
            </button>
            <p style={{ fontSize: '12px', color: '#666', marginTop: '5px' }}>
              ⚠️ Als Gast werden deine Fortschritte nicht gespeichert
            </p>
          </div>
        )}
      </div>
    </div>
  );
};

export default LoginPage;
