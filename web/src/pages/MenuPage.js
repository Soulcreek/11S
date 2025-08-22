// File: web/src/pages/MenuPage.js
// Description: Main menu page with navigation buttons

import React, { useState, useEffect } from 'react';

const MenuPage = ({ onStartGame, onShowHighscores, onShowSettings, onLogout }) => {
  const [userInfo, setUserInfo] = useState(null);
  const [deployStatus, setDeployStatus] = useState('');
  const [isAdmin, setIsAdmin] = useState(false);

  useEffect(() => {
    // Get username from localStorage
    const username = localStorage.getItem('username');
    if (username) {
      setUserInfo({ username });
    }
    const role = localStorage.getItem('userRole');
    setIsAdmin(role === 'admin');
    fetchDeployStatus();
  }, []);

  const fetchDeployStatus = () => {
    fetch('/deploy-status.txt')
      .then(res => res.text())
      .then(text => setDeployStatus(text))
      .catch(() => setDeployStatus('Kein Deployment-Status gefunden.'));
  };

  return (
    <div style={styles.container}>
      <div style={styles.menuContainer}>
        {/* Header */}
        <div style={styles.header}>
          <h1 style={styles.title}>11Seconds</h1>
          <p style={styles.subtitle}>Quiz Challenge</p>

          {userInfo && (
            <div style={styles.welcomeContainer}>
              <p style={styles.welcomeText}>
                Welcome, <strong>{userInfo.username}</strong>!
              </p>
            </div>
          )}
          <div style={{ marginTop: '10px', fontSize: '14px', color: '#666' }}>
            <b>Deployment-Status:</b> {deployStatus}
            <button onClick={fetchDeployStatus} style={{ marginLeft: '10px', fontSize: '12px', padding: '2px 8px', borderRadius: '6px', border: '1px solid #ccc', background: '#f8f8f8', cursor: 'pointer' }}>Aktualisieren</button>
          </div>
        </div>

        {/* Main Menu Buttons */}
        <div style={styles.buttonContainer}>
          <button
            onClick={onStartGame}
            style={{ ...styles.menuButton, ...styles.primaryButton }}
            className="menu-button"
          >
            <div style={styles.buttonIcon}>🎯</div>
            <div style={styles.buttonContent}>
              <h3 style={styles.buttonTitle}>Solo Game</h3>
              <p style={styles.buttonDescription}>
                5 questions, 11 seconds each
              </p>
            </div>
          </button>

          <button
            onClick={onShowHighscores}
            style={{ ...styles.menuButton, ...styles.secondaryButton }}
            className="menu-button"
          >
            <div style={styles.buttonIcon}>🏆</div>
            <div style={styles.buttonContent}>
              <h3 style={styles.buttonTitle}>Highscores</h3>
              <p style={styles.buttonDescription}>
                View top players & your best scores
              </p>
            </div>
          </button>

          <button
            onClick={onShowSettings}
            style={{ ...styles.menuButton, ...styles.settingsButton }}
            className="menu-button"
          >
            <div style={styles.buttonIcon}>⚙️</div>
            <div style={styles.buttonContent}>
              <h3 style={styles.buttonTitle}>Settings</h3>
              <p style={styles.buttonDescription}>
                Game configuration
              </p>
            </div>
          </button>

          {isAdmin && (
            <button
              onClick={() => window.dispatchEvent(new CustomEvent('showAdmin'))}
              style={{ ...styles.menuButton, backgroundColor: '#333', color: '#fff' }}
            >
              <div style={styles.buttonIcon}>🛠️</div>
              <div style={styles.buttonContent}>
                <h3 style={styles.buttonTitle}>Admin</h3>
                <p style={styles.buttonDescription}>Raw data & user management</p>
              </div>
            </button>
          )}

          {/* Future game modes */}
          <button
            style={{ ...styles.menuButton, ...styles.disabledButton }}
            disabled
          >
            <div style={styles.buttonIcon}>👥</div>
            <div style={styles.buttonContent}>
              <h3 style={styles.buttonTitle}>Multiplayer</h3>
              <p style={styles.buttonDescription}>
                Coming soon...
              </p>
            </div>
          </button>
        </div>

        {/* Footer with logout and settings */}
        <div style={styles.footer}>
          <button
            onClick={onLogout}
            style={styles.logoutButton}
            className="logout-button"
          >
            🚪 Logout
          </button>

          <div style={styles.footerInfo}>
            <p style={styles.versionText}>Version 1.0 MVP</p>
          </div>
        </div>
      </div>

      {/* Background decoration */}
      <div style={styles.backgroundDecoration}>
        <div style={{ ...styles.floatingShape, ...styles.shape1 }}>🎲</div>
        <div style={{ ...styles.floatingShape, ...styles.shape2 }}>⚡</div>
        <div style={{ ...styles.floatingShape, ...styles.shape3 }}>🎯</div>
        <div style={{ ...styles.floatingShape, ...styles.shape4 }}>🏆</div>
      </div>
    </div>
  );
};

// Styles
const styles = {
  container: {
    display: 'flex',
    justifyContent: 'center',
    alignItems: 'center',
    minHeight: '100vh',
    background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    fontFamily: 'Arial, sans-serif',
    position: 'relative',
    overflow: 'hidden'
  },
  menuContainer: {
    padding: '40px',
    borderRadius: '20px',
    boxShadow: '0 20px 40px rgba(0, 0, 0, 0.1)',
    backgroundColor: 'rgba(255, 255, 255, 0.95)',
    backdropFilter: 'blur(10px)',
    width: '100%',
    maxWidth: '500px',
    textAlign: 'center',
    position: 'relative',
    zIndex: 10
  },
  header: {
    marginBottom: '40px'
  },
  title: {
    fontSize: '48px',
    fontWeight: 'bold',
    background: 'linear-gradient(45deg, #667eea, #764ba2)',
    WebkitBackgroundClip: 'text',
    WebkitTextFillColor: 'transparent',
    marginBottom: '10px',
    textShadow: '0 2px 4px rgba(0,0,0,0.1)'
  },
  subtitle: {
    fontSize: '18px',
    color: '#666',
    marginBottom: '20px'
  },
  welcomeContainer: {
    padding: '15px',
    backgroundColor: '#f8f9fa',
    borderRadius: '12px',
    border: '2px solid #e9ecef'
  },
  welcomeText: {
    fontSize: '16px',
    color: '#495057',
    margin: 0
  },
  buttonContainer: {
    display: 'flex',
    flexDirection: 'column',
    gap: '20px',
    marginBottom: '40px'
  },
  menuButton: {
    display: 'flex',
    alignItems: 'center',
    padding: '20px',
    border: 'none',
    borderRadius: '16px',
    cursor: 'pointer',
    transition: 'all 0.3s ease',
    textAlign: 'left',
    fontSize: '16px',
    boxShadow: '0 4px 12px rgba(0, 0, 0, 0.1)',
    position: 'relative',
    overflow: 'hidden'
  },
  primaryButton: {
    background: 'linear-gradient(135deg, #4CAF50, #45a049)',
    color: 'white',
    transform: 'scale(1)',
    ':hover': {
      transform: 'scale(1.02)'
    }
  },
  secondaryButton: {
    background: 'linear-gradient(135deg, #2196F3, #1976D2)',
    color: 'white'
  },
  disabledButton: {
    background: 'linear-gradient(135deg, #ccc, #999)',
    color: '#666',
    cursor: 'not-allowed',
    opacity: 0.6
  },
  buttonIcon: {
    fontSize: '32px',
    marginRight: '20px',
    minWidth: '50px'
  },
  buttonContent: {
    flex: 1
  },
  buttonTitle: {
    margin: '0 0 5px 0',
    fontSize: '20px',
    fontWeight: 'bold'
  },
  buttonDescription: {
    margin: 0,
    fontSize: '14px',
    opacity: 0.9
  },
  footer: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingTop: '20px',
    borderTop: '1px solid #e9ecef'
  },
  logoutButton: {
    padding: '12px 20px',
    backgroundColor: '#dc3545',
    color: 'white',
    border: 'none',
    borderRadius: '8px',
    cursor: 'pointer',
    fontSize: '14px',
    fontWeight: 'bold',
    transition: 'background-color 0.3s ease'
  },
  footerInfo: {
    textAlign: 'right'
  },
  versionText: {
    fontSize: '12px',
    color: '#999',
    margin: 0
  },
  // Background decoration
  backgroundDecoration: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    pointerEvents: 'none',
    zIndex: 1
  },
  floatingShape: {
    position: 'absolute',
    fontSize: '24px',
    opacity: 0.1,
    animation: 'float 6s ease-in-out infinite'
  },
  shape1: {
    top: '10%',
    left: '10%',
    animationDelay: '0s'
  },
  shape2: {
    top: '20%',
    right: '15%',
    animationDelay: '2s'
  },
  shape3: {
    bottom: '20%',
    left: '15%',
    animationDelay: '4s'
  },
  shape4: {
    bottom: '10%',
    right: '10%',
    animationDelay: '1s'
  }
};

export default MenuPage;
