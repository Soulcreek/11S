// File: web/src/App.js
// Description: The main component that handles navigation between all pages

import React, { useState, useEffect } from 'react';
import LoginPage from './pages/LoginPage';
import MenuPage from './pages/MenuPage';
import GameConfigPage from './pages/GameConfigPage';
import GamePage from './pages/GamePage';
import HighscorePage from './pages/HighscorePage';

function App() {
  // App state to manage which page to show
  const [currentPage, setCurrentPage] = useState('login'); // 'login', 'menu', 'gameConfig', 'game', 'highscores'
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [gameConfig, setGameConfig] = useState({});

  // Check for existing username on app start
  useEffect(() => {
    const username = localStorage.getItem('username');
    if (username) {
      setIsAuthenticated(true);
      setCurrentPage('menu');
    }
  }, []);

  const handleLoginSuccess = () => {
    setIsAuthenticated(true);
    setCurrentPage('menu');
  };

  const handleLogout = () => {
    localStorage.removeItem('username');
    setIsAuthenticated(false);
    setCurrentPage('login');
  };

  const handleStartGame = () => {
    setCurrentPage('game'); // Direkt ins Spiel
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

  return (
    <div>
      {currentPage === 'login' && (
        <LoginPage onLoginSuccess={handleLoginSuccess} />
      )}
      {currentPage === 'menu' && (
        <MenuPage
          onStartGame={handleStartGame}
          onShowHighscores={handleShowHighscores}
          onShowSettings={handleShowSettings}
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
      {currentPage === 'settings' && (
        <GameConfigPage
          onStartGame={handleStartGameWithConfig}
          onBackToMenu={handleBackToMenu}
        />
      )}
    </div>
  );
}

export default App;
