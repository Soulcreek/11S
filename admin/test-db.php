<?php
// Simple database test and initialization
try {
    require_once 'includes/DatabaseManager.php';
    
    echo "Testing database connection...\n";
    $db = DatabaseManager::getInstance();
    echo "Database connected successfully!\n";
    
    echo "Initializing tables...\n";
    $db->initializeTables();
    echo "Tables created successfully!\n";
    
    $stats = $db->getStats();
    echo "Current stats:\n";
    echo "Questions: " . ($stats['questions'] ?? 0) . "\n";
    echo "Users: " . ($stats['users'] ?? 0) . "\n";
    echo "Sessions: " . ($stats['sessions'] ?? 0) . "\n";
    echo "Setup complete!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
