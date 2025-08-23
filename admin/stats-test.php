<?php
// Quick stats test
require_once 'includes/HybridDataManager.php';

echo "<h1>Statistics Test</h1>";

try {
    $hybrid = HybridDataManager::getInstance();
    $stats = $hybrid->getStats();
    
    echo "<h2>Current Statistics:</h2>";
    echo "<ul>";
    echo "<li><strong>Questions:</strong> " . ($stats['questions'] ?? 'N/A') . "</li>";
    echo "<li><strong>Users:</strong> " . ($stats['users'] ?? 'N/A') . "</li>";
    echo "<li><strong>Sessions:</strong> " . ($stats['sessions'] ?? 'N/A') . "</li>";
    echo "<li><strong>Average Score:</strong> " . ($stats['avg_score'] ?? 'N/A') . "%</li>";
    echo "</ul>";
    
    echo "<h2>Raw Stats Array:</h2>";
    echo "<pre>" . htmlspecialchars(json_encode($stats, JSON_PRETTY_PRINT)) . "</pre>";
    
} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
