// File: api/routes/game.js
// Description: Handles game logic routes like fetching questions and submitting scores.

const express = require('express');
const db = require('../db');
const auth = require('../middleware/auth'); // Import the auth middleware

const router = express.Router();

// --- GET /api/game/questions ---
// Fetches 5 random questions for a new solo game.
// This is a protected route, so we add the 'auth' middleware.
router.get('/questions', auth, async (req, res) => {
    try {
        // SQL query to select 5 random questions from the table
        const [questions] = await db.query(
            'SELECT question_id, question_text, correct_answer FROM questions ORDER BY RAND() LIMIT 5'
        );
        res.json(questions);
    } catch (error) {
        console.error('Error fetching questions:', error);
        res.status(500).json({ message: 'Server error while fetching questions.' });
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
        // 1. Save the game result to the solo_games table
        await db.query(
            'INSERT INTO solo_games (user_id, final_score) VALUES (?, ?)',
            [userId, finalScore]
        );

        // 2. Check and update the highscore table
        // We use INSERT ... ON DUPLICATE KEY UPDATE to handle this in a single, efficient query.
        // This requires a UNIQUE key on the user_id column in the highscores table, which we have.
        const sql = `
            INSERT INTO highscores (user_id, score)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE score = IF(VALUES(score) > score, VALUES(score), score);
        `;
        await db.query(sql, [userId, finalScore]);

        res.status(200).json({ message: 'Game score submitted successfully.' });

    } catch (error) {
        console.error('Error submitting score:', error);
        res.status(500).json({ message: 'Server error while submitting score.' });
    }
});

module.exports = router;
