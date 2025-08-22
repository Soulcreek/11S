// File: web/src/pages/GamePage.js
// Description: Solo game mode with 5 questions, 11-second timer per question

import React, { useState, useEffect, useRef } from 'react';
import extraQuestions from '../data/extraQuestions';
import { gameStats, userManager } from '../utils/localStorage';

const GamePage = ({ onBackToMenu, gameConfig = {} }) => {
  // Game state
  // Shuffle helper
  const shuffleArray = (arr) => {
    for (let i = arr.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
  };
  const [gameState, setGameState] = useState('playing'); // 'playing', 'finished'
  const [questions] = useState(extraQuestions);
  const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
  const [shuffledQuestions, setShuffledQuestions] = useState(() => shuffleArray([...extraQuestions.slice(0, 5)]));
  const [userAnswers, setUserAnswers] = useState([]);
  const [currentAnswer, setCurrentAnswer] = useState('');
  const [timeLeft, setTimeLeft] = useState(11);
  const [scores, setScores] = useState([]);
  const [finalScore, setFinalScore] = useState(0);
  const [notification, setNotification] = useState({ message: '', type: '' });

  const submitRef = useRef();
  submitRef.current = () => handleSubmitAnswer();

  // Timer
  useEffect(() => {
    if (gameState === 'playing' && timeLeft > 0) {
      const timer = setTimeout(() => {
        setTimeLeft(timeLeft - 1);
      }, 1000);
      return () => clearTimeout(timer);
    } else if (timeLeft === 0 && gameState === 'playing') {
      // Time's up, submit answer via ref to avoid hook dependency on handler
      submitRef.current();
    }
  }, [timeLeft, gameState]);

  // ensure shuffled on mount and select random 5 questions
  useEffect(() => {
    const allQuestions = [...extraQuestions];
    const selectedQuestions = shuffleArray(allQuestions).slice(0, 5);
    setShuffledQuestions(selectedQuestions);
  }, []);

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
    const currentQuestion = questions[currentQuestionIndex];
    const answer = currentAnswer || '0';
    // calculate with difficulty multiplier
    let rawScore = calculateQuestionScore(answer, currentQuestion.correct_answer);
    const diffMult = currentQuestion.difficulty === 'medium' ? 1.1 : currentQuestion.difficulty === 'hard' ? 1.25 : 1.0;
    let score = Math.round(Math.min(120, rawScore * diffMult));

    // Store answer and score
    const newUserAnswers = [...userAnswers, {
      questionId: currentQuestion.question_id,
      question: currentQuestion.question_text,
      userAnswer: answer,
      correctAnswer: currentQuestion.correct_answer,
      category: currentQuestion.category,
      difficulty: currentQuestion.difficulty,
      score: score
    }];
    setUserAnswers(newUserAnswers);

    const newScores = [...scores, score];
    setScores(newScores);

    // Check if this was the last question (now always 5 questions)
    if (currentQuestionIndex >= 4) {
      // Game finished
      const totalScore = newScores.reduce((sum, s) => sum + s, 0);
      setFinalScore(totalScore);
      
      // Save enhanced game statistics
      const gameData = {
        finalScore: totalScore,
        maxScore: 5 * 120,
        categories: [...new Set(newUserAnswers.map(a => a.category))],
        difficulties: [...new Set(newUserAnswers.map(a => a.difficulty))],
        questionCount: 5,
        totalTime: 55, // 5 questions * 11 seconds
        timeBonus: newScores.reduce((sum, score, index) => sum + (11 - index), 0),
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

      // Save using enhanced gameStats system
      const saveResult = gameStats.saveGameResult(gameData);
      if (saveResult.success) {
        showNotification('Score saved with stats!', 'success');
      } else {
        // Fallback to simple save
        const username = localStorage.getItem('username') || 'Unbekannt';
        const highscores = JSON.parse(localStorage.getItem('highscores') || '[]');
        highscores.push({ name: username, score: totalScore, date: new Date().toISOString() });
        localStorage.setItem('highscores', JSON.stringify(highscores));
        showNotification('Score saved!', 'success');
      }
      
      setGameState('finished');
    } else {
      // Next question
      setCurrentQuestionIndex(currentQuestionIndex + 1);
      setCurrentAnswer('');
      setTimeLeft(11);
    }
  };

  const startNewGame = () => {
    const allQuestions = [...extraQuestions];
    const selectedQuestions = shuffleArray(allQuestions).slice(0, 5);
    setShuffledQuestions(selectedQuestions);
    setCurrentQuestionIndex(0);
    setUserAnswers([]);
    setCurrentAnswer('');
    setScores([]);
    setFinalScore(0);
    setTimeLeft(11);
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
          <h2>Loading questions...</h2>
          <div style={styles.loadingSpinner}></div>
        </div>
      </div>
    );
  }

  if (gameState === 'finished') {
    return (
      <div style={styles.container}>
        <div style={styles.gameContainer}>
          <h1 style={styles.title}>Game Finished!</h1>

          {notification.message && (
            <div style={{ ...styles.notification, backgroundColor: notification.type === 'success' ? '#4CAF50' : '#f44336' }}>
              {notification.message}
            </div>
          )}

          <div style={styles.finalScoreContainer}>
            <h2 style={styles.finalScore}>Final Score: {finalScore}/{5 * 120}</h2>
            <p style={styles.scorePercentage}>
              {((finalScore / (5 * 120)) * 100).toFixed(1)}% Accuracy
            </p>
          </div>

          <div style={styles.resultsContainer}>
            <h3>Question Results:</h3>
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
              Play Again
            </button>
            <button onClick={onBackToMenu} style={{ ...styles.button, ...styles.secondaryButton }}>
              Back to Menu
            </button>
          </div>
        </div>
      </div>
    );
  }

  // Playing state
  const currentQuestion = shuffledQuestions[currentQuestionIndex];
  const progress = ((currentQuestionIndex + 1) / 5) * 100;

  return (
    <div style={styles.container}>
      <div style={styles.gameContainer}>
        <div style={styles.header}>
          <div style={styles.progressContainer}>
            <div style={styles.progressBar}>
              <div style={{ ...styles.progressFill, width: `${progress}%` }}></div>
            </div>
            <span style={styles.progressText}>
              Question {currentQuestionIndex + 1} of 5
            </span>
          </div>

          <div style={styles.timerContainer}>
            <div style={{
              ...styles.timer,
              backgroundColor: timeLeft <= 3 ? '#f44336' : timeLeft <= 6 ? '#FF9800' : '#4CAF50'
            }}>
              {timeLeft}s
            </div>
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
              placeholder="Enter your estimate..."
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
              {currentQuestionIndex >= 4 ? 'Finish Game' : 'Next Question'}
            </button>
          </div>
        </div>

        <div style={styles.scoreContainer}>
          <p>Current Score: {scores.reduce((sum, score) => sum + score, 0)}/{5 * 120}</p>
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
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: '30px'
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
