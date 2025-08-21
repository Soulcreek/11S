// File: test-db-switcher.js
// Description: Test script for the smart database switcher

const path = require('path');
require('dotenv').config({ path: path.resolve(__dirname, '.env') });

async function testDatabaseSwitcher() {
    console.log('🧪 Testing Smart Database Switcher...\n');

    try {
        // Import the database switcher
        const dbPromise = require('./api/db-switcher');
        const db = await dbPromise;

        // Get database info
        const dbInfo = dbPromise.getDatabaseInfo();
        console.log(`🔧 Database Type: ${dbInfo.type}`);
        console.log(`🏭 Production Mode: ${dbInfo.isProduction}`);
        console.log(`🔧 Development Mode: ${dbInfo.isDevelopment}\n`);

        // Test basic queries
        console.log('📊 Testing database queries...');

        // Test categories
        const [categories] = await db.query('SELECT DISTINCT category FROM questions ORDER BY category');
        console.log(`✅ Found ${categories.length} categories:`, categories.map(c => c.category).join(', '));

        // Test difficulties
        const [difficulties] = await db.query('SELECT DISTINCT difficulty FROM questions ORDER BY difficulty');
        console.log(`✅ Found ${difficulties.length} difficulty levels:`, difficulties.map(d => d.difficulty).join(', '));

        // Test question count
        const [questionCount] = await db.query('SELECT COUNT(*) as count FROM questions');
        console.log(`✅ Total questions: ${questionCount[0].count}`);

        // Test category/difficulty combinations
        const [combinations] = await db.query(`
            SELECT category, difficulty, COUNT(*) as count 
            FROM questions 
            GROUP BY category, difficulty 
            ORDER BY category, difficulty
        `);

        console.log('\n📋 Question distribution:');
        combinations.forEach(combo => {
            console.log(`   ${combo.category} (${combo.difficulty}): ${combo.count} questions`);
        });

        // Test filtered questions
        console.log('\n🎯 Testing filtered questions...');
        const [geoEasy] = await db.query(
            'SELECT * FROM questions WHERE category = ? AND difficulty = ? LIMIT 3',
            ['geography', 'easy']
        );

        if (geoEasy.length > 0) {
            console.log(`✅ Geography Easy questions (${geoEasy.length} found):`);
            geoEasy.forEach((q, i) => {
                console.log(`   ${i + 1}. ${q.question_text} (Answer: ${q.correct_answer})`);
            });
        } else {
            console.log('⚠️  No Geography Easy questions found');
        }

        console.log('\n🎉 Database switcher test completed successfully!');

        if (dbInfo.type === 'SQLite') {
            console.log('\n💡 Note: Currently using SQLite for development.');
            console.log('   For production deployment on Netcup, MySQL connection will be attempted first.');
        } else {
            console.log('\n🚀 MySQL connection successful! Ready for production deployment.');
        }

    } catch (error) {
        console.error('❌ Database switcher test failed:', error);
        process.exit(1);
    }
}

// Run the test
testDatabaseSwitcher();
