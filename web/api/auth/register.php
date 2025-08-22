<?php
// File: web/api/auth/register.php
// Description: User registration endpoint

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
    
    if (!$input) {
        throw new Exception('Invalid input data');
    }
    
    $auth_manager = new AuthManager();
    
    // Prepare registration data
    $registration_data = [
        'email' => $input['email'] ?? null,
        'phone' => $input['phone'] ?? null,
        'password' => $input['password'] ?? null,
        'username' => $input['username'] ?? null,
        'guest_data' => $input['guest_data'] ?? null,
        'ip' => $input['ip'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    $result = $auth_manager->registerUser($registration_data);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'user_id' => $result['user_id'],
            'username' => $result['username'],
            'verification_sent' => $result['verification_sent'] ?? false,
            'message' => $result['message']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
    }
    
} catch (Exception $e) {
    error_log('Registration error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Fehler bei der Registrierung'
    ]);
}
?>
