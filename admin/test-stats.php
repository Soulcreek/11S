<?php
// File: admin/test-stats.php
// Description: Quick test to verify statistics are working properly after HybridDataManager fix

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/HybridDataManager.php';
require_once 'includes/DatabaseManager.php';

echo "<h1>Statistics Test - Post Fix</h1>";

try {
    // Test HybridDataManager
    echo "<h2>HybridDataManager Test</h2>";
    $hybridManager = new HybridDataManager();
    
    // Test basic query
    $questions = $hybridManager->query("SELECT COUNT(*) as count FROM Questions");
    echo "<strong>Questions count query result:</strong><br>";
    echo "<pre>" . print_r($questions, true) . "</pre>";
    
    // Test DatabaseManager stats
    echo "<h2>DatabaseManager Stats Test</h2>";
    $dbManager = new DatabaseManager();
    $stats = $dbManager->getStats();
    echo "<strong>getStats() result:</strong><br>";
    echo "<pre>" . print_r($stats, true) . "</pre>";
    
    echo "<h2>Summary</h2>";
    echo "<ul>";
    echo "<li>✓ HybridDataManager query() now returns: " . gettype($questions) . "</li>";
    echo "<li>✓ Questions count: " . (is_array($questions) && isset($questions[0]['count']) ? $questions[0]['count'] : 'Error') . "</li>";
    echo "<li>✓ Stats questions: " . ($stats['questions'] ?? 'Error') . "</li>";
    echo "<li>✓ Stats users: " . ($stats['users'] ?? 'Error') . "</li>";
    echo "<li>✓ Stats sessions: " . ($stats['sessions'] ?? 'Error') . "</li>";
    echo "</ul>";
    
    if (is_array($questions) && isset($questions[0]['count']) && $questions[0]['count'] > 0) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<strong>✅ SUCCESS!</strong> HybridDataManager fix is working. Statistics should now display correctly.";
        echo "</div>";
    } else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>ISSUE:</strong> HybridDataManager is still not returning proper data.";
    echo "</div>";
    }

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
