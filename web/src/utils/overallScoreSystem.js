// File: web/src/utils/overallScoreSystem.js
// Description: Global score system that tracks overall player performance across all games

class OverallScoreSystem {
  constructor() {
    this.playerStats = this.loadPlayerStats();
  }

  loadPlayerStats() {
    const saved = localStorage.getItem('overallPlayerStats');
    if (saved) {
      return JSON.parse(saved);
    }
    
    // Initialize default stats
    return {
      overallScore: 0,
      level: 1,
      experience: 0,
      experienceToNext: 100,
      gamesPlayed: 0,
      totalGameScore: 0,
      averageScore: 0,
      bestSingleGame: 0,
      totalPlayTime: 0, // in seconds
      streakRecord: 0,
      achievements: 0,
      categoryMastery: {},
      modeMastery: {
        klassisch: { played: 0, bestScore: 0, averageScore: 0 },
        marathon: { played: 0, bestScore: 0, averageScore: 0 },
        blitz: { played: 0, bestScore: 0, averageScore: 0 },
        kategorien: { played: 0, bestScore: 0, averageScore: 0 }
      },
      skillRatings: {
        accuracy: 0,      // How close to correct answers
        speed: 0,         // How quickly questions are answered
        consistency: 0,   // How consistent performance is
        knowledge: 0,     // Overall knowledge across categories
        strategy: 0       // Ability to manage streaks and bonuses
      },
      milestones: {
        firstGame: null,
        first100: null,
        first500: null,
        first1000: null,
        hundredGames: null,
        perfectStreak: null
      },
      weeklyStats: {},
      monthlyStats: {}
    };
  }

  savePlayerStats() {
    localStorage.setItem('overallPlayerStats', JSON.stringify(this.playerStats));
  }

  updateAfterGame(gameData) {
    const stats = this.playerStats;
    
    // Basic game stats
    stats.gamesPlayed += 1;
    stats.totalGameScore += gameData.finalScore;
    stats.averageScore = Math.round(stats.totalGameScore / stats.gamesPlayed);
    stats.bestSingleGame = Math.max(stats.bestSingleGame, gameData.finalScore);
    stats.totalPlayTime += gameData.totalTime || 0;
    stats.streakRecord = Math.max(stats.streakRecord, gameData.maxStreak || 0);

    // Experience and level calculation
    const expGained = this.calculateExperience(gameData);
    stats.experience += expGained;
    
    // Level up check
    while (stats.experience >= stats.experienceToNext) {
      stats.experience -= stats.experienceToNext;
      stats.level += 1;
      stats.experienceToNext = this.getNextLevelRequirement(stats.level);
    }

    // Update mode mastery
    if (gameData.mode && stats.modeMastery[gameData.mode]) {
      const mode = stats.modeMastery[gameData.mode];
      mode.played += 1;
      mode.bestScore = Math.max(mode.bestScore, gameData.finalScore);
      mode.averageScore = Math.round(
        ((mode.averageScore * (mode.played - 1)) + gameData.finalScore) / mode.played
      );
    }

    // Update category mastery
    if (gameData.categoryPerformance) {
      Object.keys(gameData.categoryPerformance).forEach(category => {
        if (!stats.categoryMastery[category]) {
          stats.categoryMastery[category] = {
            questionsAnswered: 0,
            totalScore: 0,
            bestScore: 0,
            averageScore: 0,
            accuracy: 0
          };
        }
        
        const catStats = stats.categoryMastery[category];
        const gamePerf = gameData.categoryPerformance[category];
        
        catStats.questionsAnswered += gamePerf.questionsAnswered;
        catStats.totalScore += gamePerf.totalScore;
        catStats.bestScore = Math.max(catStats.bestScore, gamePerf.bestScore);
        catStats.averageScore = Math.round(catStats.totalScore / catStats.questionsAnswered);
      });
    }

    // Update skill ratings
    this.updateSkillRatings(gameData);

    // Update time-based stats
    this.updateTimeBasedStats(gameData);

    // Check milestones
    this.checkMilestones(gameData);

    // Calculate overall score
    stats.overallScore = this.calculateOverallScore();

    this.savePlayerStats();
    
    return {
      expGained,
      leveledUp: expGained > 0 && stats.experience < expGained,
      newLevel: stats.level,
      overallScore: stats.overallScore
    };
  }

  calculateExperience(gameData) {
    let exp = 0;
    
    // Base experience for completing a game
    exp += 10;
    
    // Score-based experience (1 exp per 10 score points)
    exp += Math.floor(gameData.finalScore / 10);
    
    // Streak bonus experience
    if (gameData.maxStreak >= 3) {
      exp += gameData.maxStreak * 2;
    }
    
    // Mode difficulty multiplier
    const modeMultipliers = {
      klassisch: 1.0,
      marathon: 1.5,  // Longer games give more exp
      blitz: 1.2,     // Speed challenge bonus
      kategorien: 1.1  // Category focus bonus
    };
    
    exp = Math.round(exp * (modeMultipliers[gameData.mode] || 1.0));
    
    // Performance bonus
    const accuracyPercent = (gameData.finalScore / gameData.maxScore) * 100;
    if (accuracyPercent >= 90) exp += 20;      // Excellence bonus
    else if (accuracyPercent >= 80) exp += 10; // Good performance bonus
    else if (accuracyPercent >= 70) exp += 5;  // Decent performance bonus
    
    return exp;
  }

  getNextLevelRequirement(level) {
    // Exponential level requirement: level * 50 + (level^1.5 * 25)
    return Math.floor(level * 50 + Math.pow(level, 1.5) * 25);
  }

  updateSkillRatings(gameData) {
    const skills = this.playerStats.skillRatings;
    const weight = 0.1; // How much new game affects overall rating (0.1 = 10%)
    
    // Accuracy: Based on how close answers were to correct
    const accuracyScore = Math.min(100, (gameData.finalScore / gameData.maxScore) * 100);
    skills.accuracy = Math.round(skills.accuracy * (1 - weight) + accuracyScore * weight);
    
    // Speed: Based on time left when answering
    const speedScore = gameData.answers ? 
      gameData.answers.reduce((sum, ans) => sum + (ans.timeLeft || 0), 0) / gameData.answers.length * 10 :
      50;
    skills.speed = Math.round(skills.speed * (1 - weight) + Math.min(100, speedScore) * weight);
    
    // Consistency: Based on score variation across questions
    if (gameData.answers && gameData.answers.length > 1) {
      const scores = gameData.answers.map(a => a.score);
      const avg = scores.reduce((sum, s) => sum + s, 0) / scores.length;
      const variance = scores.reduce((sum, s) => sum + Math.pow(s - avg, 2), 0) / scores.length;
      const consistencyScore = Math.max(0, 100 - Math.sqrt(variance));
      skills.consistency = Math.round(skills.consistency * (1 - weight) + consistencyScore * weight);
    }
    
    // Knowledge: Based on performance across different categories
    const categoryCount = gameData.categories ? gameData.categories.length : 1;
    const knowledgeScore = accuracyScore * (1 + (categoryCount - 1) * 0.1); // Bonus for multi-category
    skills.knowledge = Math.round(skills.knowledge * (1 - weight) + Math.min(100, knowledgeScore) * weight);
    
    // Strategy: Based on streak performance and bonus utilization
    const strategyScore = gameData.maxStreak ? 
      Math.min(100, gameData.maxStreak * 20 + (gameData.streakBonus || 0) * 2) :
      50;
    skills.strategy = Math.round(skills.strategy * (1 - weight) + strategyScore * weight);
  }

  updateTimeBasedStats(gameData) {
    const now = new Date();
    const week = this.getWeekKey(now);
    const month = this.getMonthKey(now);
    
    // Weekly stats
    if (!this.playerStats.weeklyStats[week]) {
      this.playerStats.weeklyStats[week] = { gamesPlayed: 0, totalScore: 0, bestScore: 0 };
    }
    const weekStats = this.playerStats.weeklyStats[week];
    weekStats.gamesPlayed += 1;
    weekStats.totalScore += gameData.finalScore;
    weekStats.bestScore = Math.max(weekStats.bestScore, gameData.finalScore);
    
    // Monthly stats
    if (!this.playerStats.monthlyStats[month]) {
      this.playerStats.monthlyStats[month] = { gamesPlayed: 0, totalScore: 0, bestScore: 0 };
    }
    const monthStats = this.playerStats.monthlyStats[month];
    monthStats.gamesPlayed += 1;
    monthStats.totalScore += gameData.finalScore;
    monthStats.bestScore = Math.max(monthStats.bestScore, gameData.finalScore);
  }

  checkMilestones(gameData) {
    const milestones = this.playerStats.milestones;
    
    if (!milestones.firstGame) {
      milestones.firstGame = new Date().toISOString();
    }
    
    if (!milestones.first100 && gameData.finalScore >= 100) {
      milestones.first100 = new Date().toISOString();
    }
    
    if (!milestones.first500 && gameData.finalScore >= 500) {
      milestones.first500 = new Date().toISOString();
    }
    
    if (!milestones.first1000 && gameData.finalScore >= 1000) {
      milestones.first1000 = new Date().toISOString();
    }
    
    if (!milestones.hundredGames && this.playerStats.gamesPlayed >= 100) {
      milestones.hundredGames = new Date().toISOString();
    }
    
    if (!milestones.perfectStreak && gameData.maxStreak >= 10) {
      milestones.perfectStreak = new Date().toISOString();
    }
  }

  calculateOverallScore() {
    const stats = this.playerStats;
    
    // Base score from level and experience
    let score = stats.level * 100 + Math.floor(stats.experience / 10);
    
    // Activity bonus (games played)
    score += stats.gamesPlayed * 5;
    
    // Performance bonus (average score across all games)
    score += Math.floor(stats.averageScore / 2);
    
    // Skill ratings bonus
    const skillAverage = Object.values(stats.skillRatings).reduce((sum, skill) => sum + skill, 0) / 5;
    score += Math.floor(skillAverage);
    
    // Achievement bonus
    score += stats.achievements * 25;
    
    // Streak record bonus
    score += stats.streakRecord * 10;
    
    // Consistency bonus (based on games played vs best performance)
    if (stats.gamesPlayed > 10) {
      const consistencyRatio = stats.averageScore / Math.max(stats.bestSingleGame, 1);
      score += Math.floor(consistencyRatio * 100);
    }
    
    return score;
  }

  getPlayerLevel() {
    return this.playerStats.level;
  }

  getOverallScore() {
    return this.playerStats.overallScore;
  }

  getPlayerStats() {
    return { ...this.playerStats };
  }

  getSkillRatings() {
    return { ...this.playerStats.skillRatings };
  }

  getLevelProgress() {
    return {
      level: this.playerStats.level,
      experience: this.playerStats.experience,
      experienceToNext: this.playerStats.experienceToNext,
      progressPercent: Math.round((this.playerStats.experience / this.playerStats.experienceToNext) * 100)
    };
  }

  getWeekKey(date) {
    const year = date.getFullYear();
    const week = Math.ceil((date - new Date(year, 0, 1)) / (7 * 24 * 60 * 60 * 1000));
    return `${year}-W${week}`;
  }

  getMonthKey(date) {
    return `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`;
  }

  // Get leaderboard position (if we had multiple users)
  getLeaderboardRank() {
    // For now, just return 1 since it's single player
    return 1;
  }

  exportStats() {
    return {
      ...this.playerStats,
      exportDate: new Date().toISOString(),
      version: '1.0'
    };
  }

  importStats(importedStats) {
    if (importedStats.version) {
      this.playerStats = { ...this.playerStats, ...importedStats };
      this.savePlayerStats();
      return true;
    }
    return false;
  }
}

// Export singleton instance
const overallScoreSystem = new OverallScoreSystem();
export default overallScoreSystem;
