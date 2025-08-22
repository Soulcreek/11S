<?php
// File: web/api/auth/login.php
// Description: User login endpoint

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
    
    if (!$input || !isset($input['identifier']) || !isset($input['password'])) {
        throw new Exception('Identifier and password are required');
    }
    
    $auth_manager = new AuthManager();
    $result = $auth_manager->authenticateUser($input['identifier'], $input['password']);
    
    if ($result['success']) {
        // Generate session token
        $session_token = bin2hex(random_bytes(32));
        
        // Store session
        $sessions_file = '../../../admin/data/user_sessions.json';
        $sessions = [];
        if (file_exists($sessions_file)) {
            $sessions = json_decode(file_get_contents($sessions_file), true) ?? [];
        }
        
        $sessions[$session_token] = [
            'user_id' => $result['user']['user_id'],
            'created_at' => time(),
            'expires_at' => time() + 3600, // 1 hour
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        // Clean up expired sessions
        $now = time();
        $sessions = array_filter($sessions, function($session) use ($now) {
            return $session['expires_at'] > $now;
        });
        
        file_put_contents($sessions_file, json_encode($sessions, JSON_PRETTY_PRINT));
        
        echo json_encode([
            'success' => true,
            'user' => $result['user'],
            'session_token' => $session_token,
            'message' => $result['message']
        ]);
    } else {
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Fehler bei der Anmeldung'
    ]);
}
?>
