// File: setup-extended-questions.js
// Description: Setup script to add more questions with categories and difficulty levels

const dbPromise = require('./api/db-switcher');

async function setupExtendedQuestions() {
    try {
        console.log('🔄 Setting up extended questions database...');
        const db = await dbPromise;

        // First, clear existing questions to avoid duplicates
        await db.query('DELETE FROM questions');
        console.log('🗑️ Cleared existing questions');

        // Extended question set with categories and difficulty levels
        const questions = [
            // GEOGRAPHY - EASY
            { text: "Wie viele Kontinente gibt es?", answer: 7, category: "geography", difficulty: "easy" },
            { text: "Welcher ist der längste Fluss der Welt (in km)?", answer: 6650, category: "geography", difficulty: "easy" },
            { text: "Wie viele Ozeane gibt es auf der Erde?", answer: 5, category: "geography", difficulty: "easy" },
            { text: "Wie viele Bundesländer hat Deutschland?", answer: 16, category: "geography", difficulty: "easy" },

            // GEOGRAPHY - MEDIUM
            { text: "Wie hoch ist der Mount Everest in Metern?", answer: 8848, category: "geography", difficulty: "medium" },
            { text: "Wie viele Einwohner hat Berlin (in Millionen)?", answer: 3.7, category: "geography", difficulty: "medium" },
            { text: "Wie lang ist der Äquator der Erde (in km)?", answer: 40075, category: "geography", difficulty: "medium" },
            { text: "Wie tief ist der Marianengraben (in Metern)?", answer: 11034, category: "geography", difficulty: "medium" },

            // GEOGRAPHY - HARD
            { text: "Wie viele Zeitzonen hat Russland?", answer: 11, category: "geography", difficulty: "hard" },
            { text: "Auf welcher Höhe liegt La Paz, Bolivien (in Metern)?", answer: 3500, category: "geography", difficulty: "hard" },

            // HISTORY - EASY
            { text: "In welchem Jahr begann der Zweite Weltkrieg?", answer: 1939, category: "history", difficulty: "easy" },
            { text: "In welchem Jahr fiel die Berliner Mauer?", answer: 1989, category: "history", difficulty: "easy" },
            { text: "In welchem Jahr landeten Menschen zum ersten Mal auf dem Mond?", answer: 1969, category: "history", difficulty: "easy" },
            { text: "In welchem Jahrhundert lebte Shakespeare?", answer: 16, category: "history", difficulty: "easy" },

            // HISTORY - MEDIUM
            { text: "In welchem Jahr wurde die Berliner Mauer gebaut?", answer: 1961, category: "history", difficulty: "medium" },
            { text: "Wie alt wurde Mozart (Jahre)?", answer: 35, category: "history", difficulty: "medium" },
            { text: "In welchem Jahr wurde das Internet erfunden?", answer: 1969, category: "history", difficulty: "medium" },
            { text: "Wie lange dauerte der Hundertjährige Krieg (Jahre)?", answer: 116, category: "history", difficulty: "medium" },

            // HISTORY - HARD
            { text: "In welchem Jahr wurde Napoleon geboren?", answer: 1769, category: "history", difficulty: "hard" },
            { text: "Wie viele Jahre dauerte die Weimarer Republik?", answer: 14, category: "history", difficulty: "hard" },

            // SCIENCE - EASY
            { text: "Wie viele Knochen hat der menschliche Körper?", answer: 206, category: "science", difficulty: "easy" },
            { text: "Wie viele Zähne hat ein erwachsener Mensch normalerweise?", answer: 32, category: "science", difficulty: "easy" },
            { text: "Bei welcher Temperatur gefriert Wasser (°C)?", answer: 0, category: "science", difficulty: "easy" },
            { text: "Bei welcher Temperatur kocht Wasser (°C)?", answer: 100, category: "science", difficulty: "easy" },

            // SCIENCE - MEDIUM
            { text: "Wie schnell ist die Lichtgeschwindigkeit (km/s)?", answer: 299792458, category: "science", difficulty: "medium" },
            { text: "Wie viele Herzen hat ein Oktopus?", answer: 3, category: "science", difficulty: "medium" },
            { text: "Wie lange dauert es, bis Licht von der Sonne zur Erde gelangt (Minuten)?", answer: 8, category: "science", difficulty: "medium" },
            { text: "Wie viele Monde hat der Jupiter?", answer: 79, category: "science", difficulty: "medium" },

            // SCIENCE - HARD
            { text: "Bei welcher Temperatur ist Wasserstoff flüssig (°C)?", answer: -253, category: "science", difficulty: "hard" },
            { text: "Wie schwer ist ein Proton (in atomic mass units)?", answer: 1.007, category: "science", difficulty: "hard" },

            // NATURE - EASY
            { text: "Wie viele Beine hat eine Spinne?", answer: 8, category: "nature", difficulty: "easy" },
            { text: "Wie viele Flügel hat ein Schmetterling?", answer: 4, category: "nature", difficulty: "easy" },
            { text: "Wie viele Augen hat eine Biene?", answer: 5, category: "nature", difficulty: "easy" },

            // NATURE - MEDIUM
            { text: "Wie hoch kann ein Känguru springen (in Metern)?", answer: 3, category: "nature", difficulty: "medium" },
            { text: "Wie schnell kann ein Gepard laufen (km/h)?", answer: 120, category: "nature", difficulty: "medium" },
            { text: "Wie alt kann eine Schildkröte werden (Jahre)?", answer: 150, category: "nature", difficulty: "medium" },

            // NATURE - HARD
            { text: "Wie tief kann ein Pottwal tauchen (in Metern)?", answer: 2000, category: "nature", difficulty: "hard" },
            { text: "Wie viele Arten von Pinguinen gibt es?", answer: 18, category: "nature", difficulty: "hard" },

            // SPORTS - EASY
            { text: "Wie viele Spieler hat eine Fußballmannschaft auf dem Feld?", answer: 11, category: "sports", difficulty: "easy" },
            { text: "Wie oft findet die Fußball-WM statt (Jahre)?", answer: 4, category: "sports", difficulty: "easy" },
            { text: "Wie viele Ringe hat das olympische Symbol?", answer: 5, category: "sports", difficulty: "easy" },

            // SPORTS - MEDIUM
            { text: "Wie lang ist ein Marathon (km)?", answer: 42.195, category: "sports", difficulty: "medium" },
            { text: "Wie hoch ist ein Basketball-Korb (in Metern)?", answer: 3.05, category: "sports", difficulty: "medium" },
            { text: "Wie viele Grand-Slam-Turniere gibt es im Tennis?", answer: 4, category: "sports", difficulty: "medium" },

            // SPORTS - HARD
            { text: "Welcher Läufer hält den 100m-Weltrekord (Sekunden)?", answer: 9.58, category: "sports", difficulty: "hard" },
            { text: "In welchem Jahr fanden die ersten Olympischen Spiele der Neuzeit statt?", answer: 1896, category: "sports", difficulty: "hard" },

            // TECHNOLOGY - EASY
            { text: "In welchem Jahr wurde das iPhone vorgestellt?", answer: 2007, category: "technology", difficulty: "easy" },
            { text: "Wie viele Bits sind ein Byte?", answer: 8, category: "technology", difficulty: "easy" },
            { text: "Wer gründete Microsoft?", answer: 1975, category: "technology", difficulty: "easy" }, // Jahr der Gründung

            // TECHNOLOGY - MEDIUM
            { text: "Wie viele Megabyte sind ein Gigabyte?", answer: 1024, category: "technology", difficulty: "medium" },
            { text: "In welchem Jahr wurde Facebook gegründet?", answer: 2004, category: "technology", difficulty: "medium" },
            { text: "Wie viele Transistoren hat der erste Mikroprozessor?", answer: 2300, category: "technology", difficulty: "medium" },

            // TECHNOLOGY - HARD
            { text: "In welchem Jahr wurde der erste Computer gebaut?", answer: 1946, category: "technology", difficulty: "hard" },
            { text: "Wie viele IPv4-Adressen gibt es theoretisch?", answer: 4294967296, category: "technology", difficulty: "hard" },

            // MUSIC - EASY
            { text: "Wie viele Saiten hat eine Gitarre normalerweise?", answer: 6, category: "music", difficulty: "easy" },
            { text: "Wie viele Tasten hat ein Klavier?", answer: 88, category: "music", difficulty: "easy" },
            { text: "Wie viele Noten gibt es in einer Oktave?", answer: 12, category: "music", difficulty: "easy" },

            // MUSIC - MEDIUM
            { text: "In welchem Jahr wurde die CD erfunden?", answer: 1982, category: "music", difficulty: "medium" },
            { text: "Wie viele Symphonien komponierte Beethoven?", answer: 9, category: "music", difficulty: "medium" },

            // MUSIC - HARD
            { text: "Wie viele Opern komponierte Mozart?", answer: 22, category: "music", difficulty: "hard" },
            { text: "In welchem Jahr wurde das erste Grammophon erfunden?", answer: 1887, category: "music", difficulty: "hard" }
        ];

        console.log(`📝 Adding ${questions.length} questions to database...`);

        // Insert all questions
        for (const question of questions) {
            await db.query(
                'INSERT INTO questions (question_text, correct_answer, category, difficulty) VALUES (?, ?, ?, ?)',
                [question.text, question.answer, question.category, question.difficulty]
            );
        }

        console.log(`✅ Successfully added ${questions.length} questions!`);

        // Show category and difficulty distribution
        const [categoryStats] = await db.query(`
            SELECT category, difficulty, COUNT(*) as count 
            FROM questions 
            GROUP BY category, difficulty 
            ORDER BY category, difficulty
        `);

        console.log('\n📊 Question Distribution:');
        console.log('========================');

        const categories = {};
        categoryStats.forEach(stat => {
            if (!categories[stat.category]) {
                categories[stat.category] = {};
            }
            categories[stat.category][stat.difficulty] = stat.count;
        });

        Object.keys(categories).forEach(category => {
            const cat = categories[category];
            console.log(`${category.toUpperCase()}: Easy: ${cat.easy || 0}, Medium: ${cat.medium || 0}, Hard: ${cat.hard || 0}, Total: ${(cat.easy || 0) + (cat.medium || 0) + (cat.hard || 0)}`);
        });

        // Test a random selection
        const [randomQuestions] = await db.query('SELECT * FROM questions ORDER BY RANDOM() LIMIT 5');
        console.log('\n🎲 Sample Questions:');
        console.log('===================');
        randomQuestions.forEach((q, index) => {
            console.log(`${index + 1}. [${q.category.toUpperCase()} - ${q.difficulty.toUpperCase()}] ${q.question_text} (${q.correct_answer})`);
        });

    } catch (error) {
        console.error('❌ Error setting up questions:', error);
    } finally {
        process.exit(0);
    }
}

setupExtendedQuestions();
