// File: api/db-switcher.js
// Description: Smart database switcher - MySQL first (for production), SQLite fallback (for development)

const path = require('path');
require('dotenv').config({ path: path.resolve(__dirname, '../.env') });

let db;
let usingMySQL = false;

// Try MySQL first, fallback to SQLite if MySQL is not available
async function initializeDatabase() {
    try {
        console.log('🔄 Attempting MySQL connection (for Netcup production)...');

        // Try MySQL connection with Netcup-optimized settings
        const mysql = require('mysql2');

        const connectionConfig = {
            host: process.env.DB_HOST,
            port: parseInt(process.env.DB_PORT) || 3306,
            user: process.env.DB_USER,
            password: process.env.DB_PASS,
            database: process.env.DB_NAME,
            charset: 'utf8mb4',
            ssl: false, // Netcup meist ohne SSL
            connectTimeout: 30000,
            acquireTimeout: 30000,
            timeout: 30000,
            waitForConnections: true,
            connectionLimit: 5, // Reduziert für Shared Hosting
            queueLimit: 0,
            reconnect: true
        };

        const pool = mysql.createPool(connectionConfig);
        const promisePool = pool.promise();

        // Test the connection with timeout
        const testConnection = new Promise((resolve, reject) => {
            setTimeout(() => reject(new Error('Connection timeout after 15 seconds')), 15000);
            promisePool.query('SELECT 1 as test').then(resolve).catch(reject);
        });

        await testConnection;

        console.log('✅ MySQL connection successful! Using MySQL database.');
        usingMySQL = true;

        // Setup MySQL tables if needed
        await setupMySQLTables(promisePool);

        db = promisePool;
        return db;

    } catch (error) {
        console.error('❌ MySQL connection failed:', error.message);
        console.error('� SQLite fallback has been disabled by configuration (no-sqlite mode).');
        console.error('Please ensure MySQL is reachable and environment variables DB_HOST/DB_USER/DB_PASS/DB_NAME are set.');
        // Fail fast: do not fallback to SQLite. Re-throw to prevent the app from starting in an unexpected state.
        throw new Error('MySQL connection failed and SQLite fallback is disabled: ' + error.message);
    }
}

// Setup MySQL tables for production
async function setupMySQLTables(pool) {
    try {
        console.log('🔧 Setting up MySQL tables...');

        // Create users table (add role for admin/user management)
        await pool.query(`
            CREATE TABLE IF NOT EXISTS users (
                user_id INT PRIMARY KEY AUTO_INCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role ENUM('user','admin') DEFAULT 'user',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        `);

        // Create questions table with categories and difficulty
        await pool.query(`
            CREATE TABLE IF NOT EXISTS questions (
                question_id INT PRIMARY KEY AUTO_INCREMENT,
                question_text TEXT NOT NULL,
                correct_answer TEXT NOT NULL,
                category VARCHAR(50) DEFAULT 'general',
                difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_category (category),
                INDEX idx_difficulty (difficulty),
                INDEX idx_category_difficulty (category, difficulty)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        `);

        // Create solo_games table
        await pool.query(`
            CREATE TABLE IF NOT EXISTS solo_games (
                game_id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                final_score INT NOT NULL,
                played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_played_at (played_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        `);

        // Create highscores table
        await pool.query(`
            CREATE TABLE IF NOT EXISTS highscores (
                user_id INT PRIMARY KEY,
                score INT NOT NULL,
                achieved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                INDEX idx_score (score DESC)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        `);

        console.log('✅ MySQL tables setup completed');

        // Check if we need to add sample questions
        const [questionCount] = await pool.query('SELECT COUNT(*) as count FROM questions');
        if (questionCount[0].count === 0) {
            console.log('📝 Adding sample questions to MySQL...');
            await addSampleQuestions(pool);
        } else {
            console.log(`📊 Found ${questionCount[0].count} questions in MySQL database`);
        }

        // Ensure an admin user exists if environment variables provided
        try {
            const adminEmail = process.env.ADMIN_EMAIL;
            const adminPass = process.env.ADMIN_PASS;
            const adminUsername = process.env.ADMIN_USERNAME || 'admin';
            if (adminEmail && adminPass) {
                const [admins] = await pool.query('SELECT user_id FROM users WHERE role = "admin" LIMIT 1');
                if (admins.length === 0) {
                    console.log('🔐 Creating initial admin user from environment variables');
                    const bcrypt = require('bcryptjs');
                    const salt = await bcrypt.genSalt(10);
                    const passwordHash = await bcrypt.hash(adminPass, salt);
                    await pool.query(
                        'INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)',
                        [adminUsername, adminEmail, passwordHash, 'admin']
                    );
                    console.log('✅ Admin user created');
                } else {
                    console.log('🔑 Admin user already exists');
                }
            }
        } catch (err) {
            console.warn('⚠️ Could not ensure admin user:', err.message);
        }

    } catch (error) {
        console.error('❌ Error setting up MySQL tables:', error);
        throw error;
    }
}

// Add sample questions for production
async function addSampleQuestions(pool) {
    const sampleQuestions = [
        // Geography - Easy
        { text: "Wie viele Kontinente gibt es?", answer: 7, category: "geography", difficulty: "easy" },
        { text: "Welcher ist der längste Fluss der Welt (in km)?", answer: 6650, category: "geography", difficulty: "easy" },
        { text: "Wie viele Bundesländer hat Deutschland?", answer: 16, category: "geography", difficulty: "easy" },

        // Geography - Medium  
        { text: "Wie hoch ist der Mount Everest in Metern?", answer: 8848, category: "geography", difficulty: "medium" },
        { text: "Wie viele Einwohner hat Berlin (in Millionen)?", answer: 3.7, category: "geography", difficulty: "medium" },

        // History - Easy
        { text: "In welchem Jahr begann der Zweite Weltkrieg?", answer: 1939, category: "history", difficulty: "easy" },
        { text: "In welchem Jahr fiel die Berliner Mauer?", answer: 1989, category: "history", difficulty: "easy" },

        // Science - Easy
        { text: "Wie viele Knochen hat der menschliche Körper?", answer: 206, category: "science", difficulty: "easy" },
        { text: "Bei welcher Temperatur gefriert Wasser (°C)?", answer: 0, category: "science", difficulty: "easy" },

        // Technology - Medium
        { text: "In welchem Jahr wurde das iPhone vorgestellt?", answer: 2007, category: "technology", difficulty: "medium" },

        // Sports - Easy
        { text: "Wie viele Spieler hat eine Fußballmannschaft auf dem Feld?", answer: 11, category: "sports", difficulty: "easy" },

        // Music - Easy
        { text: "Wie viele Saiten hat eine Gitarre normalerweise?", answer: 6, category: "music", difficulty: "easy" }
    ];

    for (const question of sampleQuestions) {
        await pool.query(
            'INSERT INTO questions (question_text, correct_answer, category, difficulty) VALUES (?, ?, ?, ?)',
            [question.text, question.answer, question.category, question.difficulty]
        );
    }

    console.log(`✅ Added ${sampleQuestions.length} sample questions to MySQL`);
}

// Export database type info
function getDatabaseInfo() {
    return {
        type: usingMySQL ? 'MySQL' : 'SQLite',
        isProduction: usingMySQL,
        isDevelopment: !usingMySQL
    };
}

// Export a promise that resolves to the database connection
const dbPromise = initializeDatabase();

// Add info method to the promise
dbPromise.getDatabaseInfo = getDatabaseInfo;

module.exports = dbPromise;
