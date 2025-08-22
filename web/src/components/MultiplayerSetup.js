// File: web/src/components/MultiplayerSetup.js
// Description: Setup screen for multiplayer games

import React, { useState } from 'react';

const MultiplayerSetup = ({ onStartSequentialGame, onStartSimultaneousGame, onBackToMenu }) => {
  const [players, setPlayers] = useState(['Spieler 1', 'Spieler 2']);
  const [gameConfig, setGameConfig] = useState({
    categories: ['all'],
    difficulty: 'all',
    questionsPerPlayer: 5,
    timePerQuestion: 11
  });

  const addPlayer = () => {
    if (players.length < 4) {
      setPlayers([...players, `Spieler ${players.length + 1}`]);
    }
  };

  const removePlayer = (index) => {
    if (players.length > 2) {
      const newPlayers = players.filter((_, i) => i !== index);
      setPlayers(newPlayers);
    }
  };

  const updatePlayerName = (index, name) => {
    const newPlayers = [...players];
    newPlayers[index] = name.trim() || `Spieler ${index + 1}`;
    setPlayers(newPlayers);
  };

  const categories = [
    { value: 'all', label: 'Alle Kategorien' },
    { value: 'geography', label: 'Geografie' },
    { value: 'history', label: 'Geschichte' },
    { value: 'science', label: 'Wissenschaft' },
    { value: 'sports', label: 'Sport' },
    { value: 'entertainment', label: 'Unterhaltung' },
    { value: 'art_literature', label: 'Kunst & Literatur' },
    { value: 'nature', label: 'Natur' },
    { value: 'technology', label: 'Technologie' }
  ];

  const difficulties = [
    { value: 'all', label: 'Alle Schwierigkeiten' },
    { value: 'easy', label: 'Einfach' },
    { value: 'medium', label: 'Mittel' },
    { value: 'hard', label: 'Schwer' }
  ];

  const handleStartSequential = () => {
    onStartSequentialGame(gameConfig, players);
  };

  const handleStartSimultaneous = () => {
    onStartSimultaneousGame(gameConfig, players);
  };

  return (
    <div style={styles.container}>
      <div style={styles.setupContainer}>
        <div style={styles.header}>
          <button onClick={onBackToMenu} style={styles.backButton}>
            ← Zurück zum Menü
          </button>
          <h1 style={styles.title}>👥 Mehrspieler Setup</h1>
        </div>

        {/* Players Section */}
        <div style={styles.section}>
          <h2 style={styles.sectionTitle}>Spieler ({players.length}/4)</h2>
          <div style={styles.playersGrid}>
            {players.map((player, index) => (
              <div key={index} style={styles.playerItem}>
                <input
                  type="text"
                  value={player}
                  onChange={(e) => updatePlayerName(index, e.target.value)}
                  style={styles.playerInput}
                  placeholder={`Spieler ${index + 1}`}
                />
                {players.length > 2 && (
                  <button
                    onClick={() => removePlayer(index)}
                    style={styles.removeButton}
                  >
                    ✕
                  </button>
                )}
              </div>
            ))}
          </div>
          
          {players.length < 4 && (
            <button onClick={addPlayer} style={styles.addPlayerButton}>
              + Spieler hinzufügen
            </button>
          )}
        </div>

        {/* Game Settings */}
        <div style={styles.section}>
          <h2 style={styles.sectionTitle}>Spiel-Einstellungen</h2>
          
          <div style={styles.settingsGrid}>
            <div style={styles.settingGroup}>
              <label style={styles.settingLabel}>Kategorie</label>
              <select
                value={gameConfig.categories[0]}
                onChange={(e) => setGameConfig({...gameConfig, categories: [e.target.value]})}
                style={styles.select}
              >
                {categories.map(cat => (
                  <option key={cat.value} value={cat.value}>{cat.label}</option>
                ))}
              </select>
            </div>

            <div style={styles.settingGroup}>
              <label style={styles.settingLabel}>Schwierigkeit</label>
              <select
                value={gameConfig.difficulty}
                onChange={(e) => setGameConfig({...gameConfig, difficulty: e.target.value})}
                style={styles.select}
              >
                {difficulties.map(diff => (
                  <option key={diff.value} value={diff.value}>{diff.label}</option>
                ))}
              </select>
            </div>

            <div style={styles.settingGroup}>
              <label style={styles.settingLabel}>Fragen pro Spieler</label>
              <select
                value={gameConfig.questionsPerPlayer}
                onChange={(e) => setGameConfig({...gameConfig, questionsPerPlayer: parseInt(e.target.value)})}
                style={styles.select}
              >
                <option value={3}>3 Fragen</option>
                <option value={5}>5 Fragen</option>
                <option value={7}>7 Fragen</option>
                <option value={10}>10 Fragen</option>
              </select>
            </div>

            <div style={styles.settingGroup}>
              <label style={styles.settingLabel}>Zeit pro Frage</label>
              <select
                value={gameConfig.timePerQuestion}
                onChange={(e) => setGameConfig({...gameConfig, timePerQuestion: parseInt(e.target.value)})}
                style={styles.select}
              >
                <option value={8}>8 Sekunden</option>
                <option value={11}>11 Sekunden</option>
                <option value={15}>15 Sekunden</option>
                <option value={20}>20 Sekunden</option>
              </select>
            </div>
          </div>
        </div>

        {/* Game Modes */}
        <div style={styles.section}>
          <h2 style={styles.sectionTitle}>Spiel-Modus wählen</h2>
          <div style={styles.modesGrid}>
            <div style={styles.modeCard}>
              <div style={styles.modeIcon}>🔄</div>
              <h3 style={styles.modeTitle}>Nacheinander</h3>
              <p style={styles.modeDescription}>
                Spieler beantworten Fragen der Reihe nach. Jeder sieht nur seine eigenen Fragen.
              </p>
              <button onClick={handleStartSequential} style={styles.modeButton}>
                Nacheinander spielen
              </button>
            </div>

            <div style={styles.modeCard}>
              <div style={styles.modeIcon}>⚡</div>
              <h3 style={styles.modeTitle}>Gleichzeitig</h3>
              <p style={styles.modeDescription}>
                Alle Spieler sehen dieselbe Frage. Schnellste richtige Antwort gewinnt Punkte.
              </p>
              <button onClick={handleStartSimultaneous} style={styles.modeButton}>
                Gleichzeitig spielen
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

const styles = {
  container: {
    display: 'flex',
    justifyContent: 'center',
    alignItems: 'flex-start',
    minHeight: '100vh',
    background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    padding: '20px'
  },
  setupContainer: {
    background: 'rgba(255, 255, 255, 0.95)',
    borderRadius: '20px',
    padding: '30px',
    boxShadow: '0 20px 60px rgba(0,0,0,0.2)',
    backdropFilter: 'blur(10px)',
    width: '100%',
    maxWidth: '800px'
  },
  header: {
    display: 'flex',
    alignItems: 'center',
    marginBottom: '30px'
  },
  backButton: {
    padding: '10px 15px',
    backgroundColor: '#6c757d',
    color: 'white',
    border: 'none',
    borderRadius: '8px',
    cursor: 'pointer',
    fontSize: '14px',
    marginRight: '20px'
  },
  title: {
    fontSize: '28px',
    fontWeight: 'bold',
    background: 'linear-gradient(45deg, #667eea, #764ba2)',
    backgroundClip: 'text',
    WebkitBackgroundClip: 'text',
    color: 'transparent',
    margin: 0
  },
  section: {
    marginBottom: '30px',
    padding: '25px',
    background: '#f8f9fa',
    borderRadius: '15px',
    boxShadow: '0 4px 15px rgba(0,0,0,0.1)'
  },
  sectionTitle: {
    fontSize: '20px',
    fontWeight: 'bold',
    marginBottom: '20px',
    color: '#333'
  },
  playersGrid: {
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
    gap: '15px',
    marginBottom: '20px'
  },
  playerItem: {
    display: 'flex',
    alignItems: 'center',
    gap: '10px'
  },
  playerInput: {
    flex: 1,
    padding: '10px 15px',
    border: '2px solid #ddd',
    borderRadius: '8px',
    fontSize: '14px'
  },
  removeButton: {
    width: '30px',
    height: '30px',
    border: 'none',
    borderRadius: '50%',
    background: '#f44336',
    color: 'white',
    cursor: 'pointer',
    fontSize: '12px'
  },
  addPlayerButton: {
    padding: '10px 20px',
    background: '#4CAF50',
    color: 'white',
    border: 'none',
    borderRadius: '8px',
    cursor: 'pointer',
    fontSize: '14px',
    fontWeight: 'bold'
  },
  settingsGrid: {
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
    gap: '20px'
  },
  settingGroup: {
    display: 'flex',
    flexDirection: 'column'
  },
  settingLabel: {
    fontSize: '14px',
    fontWeight: 'bold',
    marginBottom: '8px',
    color: '#333'
  },
  select: {
    padding: '10px 15px',
    border: '2px solid #ddd',
    borderRadius: '8px',
    fontSize: '14px',
    background: 'white'
  },
  modesGrid: {
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))',
    gap: '20px'
  },
  modeCard: {
    background: 'white',
    padding: '25px',
    borderRadius: '15px',
    textAlign: 'center',
    boxShadow: '0 4px 15px rgba(0,0,0,0.1)',
    border: '2px solid #eee',
    transition: 'transform 0.2s ease'
  },
  modeIcon: {
    fontSize: '48px',
    marginBottom: '15px'
  },
  modeTitle: {
    fontSize: '20px',
    fontWeight: 'bold',
    marginBottom: '15px',
    color: '#333'
  },
  modeDescription: {
    fontSize: '14px',
    color: '#666',
    marginBottom: '20px',
    lineHeight: '1.4'
  },
  modeButton: {
    padding: '12px 24px',
    background: 'linear-gradient(45deg, #667eea, #764ba2)',
    color: 'white',
    border: 'none',
    borderRadius: '8px',
    cursor: 'pointer',
    fontSize: '14px',
    fontWeight: 'bold',
    width: '100%'
  }
};

export default MultiplayerSetup;
