// Script: api/scripts/countQuestions.js
// Purpose: Count rows in questions table using db-switcher (works for MySQL or SQLite fallback)

(async () => {
    try {
        const dbPromise = require('../db-switcher');
        const db = await dbPromise;
        const [rows] = await db.query('SELECT COUNT(*) as count FROM questions');
        console.log('Questions count:', rows[0].count);
    } catch (err) {
        console.error('Failed to count questions:', err.message || err);
        process.exit(2);
    }
})();
