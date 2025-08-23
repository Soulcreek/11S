<?php
// Simple admin test - bypass complex includes
session_start();

echo "<!DOCTYPE html>";
echo "<html><head><title>Admin Test</title></head><body>";
echo "<h1>Admin Directory Test</h1>";
echo "<p>PHP is working: " . date('Y-m-d H:i:s') . "</p>";

// Test database config file
$configFile = __DIR__ . '/data/db-config.json';
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    echo "<p>✅ Database config found</p>";
    echo "<p>Host: " . htmlspecialchars($config['host']) . "</p>";
    echo "<p>Database: " . htmlspecialchars($config['database']) . "</p>";
    
    // Test database connection
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        echo "<p>✅ Database connection successful!</p>";
    } catch (Exception $e) {
        echo "<p>Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p>Database config not found</p>";
}

echo "<br><a href='index.php'>Go to Admin Login</a>";
echo "<br><a href='dashboard.php'>Go to Dashboard</a>";
echo "</body></html>";
?>
