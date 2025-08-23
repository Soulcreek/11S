<?php
// Check what tables actually exist in the database
session_start();
require_once 'includes/DatabaseManager.php';

echo "<h1>Database Table Check</h1>";
echo "<pre>";

try {
    $db = DatabaseManager::getInstance();
    $config = json_decode(file_get_contents(__DIR__ . '/data/db-config.json'), true);
    
    echo "Database: " . $config['database'] . "\n";
    echo "Host: " . $config['host'] . "\n\n";
    
    // Get the PDO connection directly
    $reflection = new ReflectionClass($db);
    $connectionProperty = $reflection->getProperty('connection');
    $connectionProperty->setAccessible(true);
    $pdo = $connectionProperty->getValue($db);
    
    // Show all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Existing tables (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        echo "  - " . $table . "\n";
        
        // Count rows in each table
        try {
            $countStmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
            $count = $countStmt->fetchColumn();
            echo "    Rows: " . $count . "\n";
        } catch (Exception $e) {
            echo "    Error counting: " . $e->getMessage() . "\n";
        }
    }
    
    if (empty($tables)) {
        echo "\nNO TABLES FOUND - Database initialization needed!\n";
        echo "<br><br><a href='dashboard.php?force_init=1' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Initialize Database</a>";
    } else {
        echo "\n✅ Tables exist but may be empty\n";
        echo "<br><br><a href='dashboard.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📊 Go to Dashboard</a>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
