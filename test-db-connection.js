// File: test-db-connection.js
// Description: Test script to verify database connection and add sample questions

const db = require('./api/db');

async function testConnection() {
  try {
    console.log('Testing database connection...');

    // Test basic connection
    const [result] = await db.query('SELECT 1 as test');
    console.log('✅ Database connection successful:', result);

    // Check if tables exist
    const [tables] = await db.query('SHOW TABLES');
    console.log('📋 Available tables:', tables);

    // Check users table structure
    const [userTable] = await db.query('DESCRIBE users');
    console.log('👤 Users table structure:', userTable);

    // Check questions table structure
    try {
      const [questionTable] = await db.query('DESCRIBE questions');
      console.log('❓ Questions table structure:', questionTable);
    } catch (error) {
      console.log('❌ Questions table does not exist. Creating...');

      // Create questions table
      await db.query(`
        CREATE TABLE questions (
          question_id INT PRIMARY KEY AUTO_INCREMENT,
          question_text TEXT NOT NULL,
          correct_answer DECIMAL(15,2) NOT NULL,
          category VARCHAR(50) DEFAULT 'general',
          difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
      `);
      console.log('✅ Questions table created');
    }

    // Check if questions exist
    const [questionCount] = await db.query('SELECT COUNT(*) as count FROM questions');
    console.log(`📊 Questions in database: ${questionCount[0].count}`);

    if (questionCount[0].count === 0) {
      console.log('🔄 Adding sample questions...');

      const sampleQuestions = [
        {
          text: "Wie hoch ist der Mount Everest in Metern?",
          answer: 8848,
          category: "geography"
        },
        {
          text: "Wie viele Einwohner hat Berlin (in Millionen)?",
          answer: 3.7,
          category: "geography"
        },
        {
          text: "In welchem Jahr begann der Zweite Weltkrieg?",
          answer: 1939,
          category: "history"
        },
        {
          text: "Wie schnell ist die Lichtgeschwindigkeit (km/s)?",
          answer: 299792458,
          category: "science"
        },
        {
          text: "Wie viele Bones hat der menschliche Körper?",
          answer: 206,
          category: "science"
        },
        {
          text: "Wie lang ist der Äquator der Erde (in km)?",
          answer: 40075,
          category: "geography"
        },
        {
          text: "In welchem Jahr wurde die Berliner Mauer gebaut?",
          answer: 1961,
          category: "history"
        },
        {
          text: "Wie hoch kann ein Känguru springen (in Metern)?",
          answer: 3,
          category: "nature"
        },
        {
          text: "Wie viele Herzen hat ein Oktopus?",
          answer: 3,
          category: "nature"
        },
        {
          text: "Wie alt wurde Mozart (Jahre)?",
          answer: 35,
          category: "history"
        }
      ];

      for (const question of sampleQuestions) {
        await db.query(
          'INSERT INTO questions (question_text, correct_answer, category) VALUES (?, ?, ?)',
          [question.text, question.answer, question.category]
        );
      }

      console.log(`✅ Added ${sampleQuestions.length} sample questions`);
    }

    // Test fetching random questions
    const [randomQuestions] = await db.query('SELECT * FROM questions ORDER BY RAND() LIMIT 5');
    console.log('🎲 Sample random questions:');
    randomQuestions.forEach((q, index) => {
      console.log(`  ${index + 1}. ${q.question_text} (Answer: ${q.correct_answer})`);
    });

  } catch (error) {
    console.error('❌ Database error:', error);
  } finally {
    process.exit(0);
  }
}

testConnection();
