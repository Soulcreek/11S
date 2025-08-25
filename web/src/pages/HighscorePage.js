// File: web/src/pages/HighscorePage.js
// Description: Enhanced highscore page with achievements, overall score, and detailed stats

import React, { useState, useEffect } from 'react';
import achievementSystem from '../utils/achievementSystem';
import overallScoreSystem from '../utils/overallScoreSystem';

const HighscorePage = ({ onBackToMenu }) => {
  const [highscores, setHighscores] = useState([]);
  const [globalHighscores, setGlobalHighscores] = useState([]);
  const [loading, setLoading] = useState(true);
  const [notification, setNotification] = useState('');
  const [activeTab, setActiveTab] = useState('overview'); // 'overview' | 'games' | 'achievements' | 'categories'
  const [playerStats, setPlayerStats] = useState({});
  const [achievements, setAchievements] = useState({});
  const [levelProgress, setLevelProgress] = useState({});

  useEffect(() => {
    loadAllData();
  }, []);

  const loadAllData = () => {
    setLoading(true);
    try {
      // Load traditional highscores
      const scores = JSON.parse(localStorage.getItem('highscores') || '[]');
      scores.sort((a, b) => b.score - a.score || new Date(b.date) - new Date(a.date));
      setHighscores(scores);

      // Load global highscores (best per user)
      const byUser = {};
      for (const s of scores) {
        const name = (s.name || 'Gast').trim();
        const score = Number(s.score) || 0;
        const date = s.date || new Date().toISOString();
        if (!byUser[name] || score > byUser[name].score || (score === byUser[name].score && new Date(date) > new Date(byUser[name].date))) {
          byUser[name] = { ...s, name, score, date };
        }
      }
      const global = Object.values(byUser).sort((a, b) => b.score - a.score || new Date(b.date) - new Date(a.date));
      setGlobalHighscores(global);

      // Load new scoring systems
      setPlayerStats(overallScoreSystem.getPlayerStats());
      setAchievements(achievementSystem.getAllAchievements());
      setLevelProgress(overallScoreSystem.getLevelProgress());
      
      setLoading(false);
    } catch (e) {
      console.error('Error loading data:', e);
      setNotification('Fehler beim Laden der Daten.');
      setLoading(false);
    }
  };

  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('de-DE', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const getScoreColor = (score) => {
    if (score >= 400) return '#4CAF50';
    if (score >= 300) return '#FF9800';
    return '#f44336';
  };

  if (loading) {
    return (
      <div style={styles.container}>
        <div style={styles.loadingContainer}>
          <h2>Highscores werden geladen...</h2>
          <div style={styles.loadingSpinner}></div>
        </div>
      </div>
    );
  }

  return (
    <div style={styles.container}>
      <div style={styles.highscoreContainer}>
        <div style={styles.header}>
          <button onClick={onBackToMenu} style={styles.backButton}>
            ← Zurück zum Menü
          </button>
          <h1 style={styles.title}>🏆 Highscores</h1>
        </div>
        {notification && (
          <div style={{ ...styles.notification, backgroundColor: '#f44336' }}>{notification}</div>
        )}
        <div style={styles.tabContainer}>
          <button
            style={{ ...styles.tab, ...(activeTab === 'overview' ? styles.activeTab : styles.inactiveTab) }}
            onClick={() => setActiveTab('overview')}
          >
            📊 Übersicht
          </button>
          <button
            style={{ ...styles.tab, ...(activeTab === 'games' ? styles.activeTab : styles.inactiveTab) }}
            onClick={() => setActiveTab('games')}
          >
            🎮 Spiele
          </button>
          <button
            style={{ ...styles.tab, ...(activeTab === 'achievements' ? styles.activeTab : styles.inactiveTab) }}
            onClick={() => setActiveTab('achievements')}
          >
            🏆 Erfolge
          </button>
          <button
            style={{ ...styles.tab, ...(activeTab === 'categories' ? styles.activeTab : styles.inactiveTab) }}
            onClick={() => setActiveTab('categories')}
          >
            📂 Kategorien
          </button>
        </div>

        <div style={styles.content}>
          {activeTab === 'overview' && (
            <div style={styles.tabContent}>
              <h2 style={styles.sectionTitle}>🎮 Spieler-Statistiken</h2>
              
              <div style={styles.statsGrid}>
                <div style={styles.statCard}>
                  <h3 style={styles.statTitle}>Level & XP</h3>
                  <div style={styles.levelDisplay}>
                    <span style={styles.level}>Level {levelProgress.level || 1}</span>
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

                <div style={styles.statCard}>
                  <h3 style={styles.statTitle}>Spiel-Statistiken</h3>
                  <div style={styles.statGrid}>
                    <div style={styles.statItem}>
                      <span style={styles.statValue}>{playerStats.totalGames || 0}</span>
                      <span style={styles.statLabel}>Gespielte Spiele</span>
                    </div>
                    <div style={styles.statItem}>
                      <span style={styles.statValue}>{playerStats.totalQuestions || 0}</span>
                      <span style={styles.statLabel}>Beantwortete Fragen</span>
                    </div>
                    <div style={styles.statItem}>
                      <span style={styles.statValue}>{((playerStats.correctPercentage || 0) * 100).toFixed(1)}%</span>
                      <span style={styles.statLabel}>Genauigkeit</span>
                    </div>
                    <div style={styles.statItem}>
                      <span style={styles.statValue}>{playerStats.bestStreak || 0}</span>
                      <span style={styles.statLabel}>Beste Serie</span>
                    </div>
                  </div>
                </div>

                <div style={styles.statCard}>
                  <h3 style={styles.statTitle}>Fähigkeitsbewertungen</h3>
                  <div style={styles.skillGrid}>
                    <div style={styles.skillItem}>
                      <span style={styles.skillName}>Genauigkeit</span>
                      <div style={styles.skillBar}>
                        <div style={{...styles.skillFill, width: `${(playerStats.skillRatings?.accuracy || 0) * 100}%`}}></div>
                      </div>
                    </div>
                    <div style={styles.skillItem}>
                      <span style={styles.skillName}>Geschwindigkeit</span>
                      <div style={styles.skillBar}>
                        <div style={{...styles.skillFill, width: `${(playerStats.skillRatings?.speed || 0) * 100}%`}}></div>
                      </div>
                    </div>
                    <div style={styles.skillItem}>
                      <span style={styles.skillName}>Konstanz</span>
                      <div style={styles.skillBar}>
                        <div style={{...styles.skillFill, width: `${(playerStats.skillRatings?.consistency || 0) * 100}%`}}></div>
                      </div>
                    </div>
                  </div>
                </div>

                <div style={styles.statCard}>
                  <h3 style={styles.statTitle}>Erfolge</h3>
                  <div style={styles.achievementSummary}>
                    <div style={styles.achievementCount}>
                      <span style={styles.achievementNumber}>
                        {Object.values(achievements).filter(a => a.unlocked).length}
                      </span>
                      <span style={styles.achievementTotal}>/ {Object.keys(achievements).length}</span>
                      <span style={styles.achievementLabel}>Freigeschaltet</span>
                    </div>
                    <div style={styles.recentAchievements}>
                      {Object.values(achievements)
                        .filter(a => a.unlocked)
                        .sort((a, b) => new Date(b.unlockedAt) - new Date(a.unlockedAt))
                        .slice(0, 3)
                        .map(achievement => (
                          <div key={achievement.id} style={styles.recentAchievement}>
                            <span style={styles.achievementIcon}>{achievement.icon}</span>
                            <span style={styles.achievementName}>{achievement.name}</span>
                          </div>
                        ))}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}

          {activeTab === 'games' && (
            <div style={styles.tabContent}>
              <h2 style={styles.sectionTitle}>🎮 Spiel-Bestenliste</h2>
              {highscores.length === 0 ? (
                <p>Keine Spiele gespielt. Starte dein erstes Quiz!</p>
              ) : (
                <div style={styles.highscoreList}>
                  {highscores.slice(0, 20).map((entry, idx) => (
                    <div key={idx} style={styles.highscoreItem}>
                      <span style={styles.rank}>{idx + 1}.</span>
                      <span style={styles.username}>{entry.name}</span>
                      <span style={{ ...styles.score, color: getScoreColor(entry.score) }}>{entry.score}</span>
                      <span style={styles.mode}>{entry.mode || 'Klassisch'}</span>
                      <span style={styles.date}>{formatDate(entry.date)}</span>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}

          {activeTab === 'achievements' && (
            <div style={styles.tabContent}>
              <h2 style={styles.sectionTitle}>🏆 Erfolge ({Object.values(achievements).filter(a => a.unlocked).length}/{Object.keys(achievements).length})</h2>
              <div style={styles.achievementGrid}>
                {Object.values(achievements).map(achievement => (
                  <div 
                    key={achievement.id} 
                    style={{
                      ...styles.achievementCard,
                      opacity: achievement.unlocked ? 1 : 0.5,
                      backgroundColor: achievement.unlocked ? '#e8f5e8' : '#f5f5f5'
                    }}
                  >
                    <div style={styles.achievementIcon}>
                      {achievement.unlocked ? achievement.icon : '🔒'}
                    </div>
                    <div style={styles.achievementInfo}>
                      <h4 style={styles.achievementName}>
                        {achievement.unlocked ? achievement.name : '???'}
                      </h4>
                      <p style={styles.achievementDescription}>
                        {achievement.unlocked ? achievement.description : 'Erfolg noch nicht freigeschaltet'}
                      </p>
                      {achievement.unlocked && achievement.unlockedAt && (
                        <p style={styles.achievementDate}>
                          Freigeschaltet: {formatDate(achievement.unlockedAt)}
                        </p>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {activeTab === 'categories' && (
            <div style={styles.tabContent}>
              <h2 style={styles.sectionTitle}>📂 Kategorien-Statistiken</h2>
              <div style={styles.categoryGrid}>
                {/* This will be populated with category-specific stats */}
                <div style={styles.comingSoon}>
                  <h3>🚧 In Entwicklung</h3>
                  <p>Kategorie-spezifische Statistiken werden bald verfügbar sein.</p>
                </div>
              </div>
            </div>
          )}

          {activeTab === 'local' && (
            <div style={styles.tabContent}>
              {highscores.length === 0 ? (
                <p>Keine Highscores vorhanden. Spiele ein Quiz!</p>
              ) : (
                highscores.slice(0, 20).map((entry, idx) => (
                  <div key={idx} style={styles.highscoreItem}>
                    <span style={styles.rank}>{idx + 1}.</span>
                    <span style={styles.username}>{entry.name}</span>
                    <span style={{ ...styles.score, color: getScoreColor(entry.score) }}>{entry.score}</span>
                    <span style={styles.date}>{formatDate(entry.date)}</span>
                  </div>
                ))
              )}
            </div>
          )}

          {activeTab === 'global' && (
            <div style={styles.tabContent}>
              {globalHighscores.length === 0 ? (
                <p>Keine globalen Highscores vorhanden.</p>
              ) : (
                globalHighscores.slice(0, 50).map((entry, idx) => (
                  <div key={entry.name} style={styles.highscoreItem}>
                    <span style={styles.rank}>{idx + 1}.</span>
                    <span style={styles.username}>{entry.name}</span>
                    <span style={{ ...styles.score, color: getScoreColor(entry.score) }}>{entry.score}</span>
                    <span style={styles.date}>{formatDate(entry.date)}</span>
                  </div>
                ))
              )}
            </div>
          )}
        </div>
        <div style={styles.footer}>
          <button onClick={loadAllData} style={styles.refreshButton}>
            🔄 Aktualisieren
          </button>
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
    alignItems: 'center',
    minHeight: '100vh',
    background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
    fontFamily: 'Arial, sans-serif',
    padding: '20px'
  },
  highscoreContainer: {
    padding: '30px',
    borderRadius: '20px',
    boxShadow: '0 20px 40px rgba(0, 0, 0, 0.1)',
    backgroundColor: 'rgba(255, 255, 255, 0.95)',
    backdropFilter: 'blur(10px)',
    width: '100%',
    maxWidth: '700px'
  },
  loadingContainer: {
    padding: '50px',
    textAlign: 'center'
  },
  loadingSpinner: {
    border: '4px solid #f3f3f3',
    borderTop: '4px solid #10b981',
    borderRadius: '50%',
    width: '40px',
    height: '40px',
    animation: 'spin 1s linear infinite',
    margin: '20px auto'
  },
  header: {
    display: 'flex',
    alignItems: 'center',
    marginBottom: '30px',
    position: 'relative'
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
    WebkitBackgroundClip: 'text',
    WebkitTextFillColor: 'transparent',
    margin: 0,
    flex: 1,
    textAlign: 'center'
  },
  notification: {
    padding: '12px',
    color: 'white',
    borderRadius: '8px',
    marginBottom: '20px',
    fontSize: '14px',
    textAlign: 'center'
  },
  tabContainer: {
    display: 'flex',
    marginBottom: '30px',
    backgroundColor: '#f8f9fa',
    borderRadius: '12px',
    padding: '4px'
  },
  tab: {
    flex: 1,
    padding: '12px 16px',
    border: 'none',
    borderRadius: '8px',
    cursor: 'pointer',
    fontSize: '14px',
    fontWeight: 'bold',
    transition: 'all 0.3s ease'
  },
  activeTab: {
    backgroundColor: '#10b981',
    color: 'white'
  },
  inactiveTab: {
    backgroundColor: 'transparent',
    color: '#666'
  },
  content: {
    minHeight: '400px'
  },
  tabContent: {
    animation: 'fadeIn 0.3s ease'
  },
  sectionTitle: {
    fontSize: '20px',
    fontWeight: 'bold',
    color: '#333',
    marginBottom: '20px',
    textAlign: 'center'
  },
  highscoreList: {
    display: 'flex',
    flexDirection: 'column',
    gap: '10px'
  },
  highscoreItem: {
    display: 'flex',
    alignItems: 'center',
    padding: '15px',
    backgroundColor: '#f8f9fa',
    borderRadius: '12px',
    border: '2px solid transparent',
    transition: 'all 0.3s ease'
  },
  rankContainer: {
    minWidth: '60px'
  },
  rank: {
    fontSize: '18px',
    fontWeight: 'bold'
  },
  playerInfo: {
    flex: 1,
    display: 'flex',
    flexDirection: 'column',
    paddingLeft: '15px'
  },
  username: {
    fontSize: '16px',
    fontWeight: 'bold',
    color: '#333'
  },
  date: {
    fontSize: '12px',
    color: '#666'
  },
  scoreContainer: {
    minWidth: '80px',
    textAlign: 'right'
  },
  score: {
    fontSize: '18px',
    fontWeight: 'bold'
  },
  statsGrid: {
    display: 'grid',
    gridTemplateColumns: 'repeat(2, 1fr)',
    gap: '20px',
    marginBottom: '20px'
  },
  statCard: {
    padding: '20px',
    backgroundColor: '#f8f9fa',
    borderRadius: '12px',
    textAlign: 'center',
    border: '2px solid #e9ecef'
  },
  statIcon: {
    fontSize: '24px',
    marginBottom: '10px'
  },
  statValue: {
    fontSize: '24px',
    fontWeight: 'bold',
    color: '#333',
    marginBottom: '5px'
  },
  statLabel: {
    fontSize: '12px',
    color: '#666',
    textTransform: 'uppercase'
  },
  recentGamesList: {
    display: 'flex',
    flexDirection: 'column',
    gap: '10px'
  },
  recentGameItem: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: '15px',
    backgroundColor: '#f8f9fa',
    borderRadius: '12px'
  },
  gameInfo: {
    flex: 1
  },
  gameDate: {
    fontSize: '14px',
    color: '#666'
  },
  gameScore: {
    minWidth: '80px',
    textAlign: 'right'
  },
  footer: {
    marginTop: '30px',
    textAlign: 'center'
  },
  refreshButton: {
    padding: '12px 24px',
    backgroundColor: '#28a745',
    color: 'white',
    border: 'none',
    borderRadius: '8px',
    cursor: 'pointer',
    fontSize: '14px',
    fontWeight: 'bold'
  },
  // Enhanced overview styles
  sectionTitle: {
    fontSize: '24px',
    fontWeight: 'bold',
    marginBottom: '20px',
    textAlign: 'center',
    color: '#333'
  },
  statsGrid: {
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))',
    gap: '20px',
    marginBottom: '30px'
  },
  statCard: {
    background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
    borderRadius: '15px',
    padding: '20px',
    color: 'white',
    boxShadow: '0 8px 32px rgba(0,0,0,0.1)'
  },
  statTitle: {
    fontSize: '16px',
    fontWeight: 'bold',
    marginBottom: '15px',
    textAlign: 'center'
  },
  levelDisplay: {
    textAlign: 'center'
  },
  level: {
    fontSize: '24px',
    fontWeight: 'bold',
    display: 'block',
    marginBottom: '10px'
  },
  xpBar: {
    width: '100%',
    height: '8px',
    backgroundColor: 'rgba(255,255,255,0.3)',
    borderRadius: '4px',
    overflow: 'hidden',
    marginBottom: '8px'
  },
  xpFill: {
    height: '100%',
    backgroundColor: '#4CAF50',
    borderRadius: '4px',
    transition: 'width 0.3s ease'
  },
  xpText: {
    fontSize: '14px',
    opacity: 0.9
  },
  statGrid: {
    display: 'grid',
    gridTemplateColumns: '1fr 1fr',
    gap: '15px'
  },
  statItem: {
    textAlign: 'center'
  },
  statValue: {
    fontSize: '24px',
    fontWeight: 'bold',
    display: 'block'
  },
  statLabel: {
    fontSize: '12px',
    opacity: 0.8
  },
  skillGrid: {
    display: 'flex',
    flexDirection: 'column',
    gap: '12px'
  },
  skillItem: {
    display: 'flex',
    alignItems: 'center',
    gap: '12px'
  },
  skillName: {
    minWidth: '100px',
    fontSize: '14px'
  },
  skillBar: {
    flex: 1,
    height: '6px',
    backgroundColor: 'rgba(255,255,255,0.3)',
    borderRadius: '3px',
    overflow: 'hidden'
  },
  skillFill: {
    height: '100%',
    backgroundColor: '#4CAF50',
    borderRadius: '3px',
    transition: 'width 0.3s ease'
  },
  achievementSummary: {
    textAlign: 'center'
  },
  achievementCount: {
    marginBottom: '15px'
  },
  achievementNumber: {
    fontSize: '32px',
    fontWeight: 'bold'
  },
  achievementTotal: {
    fontSize: '24px',
    opacity: 0.7
  },
  achievementLabel: {
    display: 'block',
    fontSize: '12px',
    opacity: 0.8,
    marginTop: '5px'
  },
  recentAchievements: {
    display: 'flex',
    flexDirection: 'column',
    gap: '8px'
  },
  recentAchievement: {
    display: 'flex',
    alignItems: 'center',
    gap: '8px',
    fontSize: '14px'
  },
  highscoreList: {
    display: 'flex',
    flexDirection: 'column',
    gap: '10px'
  },
  mode: {
    minWidth: '100px',
    fontSize: '14px',
    color: '#666'
  },
  achievementGrid: {
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))',
    gap: '15px'
  },
  achievementCard: {
    display: 'flex',
    padding: '15px',
    borderRadius: '12px',
    border: '2px solid #e0e0e0',
    transition: 'all 0.3s ease'
  },
  achievementInfo: {
    flex: 1,
    marginLeft: '15px'
  },
  achievementIcon: {
    fontSize: '32px',
    minWidth: '50px',
    textAlign: 'center'
  },
  achievementName: {
    fontSize: '16px',
    fontWeight: 'bold',
    marginBottom: '5px',
    color: '#333'
  },
  achievementDescription: {
    fontSize: '14px',
    color: '#666',
    marginBottom: '5px'
  },
  achievementDate: {
    fontSize: '12px',
    color: '#999'
  },
  categoryGrid: {
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))',
    gap: '20px'
  },
  comingSoon: {
    textAlign: 'center',
    padding: '40px',
    backgroundColor: '#f8f9fa',
    borderRadius: '12px',
    color: '#666'
  }
};

export default HighscorePage;
