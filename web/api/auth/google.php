<?php
// File: web/api/auth/google.php
// Description: Google OAuth authentication endpoint

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
require_once '../../../admin/includes/GoogleAuth.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['credential'])) {
        throw new Exception('Google credential is required');
    }
    
    // Load configuration
    $config_file = '../../../admin/data/auth-config.json';
    $config = [];
    if (file_exists($config_file)) {
        $config = json_decode(file_get_contents($config_file), true) ?? [];
    }
    
    // Verify Google JWT token
    $google_user = verifyGoogleJWT($input['credential']);
    if (!$google_user) {
        throw new Exception('Invalid Google credential');
    }
    
    $auth_manager = new AuthManager();
    
    // Check if user exists
    $users = $auth_manager->getAllUsers();
    $existing_user = null;
    
    foreach ($users as $user) {
        if ($user['email'] === $google_user['email'] || 
            (isset($user['google_id']) && $user['google_id'] === $google_user['sub'])) {
            $existing_user = $user;
            break;
        }
    }
    
    if ($existing_user) {
        // Update existing user with Google info
        $users_file = '../../../admin/data/users.json';
        $all_users = json_decode(file_get_contents($users_file), true) ?? [];
        
        foreach ($all_users as &$user) {
            if ($user['user_id'] === $existing_user['user_id']) {
                $user['google_id'] = $google_user['sub'];
                $user['google_email'] = $google_user['email'];
                $user['google_name'] = $google_user['name'];
                $user['google_picture'] = $google_user['picture'] ?? '';
                $user['is_verified'] = true; // Google accounts are pre-verified
                $user['updated_at'] = time();
                $user['security']['last_login'] = time();
                $user['security']['last_activity'] = time();
                break;
            }
        }
        
        file_put_contents($users_file, json_encode($all_users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $result_user = $existing_user;
        $message = 'Google Anmeldung erfolgreich';
    } else {
        // Create new user from Google account
        $guest_data = $input['guest_data'] ?? null;
        
        $user_data = [
            'email' => $google_user['email'],
            'username' => $google_user['name'] ?? 'User' . substr($google_user['sub'], -6),
            'google_login' => true,
            'guest_data' => $guest_data,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $result = $auth_manager->registerUser($user_data);
        
        if (!$result['success']) {
            throw new Exception($result['error']);
        }
        
        // Update with Google specific data
        $users_file = '../../../admin/data/users.json';
        $all_users = json_decode(file_get_contents($users_file), true) ?? [];
        
        foreach ($all_users as &$user) {
            if ($user['user_id'] === $result['user_id']) {
                $user['google_id'] = $google_user['sub'];
                $user['google_email'] = $google_user['email'];
                $user['google_name'] = $google_user['name'];
                $user['google_picture'] = $google_user['picture'] ?? '';
                $user['is_verified'] = true; // Google accounts are pre-verified
                $user['auth_method'] = 'google';
                $result_user = $user;
                break;
            }
        }
        
        file_put_contents($users_file, json_encode($all_users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Delete guest user if converted
        if ($guest_data) {
            $auth_manager->deleteUser($guest_data['user_id']);
        }
        
        $message = 'Google Konto erfolgreich erstellt und verknüpft';
    }
    
    // Generate session token
    $session_token = bin2hex(random_bytes(32));
    
    // Store session
    $sessions_file = '../../../admin/data/user_sessions.json';
    $sessions = [];
    if (file_exists($sessions_file)) {
        $sessions = json_decode(file_get_contents($sessions_file), true) ?? [];
    }
    
    $sessions[$session_token] = [
        'user_id' => $result_user['user_id'],
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
        'user' => $result_user,
        'session_token' => $session_token,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    error_log('Google auth error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Fehler bei der Google Anmeldung: ' . $e->getMessage()
    ]);
}

function verifyGoogleJWT($jwt_token) {
    // Simple JWT verification for Google tokens
    // In production, use a proper JWT library
    $parts = explode('.', $jwt_token);
    if (count($parts) !== 3) {
        return false;
    }
    
    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
    
    // Basic validation
    if (!$payload || !isset($payload['sub']) || !isset($payload['email'])) {
        return false;
    }
    
    // Check if token is from Google
    if ($payload['iss'] !== 'https://accounts.google.com' && $payload['iss'] !== 'accounts.google.com') {
        return false;
    }
    
    // Check expiration
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }
    
    return $payload;
}
?>
