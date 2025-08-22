<?php
// File: web/api/auth/verify.php
// Description: Email/SMS verification endpoint

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once '../../../admin/includes/AuthManager.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['user_id']) || !isset($input['code'])) {
        throw new Exception('User ID and code are required');
    }
    
    $auth_manager = new AuthManager();
    $result = $auth_manager->verifyUser($input['user_id'], $input['code']);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('Verification error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Fehler bei der Bestätigung'
    ]);
}
?>
