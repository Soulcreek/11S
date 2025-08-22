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

function App() {
  // App state to manage which page to show
  const [currentPage, setCurrentPage] = useState('login'); // 'login', 'menu', 'gameConfig', 'game', 'highscores', 'multiplayer'
  const [gameConfig, setGameConfig] = useState({});

  // Check for existing username on app start
  useEffect(() => {
    const username = localStorage.getItem('username');
    if (username) {
      setCurrentPage('menu');
    }
    const onShowAdminEvent = () => setCurrentPage('admin');
    window.addEventListener('showAdmin', onShowAdminEvent);
    return () => window.removeEventListener('showAdmin', onShowAdminEvent);
  }, []);

  const handleLoginSuccess = () => {
    setCurrentPage('menu');
  };

  const handleLogout = () => {
    localStorage.removeItem('username');
    setCurrentPage('login');
  };

  const handleStartGameWithConfig = (config) => {
    setGameConfig(config);
    setCurrentPage('game');
  };

  const handleShowHighscores = () => {
    setCurrentPage('highscores');
  };

  const handleBackToMenu = () => {
    setCurrentPage('menu');
  };

  const handleShowSettings = () => {
    setCurrentPage('settings');
  };

  const handleShowAdmin = () => {
    setCurrentPage('admin');
  };

  const handleShowMultiplayer = () => {
    setCurrentPage('multiplayer');
  };

  return (
    <div>
      {/* Deployment banner for quick verification */}
      <div style={{
        background: '#0e7490',
        color: 'white',
        padding: '8px 12px',
        fontFamily: 'system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Helvetica, Arial',
        fontSize: 14,
        display: 'flex',
        alignItems: 'center',
        gap: 8
      }}>
        <span role="img" aria-label="rocket">🚀</span>
        <strong>11Seconds</strong>
        <span style={{opacity: 0.85}}>Deployed successfully:</span>
        <span>{process.env.REACT_APP_BUILD_TIME || 'dev'}</span>
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
