// File: app.js
// Description: Main server file for the 11Seconds Quiz Game

const express = require('express');
const cors = require('cors');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3011;

// Middleware
app.use(cors());
app.use(express.json());

// Serve static files from httpdocs folder for frontend
app.use(express.static(path.join(__dirname, 'httpdocs')));

// Initialize database connection with smart switcher
let db;
async function initializeDatabase() {
    try {
        console.log('🔄 Initializing database connection...');
        const dbPromise = require('./api/db-switcher');
        db = await dbPromise;

        const dbInfo = dbPromise.getDatabaseInfo();
        console.log(`✅ Database connected successfully!`);
        console.log(`   Type: ${dbInfo.type}`);
        console.log(`   Mode: ${dbInfo.isProduction ? 'Production (MySQL)' : 'Development (SQLite)'}`);

        // Make database available to routes
        app.locals.db = db;
        app.locals.dbInfo = dbInfo;

        return db;
    } catch (error) {
        console.error('❌ Database initialization failed:', error);
        process.exit(1);
    }
}

// Import routes
const authRoutes = require('./api/routes/auth');
const gameRoutes = require('./api/routes/game');
const adminRoutes = require('./api/routes/admin');

// API Routes
app.use('/api/auth', authRoutes);
app.use('/api/game', gameRoutes);
app.use('/api/admin', adminRoutes);

// Catch-all handler: send back React's index.html file for client-side routing
app.get('*', (req, res) => {
    res.sendFile(path.join(__dirname, 'httpdocs', 'index.html'));
});

// Start server after database is initialized
async function startServer() {
    try {
        await initializeDatabase();

        app.listen(PORT, () => {
            console.log(`🚀 Server is running on port ${PORT}`);
            console.log(`📱 Frontend: http://localhost:${PORT}`);
            console.log(`🔗 API: http://localhost:${PORT}/api`);
            console.log(`🎮 Game ready to play!`);
        });
    } catch (error) {
        console.error('❌ Failed to start server:', error);
        process.exit(1);
    }
}

// Handle graceful shutdown
process.on('SIGTERM', () => {
    console.log('⏹️  Received SIGTERM. Shutting down gracefully...');
    process.exit(0);
});

process.on('SIGINT', () => {
    console.log('⏹️  Received SIGINT. Shutting down gracefully...');
    process.exit(0);
});

startServer();
