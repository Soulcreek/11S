// File: web/src/pages/MenuPage.js
// Description: Main menu page with navigation buttons

import React, { useState, useEffect } from 'react';
import GameSetup from '../components/GameSetup';
import soundEffects from '../utils/soundEffects';
import achievementSystem from '../utils/achievementSystem';
import overallScoreSystem from '../utils/overallScoreSystem';

const MenuPage = ({ onStartGame, onShowHighscores, onShowSettings, onShowMultiplayer, onLogout }) => {
  const [userInfo, setUserInfo] = useState(null);
  const [isAdmin, setIsAdmin] = useState(false);
  const [soundEnabled, setSoundEnabled] = useState(true);
  const [showGameSetup, setShowGameSetup] = useState(false);
  const [playerStats, setPlayerStats] = useState({});
  const [levelProgress, setLevelProgress] = useState({});
  const [recentAchievements, setRecentAchievements] = useState([]);
  const [isMobile, setIsMobile] = useState(window.innerWidth <= 768);

  useEffect(() => {
    // Get username from localStorage
    const username = localStorage.getItem('username');
    if (username) {
      setUserInfo({ username });
    }
    const role = localStorage.getItem('userRole');
    setIsAdmin(role === 'admin');
    loadPlayerData();

    // Handle window resize for responsive design
    const handleResize = () => {
      setIsMobile(window.innerWidth <= 768);
    };

    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  const loadPlayerData = () => {
    try {
      // Load player stats and progress
      setPlayerStats(overallScoreSystem.getPlayerStats());
      setLevelProgress(overallScoreSystem.getLevelProgress());
      
      // Get recent achievements
      const achievements = achievementSystem.getAllAchievements();
      const recent = Object.values(achievements)
        .filter(a => a.unlocked)
        .sort((a, b) => new Date(b.unlockedAt) - new Date(a.unlockedAt))
        .slice(0, 3);
      setRecentAchievements(recent);
    } catch (error) {
      console.error('Error loading player data:', error);
    }
  };

  const toggleSound = () => {
    const newState = soundEffects.toggle();
    setSoundEnabled(newState);
    if (newState) {
      soundEffects.beep(440, 100, 0.1); // Test beep when enabled
    }
  };

  const handleShowGameSetup = () => {
    setShowGameSetup(true);
  };

  const handleBackToMenu = () => {
    setShowGameSetup(false);
  };

  const handleStartGameWithConfig = (gameConfig) => {
    setShowGameSetup(false);
    onStartGame(gameConfig);
  };

  // Show GameSetup component if requested
  if (showGameSetup) {
    return (
      <GameSetup 
        onStartGame={handleStartGameWithConfig} 
        onBack={handleBackToMenu}
      />
    );
  }

  const styles = getStyles(isMobile);

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
                Willkommen, <strong>{userInfo.username}</strong>!
              </p>
              
              {/* Player Progress Summary */}
              <div style={styles.playerProgress}>
                <div style={styles.levelBadge}>
                  <span style={styles.levelText}>Level {levelProgress.level || 1}</span>
                  <div style={styles.xpBar}>
                    <div 
                      style={{
                        ...styles.xpFill,
                        width: `${levelProgress.progressPercent || 0}%`
                      }}
                    ></div>
                  </div>
                  <span style={styles.xpText}>
                    {levelProgress.currentXP || 0} / {levelProgress.nextLevelXP || 100} XP
                  </span>
                </div>

                {/* Quick Stats */}
                <div style={styles.quickStats}>
                  <div style={styles.statItem}>
                    <span style={styles.statValue}>{playerStats.totalGames || 0}</span>
                    <span style={styles.statLabel}>Spiele</span>
                  </div>
                  <div style={styles.statItem}>
                    <span style={styles.statValue}>{((playerStats.correctPercentage || 0) * 100).toFixed(0)}%</span>
                    <span style={styles.statLabel}>Genauigkeit</span>
                  </div>
                  <div style={styles.statItem}>
                    <span style={styles.statValue}>{playerStats.bestStreak || 0}</span>
                    <span style={styles.statLabel}>Beste Serie</span>
                  </div>
                </div>

                {/* Recent Achievements */}
                {recentAchievements.length > 0 && (
                  <div style={styles.recentAchievements}>
                    <span style={styles.achievementsTitle}>🏆 Neueste Erfolge:</span>
                    <div style={styles.achievementsList}>
                      {recentAchievements.map(achievement => (
                        <span 
                          key={achievement.id} 
                          style={styles.achievementBadge}
                          title={achievement.name}
                        >
                          {achievement.icon}
                        </span>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            </div>
          )}
        </div>

        {/* Main Menu Buttons */}
        <div style={styles.buttonContainer}>
          <button
            onClick={handleShowGameSetup}
            style={{ ...styles.menuButton, ...styles.primaryButton }}
            className="menu-button"
          >
            <div style={styles.buttonIcon}>🎯</div>
            <div style={styles.buttonContent}>
              <h3 style={styles.buttonTitle}>Spiel starten</h3>
              <p style={styles.buttonDescription}>
                Modus, Kategorie & Schwierigkeit wählen
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
              <h3 style={styles.buttonTitle}>Bestenliste</h3>
              <p style={styles.buttonDescription}>
                Top-Spieler & deine besten Punkte
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
              <h3 style={styles.buttonTitle}>Einstellungen</h3>
              <p style={styles.buttonDescription}>
                Spiel-Konfiguration
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
                <p style={styles.buttonDescription}>Rohdaten & Benutzerverwaltung</p>
              </div>
            </button>
          )}

          {/* Multiplayer game modes */}
          <button
            onClick={onShowMultiplayer}
            style={{ ...styles.menuButton, ...styles.multiplayerButton }}
            className="menu-button"
          >
            <div style={styles.buttonIcon}>👥</div>
            <div style={styles.buttonContent}>
              <h3 style={styles.buttonTitle}>Mehrspieler</h3>
              <p style={styles.buttonDescription}>
                Offline-Mehrspieler für 2-4 Spieler
              </p>
            </div>
          </button>
        </div>

        {/* Footer with logout and settings */}
        <div style={styles.footer}>
          <button
            onClick={toggleSound}
            style={{
              ...styles.logoutButton,
              backgroundColor: soundEnabled ? '#4CAF50' : '#666',
              marginRight: '10px'
            }}
            title={soundEnabled ? 'Sound AN - Klicken zum Deaktivieren' : 'Sound AUS - Klicken zum Aktivieren'}
          >
            {soundEnabled ? '🔊' : '🔇'} Sound
          </button>
          
          <button
            onClick={onLogout}
            style={styles.logoutButton}
            className="logout-button"
          >
            🚪 Abmelden
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

// Styles function that takes isMobile parameter
const getStyles = (isMobile) => ({
  container: {
    display: 'flex',
    justifyContent: 'center',
    alignItems: 'center',
    minHeight: '100vh',
    background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    fontFamily: 'Arial, sans-serif',
    position: 'relative',
    overflow: 'hidden',
    padding: isMobile ? '10px' : '20px'
  },
  menuContainer: {
    padding: isMobile ? '20px' : '40px',
    borderRadius: isMobile ? '15px' : '20px',
    boxShadow: '0 20px 40px rgba(0, 0, 0, 0.1)',
    backgroundColor: 'rgba(255, 255, 255, 0.95)',
    backdropFilter: 'blur(10px)',
    width: '100%',
    maxWidth: isMobile ? '90vw' : '500px',
    textAlign: 'center',
    position: 'relative',
    zIndex: 10
  },
  header: {
    marginBottom: '40px'
  },
  title: {
    fontSize: isMobile ? '32px' : '48px',
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
  settingsButton: {
    background: 'linear-gradient(135deg, #ff9800 0%, #f57c00 100%)',
    color: 'white'
  },
  multiplayerButton: {
    background: 'linear-gradient(135deg, #9c27b0 0%, #673ab7 100%)',
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
  },
  // New profile styles
  playerProgress: {
    marginTop: '15px',
    padding: '15px',
    background: 'white',
    borderRadius: '10px',
    boxShadow: '0 4px 12px rgba(0,0,0,0.1)'
  },
  levelBadge: {
    display: 'flex',
    alignItems: 'center',
    gap: '10px',
    marginBottom: '15px'
  },
  levelText: {
    fontSize: '14px',
    fontWeight: 'bold',
    color: '#667eea',
    minWidth: '60px'
  },
  xpBar: {
    flex: 1,
    height: '8px',
    backgroundColor: '#e0e0e0',
    borderRadius: '4px',
    overflow: 'hidden'
  },
  xpFill: {
    height: '100%',
    background: 'linear-gradient(45deg, #4CAF50, #8BC34A)',
    borderRadius: '4px',
    transition: 'width 0.3s ease'
  },
  xpText: {
    fontSize: '11px',
    color: '#666',
    minWidth: '80px',
    textAlign: 'right'
  },
  quickStats: {
    display: 'flex',
    justifyContent: 'space-around',
    marginBottom: '15px'
  },
  statItem: {
    textAlign: 'center'
  },
  statValue: {
    fontSize: '16px',
    fontWeight: 'bold',
    color: '#667eea',
    display: 'block'
  },
  statLabel: {
    fontSize: '10px',
    color: '#666',
    textTransform: 'uppercase'
  },
  recentAchievements: {
    display: 'flex',
    alignItems: 'center',
    gap: '8px',
    flexWrap: 'wrap'
  },
  achievementsTitle: {
    fontSize: '12px',
    fontWeight: 'bold',
    color: '#666'
  },
  achievementsList: {
    display: 'flex',
    gap: '5px'
  },
  achievementBadge: {
    fontSize: '18px',
    padding: '2px',
    borderRadius: '4px',
    background: 'linear-gradient(45deg, #FFD700, #FFA500)',
    cursor: 'help'
  }
});

export default MenuPage;
