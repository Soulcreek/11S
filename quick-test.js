// File: quick-test.js
// Simple test of database switcher

console.log('Starting database test...');

async function quickTest() {
    try {
        const dbPromise = require('./api/db-switcher');
        console.log('Database switcher loaded');

        const db = await dbPromise;
        console.log('Database connection established');

        // Test a simple query
        const [result] = await db.query('SELECT 1 as test');
        console.log('Test query result:', result);

        // Get info about database type
        const info = dbPromise.getDatabaseInfo();
        console.log('Database info:', info);

        console.log('✅ All tests passed!');

    } catch (error) {
        console.error('❌ Test failed:', error);
    }
}

quickTest();
