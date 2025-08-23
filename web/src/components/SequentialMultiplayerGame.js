// File: web/src/components/SequentialMultiplayerGame.js
// Description: Sequential multiplayer game where players take turns answering questions

import React, { useState, useEffect } from 'react';

const SequentialMultiplayerGame = ({ gameConfig, players, onBackToSetup, onBackToMenu }) => {
  const [questions, setQuestions] = useState([]);
  const [currentPlayerIndex, setCurrentPlayerIndex] = useState(0);
  const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
  const [playerScores, setPlayerScores] = useState({});
  const [gamePhase, setGamePhase] = useState('loading'); // 'loading', 'question', 'results', 'final'
  const [timeLeft, setTimeLeft] = useState(gameConfig.timePerQuestion);
  const [selectedAnswer, setSelectedAnswer] = useState(null);
  const [isAnswerRevealed, setIsAnswerRevealed] = useState(false);
  const [currentQuestion, setCurrentQuestion] = useState(null);

  // Initialize game
  useEffect(() => {
    initializeGame();
  }, []);

  // Timer effect
  useEffect(() => {
    if (gamePhase === 'question' && timeLeft > 0 && !isAnswerRevealed) {
      const timer = setTimeout(() => setTimeLeft(timeLeft - 1), 1000);
      return () => clearTimeout(timer);
    } else if (timeLeft === 0 && !isAnswerRevealed) {
      handleTimeUp();
    }
  }, [timeLeft, gamePhase, isAnswerRevealed]);

  const initializeGame = async () => {
    try {
      // Initialize player scores
      const scores = {};
      players.forEach(player => {
        scores[player] = 0;
      });
      setPlayerScores(scores);

      // Load questions (simulate API call)
      const totalQuestions = players.length * gameConfig.questionsPerPlayer;
      const loadedQuestions = await loadQuestions(totalQuestions);
      setQuestions(loadedQuestions);
      
      if (loadedQuestions.length > 0) {
        setCurrentQuestion(loadedQuestions[0]);
        setGamePhase('question');
        setTimeLeft(gameConfig.timePerQuestion);
      }
    } catch (error) {
      console.error('Error initializing game:', error);
    }
  };

  const loadQuestions = async (count) => {
    // Simulated question loading - in real app this would be API call
    const sampleQuestions = [
      {
        question: "Was ist die Hauptstadt von Deutschland?",
        options: ["Berlin", "München", "Hamburg", "Köln"],
        correctAnswer: 0,
        category: "geography"
      },
      {
        question: "Welcher Planet ist der größte in unserem Sonnensystem?",
        options: ["Saturn", "Jupiter", "Neptun", "Uranus"],
        correctAnswer: 1,
        category: "science"
      },
      {
        question: "In welchem Jahr fiel die Berliner Mauer?",
        options: ["1987", "1988", "1989", "1990"],
        correctAnswer: 2,
        category: "history"
      },
      {
        question: "Wie viele Sekunden hat eine Minute?",
        options: ["50", "60", "70", "80"],
        correctAnswer: 1,
        category: "science"
      },
      {
        question: "Welcher Ozean ist der größte?",
        options: ["Atlantik", "Indischer Ozean", "Pazifik", "Arktischer Ozean"],
        correctAnswer: 2,
        category: "geography"
      },
      // Add more questions as needed
    ];

    // Shuffle and return requested number of questions
    const shuffled = [...sampleQuestions].sort(() => 0.5 - Math.random());
    return shuffled.slice(0, count);
  };

  const handleAnswerSelect = (answerIndex) => {
    if (isAnswerRevealed) return;
    
    setSelectedAnswer(answerIndex);
    setIsAnswerRevealed(true);
    
    // Check if answer is correct and update score
    const currentPlayer = players[currentPlayerIndex];
    const isCorrect = answerIndex === currentQuestion.correctAnswer;
    
    if (isCorrect) {
      const timeBonus = Math.max(1, Math.floor(timeLeft / 2)); // Bonus points for speed
      setPlayerScores(prev => ({
        ...prev,
        [currentPlayer]: prev[currentPlayer] + 10 + timeBonus
      }));
    }

    // Move to next question after delay
    setTimeout(() => {
      moveToNextQuestion();
    }, 2000);
  };

  const handleTimeUp = () => {
    setIsAnswerRevealed(true);
    setSelectedAnswer(-1); // Indicate time up
    
    setTimeout(() => {
      moveToNextQuestion();
    }, 2000);
  };

  const moveToNextQuestion = () => {
    const nextQuestionIndex = currentQuestionIndex + 1;
    const nextPlayerIndex = (currentPlayerIndex + 1) % players.length;
    
    // Check if game is complete
    const questionsPerPlayer = gameConfig.questionsPerPlayer;
    const totalQuestions = players.length * questionsPerPlayer;
    
    if (nextQuestionIndex >= totalQuestions) {
      setGamePhase('final');
      return;
    }
    
    // Move to next question
    setCurrentQuestionIndex(nextQuestionIndex);
    setCurrentPlayerIndex(nextPlayerIndex);
    setCurrentQuestion(questions[nextQuestionIndex]);
    setSelectedAnswer(null);
    setIsAnswerRevealed(false);
    setTimeLeft(gameConfig.timePerQuestion);
    setGamePhase('question');
  };

  const getCurrentPlayer = () => players[currentPlayerIndex];
  const getQuestionNumber = () => Math.floor(currentQuestionIndex / players.length) + 1;

  const renderQuestion = () => (
    <div style={styles.questionContainer}>
      <div style={styles.gameHeader}>
        <div style={styles.playerInfo}>
          <div style={styles.currentPlayer}>
            🎯 {getCurrentPlayer()}
          </div>
          <div style={styles.questionNumber}>
            Frage {getQuestionNumber()} von {gameConfig.questionsPerPlayer}
          </div>
        </div>
        <div style={styles.timer}>
          ⏱️ {timeLeft}s
        </div>
      </div>

      <div style={styles.questionText}>
        {currentQuestion?.question}
      </div>

      <div style={styles.optionsGrid}>
        {currentQuestion?.options.map((option, index) => (
          <button
            key={index}
            onClick={() => handleAnswerSelect(index)}
            style={{
              ...styles.optionButton,
              ...(isAnswerRevealed && index === currentQuestion.correctAnswer
                ? styles.correctAnswer
                : {}),
              ...(isAnswerRevealed && selectedAnswer === index && index !== currentQuestion.correctAnswer
                ? styles.wrongAnswer
                : {}),
              ...(selectedAnswer === index && !isAnswerRevealed
                ? styles.selectedAnswer
                : {})
            }}
            disabled={isAnswerRevealed}
          >
            {String.fromCharCode(65 + index)} {option}
          </button>
        ))}
      </div>

      {isAnswerRevealed && (
        <div style={styles.answerFeedback}>
          {selectedAnswer === currentQuestion.correctAnswer ? (
            <div style={styles.correctFeedback}>✅ Richtig! +{10 + Math.max(1, Math.floor(timeLeft / 2))} Punkte</div>
          ) : selectedAnswer === -1 ? (
            <div style={styles.wrongFeedback}>⏰ Zeit abgelaufen!</div>
          ) : (
      <div style={styles.wrongFeedback}>Falsch! Die richtige Antwort war {String.fromCharCode(65 + currentQuestion.correctAnswer)}</div>
          )}
        </div>
      )}
    </div>
  );

  const renderFinalResults = () => {
    const sortedPlayers = players
      .map(player => ({ name: player, score: playerScores[player] }))
      .sort((a, b) => b.score - a.score);

    return (
      <div style={styles.resultsContainer}>
        <h1 style={styles.resultsTitle}>🏆 Endergebnis</h1>
        
        <div style={styles.podium}>
          {sortedPlayers.map((player, index) => (
            <div key={player.name} style={{
              ...styles.podiumPlace,
              ...(index === 0 ? styles.firstPlace : {}),
              ...(index === 1 ? styles.secondPlace : {}),
              ...(index === 2 ? styles.thirdPlace : {})
            }}>
              <div style={styles.podiumRank}>
                {index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : `${index + 1}.`}
              </div>
              <div style={styles.podiumName}>{player.name}</div>
              <div style={styles.podiumScore}>{player.score} Punkte</div>
            </div>
          ))}
        </div>

        <div style={styles.buttonsContainer}>
          <button onClick={onBackToSetup} style={styles.backButton}>
            🔄 Neues Spiel
          </button>
          <button onClick={onBackToMenu} style={styles.menuButton}>
            🏠 Hauptmenü
          </button>
        </div>
      </div>
    );
  };

  if (gamePhase === 'loading') {
    return (
      <div style={styles.loadingContainer}>
        <div style={styles.loadingText}>Spiel wird vorbereitet...</div>
      </div>
    );
  }

  return (
    <div style={styles.container}>
      {gamePhase === 'question' && renderQuestion()}
      {gamePhase === 'final' && renderFinalResults()}
    </div>
  );
};

const styles = {
  container: {
    display: 'flex',
    justifyContent: 'center',
    alignItems: 'center',
    minHeight: '100vh',
    background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    padding: '20px'
  },
  loadingContainer: {
    display: 'flex',
    justifyContent: 'center',
    alignItems: 'center',
    minHeight: '100vh',
    background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
  },
  loadingText: {
    fontSize: '24px',
    color: 'white',
    textAlign: 'center'
  },
  questionContainer: {
    background: 'rgba(255, 255, 255, 0.95)',
    borderRadius: '20px',
    padding: '30px',
    boxShadow: '0 20px 60px rgba(0,0,0,0.2)',
    backdropFilter: 'blur(10px)',
    width: '100%',
    maxWidth: '800px'
  },
  gameHeader: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: '30px',
    padding: '20px',
    background: '#f8f9fa',
    borderRadius: '15px'
  },
  playerInfo: {
    display: 'flex',
    flexDirection: 'column',
    gap: '5px'
  },
  currentPlayer: {
    fontSize: '24px',
    fontWeight: 'bold',
    color: '#333'
  },
  questionNumber: {
    fontSize: '14px',
    color: '#666'
  },
  timer: {
    fontSize: '32px',
    fontWeight: 'bold',
    color: '#e74c3c',
    padding: '10px 20px',
    background: 'rgba(231, 76, 60, 0.1)',
    borderRadius: '10px'
  },
  questionText: {
    fontSize: '24px',
    fontWeight: 'bold',
    textAlign: 'center',
    marginBottom: '40px',
    color: '#333',
    lineHeight: '1.4'
  },
  optionsGrid: {
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))',
    gap: '15px',
    marginBottom: '30px'
  },
  optionButton: {
    padding: '20px',
    fontSize: '18px',
    fontWeight: 'bold',
    border: '3px solid #ddd',
    borderRadius: '15px',
    background: 'white',
    cursor: 'pointer',
    transition: 'all 0.2s ease',
    textAlign: 'left'
  },
  selectedAnswer: {
    border: '3px solid #3498db',
    background: '#e8f4fd'
  },
  correctAnswer: {
    border: '3px solid #27ae60',
    background: '#d5f4e6',
    color: '#27ae60'
  },
  wrongAnswer: {
    border: '3px solid #e74c3c',
    background: '#fadbd8',
    color: '#e74c3c'
  },
  answerFeedback: {
    textAlign: 'center',
    fontSize: '20px',
    fontWeight: 'bold',
    padding: '15px',
    borderRadius: '10px',
    marginTop: '20px'
  },
  correctFeedback: {
    color: '#27ae60',
    background: 'rgba(39, 174, 96, 0.1)'
  },
  wrongFeedback: {
    color: '#e74c3c',
    background: 'rgba(231, 76, 60, 0.1)'
  },
  resultsContainer: {
    background: 'rgba(255, 255, 255, 0.95)',
    borderRadius: '20px',
    padding: '40px',
    boxShadow: '0 20px 60px rgba(0,0,0,0.2)',
    backdropFilter: 'blur(10px)',
    width: '100%',
    maxWidth: '800px',
    textAlign: 'center'
  },
  resultsTitle: {
    fontSize: '36px',
    marginBottom: '40px',
    background: 'linear-gradient(45deg, #667eea, #764ba2)',
    backgroundClip: 'text',
    WebkitBackgroundClip: 'text',
    color: 'transparent'
  },
  podium: {
    display: 'flex',
    flexDirection: 'column',
    gap: '15px',
    marginBottom: '40px'
  },
  podiumPlace: {
    display: 'flex',
    alignItems: 'center',
    justify: 'space-between',
    padding: '20px',
    borderRadius: '15px',
    background: '#f8f9fa',
    border: '2px solid #eee'
  },
  firstPlace: {
    background: 'linear-gradient(45deg, #ffd700, #ffed4e)',
    border: '2px solid #ffd700',
    transform: 'scale(1.05)'
  },
  secondPlace: {
    background: 'linear-gradient(45deg, #c0c0c0, #e8e8e8)',
    border: '2px solid #c0c0c0'
  },
  thirdPlace: {
    background: 'linear-gradient(45deg, #cd7f32, #deb887)',
    border: '2px solid #cd7f32'
  },
  podiumRank: {
    fontSize: '24px',
    fontWeight: 'bold',
    minWidth: '60px'
  },
  podiumName: {
    fontSize: '20px',
    fontWeight: 'bold',
    flex: 1,
    textAlign: 'center'
  },
  podiumScore: {
    fontSize: '18px',
    fontWeight: 'bold',
    minWidth: '100px',
    textAlign: 'right'
  },
  buttonsContainer: {
    display: 'flex',
    gap: '20px',
    justifyContent: 'center'
  },
  backButton: {
    padding: '15px 30px',
    fontSize: '16px',
    fontWeight: 'bold',
    background: 'linear-gradient(45deg, #4CAF50, #45a049)',
    color: 'white',
    border: 'none',
    borderRadius: '10px',
    cursor: 'pointer'
  },
  menuButton: {
    padding: '15px 30px',
    fontSize: '16px',
    fontWeight: 'bold',
    background: 'linear-gradient(45deg, #6c757d, #5a6268)',
    color: 'white',
    border: 'none',
    borderRadius: '10px',
    cursor: 'pointer'
  }
};

export default SequentialMultiplayerGame;
