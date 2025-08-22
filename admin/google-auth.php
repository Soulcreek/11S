<?php
// File: admin/google-auth.php
// Description: Google OAuth handler for admin login

session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['credential'])) {
        throw new Exception('Google credential is required');
    }
    
    // Load Google client ID from config
    $config_file = __DIR__ . '/data/auth-config.json';
    $config = [];
    if (file_exists($config_file)) {
        $config = json_decode(file_get_contents($config_file), true) ?? [];
    }
    
    $google_client_id = $config['auth']['google_client_id'] ?? '';
    if (empty($google_client_id)) {
        throw new Exception('Google client ID not configured');
    }
    
    // Verify JWT token
    $jwt_parts = explode('.', $input['credential']);
    if (count($jwt_parts) !== 3) {
        throw new Exception('Invalid JWT format');
    }
    
    // Decode payload (simple verification - for production use proper JWT library)
    $payload = json_decode(base64_decode($jwt_parts[1]), true);
    
    if (!$payload || !isset($payload['email'])) {
        throw new Exception('Invalid JWT payload');
    }
    
    // Check if user is authorized admin
    $authorized_admins = [
        'admin@11seconds.de',
        // Add more admin emails here
    ];
    
    if (!in_array($payload['email'], $authorized_admins)) {
        throw new Exception('Unauthorized: Only admin accounts can access the admin center');
    }
    
    // Set admin session
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user'] = [
        'email' => $payload['email'],
        'name' => $payload['name'] ?? 'Admin',
        'picture' => $payload['picture'] ?? '',
        'login_method' => 'google'
    ];
    
    echo json_encode(['success' => true, 'message' => 'Google login successful']);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
