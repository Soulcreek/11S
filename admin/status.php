<?php
// Lightweight status endpoint for HybridDataManager
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/includes/DatabaseManager.php';
    require_once __DIR__ . '/includes/HybridDataManager.php';

    $hm = HybridDataManager::getInstance();
    $status = [
        'fallback' => $hm->isInFallbackMode(),
        'pending' => count($hm->getPendingOperations()),
        'log_entries' => $hm->getSyncLog(),
    ];

    echo json_encode(['success' => true, 'status' => $status], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
