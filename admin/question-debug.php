<?php
// Check questions table columns and sample data
require_once 'includes/DatabaseManager.php';

try {
    $db = new DatabaseManager();
    $pdo = $db->getConnection();
    
    echo "<h1>Questions Table Detailed Analysis</h1>";
    
    // Show table structure
    echo "<h2>Columns:</h2>";
    $columns = $pdo->query("SHOW COLUMNS FROM questions")->fetchAll();
    foreach ($columns as $col) {
        echo "<p><strong>{$col['Field']}</strong>: {$col['Type']}</p>";
    }
    
    // Show sample data
    echo "<h2>Sample Data (first 3 rows):</h2>";
    $sample = $pdo->query("SELECT * FROM questions LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . htmlspecialchars(json_encode($sample, JSON_PRETTY_PRINT)) . "</pre>";
    
    // Test individual queries
    echo "<h2>Query Tests:</h2>";
    
    $total = $pdo->query("SELECT COUNT(*) as count FROM questions")->fetchColumn();
    echo "<p><strong>Total questions:</strong> $total</p>";
    
    // Check if difficulty values exist
    $difficulties = $pdo->query("SELECT difficulty, COUNT(*) as count FROM questions GROUP BY difficulty")->fetchAll();
    echo "<p><strong>Difficulty breakdown:</strong></p>";
    echo "<ul>";
    foreach ($difficulties as $diff) {
        echo "<li>{$diff['difficulty']}: {$diff['count']}</li>";
    }
    echo "</ul>";
    
    // Check categories
    $categories = $pdo->query("SELECT DISTINCT category FROM questions WHERE category IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p><strong>Categories found:</strong> " . count($categories) . "</p>";
    echo "<ul>";
    foreach (array_slice($categories, 0, 10) as $cat) {
        echo "<li>" . htmlspecialchars($cat) . "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
