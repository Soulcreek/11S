<?php
// Database initialization script
require_once 'includes/DatabaseManager.php';

header('Content-Type: application/json');

try {
    $db = DatabaseManager::getInstance();
    
    // Initialize all required tables
    $db->initializeTables();
    
    // Get current stats to verify everything works
    $stats = $db->getStats();
    
    echo json_encode([
        'success' => true,
        'message' => 'Database tables initialized successfully',
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database initialization failed: ' . $e->getMessage()
    ]);
}
?>
