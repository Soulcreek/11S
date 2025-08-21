// File: api/db-local.js
// Description: Local SQLite database fallback for development

const sqlite3 = require('sqlite3').verbose();
const path = require('path');

// Create or connect to local SQLite database
const dbPath = path.join(__dirname, '../local-database.db');
const db = new sqlite3.Database(dbPath);

// Initialize tables
db.serialize(() => {
    // Users table
    db.run(`CREATE TABLE IF NOT EXISTS users (
        user_id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )`);

    // Questions table
    db.run(`CREATE TABLE IF NOT EXISTS questions (
        question_id INTEGER PRIMARY KEY AUTOINCREMENT,
        question_text TEXT NOT NULL,
        correct_answer DECIMAL(15,2) NOT NULL,
        category TEXT DEFAULT 'general',
        difficulty TEXT DEFAULT 'medium',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )`);

    // Solo games table
    db.run(`CREATE TABLE IF NOT EXISTS solo_games (
        game_id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        final_score INTEGER,
        played_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    )`);

    // Highscores table
    db.run(`CREATE TABLE IF NOT EXISTS highscores (
        user_id INTEGER PRIMARY KEY,
        score INTEGER,
        achieved_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    )`);

    // Insert sample questions if none exist
    db.get("SELECT COUNT(*) as count FROM questions", (err, row) => {
        if (!err && row.count === 0) {
            console.log('Adding sample questions to local database...');

            const questions = [
                ["Wie hoch ist der Mount Everest in Metern?", 8848, "geography"],
                ["Wie viele Einwohner hat Berlin (in Millionen)?", 3.7, "geography"],
                ["In welchem Jahr begann der Zweite Weltkrieg?", 1939, "history"],
                ["Wie schnell ist die Lichtgeschwindigkeit (km/s)?", 299792458, "science"],
                ["Wie viele Knochen hat der menschliche Körper?", 206, "science"],
                ["Wie lang ist der Äquator der Erde (in km)?", 40075, "geography"],
                ["In welchem Jahr wurde die Berliner Mauer gebaut?", 1961, "history"],
                ["Wie hoch kann ein Känguru springen (in Metern)?", 3, "nature"],
                ["Wie viele Herzen hat ein Oktopus?", 3, "nature"],
                ["Wie alt wurde Mozart (Jahre)?", 35, "history"],
                ["Wie viele Kontinente gibt es?", 7, "geography"],
                ["In welchem Jahr landeten Menschen zum ersten Mal auf dem Mond?", 1969, "history"],
                ["Wie viele Zähne hat ein erwachsener Mensch normalerweise?", 32, "science"],
                ["Wie tief ist der Marianengraben (in Metern)?", 11034, "geography"],
                ["Wie viele Saiten hat eine Gitarre normalerweise?", 6, "music"]
            ];

            const stmt = db.prepare("INSERT INTO questions (question_text, correct_answer, category) VALUES (?, ?, ?)");
            questions.forEach(q => stmt.run(q));
            stmt.finalize();

            console.log(`Added ${questions.length} questions to local database`);
        }
    });
});

// Promise wrapper for SQLite
const query = (sql, params = []) => {
    return new Promise((resolve, reject) => {
        if (sql.toUpperCase().startsWith('SELECT')) {
            db.all(sql, params, (err, rows) => {
                if (err) reject(err);
                else resolve([rows]); // Return in MySQL2 format [rows, fields]
            });
        } else {
            db.run(sql, params, function (err) {
                if (err) reject(err);
                else resolve([{ insertId: this.lastID, affectedRows: this.changes }]);
            });
        }
    });
};

module.exports = { query };

console.log('Local SQLite database initialized at:', dbPath);
