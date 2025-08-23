<?php
// Check questions table structure
require_once 'includes/DatabaseManager.php';

try {
    $db = new DatabaseManager();
    $pdo = $db->getConnection();
    
    echo "<h1>Questions Table Analysis</h1>";
    
    // Check table structure
    echo "<h2>Table Structure:</h2>";
    $columns = $pdo->query("SHOW COLUMNS FROM questions")->fetchAll();
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li><strong>{$col['Field']}</strong>: {$col['Type']} " . ($col['Null'] == 'YES' ? '(nullable)' : '(not null)') . "</li>";
    }
    echo "</ul>";
    
    // Test the queries that are failing
    echo "<h2>Query Results:</h2>";
    
    $total = $pdo->query("SELECT COUNT(*) as count FROM questions")->fetchColumn();
    echo "<p><strong>Total questions:</strong> $total</p>";
    
    try {
        $active = $pdo->query("SELECT COUNT(*) as count FROM questions WHERE is_active = 1")->fetchColumn();
        echo "<p><strong>Active questions (is_active = 1):</strong> $active</p>";
    } catch (Exception $e) {
        echo "<p><strong>Active questions query failed:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Same for users
    echo "<h2>Users Table Analysis:</h2>";
    $userColumns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll();
    echo "<ul>";
    foreach ($userColumns as $col) {
        echo "<li><strong>{$col['Field']}</strong>: {$col['Type']}</li>";
    }
    echo "</ul>";
    
    $totalUsers = $pdo->query("SELECT COUNT(*) as count FROM users")->fetchColumn();
    echo "<p><strong>Total users:</strong> $totalUsers</p>";
    
    try {
        $activeUsers = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1")->fetchColumn();
        echo "<p><strong>Active users (is_active = 1):</strong> $activeUsers</p>";
    } catch (Exception $e) {
        echo "<p><strong>Active users query failed:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
