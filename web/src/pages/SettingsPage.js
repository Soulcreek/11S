// File: web/src/pages/SettingsPage.js
// Description: Modern settings page with profile, progress, and game preferences

import React, { useState, useEffect } from 'react';
import { settingsManager } from '../utils/localStorage';
import achievementSystem from '../utils/achievementSystem';
import overallScoreSystem from '../utils/overallScoreSystem';

const SettingsPage = ({ onBackToMenu }) => {
  const [settings, setSettings] = useState({});
  const [playerStats, setPlayerStats] = useState({});
  const [levelProgress, setLevelProgress] = useState({});
  const [achievements, setAchievements] = useState({});
  const [notification, setNotification] = useState('');
  const [activeTab, setActiveTab] = useState('profile'); // 'profile' | 'preferences' | 'data'

  useEffect(() => {
    loadAllData();
  }, []);

  const loadAllData = () => {
    try {
      // Load settings
      const userSettings = settingsManager.getSettings();
      setSettings(userSettings.settings || {});

      // Load player data
      setPlayerStats(overallScoreSystem.getPlayerStats());
      setLevelProgress(overallScoreSystem.getLevelProgress());
      setAchievements(achievementSystem.getAllAchievements());
    } catch (error) {
      console.error('Error loading settings data:', error);
      showNotification('Fehler beim Laden der Einstellungen', 'error');
    }
  };

  const showNotification = (message, type = 'success') => {
    setNotification({ message, type });
    setTimeout(() => setNotification(''), 3000);
  };

  const handleSettingChange = (key, value) => {
    const newSettings = { ...settings, [key]: value };
    setSettings(newSettings);
    
    const result = settingsManager.updateSettings(newSettings);
    if (result.success) {
      showNotification('Einstellungen gespeichert');
    } else {
      showNotification('Fehler beim Speichern', 'error');
    }
  };

  const handleResetData = (type) => {
    if (window.confirm(`Sicher, dass du alle ${type === 'stats' ? 'Statistiken' : type === 'achievements' ? 'Erfolge' : 'Spielstände'} zurücksetzen möchtest?`)) {
      try {
        if (type === 'stats') {
          localStorage.removeItem('playerOverallScore');
          localStorage.removeItem('playerSkillRatings');
        } else if (type === 'achievements') {
          localStorage.removeItem('playerAchievements');
        } else if (type === 'highscores') {
          localStorage.removeItem('highscores');
        }
        
        loadAllData();
        showNotification(`${type === 'stats' ? 'Statistiken' : type === 'achievements' ? 'Erfolge' : 'Spielstände'} zurückgesetzt`);
      } catch (error) {
        showNotification('Fehler beim Zurücksetzen', 'error');
      }
    }
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'Nie';
    return new Date(dateString).toLocaleDateString('de-DE', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  return (
    <div style={styles.container}>
      <div style={styles.settingsContainer}>
        <div style={styles.header}>
          <button onClick={onBackToMenu} style={styles.backButton}>
            ← Zurück zum Menü
          </button>
          <h1 style={styles.title}>⚙️ Einstellungen</h1>
        </div>

        {notification && (
          <div style={{ 
            ...styles.notification, 
            backgroundColor: notification.type === 'error' ? '#f44336' : '#4CAF50' 
          }}>
            {notification.message}
          </div>
        )}

        <div style={styles.tabContainer}>
          <button
            style={{ ...styles.tab, ...(activeTab === 'profile' ? styles.activeTab : styles.inactiveTab) }}
            onClick={() => setActiveTab('profile')}
          >
            👤 Profil
          </button>
          <button
            style={{ ...styles.tab, ...(activeTab === 'preferences' ? styles.activeTab : styles.inactiveTab) }}
            onClick={() => setActiveTab('preferences')}
          >
            🎮 Einstellungen
          </button>
          <button
            style={{ ...styles.tab, ...(activeTab === 'data' ? styles.activeTab : styles.inactiveTab) }}
            onClick={() => setActiveTab('data')}
          >
            💾 Daten
          </button>
        </div>

        <div style={styles.content}>
          {activeTab === 'profile' && (
            <div style={styles.tabContent}>
              <h2 style={styles.sectionTitle}>👤 Spieler-Profil</h2>
              
              {/* Player Level and Progress */}
              <div style={styles.profileCard}>
                <h3 style={styles.cardTitle}>Level & Fortschritt</h3>
                <div style={styles.levelContainer}>
                  <div style={styles.levelBadge}>
                    <span style={styles.levelNumber}>Level {levelProgress.level || 1}</span>
                  </div>
                  <div style={styles.xpContainer}>
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
                </div>
                
                <div style={styles.statsGrid}>
                  <div style={styles.statBox}>
                    <span style={styles.statNumber}>{playerStats.totalGames || 0}</span>
                    <span style={styles.statLabel}>Spiele gespielt</span>
                  </div>
                  <div style={styles.statBox}>
                    <span style={styles.statNumber}>{playerStats.totalQuestions || 0}</span>
                    <span style={styles.statLabel}>Fragen beantwortet</span>
                  </div>
                  <div style={styles.statBox}>
                    <span style={styles.statNumber}>{((playerStats.correctPercentage || 0) * 100).toFixed(1)}%</span>
                    <span style={styles.statLabel}>Genauigkeit</span>
                  </div>
                  <div style={styles.statBox}>
                    <span style={styles.statNumber}>{playerStats.bestStreak || 0}</span>
                    <span style={styles.statLabel}>Beste Serie</span>
                  </div>
                </div>
              </div>

              {/* Skill Ratings */}
              <div style={styles.profileCard}>
                <h3 style={styles.cardTitle}>🎯 Fähigkeitsbewertungen</h3>
                <div style={styles.skillsList}>
                  <div style={styles.skillItem}>
                    <span style={styles.skillName}>Genauigkeit</span>
                    <div style={styles.skillBarContainer}>
                      <div style={styles.skillBar}>
                        <div style={{
                          ...styles.skillBarFill,
                          width: `${(playerStats.skillRatings?.accuracy || 0) * 100}%`
                        }}></div>
                      </div>
                      <span style={styles.skillValue}>
                        {((playerStats.skillRatings?.accuracy || 0) * 100).toFixed(0)}%
                      </span>
                    </div>
                  </div>
                  
                  <div style={styles.skillItem}>
                    <span style={styles.skillName}>Geschwindigkeit</span>
                    <div style={styles.skillBarContainer}>
                      <div style={styles.skillBar}>
                        <div style={{
                          ...styles.skillBarFill,
                          width: `${(playerStats.skillRatings?.speed || 0) * 100}%`
                        }}></div>
                      </div>
                      <span style={styles.skillValue}>
                        {((playerStats.skillRatings?.speed || 0) * 100).toFixed(0)}%
                      </span>
                    </div>
                  </div>
                  
                  <div style={styles.skillItem}>
                    <span style={styles.skillName}>Konstanz</span>
                    <div style={styles.skillBarContainer}>
                      <div style={styles.skillBar}>
                        <div style={{
                          ...styles.skillBarFill,
                          width: `${(playerStats.skillRatings?.consistency || 0) * 100}%`
                        }}></div>
                      </div>
                      <span style={styles.skillValue}>
                        {((playerStats.skillRatings?.consistency || 0) * 100).toFixed(0)}%
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              {/* Recent Achievements */}
              <div style={styles.profileCard}>
                <h3 style={styles.cardTitle}>🏆 Neueste Erfolge</h3>
                <div style={styles.recentAchievements}>
                  {Object.values(achievements)
                    .filter(a => a.unlocked)
                    .sort((a, b) => new Date(b.unlockedAt) - new Date(a.unlockedAt))
                    .slice(0, 5)
                    .map(achievement => (
                      <div key={achievement.id} style={styles.achievementItem}>
                        <span style={styles.achievementIcon}>{achievement.icon}</span>
                        <div style={styles.achievementInfo}>
                          <span style={styles.achievementName}>{achievement.name}</span>
                          <span style={styles.achievementDate}>
                            {formatDate(achievement.unlockedAt)}
                          </span>
                        </div>
                      </div>
                    ))}
                  {Object.values(achievements).filter(a => a.unlocked).length === 0 && (
                    <p style={styles.noData}>Noch keine Erfolge freigeschaltet. Spiele ein Quiz!</p>
                  )}
                </div>
              </div>
            </div>
          )}

          {activeTab === 'preferences' && (
            <div style={styles.tabContent}>
              <h2 style={styles.sectionTitle}>🎮 Spiel-Einstellungen</h2>
              
              <div style={styles.settingsCard}>
                <h3 style={styles.cardTitle}>Audio</h3>
                <div style={styles.settingGroup}>
                  <label style={styles.settingLabel}>
                    <input
                      type="checkbox"
                      checked={settings.soundEnabled !== false}
                      onChange={(e) => handleSettingChange('soundEnabled', e.target.checked)}
                      style={styles.checkbox}
                    />
                    Sound-Effekte aktivieren
                  </label>
                  <p style={styles.settingDescription}>
                    Aktiviert Klänge für Spielstart, richtige/falsche Antworten und andere Events
                  </p>
                </div>
              </div>

              <div style={styles.settingsCard}>
                <h3 style={styles.cardTitle}>Gameplay</h3>
                <div style={styles.settingGroup}>
                  <label style={styles.settingLabel}>
                    Zeit pro Frage (Sekunden):
                  </label>
                  <select
                    value={settings.timePerQuestion || 11}
                    onChange={(e) => handleSettingChange('timePerQuestion', parseInt(e.target.value))}
                    style={styles.select}
                  >
                    <option value={8}>8 Sekunden (Schnell)</option>
                    <option value={11}>11 Sekunden (Standard)</option>
                    <option value={15}>15 Sekunden (Entspannt)</option>
                    <option value={20}>20 Sekunden (Gemütlich)</option>
                  </select>
                </div>

                <div style={styles.settingGroup}>
                  <label style={styles.settingLabel}>
                    <input
                      type="checkbox"
                      checked={settings.showHints !== false}
                      onChange={(e) => handleSettingChange('showHints', e.target.checked)}
                      style={styles.checkbox}
                    />
                    Hilfestellungen anzeigen
                  </label>
                  <p style={styles.settingDescription}>
                    Zeigt hilfreiche Tipps und Kategorien-Informationen während des Spiels
                  </p>
                </div>
              </div>

              <div style={styles.settingsCard}>
                <h3 style={styles.cardTitle}>Erscheinungsbild</h3>
                <div style={styles.settingGroup}>
                  <label style={styles.settingLabel}>
                    <input
                      type="checkbox"
                      checked={settings.animationsEnabled !== false}
                      onChange={(e) => handleSettingChange('animationsEnabled', e.target.checked)}
                      style={styles.checkbox}
                    />
                    Animationen aktivieren
                  </label>
                  <p style={styles.settingDescription}>
                    Aktiviert sanfte Übergänge und visuelle Effekte
                  </p>
                </div>

                <div style={styles.settingGroup}>
                  <label style={styles.settingLabel}>
                    <input
                      type="checkbox"
                      checked={settings.compactMode === true}
                      onChange={(e) => handleSettingChange('compactMode', e.target.checked)}
                      style={styles.checkbox}
                    />
                    Kompakter Modus
                  </label>
                  <p style={styles.settingDescription}>
                    Reduzierte Abstände und kleinere UI-Elemente für mehr Inhalte
                  </p>
                </div>
              </div>
            </div>
          )}

          {activeTab === 'data' && (
            <div style={styles.tabContent}>
              <h2 style={styles.sectionTitle}>💾 Datenverwaltung</h2>
              
              <div style={styles.settingsCard}>
                <h3 style={styles.cardTitle}>Spielstand</h3>
                <div style={styles.dataInfo}>
                  <p>Gesamt Spiele: <strong>{playerStats.totalGames || 0}</strong></p>
                  <p>Gesamt XP: <strong>{levelProgress.currentXP || 0}</strong></p>
                  <p>Erfolge freigeschaltet: <strong>{Object.values(achievements).filter(a => a.unlocked).length}</strong></p>
                </div>
                
                <div style={styles.buttonGroup}>
                  <button
                    onClick={() => handleResetData('stats')}
                    style={{...styles.resetButton, backgroundColor: '#ff9800'}}
                  >
                    Statistiken zurücksetzen
                  </button>
                  <button
                    onClick={() => handleResetData('achievements')}
                    style={{...styles.resetButton, backgroundColor: '#ff5722'}}
                  >
                    Erfolge zurücksetzen
                  </button>
                  <button
                    onClick={() => handleResetData('highscores')}
                    style={{...styles.resetButton, backgroundColor: '#f44336'}}
                  >
                    Spielstände löschen
                  </button>
                </div>
              </div>

              <div style={styles.settingsCard}>
                <h3 style={styles.cardTitle}>Datenschutz</h3>
                <div style={styles.privacyInfo}>
                  <p>🔒 Alle Daten werden nur lokal in deinem Browser gespeichert</p>
                  <p>📊 Keine Daten werden an externe Server übertragen</p>
                  <p>🚮 Du kannst jederzeit alle Daten löschen</p>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

// Styles
const styles = {
  container: {
    display: 'flex',
    justifyContent: 'center',
    alignItems: 'flex-start',
    minHeight: '100vh',
    background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
    padding: '20px'
  },
  settingsContainer: {
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
    fontSize: '32px',
    fontWeight: 'bold',
    background: 'linear-gradient(45deg, #10b981, #059669)',
    backgroundClip: 'text',
    WebkitBackgroundClip: 'text',
    color: 'transparent',
    margin: 0
  },
  notification: {
    padding: '12px 20px',
    borderRadius: '8px',
    marginBottom: '20px',
    color: 'white',
    fontWeight: 'bold',
    textAlign: 'center'
  },
  tabContainer: {
    display: 'flex',
    marginBottom: '30px',
    borderBottom: '2px solid #eee'
  },
  tab: {
    flex: 1,
    padding: '15px 20px',
    border: 'none',
    background: 'none',
    cursor: 'pointer',
    fontSize: '16px',
    fontWeight: 'bold',
    transition: 'all 0.3s ease'
  },
  activeTab: {
    color: '#10b981',
    borderBottom: '3px solid #10b981'
  },
  inactiveTab: {
    color: '#666'
  },
  content: {
    minHeight: '400px'
  },
  tabContent: {
    animation: 'fadeIn 0.3s ease'
  },
  sectionTitle: {
    fontSize: '24px',
    fontWeight: 'bold',
    marginBottom: '25px',
    textAlign: 'center',
    color: '#333'
  },
  profileCard: {
    background: '#f8f9fa',
    borderRadius: '15px',
    padding: '25px',
    marginBottom: '20px',
    boxShadow: '0 4px 15px rgba(0,0,0,0.1)'
  },
  settingsCard: {
    background: '#f8f9fa',
    borderRadius: '15px',
    padding: '25px',
    marginBottom: '20px',
    boxShadow: '0 4px 15px rgba(0,0,0,0.1)'
  },
  cardTitle: {
    fontSize: '18px',
    fontWeight: 'bold',
    marginBottom: '20px',
    color: '#333'
  },
  levelContainer: {
    display: 'flex',
    alignItems: 'center',
    gap: '20px',
    marginBottom: '25px'
  },
  levelBadge: {
    background: 'linear-gradient(45deg, #10b981, #059669)',
    color: 'white',
    borderRadius: '50px',
    padding: '10px 20px',
    textAlign: 'center'
  },
  levelNumber: {
    fontSize: '18px',
    fontWeight: 'bold'
  },
  xpContainer: {
    flex: 1
  },
  xpBar: {
    width: '100%',
    height: '12px',
    backgroundColor: '#ddd',
    borderRadius: '6px',
    overflow: 'hidden',
    marginBottom: '8px'
  },
  xpFill: {
    height: '100%',
    background: 'linear-gradient(45deg, #4CAF50, #8BC34A)',
    borderRadius: '6px',
    transition: 'width 0.3s ease'
  },
  xpText: {
    fontSize: '14px',
    color: '#666'
  },
  statsGrid: {
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fit, minmax(120px, 1fr))',
    gap: '15px'
  },
  statBox: {
    textAlign: 'center',
    background: 'white',
    padding: '15px',
    borderRadius: '10px',
    boxShadow: '0 2px 8px rgba(0,0,0,0.1)'
  },
  statNumber: {
    fontSize: '24px',
    fontWeight: 'bold',
    color: '#10b981',
    display: 'block'
  },
  statLabel: {
    fontSize: '12px',
    color: '#666',
    marginTop: '5px'
  },
  skillsList: {
    display: 'flex',
    flexDirection: 'column',
    gap: '15px'
  },
  skillItem: {
    display: 'flex',
    alignItems: 'center',
    gap: '15px'
  },
  skillName: {
    minWidth: '120px',
    fontSize: '14px',
    fontWeight: '500'
  },
  skillBarContainer: {
    flex: 1,
    display: 'flex',
    alignItems: 'center',
    gap: '10px'
  },
  skillBar: {
    flex: 1,
    height: '8px',
    backgroundColor: '#ddd',
    borderRadius: '4px',
    overflow: 'hidden'
  },
  skillBarFill: {
    height: '100%',
    background: 'linear-gradient(45deg, #4CAF50, #8BC34A)',
    borderRadius: '4px',
    transition: 'width 0.3s ease'
  },
  skillValue: {
    fontSize: '12px',
    fontWeight: 'bold',
    color: '#10b981',
    minWidth: '35px'
  },
  recentAchievements: {
    display: 'flex',
    flexDirection: 'column',
    gap: '12px'
  },
  achievementItem: {
    display: 'flex',
    alignItems: 'center',
    gap: '12px',
    padding: '10px',
    background: 'white',
    borderRadius: '8px',
    boxShadow: '0 2px 8px rgba(0,0,0,0.1)'
  },
  achievementIcon: {
    fontSize: '24px'
  },
  achievementInfo: {
    flex: 1
  },
  achievementName: {
    fontSize: '14px',
    fontWeight: 'bold',
    display: 'block',
    color: '#333'
  },
  achievementDate: {
    fontSize: '12px',
    color: '#666'
  },
  noData: {
    textAlign: 'center',
    color: '#666',
    fontStyle: 'italic',
    padding: '20px'
  },
  settingGroup: {
    marginBottom: '20px'
  },
  settingLabel: {
    display: 'flex',
    alignItems: 'center',
    gap: '10px',
    fontSize: '14px',
    fontWeight: '500',
    marginBottom: '8px',
    cursor: 'pointer'
  },
  checkbox: {
    width: '18px',
    height: '18px'
  },
  select: {
    width: '100%',
    padding: '10px',
    borderRadius: '8px',
    border: '2px solid #ddd',
    fontSize: '14px',
    background: 'white'
  },
  settingDescription: {
    fontSize: '12px',
    color: '#666',
    margin: '5px 0 0 28px'
  },
  dataInfo: {
    background: 'white',
    padding: '15px',
    borderRadius: '8px',
    marginBottom: '20px'
  },
  buttonGroup: {
    display: 'flex',
    gap: '10px',
    flexWrap: 'wrap'
  },
  resetButton: {
    padding: '10px 15px',
    border: 'none',
    borderRadius: '8px',
    color: 'white',
    cursor: 'pointer',
    fontSize: '14px',
    fontWeight: 'bold',
    flex: 1,
    minWidth: '150px'
  },
  privacyInfo: {
    background: 'white',
    padding: '20px',
    borderRadius: '8px'
  }
};

export default SettingsPage;
