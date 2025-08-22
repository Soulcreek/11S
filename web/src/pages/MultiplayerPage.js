// File: web/src/pages/MultiplayerPage.js
// Description: Offline multiplayer modes for 2-4 players

import React, { useState, useEffect } from 'react';
import MultiplayerSetup from '../components/MultiplayerSetup';
import SequentialMultiplayerGame from '../components/SequentialMultiplayerGame';
import SimultaneousMultiplayerGame from '../components/SimultaneousMultiplayerGame';

const MultiplayerPage = ({ onBackToMenu }) => {
  const [currentView, setCurrentView] = useState('setup'); // 'setup' | 'sequential' | 'simultaneous'
  const [gameConfig, setGameConfig] = useState({});
  const [players, setPlayers] = useState([]);

  const handleStartSequentialGame = (config, playerList) => {
    setGameConfig(config);
    setPlayers(playerList);
    setCurrentView('sequential');
  };

  const handleStartSimultaneousGame = (config, playerList) => {
    setGameConfig(config);
    setPlayers(playerList);
    setCurrentView('simultaneous');
  };

  const handleBackToSetup = () => {
    setCurrentView('setup');
    setGameConfig({});
    setPlayers([]);
  };

  return (
    <div style={styles.container}>
      {currentView === 'setup' && (
        <MultiplayerSetup
          onStartSequentialGame={handleStartSequentialGame}
          onStartSimultaneousGame={handleStartSimultaneousGame}
          onBackToMenu={onBackToMenu}
        />
      )}
      
      {currentView === 'sequential' && (
        <SequentialMultiplayerGame
          gameConfig={gameConfig}
          players={players}
          onGameEnd={handleBackToSetup}
          onBackToSetup={handleBackToSetup}
        />
      )}
      
      {currentView === 'simultaneous' && (
        <SimultaneousMultiplayerGame
          gameConfig={gameConfig}
          players={players}
          onGameEnd={handleBackToSetup}
          onBackToSetup={handleBackToSetup}
        />
      )}
    </div>
  );
};

const styles = {
  container: {
    minHeight: '100vh',
    background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
  }
};

export default MultiplayerPage;
