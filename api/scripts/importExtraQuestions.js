// Script: api/scripts/importExtraQuestions.js
// Purpose: Import questions from web/src/data/extraQuestions.js (ES module) into the configured DB (MySQL or SQLite fallback).

const path = require('path');
const fs = require('fs');

async function loadQuestions() {
    // Prefer local JSON in api/data if present (legacy), else import from web/src/data/extraQuestions.js
    const legacyJson = path.join(__dirname, '..', 'data', 'extraQuestions.json');
    if (fs.existsSync(legacyJson)) {
        const raw = fs.readFileSync(legacyJson, 'utf8');
        return JSON.parse(raw);
    }

    // Try to load the frontend JS module which exports default extraQuestions
    const frontendPath = path.join(__dirname, '..', '..', 'web', 'src', 'data', 'extraQuestions.js');
    if (!fs.existsSync(frontendPath)) {
        throw new Error('Could not find questions data in api/data or web/src/data');
    }

    // The frontend file is ESM-style 'export default extraQuestions;' so require won't parse it.
    // We'll read the file and extract the array literal using a simple heuristic.
    const contents = fs.readFileSync(frontendPath, 'utf8');

    // Find the start of the array and the end (last closing bracket before export)
    const arrStart = contents.indexOf('const extraQuestions = [');
    if (arrStart === -1) throw new Error('Could not find array declaration in extraQuestions.js');
    const afterStart = contents.indexOf('[', arrStart);
    const exportIndex = contents.lastIndexOf('export default');
    if (exportIndex === -1) throw new Error('Could not find export statement in extraQuestions.js');
    const arrText = contents.substring(afterStart, exportIndex);

    // Wrap into valid JSON by removing trailing commas and comments.
    // This is a best-effort parser for the generated file structure.
    const cleaned = arrText
        .replace(/\/\/.*$/gm, '') // remove line comments
        .replace(/\r?\n/g, ' ') // normalize newlines and flatten
        .replace(/,\s*\]/g, ']') // remove trailing commas before array end
        .trim();

    // Attempt to evaluate the array safely using Function
    let items;
    try {
        items = Function('return ' + cleaned)();
    } catch (e) {
        throw new Error('Failed to parse extraQuestions.js array: ' + e.message);
    }

    if (!Array.isArray(items)) throw new Error('Parsed data is not an array');
    return items;
}

async function main() {
    try {
        const dbPromise = require('../db-switcher');
        const db = await dbPromise;

        const items = await loadQuestions();
        console.log(`Loaded ${items.length} questions from source`);

        let inserted = 0;

        for (const q of items) {
            const question_text = q.question_text || q.text || q.question || '';
            const correct_answer = (q.correct_answer !== undefined && q.correct_answer !== null) ? String(q.correct_answer) : '';
            const category = q.category || 'general';
            const difficulty = q.difficulty || 'medium';

            try {
                await db.query(
                    'INSERT INTO questions (question_text, correct_answer, category, difficulty) VALUES (?, ?, ?, ?)',
                    [question_text, correct_answer, category, difficulty]
                );
                inserted++;
            } catch (innerErr) {
                console.warn('Failed to insert question (skipping):', question_text.slice(0, 60), '-', innerErr.message);
            }
        }

        console.log(`Import finished. Inserted ${inserted}/${items.length} questions.`);
        process.exit(0);
    } catch (err) {
        console.error('Import failed:', err);
        process.exit(2);
    }
}

main();
