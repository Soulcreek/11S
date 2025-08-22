// LocalStorage utility functions for 11Seconds Quiz Game
// Handles user management, game stats, and settings

// User Management
export const userManager = {
  // Get current user
  getCurrentUser: () => {
    const username = localStorage.getItem('username');
    if (!username) return null;
    
    const users = JSON.parse(localStorage.getItem('users') || '[]');
    return users.find(user => user.username === username);
  },

  // Register new user
  registerUser: (userData) => {
    const users = JSON.parse(localStorage.getItem('users') || '[]');
    const existingUser = users.find(user => 
      user.username === userData.username || user.email === userData.email
    );
    
    if (existingUser) {
      return { success: false, error: 'Username or email already exists' };
    }

    const newUser = {
      id: 'user_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
      username: userData.username,
      email: userData.email || '',
      createdAt: new Date().toISOString(),
      stats: {
        gamesPlayed: 0,
        totalScore: 0,
        averageScore: 0,
        bestScore: 0,
        totalTime: 0
      },
      preferences: {
        theme: 'light',
        soundEnabled: true,
        difficultiesEnabled: ['easy', 'medium', 'hard'],
        categoriesPreferred: []
      }
    };

    users.push(newUser);
    localStorage.setItem('users', JSON.stringify(users));
    localStorage.setItem('username', userData.username);
    
    return { success: true, user: newUser };
  },

  // Login user
  loginUser: (username) => {
    const users = JSON.parse(localStorage.getItem('users') || '[]');
    const user = users.find(u => u.username === username);
    
    if (!user) {
      return { success: false, error: 'User not found' };
    }

    localStorage.setItem('username', username);
    return { success: true, user };
  },

  // Update user data
  updateUser: (userData) => {
    const users = JSON.parse(localStorage.getItem('users') || '[]');
    const userIndex = users.findIndex(u => u.id === userData.id);
    
    if (userIndex === -1) {
      return { success: false, error: 'User not found' };
    }

    users[userIndex] = { ...users[userIndex], ...userData };
    localStorage.setItem('users', JSON.stringify(users));
    
    return { success: true, user: users[userIndex] };
  },

  // Get all users (for admin/leaderboard)
  getAllUsers: () => {
    return JSON.parse(localStorage.getItem('users') || '[]');
  },

  // Delete user
  deleteUser: (userId) => {
    const users = JSON.parse(localStorage.getItem('users') || '[]');
    const filteredUsers = users.filter(u => u.id !== userId);
    localStorage.setItem('users', JSON.stringify(filteredUsers));
    
    // If current user was deleted, logout
    const currentUser = userManager.getCurrentUser();
    if (currentUser && currentUser.id === userId) {
      localStorage.removeItem('username');
    }
    
    return { success: true };
  },

  // Logout
  logout: () => {
    localStorage.removeItem('username');
    return { success: true };
  }
};

// Game Statistics Management
export const gameStats = {
  // Save game result
  saveGameResult: (gameData) => {
    const currentUser = userManager.getCurrentUser();
    if (!currentUser) return { success: false, error: 'No user logged in' };

    // Enhanced highscore entry
    const highscores = JSON.parse(localStorage.getItem('highscores') || '[]');
    const newHighscore = {
      userId: currentUser.id,
      username: currentUser.username,
      score: gameData.finalScore,
      maxScore: gameData.maxScore || 600, // 5 questions * 120 max points
      percentage: ((gameData.finalScore / (gameData.maxScore || 600)) * 100).toFixed(1),
      date: new Date().toISOString(),
      categories: gameData.categories || [],
      difficulties: gameData.difficulties || [],
      questionCount: gameData.questionCount || 5,
      totalTime: gameData.totalTime || 55, // 5 * 11 seconds
      timeBonus: gameData.timeBonus || 0,
      categoryStats: gameData.categoryPerformance || {}
    };

    highscores.push(newHighscore);
    
    // Keep only top 100 scores
    highscores.sort((a, b) => b.score - a.score);
    if (highscores.length > 100) {
      highscores.splice(100);
    }
    
    localStorage.setItem('highscores', JSON.stringify(highscores));

    // Update user stats
    const updatedStats = {
      ...currentUser.stats,
      gamesPlayed: currentUser.stats.gamesPlayed + 1,
      totalScore: currentUser.stats.totalScore + gameData.finalScore,
      bestScore: Math.max(currentUser.stats.bestScore, gameData.finalScore),
      totalTime: currentUser.stats.totalTime + (gameData.totalTime || 55)
    };
    updatedStats.averageScore = Math.round(updatedStats.totalScore / updatedStats.gamesPlayed);

    userManager.updateUser({ ...currentUser, stats: updatedStats });

    // Update category stats
    gameStats.updateCategoryStats(gameData.categoryPerformance || {});

    return { success: true, highscore: newHighscore };
  },

  // Get highscores
  getHighscores: (limit = 10, userId = null) => {
    const highscores = JSON.parse(localStorage.getItem('highscores') || '[]');
    
    if (userId) {
      return highscores.filter(h => h.userId === userId).slice(0, limit);
    }
    
    return highscores.slice(0, limit);
  },

  // Get global leaderboard
  getGlobalLeaderboard: () => {
    const users = userManager.getAllUsers();
    return users
      .filter(user => user.stats.gamesPlayed > 0)
      .sort((a, b) => b.stats.bestScore - a.stats.bestScore)
      .slice(0, 20)
      .map(user => ({
        username: user.username,
        bestScore: user.stats.bestScore,
        averageScore: user.stats.averageScore,
        gamesPlayed: user.stats.gamesPlayed,
        userId: user.id
      }));
  },

  // Update category statistics
  updateCategoryStats: (categoryPerformance) => {
    const currentStats = JSON.parse(localStorage.getItem('categoryStats') || '{}');
    
    Object.keys(categoryPerformance).forEach(category => {
      if (!currentStats[category]) {
        currentStats[category] = {
          played: 0,
          totalScore: 0,
          averageScore: 0,
          bestScore: 0
        };
      }
      
      const perf = categoryPerformance[category];
      currentStats[category].played += perf.questionsAnswered || 1;
      currentStats[category].totalScore += perf.totalScore || 0;
      currentStats[category].bestScore = Math.max(
        currentStats[category].bestScore, 
        perf.bestScore || 0
      );
      currentStats[category].averageScore = Math.round(
        currentStats[category].totalScore / currentStats[category].played
      );
    });
    
    localStorage.setItem('categoryStats', JSON.stringify(currentStats));
    return currentStats;
  },

  // Get category statistics
  getCategoryStats: () => {
    return JSON.parse(localStorage.getItem('categoryStats') || '{}');
  },

  // Get user performance trends
  getPerformanceTrends: (userId, days = 30) => {
    const highscores = JSON.parse(localStorage.getItem('highscores') || '[]');
    const userScores = highscores
      .filter(h => h.userId === userId)
      .filter(h => {
        const scoreDate = new Date(h.date);
        const cutoffDate = new Date();
        cutoffDate.setDate(cutoffDate.getDate() - days);
        return scoreDate >= cutoffDate;
      })
      .sort((a, b) => new Date(a.date) - new Date(b.date));

    return userScores;
  }
};

// Settings Management
export const settingsManager = {
  // Get user settings
  getSettings: () => {
    const currentUser = userManager.getCurrentUser();
    if (!currentUser) {
      return {
        theme: 'light',
        soundEnabled: true,
        difficultiesEnabled: ['easy', 'medium', 'hard'],
        categoriesPreferred: [],
        questionCount: 5,
        timerEnabled: true
      };
    }
    
    return currentUser.preferences || {};
  },

  // Update settings
  updateSettings: (newSettings) => {
    const currentUser = userManager.getCurrentUser();
    if (!currentUser) {
      localStorage.setItem('guestSettings', JSON.stringify(newSettings));
      return { success: true, settings: newSettings };
    }
    
    const updatedUser = {
      ...currentUser,
      preferences: { ...currentUser.preferences, ...newSettings }
    };
    
    const result = userManager.updateUser(updatedUser);
    return { success: result.success, settings: updatedUser.preferences };
  },

  // Get available categories
  getAvailableCategories: () => {
    return [
      { id: 'geography', name: 'Geography', icon: '🌍' },
      { id: 'history', name: 'History', icon: '📚' },
      { id: 'science', name: 'Science', icon: '🔬' },
      { id: 'nature', name: 'Nature', icon: '🌿' },
      { id: 'sports', name: 'Sports', icon: '⚽' },
      { id: 'technology', name: 'Technology', icon: '💻' },
      { id: 'music', name: 'Music', icon: '🎵' },
      { id: 'literature', name: 'Literature', icon: '📖' }
    ];
  }
};

// Data Export/Import
export const dataManager = {
  // Export all user data
  exportUserData: () => {
    const currentUser = userManager.getCurrentUser();
    if (!currentUser) return null;

    const userData = {
      user: currentUser,
      highscores: gameStats.getHighscores(50, currentUser.id),
      categoryStats: gameStats.getCategoryStats(),
      exportDate: new Date().toISOString(),
      version: '1.0'
    };

    return JSON.stringify(userData, null, 2);
  },

  // Import user data
  importUserData: (jsonData) => {
    try {
      const data = JSON.parse(jsonData);
      
      if (!data.user || !data.version) {
        return { success: false, error: 'Invalid data format' };
      }

      // Import user
      const result = userManager.registerUser({
        username: data.user.username + '_imported_' + Date.now(),
        email: data.user.email
      });

      if (!result.success) {
        return result;
      }

      // Update user with imported stats
      userManager.updateUser({
        ...result.user,
        stats: data.user.stats,
        preferences: data.user.preferences
      });

      // Import highscores
      if (data.highscores) {
        const currentHighscores = JSON.parse(localStorage.getItem('highscores') || '[]');
        data.highscores.forEach(score => {
          score.userId = result.user.id;
          score.username = result.user.username;
        });
        
        const mergedHighscores = [...currentHighscores, ...data.highscores];
        mergedHighscores.sort((a, b) => b.score - a.score);
        localStorage.setItem('highscores', JSON.stringify(mergedHighscores.slice(0, 100)));
      }

      return { success: true, user: result.user };
    } catch (error) {
      return { success: false, error: 'Invalid JSON format' };
    }
  },

  // Clear all data
  clearAllData: () => {
    const keys = ['users', 'highscores', 'categoryStats', 'username', 'guestSettings'];
    keys.forEach(key => localStorage.removeItem(key));
    return { success: true };
  },

  // Get data summary
  getDataSummary: () => {
    const users = userManager.getAllUsers();
    const highscores = JSON.parse(localStorage.getItem('highscores') || '[]');
    const categoryStats = gameStats.getCategoryStats();
    
    return {
      totalUsers: users.length,
      totalGames: highscores.length,
      totalCategories: Object.keys(categoryStats).length,
      dataSize: JSON.stringify({
        users,
        highscores,
        categoryStats
      }).length
    };
  }
};

// Utility functions
export const utils = {
  // Generate unique ID
  generateId: () => 'id_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
  
  // Format date
  formatDate: (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
  },
  
  // Calculate percentage
  calculatePercentage: (value, total) => {
    if (total === 0) return 0;
    return Math.round((value / total) * 100);
  },

  // Validate username
  validateUsername: (username) => {
    if (!username || username.length < 3) {
      return { valid: false, error: 'Username must be at least 3 characters' };
    }
    if (username.length > 20) {
      return { valid: false, error: 'Username must be less than 20 characters' };
    }
    if (!/^[a-zA-Z0-9_-]+$/.test(username)) {
      return { valid: false, error: 'Username can only contain letters, numbers, hyphens and underscores' };
    }
    return { valid: true };
  },

  // Validate email
  validateEmail: (email) => {
    if (!email) return { valid: true }; // Email is optional
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      return { valid: false, error: 'Invalid email format' };
    }
    return { valid: true };
  }
};
