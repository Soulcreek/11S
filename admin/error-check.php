<?php
// Simple error check - test basic includes
echo "<h1>Error Diagnosis</h1>";
echo "<pre>";

echo "1. Testing basic PHP...\n";
echo "   PHP Version: " . PHP_VERSION . "\n";
echo "   Date: " . date('Y-m-d H:i:s') . "\n";

echo "\n2. Testing database config...\n";
try {
    $config = json_decode(file_get_contents(__DIR__ . '/data/db-config.json'), true);
    echo "   Config loaded: " . $config['host'] . "\n";
} catch (Exception $e) {
    echo "   Config error: " . $e->getMessage() . "\n";
}

echo "\n3. Testing DatabaseManager include...\n";
try {
    require_once 'includes/DatabaseManager.php';
    echo "   DatabaseManager included successfully\n";
} catch (Exception $e) {
    echo "   DatabaseManager error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "\n4. Testing DatabaseManager creation...\n";
try {
    $db = DatabaseManager::getInstance();
    echo "   DatabaseManager created successfully\n";
} catch (Exception $e) {
    echo "   DatabaseManager creation error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "\n5. Testing HybridDataManager include...\n";
try {
    require_once 'includes/HybridDataManager.php';
    echo "   HybridDataManager included successfully\n";
} catch (Exception $e) {
    echo "   HybridDataManager error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "</pre>";

echo "<br><a href='simple-test.php'>Back to Simple Test</a>";
echo "<br><a href='dashboard.php'>Try Dashboard</a>";
?>
