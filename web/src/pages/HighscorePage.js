// File: web/src/pages/HighscorePage.js
// Description: Highscore page showing global rankings and personal stats

import React, { useState, useEffect } from 'react';

const HighscorePage = ({ onBackToMenu }) => {
  const [highscores, setHighscores] = useState([]);
  const [loading, setLoading] = useState(true);
  const [notification, setNotification] = useState('');

  useEffect(() => {
    loadHighscores();
  }, []);

  const loadHighscores = () => {
    setLoading(true);
    try {
      const scores = JSON.parse(localStorage.getItem('highscores') || '[]');
      // Sortiere nach Score absteigend
      scores.sort((a, b) => b.score - a.score);
      setHighscores(scores);
      setLoading(false);
    } catch (e) {
      setNotification('Fehler beim Laden der Highscores.');
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
        <div style={styles.highscoreList}>
          {highscores.length === 0 ? (
            <p>Keine Highscores vorhanden. Spiele ein Quiz!</p>
          ) : (
            highscores.slice(0, 10).map((entry, idx) => (
              <div key={idx} style={styles.highscoreItem}>
                <span style={styles.rank}>{idx + 1}.</span>
                <span style={styles.username}>{entry.name}</span>
                <span style={{ ...styles.score, color: getScoreColor(entry.score) }}>{entry.score}</span>
                <span style={styles.date}>{formatDate(entry.date)}</span>
              </div>
            ))
          )}
        </div>
        <div style={styles.footer}>
          <button onClick={loadHighscores} style={styles.refreshButton}>
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
    background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
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
    borderTop: '4px solid #667eea',
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
    background: 'linear-gradient(45deg, #667eea, #764ba2)',
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
    backgroundColor: '#667eea',
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
  }
};

export default HighscorePage;
