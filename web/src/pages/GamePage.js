// File: web/src/pages/GamePage.js
// Description: Solo game mode with 5 questions, 11-second timer per question

import React, { useState, useEffect, useRef } from 'react';
import extraQuestions from '../data/extraQuestions';
import soundEffects from '../utils/soundEffects';
import achievementSystem from '../utils/achievementSystem';
import overallScoreSystem from '../utils/overallScoreSystem';
import { 
  calculateScore, 
  isAnswerCorrect, 
  generateGameQuestions, 
  saveGameResult, 
  GAME_SETTINGS 
} from '../utils/gameLogic';

const GamePage = ({ onBackToMenu, gameConfig = {} }) => {
  // Extract game configuration with defaults
  const {
    mode = 'klassisch',
    categories = ['all'],
    difficulty = 'all',
    questions: totalQuestions = 5,
    timePerQuestion = 11
  } = gameConfig;

  // Game state
  const [gameState, setGameState] = useState('playing'); // 'playing', 'finished'
  const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
  const [shuffledQuestions, setShuffledQuestions] = useState([]);
  const [userAnswers, setUserAnswers] = useState([]);
  const [currentAnswer, setCurrentAnswer] = useState('');
  const [timeLeft, setTimeLeft] = useState(timePerQuestion);
  const [questionScores, setQuestionScores] = useState([]);
  const [finalScore, setFinalScore] = useState(0);
  const [notification, setNotification] = useState({ message: '', type: '' });
  const [streakCount, setStreakCount] = useState(0);
  const [maxStreak, setMaxStreak] = useState(0);

  const submitRef = useRef();
  submitRef.current = () => handleSubmitAnswer();

  // Utility function to shuffle array
  const shuffleArray = (array) => {
    const shuffled = [...array];
    for (let i = shuffled.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }
    return shuffled;
  };

  // Timer with dynamic time per question - more stable
  useEffect(() => {
    // Only start timer if we have questions loaded and game is playing
    if (gameState === 'playing' && shuffledQuestions.length > 0 && timeLeft > 0) {
      const timer = setTimeout(() => {
        const newTime = timeLeft - 1;
        setTimeLeft(newTime);
        
        // Sound effects for timer (only if enabled)
        if (newTime === 3) {
          // soundEffects.warning(); // Warning at 3 seconds
        } else if (newTime <= 3 && newTime > 0) {
          // soundEffects.tick(); // Tick for last 3 seconds
        }
      }, 1000);
      return () => clearTimeout(timer);
    } else if (timeLeft === 0 && gameState === 'playing' && shuffledQuestions.length > 0) {
      // soundEffects.timeout(); // Timeout sound
      // Time's up, submit answer via ref to avoid hook dependency on handler
      setTimeout(() => {
        if (submitRef.current) {
          submitRef.current();
        }
      }, 100);
    }
  }, [timeLeft, gameState, shuffledQuestions.length]);

  // Filter and shuffle questions based on game config - only on mount
  useEffect(() => {
    let filteredQuestions = [...extraQuestions];
    
    // Filter by categories
    if (!categories.includes('all')) {
      filteredQuestions = filteredQuestions.filter(q => categories.includes(q.category));
    }
    
    // Filter by difficulty
    if (difficulty !== 'all') {
      filteredQuestions = filteredQuestions.filter(q => q.difficulty === difficulty);
    }
    
    // Shuffle and select required number of questions
    const selectedQuestions = shuffleArray(filteredQuestions).slice(0, totalQuestions);
    setShuffledQuestions(selectedQuestions);
    
    // Reset timer when questions are loaded
    setTimeLeft(timePerQuestion);
    
    console.log(`Loaded ${selectedQuestions.length} questions:`, selectedQuestions.map(q => ({
      id: q.question_id,
      difficulty: q.difficulty,
      category: q.category,
      question: q.question_text.substring(0, 50) + '...'
    })));
    
    // Play game start sound (disabled for now)
    // setTimeout(() => soundEffects.gameStart(), 500);
  }, []); // Only run on mount to avoid reloading during gameplay

  // Load questions when component mounts
  // Notification helper
  const showNotification = (message, type) => {
    setNotification({ message, type });
    setTimeout(() => {
      setNotification({ message: '', type: '' });
    }, 3000);
  };

  const calculateQuestionScore = (userAnswer, correctAnswer) => {
    const userNum = parseFloat(userAnswer);
    const correctNum = parseFloat(correctAnswer);

    if (Number.isNaN(correctNum) || correctNum === 0 || Number.isNaN(userNum)) {
      // invalid input or no numeric comparison possible => minimal points
      return 5;
    }

    const difference = Math.abs(userNum - correctNum);
    const percentageDiff = (difference / Math.abs(correctNum)) * 100;

    // Base points scale 0..100 depending on closeness
    let base;
    if (percentageDiff === 0) {
      base = 110; // exact answer gives bonus (110 instead of 100)
    } else if (percentageDiff <= 2) {
      base = 100;
    } else if (percentageDiff <= 5) {
      base = 90;
    } else if (percentageDiff <= 10) {
      base = 75;
    } else if (percentageDiff <= 20) {
      base = 60;
    } else if (percentageDiff <= 40) {
      base = 40;
    } else if (percentageDiff <= 100) {
      base = 20;
    } else {
      base = 10;
    }

    // Additional small bonus for faster answers (more remaining time)
    const timeBonus = Math.min(Math.max(timeLeft, 0), 11) / 11; // 0..1
    const timeMultiplier = 1 + (timeBonus * 0.1); // up to +10%

    // Difficulty is applied by caller wrapper
    // Final score rounded
    const multiplier = timeMultiplier * 1.0; // difficulty will be applied by caller wrapper
    return Math.round(base * multiplier);
  };

  const handleSubmitAnswer = () => {
    const currentQuestion = shuffledQuestions[currentQuestionIndex];
    const answer = currentAnswer || '0';
    
    // Calculate base score with difficulty multiplier
    let rawScore = calculateQuestionScore(answer, currentQuestion.correct_answer);
    const diffMult = currentQuestion.difficulty === 'medium' ? 1.1 : currentQuestion.difficulty === 'hard' ? 1.25 : 1.0;
    let score = Math.round(Math.min(120, rawScore * diffMult));
    
    // Check if answer is good enough for streak (80+ points)
    const isGoodAnswer = score >= 80;
    let newStreakCount = streakCount;
    
    if (isGoodAnswer) {
      newStreakCount = streakCount + 1;
      setStreakCount(newStreakCount);
      setMaxStreak(Math.max(maxStreak, newStreakCount));
      
      // Streak bonus: +5% per consecutive correct answer
      const streakBonus = 1 + (newStreakCount - 1) * 0.05;
      score = Math.round(score * streakBonus);
    } else {
      // Reset streak on poor answer
      setStreakCount(0);
    }

    // Store answer and score
    const newUserAnswers = [...userAnswers, {
      questionId: currentQuestion.question_id,
      question: currentQuestion.question_text,
      userAnswer: answer,
      correctAnswer: currentQuestion.correct_answer,
      category: currentQuestion.category,
      difficulty: currentQuestion.difficulty,
      score: score,
      streak: newStreakCount
    }];
    setUserAnswers(newUserAnswers);

    const newScores = [...questionScores, score];
    setQuestionScores(newScores);

    // Play sound based on score (disabled for now)
    // if (score >= 80) {
    //   soundEffects.success();
    // } else if (score >= 50) {
    //   soundEffects.goodAnswer();
    // } else {
    //   soundEffects.poorAnswer();
    // }

    // Check if this was the last question
    if (currentQuestionIndex >= totalQuestions - 1) {
      // Game finished
      const totalScore = newScores.reduce((sum, s) => sum + s, 0);
      setFinalScore(totalScore);
      
      // Enhanced game statistics with streak data and answer details
      const gameData = {
        mode: mode,
        finalScore: totalScore,
        maxScore: totalQuestions * 120,
        categories: [...new Set(newUserAnswers.map(a => a.category))],
        difficulties: [...new Set(newUserAnswers.map(a => a.difficulty))],
        questionCount: totalQuestions,
        maxStreak: Math.max(maxStreak, newStreakCount),
        streakBonus: newUserAnswers.reduce((sum, a) => sum + (a.streak > 1 ? a.streak - 1 : 0), 0),
        totalTime: totalQuestions * timePerQuestion,
        answers: newUserAnswers.map((answer, idx) => ({
          ...answer,
          timeLeft: timeLeft, // Time left when answered
          questionIndex: idx
        })),
        categoryPerformance: {}
      };

      // Calculate category performance
      newUserAnswers.forEach(answer => {
        if (!gameData.categoryPerformance[answer.category]) {
          gameData.categoryPerformance[answer.category] = {
            questionsAnswered: 0,
            totalScore: 0,
            bestScore: 0
          };
        }
        const catPerf = gameData.categoryPerformance[answer.category];
        catPerf.questionsAnswered++;
        catPerf.totalScore += answer.score;
        catPerf.bestScore = Math.max(catPerf.bestScore, answer.score);
      });

      // Check achievements
      const globalStats = overallScoreSystem.getPlayerStats();
      const newAchievements = achievementSystem.checkAchievements(gameData, globalStats);
      
      // Update overall score system
      const scoreUpdate = overallScoreSystem.updateAfterGame(gameData);
      
      // Save game result with enhanced data
      const username = localStorage.getItem('username') || 'Unbekannt';
      const highscores = JSON.parse(localStorage.getItem('highscores') || '[]');
      const gameResult = {
        name: username, 
        score: totalScore, 
        date: new Date().toISOString(),
        mode: mode,
        maxStreak: Math.max(maxStreak, newStreakCount),
        categories: gameData.categories.join(', '),
        overallScore: scoreUpdate.overallScore,
        level: scoreUpdate.newLevel
      };
      highscores.push(gameResult);
      localStorage.setItem('highscores', JSON.stringify(highscores));
      
      // Create notification message with achievements and level info
      let notificationMessage = `Spiel beendet! Max Streak: ${Math.max(maxStreak, newStreakCount)}`;
      
      if (newAchievements.length > 0) {
        notificationMessage += ` | 🏆 ${newAchievements.length} neue Erfolge!`;
      }
      
      if (scoreUpdate.expGained > 0) {
        notificationMessage += ` | +${scoreUpdate.expGained} XP`;
      }
      
      if (scoreUpdate.leveledUp) {
        notificationMessage += ` | Level UP! (${scoreUpdate.newLevel})`;
      }
      
      showNotification(notificationMessage, 'success');
      setGameState('finished');
    } else {
      // Next question
      // soundEffects.newQuestion(); // Sound for next question
      setCurrentQuestionIndex(currentQuestionIndex + 1);
      setCurrentAnswer('');
      setTimeLeft(timePerQuestion);
    }
  };

  const startNewGame = () => {
    // Filter and shuffle questions based on config
    let filteredQuestions = [...extraQuestions];
    
    if (!categories.includes('all')) {
      filteredQuestions = filteredQuestions.filter(q => categories.includes(q.category));
    }
    
    if (difficulty !== 'all') {
      filteredQuestions = filteredQuestions.filter(q => q.difficulty === difficulty);
    }
    
    const selectedQuestions = shuffleArray(filteredQuestions).slice(0, totalQuestions);
    setShuffledQuestions(selectedQuestions);
    setCurrentQuestionIndex(0);
    setUserAnswers([]);
    setCurrentAnswer('');
    setQuestionScores([]);
    setFinalScore(0);
    setTimeLeft(timePerQuestion);
    setStreakCount(0);
    setMaxStreak(0);
    setGameState('playing');
  };

  const getCategoryIcon = (category) => {
    const icons = {
      geography: '🌍',
      history: '📚',
      science: '🔬',
      nature: '🌿',
      sports: '⚽',
      technology: '💻',
      music: '🎵',
      literature: '📖'
    };
    return icons[category] || '❓';
  };

  const getDifficultyColor = (difficulty) => {
    const colors = {
      easy: '#4CAF50',
      medium: '#FF9800',
      hard: '#f44336'
    };
    return colors[difficulty] || '#666';
  };

  const getDifficultyIcon = (difficulty) => {
    const icons = {
      easy: '🟢',
      medium: '🟡',
      hard: '🔴'
    };
    return icons[difficulty] || '⚪';
  };

  if (gameState === 'loading') {
    return (
      <div style={styles.container}>
        <div style={styles.loadingContainer}>
          <h2>Fragen werden geladen...</h2>
          <div style={styles.loadingSpinner}></div>
        </div>
      </div>
    );
  }

  if (gameState === 'finished') {
    return (
      <div style={styles.container}>
        <div style={styles.gameContainer}>
          <h1 style={styles.title}>Spiel beendet!</h1>

          {notification.message && (
            <div style={{ ...styles.notification, backgroundColor: notification.type === 'success' ? '#4CAF50' : '#f44336' }}>
              {notification.message}
            </div>
          )}

          <div style={styles.finalScoreContainer}>
            <h2 style={styles.finalScore}>Endergebnis: {finalScore}/{totalQuestions * 120}</h2>
            <p style={styles.scorePercentage}>
              {((finalScore / (totalQuestions * 120)) * 100).toFixed(1)}% Genauigkeit
            </p>
            {maxStreak > 1 && (
              <p style={styles.streakDisplay}>
                🔥 Beste Serie: {maxStreak} Fragen
              </p>
            )}
            <p style={styles.gameModeDisplay}>
              🎮 Modus: {mode.charAt(0).toUpperCase() + mode.slice(1)} ({totalQuestions} Fragen)
            </p>
          </div>

          <div style={styles.resultsContainer}>
            <h3>Frage-Ergebnisse:</h3>
            {userAnswers.map((answer, index) => (
              <div key={index} style={styles.resultItem}>
                <div style={styles.resultHeader}>
                  <p style={styles.resultQuestion}>Q{index + 1}: {answer.question}</p>
                  <div style={styles.resultMeta}>
                    <span style={styles.resultCategory}>
                      {getCategoryIcon(answer.category)} {answer.category}
                    </span>
                    <span style={{
                      ...styles.resultDifficulty,
                      color: getDifficultyColor(answer.difficulty)
                    }}>
                      {getDifficultyIcon(answer.difficulty)} {answer.difficulty}
                    </span>
                  </div>
                </div>
                <div style={styles.resultDetails}>
                  <span>Your answer: {answer.userAnswer}</span>
                  <span>Correct answer: {answer.correctAnswer}</span>
                  <span style={{ color: answer.score >= 60 ? '#4CAF50' : answer.score >= 40 ? '#FF9800' : '#f44336' }}>
                    Score: {answer.score}/100
                  </span>
                </div>
              </div>
            ))}
          </div>

          <div style={styles.buttonContainer}>
            <button onClick={startNewGame} style={styles.button}>
              Nochmal spielen
            </button>
            <button onClick={onBackToMenu} style={{ ...styles.button, ...styles.secondaryButton }}>
              Zurück zum Menü
            </button>
          </div>
        </div>
      </div>
    );
  }

  // Playing state - add safety checks
  const currentQuestion = shuffledQuestions[currentQuestionIndex];
  const progress = ((currentQuestionIndex + 1) / totalQuestions) * 100;

  // Show loading if questions not loaded yet
  if (!currentQuestion || shuffledQuestions.length === 0) {
    return (
      <div style={styles.container}>
        <div style={styles.gameContainer}>
          <div style={styles.loadingContainer}>
            <h2>Fragen werden geladen...</h2>
            <div style={styles.loadingSpinner}></div>
            <p>Configuring game: {mode} mode</p>
            <p>Categories: {categories.includes('all') ? 'All' : categories.join(', ')}</p>
            <p>Difficulty: {difficulty === 'all' ? 'All' : difficulty}</p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div style={styles.container}>
      <div style={styles.gameContainer}>
        <div style={styles.header}>
          {/* Game Mode and Streak Info */}
          <div style={styles.gameInfoBar}>
            <div style={styles.gameModeInfo}>
              <span style={styles.gameModeLabel}>
                🎮 {mode.charAt(0).toUpperCase() + mode.slice(1)}
              </span>
              {streakCount > 1 && (
                <span style={styles.streakInfo}>
                  🔥 Streak: {streakCount}
                </span>
              )}
            </div>
            <div style={styles.categoryInfo}>
              {!categories.includes('all') && (
                <span style={styles.categoryFilter}>
                  📂 {categories.join(', ')}
                </span>
              )}
              {difficulty !== 'all' && (
                <span style={styles.difficultyFilter}>
                  ⚡ {difficulty}
                </span>
              )}
            </div>
          </div>

          <div style={styles.progressContainer}>
            <div style={styles.progressBar}>
              <div style={{ ...styles.progressFill, width: `${progress}%` }}></div>
            </div>
            <span style={styles.progressText}>
              Question {currentQuestionIndex + 1} of {totalQuestions}
            </span>
          </div>

          <div style={styles.timerContainer}>
            <div style={{
              ...styles.timer,
              backgroundColor: timeLeft <= 3 ? '#f44336' : timeLeft <= 6 ? '#FF9800' : '#4CAF50',
              transform: timeLeft <= 3 ? 'scale(1.1)' : 'scale(1)',
              animation: timeLeft <= 3 ? 'pulse 1s infinite' : 'none',
              boxShadow: timeLeft <= 3 ? '0 0 20px rgba(244, 67, 54, 0.6)' : 
                         timeLeft <= 6 ? '0 0 15px rgba(255, 152, 0, 0.4)' : 
                         '0 0 10px rgba(76, 175, 80, 0.3)'
            }}>
              {timeLeft}s
            </div>
            <style>{`
              @keyframes pulse {
                0% { transform: scale(1.1); }
                50% { transform: scale(1.2); }
                100% { transform: scale(1.1); }
              }
            `}</style>
          </div>
        </div>

        {notification.message && (
          <div style={{ ...styles.notification, backgroundColor: notification.type === 'success' ? '#4CAF50' : '#f44336' }}>
            {notification.message}
          </div>
        )}

        <div style={styles.questionContainer}>
          <div style={styles.questionMeta}>
            <span style={styles.categoryBadge}>
              {getCategoryIcon(currentQuestion?.category)} {currentQuestion?.category?.charAt(0).toUpperCase() + currentQuestion?.category?.slice(1)}
            </span>
            <span style={{
              ...styles.difficultyBadge,
              backgroundColor: getDifficultyColor(currentQuestion?.difficulty)
            }}>
              {getDifficultyIcon(currentQuestion?.difficulty)} {currentQuestion?.difficulty?.charAt(0).toUpperCase() + currentQuestion?.difficulty?.slice(1)}
            </span>
          </div>

          <h2 style={styles.questionText}>
            {currentQuestion?.question_text}
          </h2>

          <div style={styles.answerContainer}>
            <input
              type="number"
              value={currentAnswer}
              onChange={(e) => setCurrentAnswer(e.target.value)}
              placeholder="Gib deine Schätzung ein..."
              style={styles.answerInput}
              autoFocus
              onKeyPress={(e) => {
                if (e.key === 'Enter') {
                  handleSubmitAnswer();
                }
              }}
            />

            <button
              onClick={handleSubmitAnswer}
              style={styles.submitButton}
              disabled={timeLeft === 0}
            >
              {currentQuestionIndex >= 4 ? 'Spiel beenden' : 'Nächste Frage'}
            </button>
          </div>
        </div>

        <div style={styles.scoreContainer}>
          <p>Aktuelle Punkte: {questionScores.reduce((sum, score) => sum + score, 0)}/{totalQuestions * 120}</p>
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
    backgroundColor: '#f0f2f5',
    fontFamily: 'Arial, sans-serif',
    padding: '20px'
  },
  gameContainer: {
    padding: '30px',
    borderRadius: '12px',
    boxShadow: '0 4px 20px rgba(0, 0, 0, 0.1)',
    backgroundColor: 'white',
    width: '100%',
    maxWidth: '600px',
    textAlign: 'center'
  },
  loadingContainer: {
    padding: '50px',
    textAlign: 'center'
  },
  loadingSpinner: {
    border: '4px solid #f3f3f3',
    borderTop: '4px solid #1877f2',
    borderRadius: '50%',
    width: '40px',
    height: '40px',
    animation: 'spin 1s linear infinite',
    margin: '20px auto'
  },
  header: {
    display: 'flex',
    flexDirection: 'column',
    gap: '15px',
    marginBottom: '30px'
  },
  gameInfoBar: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    background: 'linear-gradient(135deg, #f8f9ff, #e8f2ff)',
    padding: '12px 18px',
    borderRadius: '12px',
    border: '1px solid #e0e6ff'
  },
  gameModeInfo: {
    display: 'flex',
    gap: '15px',
    alignItems: 'center'
  },
  gameModeLabel: {
    fontSize: '14px',
    fontWeight: 'bold',
    color: '#1877f2',
    background: 'white',
    padding: '6px 12px',
    borderRadius: '20px',
    border: '1px solid #1877f2'
  },
  streakInfo: {
    fontSize: '14px',
    fontWeight: 'bold',
    color: '#ff6b35',
    background: 'white',
    padding: '6px 12px',
    borderRadius: '20px',
    border: '1px solid #ff6b35'
  },
  categoryInfo: {
    display: 'flex',
    gap: '10px',
    alignItems: 'center'
  },
  categoryFilter: {
    fontSize: '12px',
    color: '#666',
    background: 'white',
    padding: '4px 8px',
    borderRadius: '15px',
    border: '1px solid #ddd'
  },
  difficultyFilter: {
    fontSize: '12px',
    color: '#666',
    background: 'white',
    padding: '4px 8px',
    borderRadius: '15px',
    border: '1px solid #ddd'
  },
  progressContainer: {
    flex: 1,
    marginRight: '20px'
  },
  progressBar: {
    width: '100%',
    height: '8px',
    backgroundColor: '#e0e0e0',
    borderRadius: '4px',
    overflow: 'hidden'
  },
  progressFill: {
    height: '100%',
    backgroundColor: '#1877f2',
    transition: 'width 0.3s ease'
  },
  progressText: {
    fontSize: '14px',
    color: '#666',
    marginTop: '5px',
    display: 'block'
  },
  timerContainer: {
    display: 'flex',
    alignItems: 'center'
  },
  timer: {
    padding: '10px 15px',
    borderRadius: '50%',
    color: 'white',
    fontWeight: 'bold',
    fontSize: '18px',
    minWidth: '50px',
    transition: 'background-color 0.3s ease'
  },
  questionContainer: {
    marginBottom: '30px'
  },
  questionMeta: {
    display: 'flex',
    justifyContent: 'center',
    gap: '15px',
    marginBottom: '20px'
  },
  categoryBadge: {
    padding: '8px 16px',
    backgroundColor: '#e3f2fd',
    color: '#1976d2',
    borderRadius: '20px',
    fontSize: '14px',
    fontWeight: 'bold',
    border: '2px solid #bbdefb'
  },
  difficultyBadge: {
    padding: '8px 16px',
    color: 'white',
    borderRadius: '20px',
    fontSize: '14px',
    fontWeight: 'bold'
  },
  questionText: {
    fontSize: '24px',
    color: '#1c1e21',
    marginBottom: '30px',
    lineHeight: '1.4'
  },
  answerContainer: {
    display: 'flex',
    flexDirection: 'column',
    gap: '15px',
    alignItems: 'center'
  },
  answerInput: {
    padding: '15px',
    fontSize: '18px',
    border: '2px solid #dddfe2',
    borderRadius: '8px',
    width: '100%',
    maxWidth: '300px',
    textAlign: 'center'
  },
  submitButton: {
    padding: '15px 30px',
    backgroundColor: '#1877f2',
    color: 'white',
    border: 'none',
    borderRadius: '8px',
    fontSize: '16px',
    fontWeight: 'bold',
    cursor: 'pointer',
    transition: 'background-color 0.3s ease'
  },
  scoreContainer: {
    marginTop: '20px',
    padding: '15px',
    backgroundColor: '#f8f9fa',
    borderRadius: '8px',
    fontSize: '16px',
    fontWeight: 'bold'
  },
  title: {
    fontSize: '32px',
    color: '#1c1e21',
    marginBottom: '20px'
  },
  finalScoreContainer: {
    marginBottom: '30px',
    padding: '20px',
    backgroundColor: '#f8f9fa',
    borderRadius: '12px'
  },
  finalScore: {
    fontSize: '28px',
    color: '#1877f2',
    margin: '10px 0'
  },
  scorePercentage: {
    fontSize: '18px',
    color: '#666'
  },
  streakDisplay: {
    fontSize: '16px',
    color: '#ff6b35',
    fontWeight: 'bold',
    margin: '10px 0'
  },
  gameModeDisplay: {
    fontSize: '16px',
    color: '#1877f2',
    fontWeight: 'bold',
    margin: '10px 0'
  },
  resultsContainer: {
    marginBottom: '30px',
    textAlign: 'left'
  },
  resultItem: {
    marginBottom: '15px',
    padding: '15px',
    border: '1px solid #e0e0e0',
    borderRadius: '8px'
  },
  resultHeader: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: '10px'
  },
  resultQuestion: {
    fontWeight: 'bold',
    marginBottom: '8px',
    flex: 1
  },
  resultMeta: {
    display: 'flex',
    gap: '10px',
    flexDirection: 'column',
    alignItems: 'flex-end'
  },
  resultCategory: {
    fontSize: '12px',
    color: '#666',
    backgroundColor: '#f5f5f5',
    padding: '4px 8px',
    borderRadius: '12px'
  },
  resultDifficulty: {
    fontSize: '12px',
    fontWeight: 'bold',
    padding: '4px 8px',
    borderRadius: '12px',
    backgroundColor: '#f5f5f5'
  },
  resultDetails: {
    display: 'flex',
    justifyContent: 'space-between',
    fontSize: '14px',
    color: '#666'
  },
  buttonContainer: {
    display: 'flex',
    gap: '15px',
    justifyContent: 'center'
  },
  button: {
    padding: '15px 25px',
    backgroundColor: '#1877f2',
    color: 'white',
    border: 'none',
    borderRadius: '8px',
    fontSize: '16px',
    fontWeight: 'bold',
    cursor: 'pointer'
  },
  secondaryButton: {
    backgroundColor: '#42b883'
  },
  notification: {
    padding: '12px',
    color: 'white',
    borderRadius: '8px',
    marginBottom: '20px',
    fontSize: '14px'
  }
};

export default GamePage;
