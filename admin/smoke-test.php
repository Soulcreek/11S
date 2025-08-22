<?php
// smoke-test.php
// Usage: ?run=1&token=...   (token in admin/data/migration-token.json)
function out($s){ echo "<p>".htmlspecialchars($s)."</p>\n"; }

$tokenPath = __DIR__ . '/data/migration-token.json';
$expectedToken = null;
if (file_exists($tokenPath)) {
    $tt = json_decode(file_get_contents($tokenPath), true);
    $expectedToken = $tt['token'] ?? null;
}
if (!isset($_GET['run']) || $_GET['run'] != '1' || !isset($_GET['token']) || $_GET['token'] !== $expectedToken) {
    echo '<h1>Smoke test</h1><p>Provide ?run=1&token=...</p>'; exit;
}

require_once __DIR__ . '/includes/DatabaseManager.php';

try {
    $db = new DatabaseManager();
    $pdo = $db->getConnection();
} catch (Throwable $e) {
    out('DB connect failed: ' . $e->getMessage()); exit;
}

// Create unique test user
$ts = date('Ymd_His');
$username = 'smoke_' . $ts . '_' . substr(md5(uniqid()),0,6);
$email = $username . '@example.test';
$pwHash = password_hash('smoke-pass', PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, account_type) VALUES (?, ?, ?, ? )');
    $stmt->execute([$username, $email, $pwHash, 'registered']);
    $userId = $pdo->lastInsertId();
    if (empty($userId)) {
        // fallback: try to find by username
        $row = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $row->execute([$username]);
        $userId = $row->fetchColumn();
    }
    out('Created test user: ' . $username . ' (id=' . $userId . ')');
} catch (Throwable $e) {
    out('Failed to create test user: ' . $e->getMessage()); exit;
}

// Record a game session and upsert into highscores
$score = rand(100,1000);
try {
    // Insert game session
    $ins = $pdo->prepare('INSERT INTO game_sessions (user_id, score, questions_answered, questions_correct, time_taken, validated) VALUES (?, ?, 10, 8, 60, 1)');
    $ins->execute([$userId, $score]);
    out('Inserted game session with score ' . $score);

    // Upsert highscores (unique user_id)
    $up = $pdo->prepare('INSERT INTO highscores (user_id, score, achieved_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE score = GREATEST(score, VALUES(score)), achieved_at = VALUES(achieved_at)');
    $up->execute([$userId, $score]);
    out('Upserted highscore for user id ' . $userId);
} catch (Throwable $e) {
    out('Failed to write game/highscore: ' . $e->getMessage()); exit;
}

// Read top highscores with usernames
try {
    $rows = $pdo->query('SELECT h.user_id, h.score, u.username FROM highscores h LEFT JOIN users u ON u.id = h.user_id ORDER BY h.score DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
    out('Top highscores:');
    echo '<pre>' . htmlspecialchars(json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)) . '</pre>';
} catch (Throwable $e) {
    out('Failed to read highscores: ' . $e->getMessage()); exit;
}

out('Smoke test completed. Note: test user and records left in DB for inspection.');

?>
