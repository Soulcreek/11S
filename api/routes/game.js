// File: api/routes/game.js
// Description: Handles game logic routes like fetching questions and submitting scores.

const express = require('express');
const dbPromise = require('../db-switcher'); // Use the database switcher
const auth = require('../middleware/auth'); // Import the auth middleware

const router = express.Router();

// --- GET /api/game/questions ---
// Fetches 5 random questions for a new solo game.
// Supports optional category and difficulty filters via query parameters
// This is a protected route, so we add the 'auth' middleware.
router.get('/questions', auth, async (req, res) => {
    try {
        const db = await dbPromise; // Wait for database initialization

        // Extract query parameters for filtering
        const { category, difficulty, count = 5 } = req.query;

        // Build dynamic SQL query based on filters
        let sql = 'SELECT question_id, question_text, correct_answer, category, difficulty FROM questions';
        let params = [];
        let whereConditions = [];

        if (category && category !== 'all') {
            whereConditions.push('category = ?');
            params.push(category);
        }

        if (difficulty && difficulty !== 'all') {
            whereConditions.push('difficulty = ?');
            params.push(difficulty);
        }

        if (whereConditions.length > 0) {
            sql += ' WHERE ' + whereConditions.join(' AND ');
        }

        sql += ' ORDER BY RANDOM() LIMIT ?';
        params.push(parseInt(count));

        const [questions] = await db.query(sql, params);

        // If we don't have enough questions with the specified filters, 
        // fall back to random questions
        if (questions.length < parseInt(count)) {
            console.log(`⚠️ Only ${questions.length} questions found for filters. Falling back to random selection.`);
            const [fallbackQuestions] = await db.query(
                'SELECT question_id, question_text, correct_answer, category, difficulty FROM questions ORDER BY RANDOM() LIMIT ?',
                [parseInt(count)]
            );
            res.json(fallbackQuestions);
        } else {
            res.json(questions);
        }

    } catch (error) {
        console.error('Error fetching questions:', error);
        res.status(500).json({ message: 'Server error while fetching questions.' });
    }
});

// --- GET /api/game/categories ---
// Fetches available categories and their question counts
router.get('/categories', auth, async (req, res) => {
    try {
        const db = await dbPromise;

        const [categories] = await db.query(`
            SELECT 
                category,
                difficulty,
                COUNT(*) as count
            FROM questions 
            GROUP BY category, difficulty
            ORDER BY category, difficulty
        `);

        // Transform into a more usable format
        const categoryMap = {};
        categories.forEach(cat => {
            if (!categoryMap[cat.category]) {
                categoryMap[cat.category] = {
                    total: 0,
                    difficulties: {}
                };
            }
            categoryMap[cat.category].difficulties[cat.difficulty] = cat.count;
            categoryMap[cat.category].total += cat.count;
        });

        res.json(categoryMap);

    } catch (error) {
        console.error('Error fetching categories:', error);
        res.status(500).json({ message: 'Server error while fetching categories.' });
    }
});

// --- GET /api/game/difficulties ---
// Fetches available difficulty levels and their question counts
router.get('/difficulties', auth, async (req, res) => {
    try {
        const db = await dbPromise;

        const [difficulties] = await db.query(`
            SELECT 
                difficulty,
                COUNT(*) as total_questions,
                COUNT(DISTINCT category) as categories_count
            FROM questions 
            GROUP BY difficulty
            ORDER BY 
                CASE difficulty 
                    WHEN 'easy' THEN 1 
                    WHEN 'medium' THEN 2 
                    WHEN 'hard' THEN 3 
                    ELSE 4 
                END
        `);

        res.json(difficulties);

    } catch (error) {
        console.error('Error fetching difficulties:', error);
        res.status(500).json({ message: 'Server error while fetching difficulties.' });
    }
});

// --- POST /api/game/submit-solo ---
// Submits the final score of a solo game.
// This is also a protected route.
router.post('/submit-solo', auth, async (req, res) => {
    const { finalScore } = req.body;
    const userId = req.user.id; // Get user ID from the decoded token (thanks to the middleware)

    if (finalScore === undefined || finalScore === null) {
        return res.status(400).json({ message: 'finalScore is required.' });
    }

    try {
        const db = await dbPromise; // Wait for database initialization

        // 1. Save the game result to the solo_games table
        await db.query(
            'INSERT INTO solo_games (user_id, final_score) VALUES (?, ?)',
            [userId, finalScore]
        );

        // 2. Check and update the highscore table
        // For SQLite, we need to use a different approach than MySQL's ON DUPLICATE KEY UPDATE
        const [existingScore] = await db.query(
            'SELECT score FROM highscores WHERE user_id = ?',
            [userId]
        );

        if (existingScore.length > 0) {
            // Update if new score is higher
            if (finalScore > existingScore[0].score) {
                await db.query(
                    'UPDATE highscores SET score = ?, achieved_at = CURRENT_TIMESTAMP WHERE user_id = ?',
                    [finalScore, userId]
                );
            }
        } else {
            // Insert new highscore
            await db.query(
                'INSERT INTO highscores (user_id, score) VALUES (?, ?)',
                [userId, finalScore]
            );
        }

        res.status(200).json({ message: 'Game score submitted successfully.' });

    } catch (error) {
        console.error('Error submitting score:', error);
        res.status(500).json({ message: 'Server error while submitting score.' });
    }
});

module.exports = router;
