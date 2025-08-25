/**
 * 11Seconds Server - Express.js Backend
 * Serves the Green Glass Admin Center and Game API
 */

const express = require('express');
const path = require('path');
const fs = require('fs');

const app = express();
// Default ports: primary 3010, secondary 3011 (used for auxiliary/test services)
const PORT = process.env.PORT || process.env.PORT_PRIMARY || 3010;

// Middleware
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(express.static(path.join(__dirname, 'static')));

// CORS for admin API
app.use('/admin/api', (req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*');
    res.header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    res.header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    if (req.method === 'OPTIONS') {
        return res.sendStatus(200);
    }
    next();
});

// Routes

// Root - Serve main game
app.get('/', (req, res) => {
    if (fs.existsSync(path.join(__dirname, 'index.html'))) {
        res.sendFile(path.join(__dirname, 'index.html'));
    } else {
        res.send(`
            <h1>🎮 11Seconds Game Server</h1>
            <p>Server is running on port ${PORT}</p>
            <p><a href="/admin/">Admin Center</a></p>
            <hr>
            <p><em>Green Glass Design System Active</em></p>
        `);
    }
});

// Admin routes (serve static files for now)
app.get('/admin/', (req, res) => {
    const adminIndex = path.join(__dirname, 'admin', 'index.html');
    if (fs.existsSync(adminIndex)) {
        res.sendFile(adminIndex);
    } else {
        res.redirect('/admin/login.html');
    }
});

app.get('/admin/login', (req, res) => {
    const loginPage = path.join(__dirname, 'admin', 'login.html');
    if (fs.existsSync(loginPage)) {
        res.sendFile(loginPage);
    } else {
        res.status(404).send('Admin login page not found');
    }
});

// Serve admin static files
app.use('/admin', express.static(path.join(__dirname, 'admin'), {
    extensions: ['html', 'js', 'css']
}));

// Simple API for admin (mock data for now)
app.get('/admin/api/stats', (req, res) => {
    res.json({
        success: true,
        data: {
            users: 5,
            questions: 42,
            sessions: 128,
            avg_score: 7.3
        }
    });
});

app.get('/admin/api/help', (req, res) => {
    res.json({
        success: true,
        message: '11Seconds Admin API - Green Glass Design System Active',
        version: '2.0',
        endpoints: {
            'GET /admin/api/stats': 'Dashboard statistics',
            'GET /admin/api/help': 'This help message'
        }
    });
});

// Game API (simple example)
app.get('/api/question', (req, res) => {
    const sampleQuestion = {
        id: 1,
        question: "What is the capital of Germany?",
        answers: ["Berlin", "Munich", "Hamburg", "Cologne"],
        correct_answer: "Berlin"
    };
    
    // Shuffle answers
    const shuffled = [...sampleQuestion.answers].sort(() => Math.random() - 0.5);
    
    res.json({
        success: true,
        data: {
            ...sampleQuestion,
            answers: shuffled
        }
    });
});

// Health check
app.get('/health', (req, res) => {
    res.json({ 
        status: 'OK', 
        timestamp: new Date().toISOString(),
        uptime: process.uptime(),
        version: '2.0-green-glass'
    });
});

// 404 handler
app.use((req, res) => {
    res.status(404).json({
        error: 'Not Found',
        path: req.path,
        message: 'The requested resource was not found on this server.'
    });
});

// Error handler
app.use((err, req, res, next) => {
    console.error('Server Error:', err);
    res.status(500).json({
        error: 'Internal Server Error',
        message: process.env.NODE_ENV === 'development' ? err.message : 'Something went wrong!'
    });
});

// Start server
app.listen(PORT, '0.0.0.0', () => {
    console.log(`🚀 11Seconds Server running on port ${PORT}`);
    console.log(`📱 Admin Center: http://localhost:${PORT}/admin/`);
    console.log(`🎮 Game API: http://localhost:${PORT}/api/question`);
    console.log(`🎨 Green Glass Design System Active`);
});

module.exports = app;
