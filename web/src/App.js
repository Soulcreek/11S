// File: web/src/App.js
// Description: The main component that handles navigation between all pages

import React, { useState, useEffect } from 'react';
import LoginPage from './pages/LoginPage';
import MenuPage from './pages/MenuPage';
import GameConfigPage from './pages/GameConfigPage';
import GamePage from './pages/GamePage';
import HighscorePage from './pages/HighscorePage';
import AdminPage from './pages/AdminPage';
import SettingsPage from './pages/SettingsPage';
import MultiplayerPage from './pages/MultiplayerPage';

// Debug Component
const DebugPanel = ({ currentPage, username, error }) => {
  const [showDebug, setShowDebug] = useState(true);
  
  if (!showDebug) {
    return (
      <div style={{
        position: 'fixed',
        top: '10px',
        right: '10px',
        zIndex: 10000,
        background: '#333',
        color: 'white',
        padding: '5px 10px',
        borderRadius: '5px',
        fontSize: '12px',
        cursor: 'pointer'
      }} onClick={() => setShowDebug(true)}>
        🐛 Debug
      </div>
    );
  }
  
  return (
    <div style={{
      position: 'fixed',
      top: '10px',
      right: '10px',
      zIndex: 10000,
      background: 'rgba(0,0,0,0.9)',
      color: 'white',
      padding: '15px',
      borderRadius: '10px',
      fontSize: '12px',
      maxWidth: '300px',
      fontFamily: 'monospace'
    }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '10px' }}>
        <strong>🐛 DEBUG INFO</strong>
        <span style={{ cursor: 'pointer' }} onClick={() => setShowDebug(false)}>✕</span>
      </div>
      <div><strong>Current Page:</strong> {currentPage}</div>
      <div><strong>Username:</strong> {username || 'Not logged in'}</div>
      <div><strong>Login Method:</strong> {localStorage.getItem('loginMethod') || 'None'}</div>
      <div><strong>Timestamp:</strong> {new Date().toLocaleTimeString()}</div>
      {error && <div style={{ color: '#ff6b6b', marginTop: '5px' }}><strong>Error:</strong> {error}</div>}
      <div style={{ marginTop: '10px', fontSize: '10px', color: '#ccc' }}>
        <strong>LocalStorage Keys:</strong><br/>
        {Object.keys(localStorage).join(', ') || 'Empty'}
      </div>
    </div>
  );
};

function App() {
  // App state to manage which page to show
  const [currentPage, setCurrentPage] = useState('login'); // 'login', 'menu', 'gameConfig', 'game', 'highscores', 'multiplayer'
  const [gameConfig, setGameConfig] = useState({});
  const [appError, setAppError] = useState('');
  const [username, setUsername] = useState('');

  // Debug logging function
  const debugLog = (message, data = null) => {
    console.log(`🐛 [11Seconds Debug] ${message}`, data || '');
    if (data) {
      console.table(data);
    }
  };

  // Check for existing username on app start
  useEffect(() => {
    debugLog('App starting, checking localStorage...');
    
    try {
      const storedUsername = localStorage.getItem('username');
      const loginMethod = localStorage.getItem('loginMethod');
      
      debugLog('Stored data found:', {
        username: storedUsername,
        loginMethod: loginMethod,
        allKeys: Object.keys(localStorage)
      });
      
      if (storedUsername) {
        setUsername(storedUsername);
        setCurrentPage('menu');
        debugLog('User found, redirecting to menu');
      } else {
        debugLog('No user found, staying on login page');
      }
    } catch (error) {
      debugLog('Error checking localStorage:', error);
      setAppError('LocalStorage error: ' + error.message);
    }
    
    const onShowAdminEvent = () => setCurrentPage('admin');
    window.addEventListener('showAdmin', onShowAdminEvent);
    return () => window.removeEventListener('showAdmin', onShowAdminEvent);
  }, []);

  const handleLoginSuccess = () => {
    debugLog('Login success triggered');
    
    try {
      const newUsername = localStorage.getItem('username');
      const loginMethod = localStorage.getItem('loginMethod');
      
      if (newUsername) {
        setUsername(newUsername);
        setCurrentPage('menu');
        setAppError('');
        debugLog('Login successful, switching to menu', { username: newUsername, method: loginMethod });
      } else {
        debugLog('Login success but no username found!');
        setAppError('Login error: No username saved');
      }
    } catch (error) {
      debugLog('Error in handleLoginSuccess:', error);
      setAppError('Login processing error: ' + error.message);
    }
  };

  const handleLogout = () => {
    debugLog('Logout triggered');
    localStorage.removeItem('username');
    localStorage.removeItem('loginMethod');
    setUsername('');
    setCurrentPage('login');
    setAppError('');
    debugLog('Logout completed, redirecting to login');
  };

  const handleStartGameWithConfig = (config) => {
    debugLog('Starting game with config:', config);
    setGameConfig(config);
    setCurrentPage('game');
  };

  const handleShowHighscores = () => {
    debugLog('Showing highscores');
    setCurrentPage('highscores');
  };

  const handleBackToMenu = () => {
    debugLog('Back to menu');
    setCurrentPage('menu');
  };

  const handleShowSettings = () => {
    debugLog('Showing settings');
    setCurrentPage('settings');
  };

  const handleShowAdmin = () => {
    debugLog('Showing admin');
    setCurrentPage('admin');
  };

  const handleShowMultiplayer = () => {
    debugLog('Showing multiplayer');
    setCurrentPage('multiplayer');
  };

  debugLog('App rendering', { currentPage, username, hasError: !!appError });

  return (
    <div>
      {/* Debug Panel */}
      <DebugPanel currentPage={currentPage} username={username} error={appError} />
      
      {/* Deployment banner for quick verification */}
      <div style={{
        background: appError ? '#dc2626' : '#0e7490',
        color: 'white',
        padding: '8px 12px',
        fontFamily: 'system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Helvetica, Arial',
        fontSize: 14,
        display: 'flex',
        alignItems: 'center',
        gap: 8
      }}>
        <span role="img" aria-label="rocket">{appError ? '⚠️' : '🚀'}</span>
        <strong>11Seconds</strong>
        {appError ? (
          <span style={{opacity: 0.85}}>Error: {appError}</span>
        ) : (
          <>
            <span style={{opacity: 0.85}}>App loaded - Page:</span>
            <span>{currentPage} ({username || 'not logged in'})</span>
          </>
        )}
      </div>
      {currentPage === 'login' && (
        <LoginPage onLoginSuccess={handleLoginSuccess} />
      )}
      {currentPage === 'menu' && (
        <MenuPage
          onStartGame={handleStartGameWithConfig}
          onShowHighscores={handleShowHighscores}
          onShowSettings={handleShowSettings}
          onShowMultiplayer={handleShowMultiplayer}
          onLogout={handleLogout}
        />
      )}
      {currentPage === 'game' && (
        <GamePage
          onBackToMenu={handleBackToMenu}
          onLogout={handleLogout}
          gameConfig={gameConfig}
        />
      )}
      {currentPage === 'highscores' && (
        <HighscorePage
          onBackToMenu={handleBackToMenu}
        />
      )}
      {currentPage === 'admin' && (
        <AdminPage onBackToMenu={handleBackToMenu} />
      )}
      {currentPage === 'settings' && (
        <SettingsPage
          onBackToMenu={handleBackToMenu}
        />
      )}
      {currentPage === 'multiplayer' && (
        <MultiplayerPage
          onBackToMenu={handleBackToMenu}
        />
      )}
    </div>
  );
}

export default App;
