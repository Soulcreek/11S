// File: web/src/components/GameSetup.js
// Description: Game setup component for mode, category, and difficulty selection

import React, { useState, useEffect } from 'react';
import extraQuestions from '../data/extraQuestions';

const GameSetup = ({ onStartGame, onBack }) => {
  const [gameMode, setGameMode] = useState('klassisch');
  const [selectedCategories, setSelectedCategories] = useState(['all']);
  const [selectedDifficulty, setSelectedDifficulty] = useState('all');
  const [availableCategories, setAvailableCategories] = useState([]);

  // Extract available categories from questions
  useEffect(() => {
    const categories = [...new Set(extraQuestions.map(q => q.category))];
    setAvailableCategories(categories);
  }, []);

  const gameModes = {
    klassisch: { name: 'Klassisch', questions: 5, timePerQuestion: 11, description: '5 Fragen, 11 Sekunden' },
    marathon: { name: 'Marathon', questions: 20, timePerQuestion: 11, description: '20 Fragen, 11 Sekunden' },
    blitz: { name: 'Blitz', questions: 3, timePerQuestion: 5, description: '3 Fragen, 5 Sekunden' },
    kategorien: { name: 'Kategorien-Challenge', questions: 10, timePerQuestion: 11, description: '10 Fragen, 11 Sekunden' }
  };

  const difficulties = ['easy', 'medium', 'hard'];

  const handleCategoryChange = (category) => {
    if (category === 'all') {
      setSelectedCategories(['all']);
    } else {
      setSelectedCategories(prev => {
        const newCategories = prev.includes('all') ? [category] : 
          prev.includes(category) ? prev.filter(c => c !== category) :
          [...prev, category];
        return newCategories.length === 0 ? ['all'] : newCategories;
      });
    }
  };

  const handleStartGame = () => {
    const config = {
      mode: gameMode,
      categories: selectedCategories,
      difficulty: selectedDifficulty,
      ...gameModes[gameMode]
    };
    onStartGame(config);
  };

  const getQuestionCount = () => {
    let filteredQuestions = extraQuestions;
    
    if (!selectedCategories.includes('all')) {
      filteredQuestions = filteredQuestions.filter(q => selectedCategories.includes(q.category));
    }
    
    if (selectedDifficulty !== 'all') {
      filteredQuestions = filteredQuestions.filter(q => q.difficulty === selectedDifficulty);
    }
    
    return filteredQuestions.length;
  };

  return (
    <div className="game-setup-container">
      <div className="game-setup-card">
        <h2>🎮 Spiel Konfiguration</h2>
        
        {/* Game Mode Selection */}
        <div className="setup-section">
          <h3>Spielmodus</h3>
          <div className="mode-grid">
            {Object.entries(gameModes).map(([key, mode]) => (
              <div 
                key={key}
                className={`mode-card ${gameMode === key ? 'selected' : ''}`}
                onClick={() => setGameMode(key)}
              >
                <h4>{mode.name}</h4>
                <p>{mode.description}</p>
              </div>
            ))}
          </div>
        </div>

        {/* Category Selection */}
        <div className="setup-section">
          <h3>Kategorien</h3>
          <div className="category-grid">
            <button
              className={`category-btn ${selectedCategories.includes('all') ? 'selected' : ''}`}
              onClick={() => handleCategoryChange('all')}
            >
              Alle Kategorien
            </button>
            {availableCategories.map(category => (
              <button
                key={category}
                className={`category-btn ${selectedCategories.includes(category) ? 'selected' : ''}`}
                onClick={() => handleCategoryChange(category)}
              >
                {category === 'geography' ? 'Geografie' :
                 category === 'history' ? 'Geschichte' :
                 category === 'science' ? 'Wissenschaft' :
                 category === 'nature' ? 'Natur' :
                 category === 'sports' ? 'Sport' :
                 category === 'technology' ? 'Technologie' :
                 category === 'music' ? 'Musik' :
                 category === 'literature' ? 'Literatur' :
                 category.charAt(0).toUpperCase() + category.slice(1)}
              </button>
            ))}
          </div>
        </div>

        {/* Difficulty Selection */}
        <div className="setup-section">
          <h3>Schwierigkeitsgrad</h3>
          <div className="difficulty-grid">
            <button
              className={`difficulty-btn ${selectedDifficulty === 'all' ? 'selected' : ''}`}
              onClick={() => setSelectedDifficulty('all')}
            >
              Alle Schwierigkeiten
            </button>
            {difficulties.map(difficulty => (
              <button
                key={difficulty}
                className={`difficulty-btn ${selectedDifficulty === difficulty ? 'selected' : ''}`}
                onClick={() => setSelectedDifficulty(difficulty)}
              >
                {difficulty === 'easy' ? 'Einfach' :
                 difficulty === 'medium' ? 'Mittel' :
                 difficulty === 'hard' ? 'Schwer' :
                 difficulty.charAt(0).toUpperCase() + difficulty.slice(1)}
              </button>
            ))}
          </div>
        </div>

        {/* Question Pool Info */}
        <div className="setup-section">
          <div className="question-pool-info">
            <p>📊 Verfügbare Fragen: <strong>{getQuestionCount()}</strong></p>
            <p>🎯 Gesamte Datenbank: <strong>{extraQuestions.length}</strong> Fragen</p>
          </div>
        </div>

        {/* Action Buttons */}
        <div className="setup-actions">
          <button className="btn-secondary" onClick={onBack}>
            ← Zurück
          </button>
          <button 
            className="btn-primary" 
            onClick={handleStartGame}
            disabled={getQuestionCount() < gameModes[gameMode].questions}
          >
            Spiel Starten 🚀
          </button>
        </div>
      </div>

      <style jsx>{`
        .game-setup-container {
          min-height: 100vh;
          background: linear-gradient(135deg, #10b981 0%, #059669 100%);
          display: flex;
          justify-content: center;
          align-items: center;
          padding: 20px;
        }

        .game-setup-card {
          background: white;
          border-radius: 20px;
          padding: 30px;
          box-shadow: 0 20px 40px rgba(0,0,0,0.1);
          max-width: 800px;
          width: 100%;
          max-height: 90vh;
          overflow-y: auto;
        }

        .game-setup-card h2 {
          text-align: center;
          color: #333;
          margin-bottom: 30px;
          font-size: 2rem;
        }

        .setup-section {
          margin-bottom: 25px;
        }

        .setup-section h3 {
          color: #555;
          margin-bottom: 15px;
          font-size: 1.2rem;
          border-bottom: 2px solid #f0f0f0;
          padding-bottom: 8px;
        }

        .mode-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
          gap: 15px;
        }

        .mode-card {
          border: 2px solid #e0e0e0;
          border-radius: 12px;
          padding: 20px;
          text-align: center;
          cursor: pointer;
          transition: all 0.3s ease;
          background: #f9f9f9;
        }

        .mode-card:hover {
          border-color: #10b981;
          transform: translateY(-2px);
          box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .mode-card.selected {
          border-color: #10b981;
          background: linear-gradient(135deg, #10b981, #059669);
          color: white;
        }

        .mode-card h4 {
          margin: 0 0 8px 0;
          font-size: 1.1rem;
        }

        .mode-card p {
          margin: 0;
          font-size: 0.9rem;
          opacity: 0.8;
        }

        .category-grid, .difficulty-grid {
          display: flex;
          flex-wrap: wrap;
          gap: 10px;
        }

        .category-btn, .difficulty-btn {
          padding: 12px 20px;
          border: 2px solid #e0e0e0;
          border-radius: 25px;
          background: #f9f9f9;
          cursor: pointer;
          transition: all 0.3s ease;
          font-size: 0.9rem;
        }

        .category-btn:hover, .difficulty-btn:hover {
          border-color: #10b981;
          background: #f0f4ff;
        }

        .category-btn.selected, .difficulty-btn.selected {
          background: linear-gradient(135deg, #10b981, #059669);
          color: white;
          border-color: #10b981;
        }

        .question-pool-info {
          background: #f8f9ff;
          border-radius: 12px;
          padding: 20px;
          text-align: center;
          border: 1px solid #e0e6ff;
        }

        .question-pool-info p {
          margin: 5px 0;
          color: #555;
        }

        .setup-actions {
          display: flex;
          gap: 15px;
          justify-content: space-between;
          margin-top: 30px;
        }

        .btn-primary, .btn-secondary {
          padding: 15px 30px;
          border: none;
          border-radius: 12px;
          font-size: 1rem;
          font-weight: bold;
          cursor: pointer;
          transition: all 0.3s ease;
          flex: 1;
        }

        .btn-primary {
          background: linear-gradient(135deg, #10b981, #059669);
          color: white;
        }

        .btn-primary:hover:not(:disabled) {
          transform: translateY(-2px);
          box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:disabled {
          background: #ccc;
          cursor: not-allowed;
        }

        .btn-secondary {
          background: #f0f0f0;
          color: #555;
        }

        .btn-secondary:hover {
          background: #e0e0e0;
        }

        @media (max-width: 768px) {
          .game-setup-card {
            padding: 20px;
            margin: 10px;
          }
          
          .mode-grid {
            grid-template-columns: 1fr;
          }
          
          .setup-actions {
            flex-direction: column;
          }
        }
      `}</style>
    </div>
  );
};

export default GameSetup;
