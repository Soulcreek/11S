<?php
// Quick SQL sanitization test
require_once 'includes/HybridDataManager.php';

echo "<h1>SQL Sanitization Test</h1>";

$hybrid = HybridDataManager::getInstance();

// Test the problematic SQL pattern that was causing 1064
$badSQL = "\\\n        SELECT gs.*, u.username \n        FROM game_sessions gs \n        LEFT JOIN users u ON gs.user_id = u.id \n        ORDER BY gs.completed_at DESC \n        LIMIT 10\n    ";

echo "<h2>Original Problematic SQL:</h2>";
echo "<pre>" . htmlspecialchars($badSQL) . "</pre>";

echo "<h2>Testing Query (should be sanitized internally):</h2>";
try {
    $result = $hybrid->query($badSQL);
    echo "<p>✅ Query executed successfully! Sanitization worked.</p>";
    echo "<p>Result count: " . (is_array($result) ? count($result) : 0) . "</p>";
} catch (Exception $e) {
    echo "<p>Query failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>Current Status:</h2>";
echo "<p>Fallback mode: " . ($hybrid->isInFallbackMode() ? 'YES' : 'NO') . "</p>";
echo "<p>Pending operations: " . count($hybrid->getPendingOperations()) . "</p>";

// Show recent log entries
echo "<h2>Recent Log Entries:</h2>";
echo "<ul>";
$logs = $hybrid->getSyncLog();
foreach (array_slice($logs, -10) as $log) {
    echo "<li>" . htmlspecialchars($log) . "</li>";
}
echo "</ul>";
?>
