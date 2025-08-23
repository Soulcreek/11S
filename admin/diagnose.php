<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Error Diagnosis</h1>";

echo "<h2>1. Testing basic PHP...</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Date: " . date('Y-m-d H:i:s') . "<br><br>";

echo "<h2>2. Testing includes path...</h2>";
$includesPath = __DIR__ . '/includes/';
echo "Includes directory: " . $includesPath . "<br>";
echo "Directory exists: " . (is_dir($includesPath) ? 'YES' : 'NO') . "<br>";
echo "Directory readable: " . (is_readable($includesPath) ? 'YES' : 'NO') . "<br><br>";

echo "<h2>3. Testing database config...</h2>";
$configFile = __DIR__ . '/data/db-config.json';
echo "Config file: " . $configFile . "<br>";
echo "Config exists: " . (file_exists($configFile) ? 'YES' : 'NO') . "<br>";
if (file_exists($configFile)) {
    $content = file_get_contents($configFile);
    $config = json_decode($content, true);
    echo "Config loaded: " . ($config ? $config['host'] : 'FAILED') . "<br>";
}
echo "<br>";

echo "<h2>4. Testing DatabaseManager file...</h2>";
$dbFile = __DIR__ . '/includes/DatabaseManager.php';
echo "DatabaseManager file: " . $dbFile . "<br>";
echo "File exists: " . (file_exists($dbFile) ? 'YES' : 'NO') . "<br>";
echo "File readable: " . (is_readable($dbFile) ? 'YES' : 'NO') . "<br>";

if (file_exists($dbFile)) {
    echo "File size: " . filesize($dbFile) . " bytes<br>";
    
    echo "<h3>Testing PHP syntax...</h3>";
    $output = [];
    $return_var = 0;
    exec("php -l " . escapeshellarg($dbFile), $output, $return_var);
    
    if ($return_var === 0) {
        echo "✓ PHP syntax is valid<br>";
    } else {
        echo "PHP syntax error:<br>";
        echo "<pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
    }
}
echo "<br>";

echo "<h2>5. Testing DatabaseManager include...</h2>";
try {
    require_once $dbFile;
    echo "✓ DatabaseManager file included successfully<br>";
    
    echo "<h3>Testing DatabaseManager instantiation...</h3>";
    $db = new DatabaseManager();
    echo "✓ DatabaseManager created successfully<br>";
    
    echo "<h3>Testing database connection...</h3>";
    $result = $db->testConnection();
    if ($result['success']) {
        echo "✓ Database connection successful<br>";
        echo "MySQL Version: " . $result['data']['version'] . "<br>";
    } else {
        echo "Database connection failed: " . htmlspecialchars($result['message']) . "<br>";
    }
    
} catch (Throwable $e) {
    echo "Error: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "File: " . htmlspecialchars($e->getFile()) . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<h2>6. All tests completed</h2>";
?>
