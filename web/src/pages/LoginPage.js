// File: web/src/pages/LoginPage.js
// Description: The login and registration page for the web app with API calls.


import React, { useState } from 'react';


const LoginPage = ({ onLoginSuccess }) => {
  const [username, setUsername] = useState('');
  const [notification, setNotification] = useState('');

  const handleLogin = (event) => {
    event.preventDefault();
    if (username.trim().length < 2) {
      setNotification('Please enter a valid name.');
      setTimeout(() => setNotification(''), 3000);
      return;
    }
    localStorage.setItem('username', username);
    setNotification('Welcome, ' + username + '!');
    setTimeout(() => {
      if (onLoginSuccess) onLoginSuccess();
    }, 1000);
  };

  return (
    <div style={styles.container}>
      <div style={styles.formContainer}>
        <h1 style={styles.title}>11seconds</h1>
        <h2 style={styles.subtitle}>Enter your name to start</h2>
        {notification && (
          <div style={{ ...styles.notification, backgroundColor: '#4CAF50' }}>{notification}</div>
        )}
        <form onSubmit={handleLogin}>
          <div style={styles.inputGroup}>
            <label htmlFor="username" style={styles.label}>Name</label>
            <input type="text" id="username" value={username} onChange={(e) => setUsername(e.target.value)} style={styles.input} required />
          </div>
          <button type="submit" style={styles.button}>Start</button>
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
  button: { width: '100%', padding: '12px', border: 'none', borderRadius: '6px', backgroundColor: '#1877f2', color: 'white', fontSize: '18px', fontWeight: 'bold', cursor: 'pointer', marginTop: '10px' },
  switchText: { marginTop: '20px', fontSize: '14px', color: '#606770' },
  link: { color: '#1877f2', textDecoration: 'none' },
  notification: { padding: '10px', color: 'white', borderRadius: '6px', marginBottom: '20px', fontSize: '14px' },
};

export default LoginPage;
