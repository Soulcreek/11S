// File: test-mysql-direct.js
// Description: Direct MySQL connection test for Netcup Webhosting

const mysql = require('mysql2/promise');
require('dotenv').config();

async function testMySQLConnection() {
    console.log('🔍 Testing MySQL connection to Netcup server...');
    console.log(`Host: ${process.env.DB_HOST}`);
    console.log(`Port: ${process.env.DB_PORT}`);
    console.log(`Database: ${process.env.DB_NAME}`);
    console.log(`User: ${process.env.DB_USER}`);

    let connection;

    try {
        // Try to connect with different configurations
        const connectionConfigs = [
            // Standard configuration
            {
                host: process.env.DB_HOST,
                port: process.env.DB_PORT,
                user: process.env.DB_USER,
                password: process.env.DB_PASS,
                database: process.env.DB_NAME,
                connectTimeout: 30000,
                acquireTimeout: 30000,
                timeout: 30000
            },
            // Alternative with SSL disabled
            {
                host: process.env.DB_HOST,
                port: process.env.DB_PORT,
                user: process.env.DB_USER,
                password: process.env.DB_PASS,
                database: process.env.DB_NAME,
                ssl: false,
                connectTimeout: 30000,
                acquireTimeout: 30000,
                timeout: 30000
            },
            // Alternative with charset specified
            {
                host: process.env.DB_HOST,
                port: process.env.DB_PORT,
                user: process.env.DB_USER,
                password: process.env.DB_PASS,
                database: process.env.DB_NAME,
                charset: 'utf8mb4',
                ssl: false,
                connectTimeout: 30000,
                acquireTimeout: 30000,
                timeout: 30000
            }
        ];

        for (let i = 0; i < connectionConfigs.length; i++) {
            console.log(`\n🔄 Trying configuration ${i + 1}...`);

            try {
                connection = await mysql.createConnection(connectionConfigs[i]);
                console.log('✅ Connection established successfully!');

                // Test basic query
                const [result] = await connection.execute('SELECT 1 as test, NOW() as current_time');
                console.log('✅ Test query successful:', result[0]);

                // Check database tables
                const [tables] = await connection.execute('SHOW TABLES');
                console.log('📋 Available tables:', tables.map(t => Object.values(t)[0]));

                // Check if users table exists
                try {
                    const [userTableInfo] = await connection.execute('DESCRIBE users');
                    console.log('👤 Users table structure:', userTableInfo.length, 'columns');
                } catch (error) {
                    console.log('⚠️ Users table does not exist, will need to create it');
                }

                // Check if questions table exists
                try {
                    const [questionTableInfo] = await connection.execute('DESCRIBE questions');
                    console.log('❓ Questions table structure:', questionTableInfo.length, 'columns');

                    // Count questions
                    const [questionCount] = await connection.execute('SELECT COUNT(*) as count FROM questions');
                    console.log('📊 Questions in database:', questionCount[0].count);
                } catch (error) {
                    console.log('⚠️ Questions table does not exist, will need to create it');
                }

                await connection.end();
                console.log('🎉 MySQL connection test completed successfully!');
                return true;

            } catch (error) {
                console.log(`❌ Configuration ${i + 1} failed:`, error.message);
                if (connection) {
                    try { await connection.end(); } catch (e) { }
                }
            }
        }

        console.log('\n❌ All connection attempts failed');
        return false;

    } catch (error) {
        console.error('❌ Unexpected error:', error);
        return false;
    }
}

testMySQLConnection().then(success => {
    process.exit(success ? 0 : 1);
});
