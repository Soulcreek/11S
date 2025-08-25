// File: web/src/components/SimultaneousMultiplayerGame.js
// Description: Simultaneous multiplayer game where all players answer at once using screen corners

import React, { useState, useEffect } from 'react';

const SimultaneousMultiplayerGame = ({ gameConfig, players, onBackToSetup, onBackToMenu }) => {
  const [questions, setQuestions] = useState([]);
  const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
  const [playerScores, setPlayerScores] = useState({});
  const [gamePhase, setGamePhase] = useState('loading'); // 'loading', 'question', 'results', 'final'
  const [timeLeft, setTimeLeft] = useState(gameConfig.timePerQuestion);
  const [playerAnswers, setPlayerAnswers] = useState({});
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
      const totalQuestions = gameConfig.questionsPerPlayer;
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
    // Simulated question loading - same as SequentialMultiplayerGame
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
      {
        question: "Welches Tier ist das Symbol für Weisheit?",
        options: ["Eule", "Fuchs", "Rabe", "Elefant"],
        correctAnswer: 0,
        category: "nature"
      },
      {
        question: "Wie viele Kontinente gibt es?",
        options: ["5", "6", "7", "8"],
        correctAnswer: 2,
        category: "geography"
      }
    ];

    const shuffled = [...sampleQuestions].sort(() => 0.5 - Math.random());
    return shuffled.slice(0, count);
  };

  const handleCornerClick = (corner, answerIndex) => {
    if (isAnswerRevealed) return;
    
    // Assign the corner to the first player who hasn't answered yet
    const availablePlayers = players.filter(player => !playerAnswers[player]);
    if (availablePlayers.length === 0) return;
    
    const player = availablePlayers[0];
    setPlayerAnswers(prev => ({
      ...prev,
      [player]: answerIndex
    }));

    // If all players have answered, reveal answers immediately
    if (availablePlayers.length === 1) {
      setTimeout(() => {
        revealAnswers();
      }, 500);
    }
  };

  const handleTimeUp = () => {
    revealAnswers();
  };

  const revealAnswers = () => {
    setIsAnswerRevealed(true);
    
    // Calculate scores
    const newScores = { ...playerScores };
    let fastestCorrectTime = timeLeft;
    let fastestPlayers = [];

    // Find fastest correct answer
    Object.entries(playerAnswers).forEach(([player, answer]) => {
      if (answer === currentQuestion.correctAnswer) {
        fastestPlayers.push(player);
      }
    });

    // Award points
    Object.entries(playerAnswers).forEach(([player, answer]) => {
      if (answer === currentQuestion.correctAnswer) {
        const basePoints = 10;
        const timeBonus = Math.max(1, Math.floor(timeLeft / 2));
        const speedBonus = fastestPlayers.length === 1 && fastestPlayers[0] === player ? 5 : 0;
        newScores[player] += basePoints + timeBonus + speedBonus;
      }
    });

    setPlayerScores(newScores);

    // Move to next question after delay
    setTimeout(() => {
      moveToNextQuestion();
    }, 3000);
  };

  const moveToNextQuestion = () => {
    const nextQuestionIndex = currentQuestionIndex + 1;
    
    if (nextQuestionIndex >= questions.length) {
      setGamePhase('final');
      return;
    }
    
    // Reset for next question
    setCurrentQuestionIndex(nextQuestionIndex);
    setCurrentQuestion(questions[nextQuestionIndex]);
    setPlayerAnswers({});
    setIsAnswerRevealed(false);
    setTimeLeft(gameConfig.timePerQuestion);
    setGamePhase('question');
  };

  const getCornerLabel = (index) => {
    const labels = ['A', 'B', 'C', 'D'];
    return labels[index];
  };

  const getPlayerAnswer = (player) => {
    return playerAnswers[player];
  };

  const renderQuestion = () => (
    <div style={styles.gameContainer}>
      {/* Header */}
      <div style={styles.gameHeader}>
        <div style={styles.questionInfo}>
          <div style={styles.questionNumber}>
            Frage {currentQuestionIndex + 1} von {questions.length}
          </div>
        </div>
        <div style={styles.timer}>
          ⏱️ {timeLeft}s
        </div>
      </div>

      {/* Question */}
      <div style={styles.questionArea}>
        <div style={styles.questionText}>
          {currentQuestion?.question}
        </div>
      </div>

      {/* Corner Buttons - Mobile Optimized Layout */}
      <div style={styles.cornersContainer}>
        {/* Top Row */}
        <div style={styles.topRow}>
          <div 
            style={{...styles.corner, ...styles.topLeft, ...(isAnswerRevealed && 0 === currentQuestion.correctAnswer ? styles.correctCorner : {})}}
            onClick={() => handleCornerClick('topLeft', 0)}
          >
            <div style={styles.cornerLabel}>A</div>
            <div style={styles.cornerText}>{currentQuestion?.options[0]}</div>
          </div>
          <div 
            style={{...styles.corner, ...styles.topRight, ...(isAnswerRevealed && 1 === currentQuestion.correctAnswer ? styles.correctCorner : {})}}
            onClick={() => handleCornerClick('topRight', 1)}
          >
            <div style={styles.cornerLabel}>B</div>
            <div style={styles.cornerText}>{currentQuestion?.options[1]}</div>
          </div>
        </div>

        {/* Bottom Row */}
        <div style={styles.bottomRow}>
          <div 
            style={{...styles.corner, ...styles.bottomLeft, ...(isAnswerRevealed && 2 === currentQuestion.correctAnswer ? styles.correctCorner : {})}}
            onClick={() => handleCornerClick('bottomLeft', 2)}
          >
            <div style={styles.cornerLabel}>C</div>
            <div style={styles.cornerText}>{currentQuestion?.options[2]}</div>
          </div>
          <div 
            style={{...styles.corner, ...styles.bottomRight, ...(isAnswerRevealed && 3 === currentQuestion.correctAnswer ? styles.correctCorner : {})}}
            onClick={() => handleCornerClick('bottomRight', 3)}
          >
            <div style={styles.cornerLabel}>D</div>
            <div style={styles.cornerText}>{currentQuestion?.options[3]}</div>
          </div>
        </div>
      </div>

      {/* Player Status */}
      <div style={styles.playerStatus}>
        {players.map(player => (
          <div key={player} style={styles.playerStatusItem}>
            <span style={styles.playerName}>{player}:</span>
            <span style={styles.playerAnswer}>
              {getPlayerAnswer(player) !== undefined 
                ? `${getCornerLabel(getPlayerAnswer(player))} ✓`
                : '⏳'
              }
            </span>
          </div>
        ))}
      </div>

      {/* Results overlay */}
      {isAnswerRevealed && (
        <div style={styles.resultsOverlay}>
          <div style={styles.correctAnswerText}>
            ✅ Richtige Antwort: {getCornerLabel(currentQuestion.correctAnswer)} - {currentQuestion.options[currentQuestion.correctAnswer]}
          </div>
          <div style={styles.playerResults}>
            {Object.entries(playerAnswers).map(([player, answer]) => (
              <div key={player} style={{
                ...styles.playerResult,
                ...(answer === currentQuestion.correctAnswer ? styles.correctPlayerResult : styles.wrongPlayerResult)
              }}>
                {player}: {answer === currentQuestion.correctAnswer ? '✅ Richtig' : 'Falsch'}
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );

  const renderFinalResults = () => {
    const sortedPlayers = players
      .map(player => ({ name: player, score: playerScores[player] }))
      .sort((a, b) => b.score - a.score);

    return (
      <div style={styles.finalResultsContainer}>
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
    minHeight: '100vh',
    background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
    display: 'flex',
    flexDirection: 'column'
  },
  loadingContainer: {
    display: 'flex',
    justifyContent: 'center',
    alignItems: 'center',
    minHeight: '100vh',
    background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)'
  },
  loadingText: {
    fontSize: '24px',
    color: 'white',
    textAlign: 'center'
  },
  gameContainer: {
    flex: 1,
    display: 'flex',
    flexDirection: 'column',
    position: 'relative'
  },
  gameHeader: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: '20px',
    background: 'rgba(255, 255, 255, 0.9)',
    boxShadow: '0 2px 10px rgba(0,0,0,0.1)'
  },
  questionInfo: {
    display: 'flex',
    alignItems: 'center'
  },
  questionNumber: {
    fontSize: '18px',
    fontWeight: 'bold',
    color: '#333'
  },
  timer: {
    fontSize: '24px',
    fontWeight: 'bold',
    color: '#e74c3c',
    padding: '10px 20px',
    background: 'rgba(231, 76, 60, 0.1)',
    borderRadius: '10px'
  },
  questionArea: {
    flex: 1,
    display: 'flex',
    justifyContent: 'center',
    alignItems: 'center',
    padding: '20px',
    minHeight: '150px'
  },
  questionText: {
    fontSize: '28px',
    fontWeight: 'bold',
    textAlign: 'center',
    color: 'white',
    textShadow: '0 2px 4px rgba(0,0,0,0.3)',
    lineHeight: '1.4',
    maxWidth: '800px'
  },
  cornersContainer: {
    flex: 2,
    display: 'flex',
    flexDirection: 'column',
    padding: '20px',
    gap: '20px'
  },
  topRow: {
    display: 'flex',
    gap: '20px',
    flex: 1
  },
  bottomRow: {
    display: 'flex',
    gap: '20px',
    flex: 1
  },
  corner: {
    flex: 1,
    display: 'flex',
    flexDirection: 'column',
    justifyContent: 'center',
    alignItems: 'center',
    background: 'rgba(255, 255, 255, 0.9)',
    borderRadius: '20px',
    cursor: 'pointer',
    transition: 'all 0.2s ease',
    boxShadow: '0 4px 15px rgba(0,0,0,0.2)',
    border: '3px solid transparent',
    minHeight: '120px',
    padding: '20px'
  },
  topLeft: {
    // Additional styling for top-left corner if needed
  },
  topRight: {
    // Additional styling for top-right corner if needed
  },
  bottomLeft: {
    // Additional styling for bottom-left corner if needed
  },
  bottomRight: {
    // Additional styling for bottom-right corner if needed
  },
  correctCorner: {
    background: 'rgba(39, 174, 96, 0.9)',
    color: 'white',
    border: '3px solid #27ae60'
  },
  cornerLabel: {
    fontSize: '24px',
    fontWeight: 'bold',
    marginBottom: '10px'
  },
  cornerText: {
    fontSize: '18px',
    fontWeight: 'bold',
    textAlign: 'center',
    lineHeight: '1.3'
  },
  playerStatus: {
    display: 'flex',
    justifyContent: 'space-around',
    padding: '20px',
    background: 'rgba(255, 255, 255, 0.9)',
    flexWrap: 'wrap',
    gap: '10px'
  },
  playerStatusItem: {
    display: 'flex',
    alignItems: 'center',
    gap: '5px',
    padding: '5px 10px',
    background: '#f8f9fa',
    borderRadius: '8px'
  },
  playerName: {
    fontWeight: 'bold',
    fontSize: '14px'
  },
  playerAnswer: {
    fontSize: '14px',
    color: '#666'
  },
  resultsOverlay: {
    position: 'absolute',
    top: '50%',
    left: '50%',
    transform: 'translate(-50%, -50%)',
    background: 'rgba(0, 0, 0, 0.9)',
    color: 'white',
    padding: '30px',
    borderRadius: '20px',
    textAlign: 'center',
    minWidth: '300px'
  },
  correctAnswerText: {
    fontSize: '20px',
    fontWeight: 'bold',
    marginBottom: '20px',
    color: '#4CAF50'
  },
  playerResults: {
    display: 'flex',
    flexDirection: 'column',
    gap: '10px'
  },
  playerResult: {
    padding: '10px',
    borderRadius: '8px',
    fontSize: '16px',
    fontWeight: 'bold'
  },
  correctPlayerResult: {
    background: 'rgba(39, 174, 96, 0.3)',
    color: '#4CAF50'
  },
  wrongPlayerResult: {
    background: 'rgba(231, 76, 60, 0.3)',
    color: '#e74c3c'
  },
  finalResultsContainer: {
    display: 'flex',
    flexDirection: 'column',
    justifyContent: 'center',
    alignItems: 'center',
    minHeight: '100vh',
    padding: '20px'
  },
  resultsTitle: {
    fontSize: '36px',
    marginBottom: '40px',
    color: 'white',
    textAlign: 'center',
    textShadow: '0 2px 4px rgba(0,0,0,0.3)'
  },
  podium: {
    display: 'flex',
    flexDirection: 'column',
    gap: '15px',
    marginBottom: '40px',
    background: 'rgba(255, 255, 255, 0.9)',
    padding: '30px',
    borderRadius: '20px',
    minWidth: '400px'
  },
  podiumPlace: {
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'space-between',
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

export default SimultaneousMultiplayerGame;
