<?php
// Quick database status check
require_once 'includes/DatabaseManager.php';

echo "<h1>Database Status Check</h1>";

try {
    $db = new DatabaseManager();
    $pdo = $db->getConnection();
    
    // Check if tables exist and their row counts
    $tables = ['users', 'questions', 'game_sessions', 'user_stats'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $stmt->fetchColumn();
            echo "<p><strong>$table:</strong> $count rows</p>";
            
            if ($count > 0 && $count < 5) {
                // Show sample data for small tables
                $sample = $pdo->query("SELECT * FROM `$table` LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
                echo "<pre style='font-size: 12px; background: #f5f5f5; padding: 10px; margin: 5px 0;'>";
                echo htmlspecialchars(json_encode($sample, JSON_PRETTY_PRINT));
                echo "</pre>";
            }
        } catch (Exception $e) {
            echo "<p><strong>$table:</strong> Error - " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // Check what tables actually exist
    echo "<h2>Existing Tables:</h2>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "<p>- $table</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
