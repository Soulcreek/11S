// File: web/src/utils/gameLogic.js
// Description: Client-side game logic for static deployment

export const GAME_SETTINGS = {
  QUESTION_TIME: 11, // seconds
  QUESTIONS_PER_GAME: 5,
  SCORING: {
    CORRECT_BASE: 100,
    TIME_BONUS_MULTIPLIER: 10, // points per second remaining
    DIFFICULTY_MULTIPLIER: {
      easy: 1.0,
      medium: 1.2,
      hard: 1.5
    }
  }
};

export const calculateScore = (timeRemaining, difficulty, isCorrect) => {
  if (!isCorrect) return 0;
  
  const baseScore = GAME_SETTINGS.SCORING.CORRECT_BASE;
  const timeBonus = timeRemaining * GAME_SETTINGS.SCORING.TIME_BONUS_MULTIPLIER;
  const difficultyMultiplier = GAME_SETTINGS.SCORING.DIFFICULTY_MULTIPLIER[difficulty] || 1.0;
  
  return Math.round((baseScore + timeBonus) * difficultyMultiplier);
};

export const isAnswerCorrect = (userAnswer, correctAnswer) => {
  if (!userAnswer || !correctAnswer) return false;
  
  const user = userAnswer.toString().trim().toLowerCase();
  const correct = correctAnswer.toString().trim().toLowerCase();
  
  // Exact match
  if (user === correct) return true;
  
  // Numeric tolerance for numbers
  const userNum = parseFloat(user);
  const correctNum = parseFloat(correct);
  if (!isNaN(userNum) && !isNaN(correctNum)) {
    // Allow 5% tolerance for numeric answers
    const tolerance = Math.max(1, Math.abs(correctNum * 0.05));
    return Math.abs(userNum - correctNum) <= tolerance;
  }
  
  // String similarity for text answers
  const similarity = calculateStringSimilarity(user, correct);
  return similarity > 0.8; // 80% similarity threshold
};

const calculateStringSimilarity = (str1, str2) => {
  const longer = str1.length > str2.length ? str1 : str2;
  const shorter = str1.length > str2.length ? str2 : str1;
  
  if (longer.length === 0) return 1.0;
  
  const editDistance = levenshteinDistance(longer, shorter);
  return (longer.length - editDistance) / longer.length;
};

const levenshteinDistance = (str1, str2) => {
  const matrix = Array(str2.length + 1).fill(null).map(() => Array(str1.length + 1).fill(null));
  
  for (let i = 0; i <= str1.length; i++) matrix[0][i] = i;
  for (let j = 0; j <= str2.length; j++) matrix[j][0] = j;
  
  for (let j = 1; j <= str2.length; j++) {
    for (let i = 1; i <= str1.length; i++) {
      const indicator = str1[i - 1] === str2[j - 1] ? 0 : 1;
      matrix[j][i] = Math.min(
        matrix[j][i - 1] + 1, // deletion
        matrix[j - 1][i] + 1, // insertion
        matrix[j - 1][i - 1] + indicator // substitution
      );
    }
  }
  
  return matrix[str2.length][str1.length];
};

export const generateGameQuestions = (allQuestions, count = GAME_SETTINGS.QUESTIONS_PER_GAME, categories = null) => {
  let filteredQuestions = [...allQuestions];
  
  // Filter by categories if specified
  if (categories && categories.length > 0) {
    filteredQuestions = allQuestions.filter(q => categories.includes(q.category));
  }
  
  // Shuffle and select
  for (let i = filteredQuestions.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [filteredQuestions[i], filteredQuestions[j]] = [filteredQuestions[j], filteredQuestions[i]];
  }
  
  return filteredQuestions.slice(0, count);
};

export const saveGameResult = (gameResult) => {
  try {
    const username = localStorage.getItem('username');
    if (!username) return false;
    
    // Get current user data
    const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
    
    // Update user stats
    const updatedUser = {
      ...currentUser,
      username,
      gamesPlayed: (currentUser.gamesPlayed || 0) + 1,
      bestScore: Math.max(currentUser.bestScore || 0, gameResult.totalScore),
      lastGameScore: gameResult.totalScore,
      lastPlayed: new Date().toISOString()
    };
    
    localStorage.setItem('currentUser', JSON.stringify(updatedUser));
    
    // Save to game history
    const gameHistory = JSON.parse(localStorage.getItem('gameHistory') || '[]');
    const gameRecord = {
      id: Date.now(),
      username,
      score: gameResult.totalScore,
      questionsAnswered: gameResult.questions.length,
      correctAnswers: gameResult.questions.filter(q => q.isCorrect).length,
      timeStamp: new Date().toISOString(),
      questions: gameResult.questions
    };
    
    gameHistory.unshift(gameRecord); // Add to beginning
    
    // Keep only last 50 games
    if (gameHistory.length > 50) {
      gameHistory.splice(50);
    }
    
    localStorage.setItem('gameHistory', JSON.stringify(gameHistory));
    
    return true;
  } catch (error) {
    console.error('Error saving game result:', error);
    return false;
  }
};

export const getHighScores = (limit = 10) => {
  try {
    const gameHistory = JSON.parse(localStorage.getItem('gameHistory') || '[]');
    
    // Sort by score descending and take top scores
    const topScores = gameHistory
      .sort((a, b) => b.score - a.score)
      .slice(0, limit)
      .map((game, index) => ({
        rank: index + 1,
        username: game.username,
        score: game.score,
        date: new Date(game.timeStamp).toLocaleDateString(),
        accuracy: Math.round((game.correctAnswers / game.questionsAnswered) * 100)
      }));
    
    return topScores;
  } catch (error) {
    console.error('Error getting high scores:', error);
    return [];
  }
};

export const getUserStats = () => {
  try {
    const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
    const gameHistory = JSON.parse(localStorage.getItem('gameHistory') || '[]');
    const username = localStorage.getItem('username');
    
    if (!username) return null;
    
    const userGames = gameHistory.filter(game => game.username === username);
    
    return {
      username,
      gamesPlayed: userGames.length,
      bestScore: Math.max(...userGames.map(g => g.score), 0),
      averageScore: userGames.length > 0 ? Math.round(userGames.reduce((sum, g) => sum + g.score, 0) / userGames.length) : 0,
      totalCorrectAnswers: userGames.reduce((sum, g) => sum + g.correctAnswers, 0),
      totalQuestionsAnswered: userGames.reduce((sum, g) => sum + g.questionsAnswered, 0),
      averageAccuracy: userGames.length > 0 ? Math.round((userGames.reduce((sum, g) => sum + g.correctAnswers, 0) / userGames.reduce((sum, g) => sum + g.questionsAnswered, 1)) * 100) : 0,
      lastPlayed: userGames.length > 0 ? userGames[0].timeStamp : null,
      memberSince: currentUser.createdAt || new Date().toISOString()
    };
  } catch (error) {
    console.error('Error getting user stats:', error);
    return null;
  }
};
