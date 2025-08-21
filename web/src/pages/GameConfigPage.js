// File: web/src/pages/GameConfigPage.js
// Description: Game configuration page to select category and difficulty

import React, { useState, useEffect } from 'react';
import axios from 'axios';

const API_URL = 'http://localhost:3011/api';

const GameConfigPage = ({ onStartGame, onBackToMenu }) => {
  const [categories, setCategories] = useState({});
  const [difficulties, setDifficulties] = useState([]);
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [selectedDifficulty, setSelectedDifficulty] = useState('all');
  const [loading, setLoading] = useState(true);
  const [notification, setNotification] = useState({ message: '', type: '' });

  useEffect(() => {
    loadGameOptions();
  }, []);

  const showNotification = (message, type) => {
    setNotification({ message, type });
    setTimeout(() => {
      setNotification({ message: '', type: '' });
    }, 3000);
  };

  const loadGameOptions = async () => {
    try {
      const token = localStorage.getItem('token');
      if (!token) {
        showNotification('No authentication token found.', 'error');
        return;
      }

      setLoading(true);

      // Load categories and difficulties in parallel
      const [categoriesResponse, difficultiesResponse] = await Promise.all([
        axios.get(`${API_URL}/game/categories`, {
          headers: { 'x-auth-token': token }
        }),
        axios.get(`${API_URL}/game/difficulties`, {
          headers: { 'x-auth-token': token }
        })
      ]);

      setCategories(categoriesResponse.data);
      setDifficulties(difficultiesResponse.data);
      setLoading(false);

    } catch (error) {
      console.error('Error loading game options:', error);
      showNotification(error.response?.data?.message || 'Failed to load game options.', 'error');
      setLoading(false);
    }
  };

  const handleStartGame = () => {
    const config = {
      category: selectedCategory,
      difficulty: selectedDifficulty
    };
    onStartGame(config);
  };

  const getCategoryIcon = (category) => {
    const icons = {
      geography: '🌍',
      history: '📚',
      science: '🔬',
      nature: '🌿',
      sports: '⚽',
      technology: '💻',
      music: '🎵'
    };
    return icons[category] || '❓';
  };

  const getDifficultyIcon = (difficulty) => {
    const icons = {
      easy: '🟢',
      medium: '🟡',
      hard: '🔴'
    };
    return icons[difficulty] || '⚪';
  };

  const getDifficultyColor = (difficulty) => {
    const colors = {
      easy: '#4CAF50',
      medium: '#FF9800',
      hard: '#f44336'
    };
    return colors[difficulty] || '#666';
  };

  const getEstimatedQuestions = () => {
    if (selectedCategory === 'all' && selectedDifficulty === 'all') {
      return Object.values(categories).reduce((total, cat) => total + cat.total, 0);
    }

    if (selectedCategory === 'all') {
      return difficulties.find(d => d.difficulty === selectedDifficulty)?.total_questions || 0;
    }

    if (selectedDifficulty === 'all') {
      return categories[selectedCategory]?.total || 0;
    }

    return categories[selectedCategory]?.difficulties[selectedDifficulty] || 0;
  };

  if (loading) {
    return (
      <div style={styles.container}>
        <div style={styles.loadingContainer}>
          <h2>Loading game options...</h2>
          <div style={styles.loadingSpinner}></div>
        </div>
      </div>
    );
  }

  return (
    <div style={styles.container}>
      <div style={styles.configContainer}>
        {/* Header */}
        <div style={styles.header}>
          <button onClick={onBackToMenu} style={styles.backButton}>
            ← Back to Menu
          </button>
          <h1 style={styles.title}>🎯 Configure Game</h1>
        </div>

        {/* Notification */}
        {notification.message && (
          <div style={{ ...styles.notification, backgroundColor: notification.type === 'success' ? '#4CAF50' : '#f44336' }}>
            {notification.message}
          </div>
        )}

        {/* Configuration Section */}
        <div style={styles.configSection}>
          {/* Category Selection */}
          <div style={styles.filterSection}>
            <h3 style={styles.sectionTitle}>📂 Select Category</h3>
            <div style={styles.optionsGrid}>
              <label style={styles.optionCard}>
                <input
                  type="radio"
                  name="category"
                  value="all"
                  checked={selectedCategory === 'all'}
                  onChange={(e) => setSelectedCategory(e.target.value)}
                  style={styles.hiddenRadio}
                />
                <div style={{
                  ...styles.optionContent,
                  ...(selectedCategory === 'all' ? styles.selectedOption : {})
                }}>
                  <div style={styles.optionIcon}>🎲</div>
                  <div style={styles.optionText}>
                    <strong>All Categories</strong>
                    <small>Mixed questions</small>
                  </div>
                </div>
              </label>

              {Object.entries(categories).map(([category, data]) => (
                <label key={category} style={styles.optionCard}>
                  <input
                    type="radio"
                    name="category"
                    value={category}
                    checked={selectedCategory === category}
                    onChange={(e) => setSelectedCategory(e.target.value)}
                    style={styles.hiddenRadio}
                  />
                  <div style={{
                    ...styles.optionContent,
                    ...(selectedCategory === category ? styles.selectedOption : {})
                  }}>
                    <div style={styles.optionIcon}>{getCategoryIcon(category)}</div>
                    <div style={styles.optionText}>
                      <strong>{category.charAt(0).toUpperCase() + category.slice(1)}</strong>
                      <small>{data.total} questions</small>
                    </div>
                  </div>
                </label>
              ))}
            </div>
          </div>

          {/* Difficulty Selection */}
          <div style={styles.filterSection}>
            <h3 style={styles.sectionTitle}>⚡ Select Difficulty</h3>
            <div style={styles.optionsGrid}>
              <label style={styles.optionCard}>
                <input
                  type="radio"
                  name="difficulty"
                  value="all"
                  checked={selectedDifficulty === 'all'}
                  onChange={(e) => setSelectedDifficulty(e.target.value)}
                  style={styles.hiddenRadio}
                />
                <div style={{
                  ...styles.optionContent,
                  ...(selectedDifficulty === 'all' ? styles.selectedOption : {})
                }}>
                  <div style={styles.optionIcon}>🎭</div>
                  <div style={styles.optionText}>
                    <strong>All Difficulties</strong>
                    <small>Mixed levels</small>
                  </div>
                </div>
              </label>

              {difficulties.map((diff) => (
                <label key={diff.difficulty} style={styles.optionCard}>
                  <input
                    type="radio"
                    name="difficulty"
                    value={diff.difficulty}
                    checked={selectedDifficulty === diff.difficulty}
                    onChange={(e) => setSelectedDifficulty(e.target.value)}
                    style={styles.hiddenRadio}
                  />
                  <div style={{
                    ...styles.optionContent,
                    ...(selectedDifficulty === diff.difficulty ? styles.selectedOption : {})
                  }}>
                    <div style={styles.optionIcon}>{getDifficultyIcon(diff.difficulty)}</div>
                    <div style={styles.optionText}>
                      <strong style={{ color: getDifficultyColor(diff.difficulty) }}>
                        {diff.difficulty.charAt(0).toUpperCase() + diff.difficulty.slice(1)}
                      </strong>
                      <small>{diff.total_questions} questions</small>
                    </div>
                  </div>
                </label>
              ))}
            </div>
          </div>

          {/* Summary */}
          <div style={styles.summarySection}>
            <h3 style={styles.sectionTitle}>📊 Game Summary</h3>
            <div style={styles.summaryCard}>
              <div style={styles.summaryItem}>
                <span style={styles.summaryLabel}>Category:</span>
                <span style={styles.summaryValue}>
                  {getCategoryIcon(selectedCategory)} {selectedCategory === 'all' ? 'All Categories' : selectedCategory.charAt(0).toUpperCase() + selectedCategory.slice(1)}
                </span>
              </div>
              <div style={styles.summaryItem}>
                <span style={styles.summaryLabel}>Difficulty:</span>
                <span style={styles.summaryValue}>
                  {getDifficultyIcon(selectedDifficulty)} {selectedDifficulty === 'all' ? 'All Difficulties' : selectedDifficulty.charAt(0).toUpperCase() + selectedDifficulty.slice(1)}
                </span>
              </div>
              <div style={styles.summaryItem}>
                <span style={styles.summaryLabel}>Available Questions:</span>
                <span style={styles.summaryValue}>{getEstimatedQuestions()}</span>
              </div>
            </div>
          </div>

          {/* Start Button */}
          <div style={styles.buttonContainer}>
            <button
              onClick={handleStartGame}
              style={styles.startButton}
              disabled={getEstimatedQuestions() < 5}
            >
              🚀 Start Solo Game
            </button>
            {getEstimatedQuestions() < 5 && (
              <p style={styles.warningText}>
                ⚠️ Not enough questions available with the selected filters (minimum 5 required)
              </p>
            )}
          </div>
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
  configContainer: {
    padding: '30px',
    borderRadius: '20px',
    boxShadow: '0 20px 40px rgba(0, 0, 0, 0.1)',
    backgroundColor: 'rgba(255, 255, 255, 0.95)',
    backdropFilter: 'blur(10px)',
    width: '100%',
    maxWidth: '800px'
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
  configSection: {
    display: 'flex',
    flexDirection: 'column',
    gap: '30px'
  },
  filterSection: {
    display: 'flex',
    flexDirection: 'column',
    gap: '15px'
  },
  sectionTitle: {
    fontSize: '20px',
    fontWeight: 'bold',
    color: '#333',
    margin: '0 0 15px 0'
  },
  optionsGrid: {
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
    gap: '15px'
  },
  optionCard: {
    cursor: 'pointer',
    borderRadius: '12px',
    transition: 'all 0.3s ease'
  },
  hiddenRadio: {
    display: 'none'
  },
  optionContent: {
    display: 'flex',
    alignItems: 'center',
    padding: '15px',
    backgroundColor: '#f8f9fa',
    borderRadius: '12px',
    border: '2px solid #e9ecef',
    transition: 'all 0.3s ease'
  },
  selectedOption: {
    backgroundColor: '#e3f2fd',
    borderColor: '#667eea',
    boxShadow: '0 4px 12px rgba(102, 126, 234, 0.3)'
  },
  optionIcon: {
    fontSize: '24px',
    marginRight: '12px',
    minWidth: '30px'
  },
  optionText: {
    display: 'flex',
    flexDirection: 'column',
    gap: '2px'
  },
  summarySection: {
    display: 'flex',
    flexDirection: 'column',
    gap: '15px'
  },
  summaryCard: {
    padding: '20px',
    backgroundColor: '#f8f9fa',
    borderRadius: '12px',
    border: '2px solid #e9ecef'
  },
  summaryItem: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: '8px 0',
    borderBottom: '1px solid #e9ecef'
  },
  summaryLabel: {
    fontWeight: 'bold',
    color: '#666'
  },
  summaryValue: {
    color: '#333',
    fontWeight: 'bold'
  },
  buttonContainer: {
    textAlign: 'center'
  },
  startButton: {
    padding: '15px 40px',
    backgroundColor: '#4CAF50',
    color: 'white',
    border: 'none',
    borderRadius: '12px',
    fontSize: '18px',
    fontWeight: 'bold',
    cursor: 'pointer',
    transition: 'all 0.3s ease',
    boxShadow: '0 4px 12px rgba(76, 175, 80, 0.3)'
  },
  warningText: {
    color: '#f44336',
    fontSize: '14px',
    marginTop: '10px',
    fontStyle: 'italic'
  }
};

export default GameConfigPage;
