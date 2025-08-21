// File: api/routes/auth.js
// Description: Authentication routes for login and registration

const express = require('express');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const router = express.Router();

// Get database from app locals (set by app.js)
function getDb(req) {
  return req.app.locals.db;
}

// --- POST /api/auth/register ---
router.post('/register', async (req, res) => {
  const { username, email, password } = req.body;

  // Basic validation
  if (!username || !email || !password) {
    return res.status(400).json({ message: 'Please provide username, email, and password.' });
  }

  try {
    const db = await dbPromise; // Wait for database initialization

    // Check if user already exists
    const [existingUsers] = await db.query(
      'SELECT * FROM users WHERE username = ? OR email = ?',
      [username, email]
    );

    if (existingUsers.length > 0) {
      return res.status(409).json({ message: 'Username or email already exists.' });
    }

    // Hash the password
    const salt = await bcrypt.genSalt(10);
    const passwordHash = await bcrypt.hash(password, salt);

    // Insert new user into the database
    await db.query(
      'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)',
      [username, email, passwordHash]
    );

    res.status(201).json({ message: 'User registered successfully.' });

  } catch (error) {
    console.error('Registration error:', error);
    res.status(500).json({ message: 'An error occurred during registration.' });
  }
});

// --- POST /api/auth/login ---
router.post('/login', async (req, res) => {
  const { email, password } = req.body;

  // Basic validation
  if (!email || !password) {
    return res.status(400).json({ message: 'Please provide email and password.' });
  }

  try {
    const db = await dbPromise; // Wait for database initialization

    // Check if user exists
    const [users] = await db.query(
      'SELECT user_id, username, email, password_hash FROM users WHERE email = ?',
      [email]
    );

    if (users.length === 0) {
      return res.status(401).json({ message: 'Invalid email or password.' });
    }

    const user = users[0];

    // Check password
    const isMatch = await bcrypt.compare(password, user.password_hash);
    if (!isMatch) {
      return res.status(401).json({ message: 'Invalid email or password.' });
    }

    // Create JWT token
    const jwt = require('jsonwebtoken');
    const payload = {
      user: {
        id: user.user_id,
        username: user.username,
        email: user.email
      }
    };

    const token = jwt.sign(payload, process.env.JWT_SECRET, { expiresIn: '1h' });

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
    res.status(500).json({ message: 'An error occurred during login.' });
  }
});

module.exports = router;
