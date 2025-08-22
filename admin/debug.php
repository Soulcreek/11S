<?php
// Debug page to check exactly what's happening
session_start();
require_once 'includes/DatabaseManager.php';
require_once 'includes/HybridDataManager.php';

echo "<h1>Admin Debug Page</h1>";
echo "<pre>";

echo "1. Testing HybridDataManager:\n";
$dataManager = HybridDataManager::getInstance();
echo "   - Fallback mode: " . ($dataManager->isInFallbackMode() ? "YES" : "NO") . "\n";

echo "\n2. Testing direct DatabaseManager:\n";
try {
    $db = DatabaseManager::getInstance();
    echo "   - DatabaseManager created: YES\n";
    
    echo "\n3. Testing getStats():\n";
    $stats = $db->getStats();
    echo "   - Stats retrieved: YES\n";
    echo "   - Questions: " . ($stats['questions'] ?? 0) . "\n";
    echo "   - Users: " . ($stats['users'] ?? 0) . "\n";
    
} catch (Exception $e) {
    echo "   - DatabaseManager error: " . $e->getMessage() . "\n";
    echo "   - Error type: ";
    if (strpos($e->getMessage(), "doesn't exist") !== false || 
        strpos($e->getMessage(), "Base table or view not found") !== false) {
        echo "MISSING TABLES (should show init button)\n";
        $should_show_button = true;
    } else {
        echo "CONNECTION ERROR (should use fallback)\n";
        $should_show_button = false;
    }
}

echo "\n4. Testing HybridDataManager getStats():\n";
try {
    $stats = $dataManager->getStats();
    echo "   - HybridDataManager stats: SUCCESS\n";
    echo "   - Questions: " . ($stats['questions'] ?? 0) . "\n";
    echo "   - Users: " . ($stats['users'] ?? 0) . "\n";
} catch (Exception $e) {
    echo "   - HybridDataManager error: " . $e->getMessage() . "\n";
}

echo "\n5. Log from HybridDataManager:\n";
foreach ($dataManager->getSyncLog() as $logEntry) {
    echo "   " . $logEntry . "\n";
}

echo "\n6. Should show init button: " . (isset($should_show_button) && $should_show_button ? "YES" : "NO") . "\n";
echo "</pre>";

echo "<br><a href='dashboard.php'>Back to Dashboard</a>";
?>
