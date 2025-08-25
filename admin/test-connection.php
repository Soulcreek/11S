<?php
// Test der neuen Database-Verbindung
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔧 DATABASE CONNECTION TEST\n\n";

try {
    require_once 'database.php';
    
    echo "✅ Database class loaded\n";
    
    $db = Database::getInstance();
    echo "✅ Database connection successful\n";
    
    // Stats abrufen
    $stats = $db->getStats();
    echo "\n📊 DATABASE STATISTICS:\n";
    echo "Users: " . $stats['users'] . "\n";
    echo "Questions: " . $stats['questions'] . "\n";
    echo "Sessions: " . $stats['sessions'] . "\n";
    echo "Avg Score: " . $stats['avg_score'] . "\n";
    
    // Prüfe ob sichere Admin-User erstellt wurden
    $admins = $db->query("SELECT username, email, role FROM users WHERE role = 'admin'");
    echo "\n🔐 ADMIN USERS:\n";
    foreach ($admins as $admin) {
        echo "- {$admin['username']} ({$admin['email']}) - {$admin['role']}\n";
    }
    
    echo "\n✅ All tests passed! Database is ready.\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
