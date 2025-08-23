<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$page_title = 'Hybrid Debug';
require_once __DIR__ . '/includes/DatabaseManager.php';
require_once __DIR__ . '/includes/HybridDataManager.php';
include __DIR__ . '/includes/header.php';

echo "<h1>HybridDataManager Debug</h1>";

echo "<h2>1. Testing direct DatabaseManager creation...</h2>";
try {
    $db = new DatabaseManager();
    echo "✅ Direct DatabaseManager creation: SUCCESS<br>";
    
    $testResult = $db->testConnection();
    if ($testResult['success']) {
        echo "✅ Direct database connection: SUCCESS<br>";
        echo "MySQL Version: " . htmlspecialchars($testResult['data']['version']) . "<br>";
    } else {
        echo "Direct database connection: FAILED - " . htmlspecialchars($testResult['message']) . "<br>";
    }
} catch (Exception $e) {
    echo "Direct DatabaseManager creation: FAILED<br>";
    echo "Error: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "File: " . htmlspecialchars($e->getFile()) . " Line: " . $e->getLine() . "<br>";
}

echo "<br><h2>2. Testing HybridDataManager creation...</h2>";
try {
    $hybrid = HybridDataManager::getInstance();
    echo "✅ HybridDataManager creation: SUCCESS<br>";
    
    // Check if it's in fallback mode
    $reflection = new ReflectionClass($hybrid);
    $fallbackProperty = $reflection->getProperty('fallbackMode');
    $fallbackProperty->setAccessible(true);
    $isFallbackMode = $fallbackProperty->getValue($hybrid);
    
    if ($isFallbackMode) {
        echo "⚠️ HybridDataManager is in FALLBACK MODE (using JSON)<br>";
    } else {
        echo "✅ HybridDataManager is using MYSQL<br>";
    }
    
} catch (Exception $e) {
    echo "HybridDataManager creation: FAILED<br>";
    echo "Error: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "File: " . htmlspecialchars($e->getFile()) . " Line: " . $e->getLine() . "<br>";
}

echo "<br><h2>3. Checking log files...</h2>";
$logFile = __DIR__ . '/data/hybrid.log';
echo "Log file: " . $logFile . "<br>";
if (file_exists($logFile)) {
    echo "Log exists: YES<br>";
    $logContent = file_get_contents($logFile);
    echo "<pre>" . htmlspecialchars($logContent) . "</pre>";
} else {
    echo "Log exists: NO<br>";
}

echo "<br><h2>4. Checking data directory...</h2>";
$dataDir = __DIR__ . '/data/';
echo "Data directory: " . $dataDir . "<br>";
echo "Directory exists: " . (is_dir($dataDir) ? 'YES' : 'NO') . "<br>";
echo "Directory writable: " . (is_writable($dataDir) ? 'YES' : 'NO') . "<br>";

if (is_dir($dataDir)) {
    $files = scandir($dataDir);
    echo "Files in data directory:<br>";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "- " . $file . "<br>";
        }
    }
}

echo "<br><h2>Debug completed</h2>";
?>

</main>
</body>
</html>
