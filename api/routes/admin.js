// File: api/routes/admin.js
// Description: Admin-only API to view and manage raw data and users

const express = require('express');
const dbPromise = require('../db-switcher');
const adminAuth = require('../middleware/admin');
const router = express.Router();

// --- GET /api/admin/users ---
// List all users (admin only)
router.get('/users', adminAuth, async (req, res) => {
    try {
        const db = await dbPromise;
        const [users] = await db.query('SELECT user_id, username, email, role, created_at FROM users ORDER BY created_at DESC');
        res.json(users);
    } catch (err) {
        console.error('Admin: error listing users', err);
        res.status(500).json({ message: 'Server error.' });
    }
});

// --- PUT /api/admin/users/:id/role ---
// Update a user's role
router.put('/users/:id/role', adminAuth, async (req, res) => {
    try {
        const db = await dbPromise;
        const userId = parseInt(req.params.id);
        const { role } = req.body;
        if (!['user', 'admin'].includes(role)) return res.status(400).json({ message: 'Invalid role.' });
        await db.query('UPDATE users SET role = ? WHERE user_id = ?', [role, userId]);
        res.json({ message: 'Role updated.' });
    } catch (err) {
        console.error('Admin: error updating role', err);
        res.status(500).json({ message: 'Server error.' });
    }
});

// --- DELETE /api/admin/users/:id ---
// Delete a user
router.delete('/users/:id', adminAuth, async (req, res) => {
    try {
        const db = await dbPromise;
        const userId = parseInt(req.params.id);
        await db.query('DELETE FROM users WHERE user_id = ?', [userId]);
        res.json({ message: 'User deleted.' });
    } catch (err) {
        console.error('Admin: error deleting user', err);
        res.status(500).json({ message: 'Server error.' });
    }
});

// --- GET /api/admin/questions ---
// List raw questions with optional filters
router.get('/questions', adminAuth, async (req, res) => {
    try {
        const db = await dbPromise;
        const { category, difficulty, limit = 100 } = req.query;
        let sql = 'SELECT question_id, question_text, correct_answer, category, difficulty, created_at FROM questions';
        const params = [];
        const where = [];
        if (category) { where.push('category = ?'); params.push(category); }
        if (difficulty) { where.push('difficulty = ?'); params.push(difficulty); }
        if (where.length) sql += ' WHERE ' + where.join(' AND ');
        sql += ' ORDER BY created_at DESC LIMIT ?'; params.push(parseInt(limit));
        const [rows] = await db.query(sql, params);
        res.json(rows);
    } catch (err) {
        console.error('Admin: error listing questions', err);
        res.status(500).json({ message: 'Server error.' });
    }
});

// --- POST /api/admin/questions ---
// Create a new question (raw insert)
router.post('/questions', adminAuth, async (req, res) => {
    try {
        const db = await dbPromise;
        const { question_text, correct_answer, category = 'general', difficulty = 'medium' } = req.body;
        if (!question_text || correct_answer === undefined) return res.status(400).json({ message: 'Missing fields.' });
        await db.query('INSERT INTO questions (question_text, correct_answer, category, difficulty) VALUES (?, ?, ?, ?)', [question_text, correct_answer, category, difficulty]);
        res.status(201).json({ message: 'Question created.' });
    } catch (err) {
        console.error('Admin: error creating question', err);
        res.status(500).json({ message: 'Server error.' });
    }
});

// --- POST /api/admin/questions/import ---
// Bulk import questions from JSON array [{question_text, correct_answer, category, difficulty}, ...]
router.post('/questions/import', adminAuth, async (req, res) => {
    try {
        const db = await dbPromise;
        const items = req.body;
        if (!Array.isArray(items)) return res.status(400).json({ message: 'Expected an array of questions.' });

        // Insert in transaction for safety (MySQL)
        const conn = await db.getConnection();
        try {
            await conn.beginTransaction();
            const insertSql = 'INSERT INTO questions (question_text, correct_answer, category, difficulty) VALUES (?, ?, ?, ?)';
            for (const it of items) {
                await conn.query(insertSql, [it.question_text, it.correct_answer, it.category || 'general', it.difficulty || 'medium']);
            }
            await conn.commit();
        } catch (err) {
            await conn.rollback();
            throw err;
        } finally {
            conn.release();
        }

        res.json({ message: `Imported ${items.length} questions.` });
    } catch (err) {
        console.error('Admin: error importing questions', err);
        res.status(500).json({ message: 'Server error.' });
    }
});

// --- PUT /api/admin/questions/:id ---
// Update a question
router.put('/questions/:id', adminAuth, async (req, res) => {
    try {
        const db = await dbPromise;
        const qid = parseInt(req.params.id);
        const { question_text, correct_answer, category, difficulty } = req.body;
        await db.query('UPDATE questions SET question_text = ?, correct_answer = ?, category = ?, difficulty = ? WHERE question_id = ?', [question_text, correct_answer, category, difficulty, qid]);
        res.json({ message: 'Question updated.' });
    } catch (err) {
        console.error('Admin: error updating question', err);
        res.status(500).json({ message: 'Server error.' });
    }
});

// --- DELETE /api/admin/questions/:id ---
router.delete('/questions/:id', adminAuth, async (req, res) => {
    try {
        const db = await dbPromise;
        const qid = parseInt(req.params.id);
        await db.query('DELETE FROM questions WHERE question_id = ?', [qid]);
        res.json({ message: 'Question deleted.' });
    } catch (err) {
        console.error('Admin: error deleting question', err);
        res.status(500).json({ message: 'Server error.' });
    }
});

module.exports = router;
