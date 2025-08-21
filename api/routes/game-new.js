// File: api/routes/game.js
// Description: Game routes for questions, scoring, and highscores with smart database switcher

const express = require('express');
const jwt = require('jsonwebtoken');
const router = express.Router();

// Get database from app locals (set by app.js)
function getDb(req) {
    return req.app.locals.db;
}

// Authentication middleware
function authenticateToken(req, res, next) {
    const authHeader = req.headers['authorization'];
    const token = authHeader && authHeader.split(' ')[1];

    if (!token) {
        return res.status(401).json({ error: 'Access token required' });
    }

    jwt.verify(token, process.env.JWT_SECRET || 'fallback-secret-key', (err, user) => {
        if (err) {
            return res.status(403).json({ error: 'Invalid or expired token' });
        }
        req.user = user;
        next();
    });
}

// GET /api/game/questions - Fetch questions with optional filtering
router.get('/questions', authenticateToken, async (req, res) => {
    try {
        const db = getDb(req);

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

        sql += ' ORDER BY RANDOM() LIMIT ?'; // SQLite uses RANDOM(), MySQL uses RAND()
        params.push(parseInt(count));

        // For MySQL compatibility, use RAND() instead of RANDOM()
        const dbInfo = req.app.locals.dbInfo;
        if (dbInfo && dbInfo.type === 'MySQL') {
            sql = sql.replace('RANDOM()', 'RAND()');
        }

        const [questions] = await db.query(sql, params);

        if (questions.length === 0) {
            return res.status(404).json({
                error: 'No questions found with the specified criteria'
            });
        }

        // Remove correct answers from the response to prevent cheating
        const questionsForClient = questions.map(q => ({
            question_id: q.question_id,
            question_text: q.question_text,
            category: q.category,
            difficulty: q.difficulty
        }));

        res.json({
            questions: questionsForClient,
            total: questions.length,
            filters: { category, difficulty }
        });

    } catch (error) {
        console.error('Error fetching questions:', error);
        res.status(500).json({ error: 'Server error fetching questions' });
    }
});

// GET /api/game/categories - Get all available categories
router.get('/categories', async (req, res) => {
    try {
        const db = getDb(req);

        const [categories] = await db.query(
            'SELECT DISTINCT category FROM questions ORDER BY category'
        );

        const categoryList = categories.map(row => row.category);

        res.json({
            categories: categoryList
        });

    } catch (error) {
        console.error('Error fetching categories:', error);
        res.status(500).json({ error: 'Server error fetching categories' });
    }
});

// GET /api/game/difficulties - Get all available difficulty levels
router.get('/difficulties', async (req, res) => {
    try {
        const db = getDb(req);

        const [difficulties] = await db.query(
            'SELECT DISTINCT difficulty FROM questions ORDER BY difficulty'
        );

        const difficultyList = difficulties.map(row => row.difficulty);

        res.json({
            difficulties: difficultyList
        });

    } catch (error) {
        console.error('Error fetching difficulties:', error);
        res.status(500).json({ error: 'Server error fetching difficulties' });
    }
});

// POST /api/game/submit-solo - Submit solo game answers and calculate score
router.post('/submit-solo', authenticateToken, async (req, res) => {
    try {
        const { answers } = req.body; // Array of { questionId, userAnswer }
        const userId = req.user.userId;

        if (!answers || !Array.isArray(answers)) {
            return res.status(400).json({
                error: 'Answers array is required'
            });
        }

        const db = getDb(req);

        let totalScore = 0;
        const detailedResults = [];

        // Calculate score for each question
        for (const answer of answers) {
            const { questionId, userAnswer } = answer;

            // Get the correct answer from database
            const [questions] = await db.query(
                'SELECT correct_answer, question_text FROM questions WHERE question_id = ?',
                [questionId]
            );

            if (questions.length === 0) {
                continue; // Skip invalid question IDs
            }

            const correctAnswer = parseFloat(questions[0].correct_answer);
            const userAnswerNum = parseFloat(userAnswer);

            // Calculate score based on percentage deviation
            let questionScore = 0;
            if (!isNaN(userAnswerNum) && correctAnswer !== 0) {
                const deviation = Math.abs((userAnswerNum - correctAnswer) / correctAnswer);
                questionScore = Math.max(0, Math.round((1 - deviation) * 100));
            }

            totalScore += questionScore;

            detailedResults.push({
                questionId,
                questionText: questions[0].question_text,
                correctAnswer,
                userAnswer: userAnswerNum,
                score: questionScore
            });
        }

        // Save game result to database
        const [gameResult] = await db.query(
            'INSERT INTO solo_games (user_id, final_score) VALUES (?, ?)',
            [userId, totalScore]
        );

        // Update or insert highscore
        const [existingHighscore] = await db.query(
            'SELECT score FROM highscores WHERE user_id = ?',
            [userId]
        );

        if (existingHighscore.length === 0) {
            // Insert new highscore
            await db.query(
                'INSERT INTO highscores (user_id, score) VALUES (?, ?)',
                [userId, totalScore]
            );
        } else if (totalScore > existingHighscore[0].score) {
            // Update existing highscore
            await db.query(
                'UPDATE highscores SET score = ?, achieved_at = CURRENT_TIMESTAMP WHERE user_id = ?',
                [totalScore, userId]
            );
        }

        res.json({
            gameId: gameResult.insertId,
            totalScore,
            maxPossibleScore: answers.length * 100,
            accuracy: Math.round((totalScore / (answers.length * 100)) * 100),
            results: detailedResults
        });

    } catch (error) {
        console.error('Error submitting solo game:', error);
        res.status(500).json({ error: 'Server error submitting game' });
    }
});

// GET /api/game/highscores - Get top highscores
router.get('/highscores', async (req, res) => {
    try {
        const db = getDb(req);

        const [highscores] = await db.query(`
            SELECT u.username, h.score, h.achieved_at 
            FROM highscores h 
            JOIN users u ON h.user_id = u.user_id 
            ORDER BY h.score DESC 
            LIMIT 10
        `);

        res.json({
            highscores: highscores.map((h, index) => ({
                rank: index + 1,
                username: h.username,
                score: h.score,
                achievedAt: h.achieved_at
            }))
        });

    } catch (error) {
        console.error('Error fetching highscores:', error);
        res.status(500).json({ error: 'Server error fetching highscores' });
    }
});

module.exports = router;
