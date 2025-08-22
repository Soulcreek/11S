<?php
// auth-google.php - Google OAuth handler for static hosting
// This file processes Google Sign-In tokens directly

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'admin/includes/DatabaseManager.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    $google_credential = $_POST['google_credential'] ?? '';
    
    if (empty($google_credential)) {
        throw new Exception('Google credential missing');
    }

    // Decode the JWT token from Google
    $parts = explode('.', $google_credential);
    
    if (count($parts) !== 3) {
        throw new Exception('Invalid Google credential format');
    }

    // Decode the payload (user info)
    $payload = json_decode(base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4, '=', STR_PAD_RIGHT)), true);
    
    if (!$payload || !isset($payload['email'])) {
        throw new Exception('Invalid Google credential payload');
    }

    $db = DatabaseManager::getInstance();
    
    // Check if user exists
    $existing_user = $db->query("SELECT * FROM users WHERE email = ? OR google_id = ?", [$payload['email'], $payload['sub']]);
    
    if (empty($existing_user)) {
        // Create new user
        $username = $payload['given_name'] ?? explode('@', $payload['email'])[0];
        
        // Ensure unique username
        $base_username = $username;
        $counter = 1;
        while (!empty($db->query("SELECT id FROM users WHERE username = ?", [$username]))) {
            $username = $base_username . $counter;
            $counter++;
        }
        
        $db->query("
            INSERT INTO users (username, email, google_id, verified, account_type, created_at) 
            VALUES (?, ?, ?, TRUE, 'user', NOW())
        ", [$username, $payload['email'], $payload['sub']]);
        
        $userId = $db->getConnection()->lastInsertId();
        
        // Create user stats entry
        $db->query("INSERT INTO user_stats (user_id) VALUES (?)", [$userId]);
        
        $user = [
            'id' => $userId,
            'username' => $username,
            'email' => $payload['email'],
            'google_id' => $payload['sub'],
            'account_type' => 'user'
        ];
    } else {
        $user = $existing_user[0];
        
        // Update Google ID if not set
        if (empty($user['google_id'])) {
            $db->query("UPDATE users SET google_id = ?, last_login = NOW() WHERE id = ?", [$payload['sub'], $user['id']]);
            $user['google_id'] = $payload['sub'];
        } else {
            $db->query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
        }
    }

    // Create session token
    $sessionToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $db->query("
        INSERT INTO sessions (id, user_id, expires_at, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?)
    ", [
        $sessionToken,
        $user['id'],
        $expiresAt,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);

    // Return success response
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'account_type' => $user['account_type']
        ],
        'session_token' => $sessionToken,
        'message' => 'Google authentication successful'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
