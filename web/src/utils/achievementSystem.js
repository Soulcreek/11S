// File: web/src/utils/achievementSystem.js
// Description: Achievement system for tracking player progress and unlocking badges

class AchievementSystem {
  constructor() {
    this.achievements = {
      // Scoring achievements
      perfectionist: {
        id: 'perfectionist',
        name: 'Perfektionist',
        description: 'Erziele 100% bei einer Frage',
        icon: '🎯',
        unlocked: false,
        category: 'scoring'
      },
      highscorer: {
        id: 'highscorer',
        name: 'Punktejäger',
        description: 'Erreiche 500 Punkte in einem Spiel',
        icon: '⭐',
        unlocked: false,
        category: 'scoring'
      },
      champion: {
        id: 'champion',
        name: 'Champion',
        description: 'Erreiche 600 Punkte in einem Spiel',
        icon: '👑',
        unlocked: false,
        category: 'scoring'
      },
      
      // Streak achievements
      streaker: {
        id: 'streaker',
        name: 'Serie',
        description: 'Beantworte 3 Fragen in Folge richtig',
        icon: '🔥',
        unlocked: false,
        category: 'streak'
      },
      hotStreak: {
        id: 'hotStreak',
        name: 'Heiße Serie',
        description: 'Beantworte 5 Fragen in Folge richtig',
        icon: '🔥🔥',
        unlocked: false,
        category: 'streak'
      },
      unstoppable: {
        id: 'unstoppable',
        name: 'Unaufhaltsam',
        description: 'Beantworte 10 Fragen in Folge über mehrere Spiele richtig',
        icon: '🔥🔥🔥',
        unlocked: false,
        category: 'streak'
      },

      // Game mode achievements
      speedDemon: {
        id: 'speedDemon',
        name: 'Geschwindigkeitsteufel',
        description: 'Schließe ein Blitz-Spiel mit 400+ Punkten ab',
        icon: '⚡',
        unlocked: false,
        category: 'gamemode'
      },
      marathoner: {
        id: 'marathoner',
        name: 'Marathonläufer',
        description: 'Schließe ein Marathon-Spiel ab',
        icon: '🏃‍♂️',
        unlocked: false,
        category: 'gamemode'
      },
      allRounder: {
        id: 'allRounder',
        name: 'Allrounder',
        description: 'Spiele alle 4 Spielmodi',
        icon: '🎮',
        unlocked: false,
        category: 'gamemode'
      },

      // Category achievements
      geographer: {
        id: 'geographer',
        name: 'Geograf',
        description: 'Erziele 400+ Punkte in einem Geografie-Spiel',
        icon: '🌍',
        unlocked: false,
        category: 'category'
      },
      historian: {
        id: 'historian',
        name: 'Historiker',
        description: 'Erziele 400+ Punkte in einem Geschichte-Spiel',
        icon: '📚',
        unlocked: false,
        category: 'category'
      },
      scientist: {
        id: 'scientist',
        name: 'Wissenschaftler',
        description: 'Erziele 400+ Punkte in einem Wissenschaft-Spiel',
        icon: '🔬',
        unlocked: false,
        category: 'category'
      },
      polymath: {
        id: 'polymath',
        name: 'Universalgelehrter',
        description: 'Erziele gute Ergebnisse in allen Kategorien',
        icon: '🧠',
        unlocked: false,
        category: 'category'
      },

      // Participation achievements
      newbie: {
        id: 'newbie',
        name: 'Erste Schritte',
        description: 'Schließe dein erstes Spiel ab',
        icon: '🌟',
        unlocked: false,
        category: 'participation'
      },
      regular: {
        id: 'regular',
        name: 'Regelmäßiger Spieler',
        description: 'Spiele 10 Spiele',
        icon: '🎯',
        unlocked: false,
        category: 'participation'
      },
      veteran: {
        id: 'veteran',
        name: 'Veteran',
        description: 'Spiele 50 Spiele',
        icon: '🏆',
        unlocked: false,
        category: 'participation'
      },
      dedicated: {
        id: 'dedicated',
        name: 'Hingabe',
        description: 'Spiele 100 Spiele',
        icon: '💎',
        unlocked: false,
        category: 'participation'
      },

      // Special achievements
      quickthinker: {
        id: 'quickthinker',
        name: 'Schnelldenker',
        description: 'Beantworte eine Frage in unter 2 Sekunden mit 100+ Punkten',
        icon: '💨',
        unlocked: false,
        category: 'special'
      },
      comeback: {
        id: 'comeback',
        name: 'Comeback Kid',
        description: 'Erziele 100+ bei der letzten Frage nach schlechtem Start',
        icon: '🔄',
        unlocked: false,
        category: 'special'
      }
    };

    this.loadAchievements();
  }

  loadAchievements() {
    const saved = localStorage.getItem('achievements');
    if (saved) {
      const savedAchievements = JSON.parse(saved);
      // Merge saved data with current achievements (to handle new achievements)
      Object.keys(this.achievements).forEach(id => {
        if (savedAchievements[id]) {
          this.achievements[id].unlocked = savedAchievements[id].unlocked;
        }
      });
    }
  }

  saveAchievements() {
    localStorage.setItem('achievements', JSON.stringify(this.achievements));
  }

  checkAchievements(gameData, globalStats = {}) {
    const newlyUnlocked = [];

    // Scoring achievements
    if (!this.achievements.perfectionist.unlocked) {
      const hasPerfectScore = gameData.answers?.some(answer => answer.score >= 110);
      if (hasPerfectScore) {
        newlyUnlocked.push(this.unlockAchievement('perfectionist'));
      }
    }

    if (!this.achievements.highscorer.unlocked && gameData.finalScore >= 500) {
      newlyUnlocked.push(this.unlockAchievement('highscorer'));
    }

    if (!this.achievements.champion.unlocked && gameData.finalScore >= 600) {
      newlyUnlocked.push(this.unlockAchievement('champion'));
    }

    // Streak achievements
    if (!this.achievements.streaker.unlocked && gameData.maxStreak >= 3) {
      newlyUnlocked.push(this.unlockAchievement('streaker'));
    }

    if (!this.achievements.hotStreak.unlocked && gameData.maxStreak >= 5) {
      newlyUnlocked.push(this.unlockAchievement('hotStreak'));
    }

    if (!this.achievements.unstoppable.unlocked && globalStats.maxStreakOverall >= 10) {
      newlyUnlocked.push(this.unlockAchievement('unstoppable'));
    }

    // Game mode achievements
    if (!this.achievements.speedDemon.unlocked && gameData.mode === 'blitz' && gameData.finalScore >= 400) {
      newlyUnlocked.push(this.unlockAchievement('speedDemon'));
    }

    if (!this.achievements.marathoner.unlocked && gameData.mode === 'marathon') {
      newlyUnlocked.push(this.unlockAchievement('marathoner'));
    }

    // Check all-rounder
    if (!this.achievements.allRounder.unlocked && globalStats.modesPlayed?.length >= 4) {
      newlyUnlocked.push(this.unlockAchievement('allRounder'));
    }

    // Category achievements
    if (gameData.categories?.length === 1 && gameData.finalScore >= 400) {
      const category = gameData.categories[0];
      if (!this.achievements.geographer.unlocked && category === 'geography') {
        newlyUnlocked.push(this.unlockAchievement('geographer'));
      }
      if (!this.achievements.historian.unlocked && category === 'history') {
        newlyUnlocked.push(this.unlockAchievement('historian'));
      }
      if (!this.achievements.scientist.unlocked && category === 'science') {
        newlyUnlocked.push(this.unlockAchievement('scientist'));
      }
    }

    // Participation achievements
    if (!this.achievements.newbie.unlocked && globalStats.gamesPlayed === 1) {
      newlyUnlocked.push(this.unlockAchievement('newbie'));
    }

    if (!this.achievements.regular.unlocked && globalStats.gamesPlayed >= 10) {
      newlyUnlocked.push(this.unlockAchievement('regular'));
    }

    if (!this.achievements.veteran.unlocked && globalStats.gamesPlayed >= 50) {
      newlyUnlocked.push(this.unlockAchievement('veteran'));
    }

    if (!this.achievements.dedicated.unlocked && globalStats.gamesPlayed >= 100) {
      newlyUnlocked.push(this.unlockAchievement('dedicated'));
    }

    // Special achievements
    if (!this.achievements.quickthinker.unlocked) {
      const hasQuickAnswer = gameData.answers?.some(answer => 
        answer.timeLeft >= 9 && answer.score >= 100
      );
      if (hasQuickAnswer) {
        newlyUnlocked.push(this.unlockAchievement('quickthinker'));
      }
    }

    if (!this.achievements.comeback.unlocked && gameData.answers?.length >= 4) {
      const firstTwoAvg = (gameData.answers[0]?.score + gameData.answers[1]?.score) / 2;
      const lastScore = gameData.answers[gameData.answers.length - 1]?.score;
      if (firstTwoAvg < 40 && lastScore >= 100) {
        newlyUnlocked.push(this.unlockAchievement('comeback'));
      }
    }

    if (newlyUnlocked.length > 0) {
      this.saveAchievements();
    }

    return newlyUnlocked;
  }

  unlockAchievement(achievementId) {
    if (this.achievements[achievementId]) {
      this.achievements[achievementId].unlocked = true;
      return this.achievements[achievementId];
    }
    return null;
  }

  getUnlockedAchievements() {
    return Object.values(this.achievements).filter(achievement => achievement.unlocked);
  }

  getAchievementsByCategory(category) {
    return Object.values(this.achievements).filter(achievement => achievement.category === category);
  }

  getProgressSummary() {
    const total = Object.keys(this.achievements).length;
    const unlocked = this.getUnlockedAchievements().length;
    return {
      total,
      unlocked,
      percentage: Math.round((unlocked / total) * 100)
    };
  }

  getAllAchievements() {
    return this.achievements;
  }
}

// Export singleton instance
const achievementSystem = new AchievementSystem();
export default achievementSystem;
