const sqlite3 = require('sqlite3').verbose();
const path = require('path');
const dbPath = path.join(__dirname, '..', '..', 'local-database.db');

const db = new sqlite3.Database(dbPath, sqlite3.OPEN_READONLY, (err) => {
    if (err) {
        console.error('Could not open sqlite db:', err.message);
        process.exit(2);
    }
    db.get('SELECT COUNT(*) as count FROM questions', (err, row) => {
        if (err) {
            console.error('Query failed:', err.message);
            process.exit(2);
        }
        console.log('SQLite questions count:', row.count);
        process.exit(0);
    });
});
