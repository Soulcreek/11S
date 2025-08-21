// File: api/routes/auth.js
// Description: Authentication routes for login and registration with smart database switcher

const express = require('express');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const router = express.Router();

// Get database from app locals (set by app.js)
function getDb(req) {
    return req.app.locals.db;
}

// Register endpoint
router.post('/register', async (req, res) => {
    try {
        const { username, email, password } = req.body;

        // Input validation
        if (!username || !email || !password) {
            return res.status(400).json({
                error: 'Username, email, and password are required'
            });
        }

        if (password.length < 6) {
            return res.status(400).json({
                error: 'Password must be at least 6 characters long'
            });
        }

        const db = getDb(req);

        // Check if user already exists
        const [existingUsers] = await db.query(
            'SELECT * FROM users WHERE username = ? OR email = ?',
            [username, email]
        );

        if (existingUsers.length > 0) {
            return res.status(400).json({
                error: 'Username or email already exists'
            });
        }

        // Hash password and create user
        const hashedPassword = await bcrypt.hash(password, 10);

        const [result] = await db.query(
            'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)',
            [username, email, hashedPassword]
        );

        // Generate JWT token
        const token = jwt.sign(
            {
                userId: result.insertId,
                username: username
            },
            process.env.JWT_SECRET || 'fallback-secret-key',
            { expiresIn: '24h' }
        );

        res.status(201).json({
            message: 'User registered successfully',
            token: token,
            user: {
                id: result.insertId,
                username: username,
                email: email
            }
        });

    } catch (error) {
        console.error('Registration error:', error);
        res.status(500).json({ error: 'Server error during registration' });
    }
});

// Login endpoint
router.post('/login', async (req, res) => {
    try {
        const { username, password } = req.body;

        // Input validation
        if (!username || !password) {
            return res.status(400).json({
                error: 'Username and password are required'
            });
        }

        const db = getDb(req);

        // Find user by username or email
        const [users] = await db.query(
            'SELECT * FROM users WHERE username = ? OR email = ?',
            [username, username]
        );

        if (users.length === 0) {
            return res.status(401).json({
                error: 'Invalid username or password'
            });
        }

        const user = users[0];

        // Check password
        const isValidPassword = await bcrypt.compare(password, user.password_hash);

        if (!isValidPassword) {
            return res.status(401).json({
                error: 'Invalid username or password'
            });
        }

        // Generate JWT token
        const token = jwt.sign(
            {
                userId: user.user_id,
                username: user.username
            },
            process.env.JWT_SECRET || 'fallback-secret-key',
            { expiresIn: '24h' }
        );

        res.json({
            message: 'Login successful',
            token: token,
            user: {
                id: user.user_id,
                username: user.username,
                email: user.email
            }
        });

    } catch (error) {
        console.error('Login error:', error);
        res.status(500).json({ error: 'Server error during login' });
    }
});

module.exports = router;
