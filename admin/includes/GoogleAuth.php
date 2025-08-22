<?php
// File: admin/includes/GoogleAuth.php
// Description: Google OAuth integration for secure authentication

class GoogleAuth {
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    private $auth_manager;
    
    public function __construct($config, $auth_manager) {
        $this->client_id = $config['auth']['google_client_id'] ?? '';
        $this->client_secret = $config['auth']['google_client_secret'] ?? '';
        $this->redirect_uri = $config['auth']['google_redirect_uri'] ?? '';
        $this->auth_manager = $auth_manager;
    }
    
    public function getAuthUrl($state = null) {
        if (empty($this->client_id)) {
            throw new Exception('Google Client ID not configured');
        }
        
        $params = [
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'scope' => 'openid email profile',
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];
        
        if ($state) {
            $params['state'] = $state;
        }
        
        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
    }
    
    public function handleCallback($code, $state = null) {
        if (empty($code)) {
            return ['success' => false, 'error' => 'Authorization code not provided'];
        }
        
        // Exchange code for access token
        $token_data = $this->exchangeCodeForToken($code);
        if (!$token_data) {
            return ['success' => false, 'error' => 'Failed to exchange code for token'];
        }
        
        // Get user info from Google
        $user_info = $this->getUserInfo($token_data['access_token']);
        if (!$user_info) {
            return ['success' => false, 'error' => 'Failed to get user information'];
        }
        
        // Check if user exists or create new one
        return $this->handleGoogleUser($user_info, $state);
    }
    
    private function exchangeCodeForToken($code) {
        $post_data = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri' => $this->redirect_uri,
            'grant_type' => 'authorization_code',
            'code' => $code
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            error_log('Google token exchange failed: ' . $response);
            return false;
        }
        
        return json_decode($response, true);
    }
    
    private function getUserInfo($access_token) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            error_log('Google user info failed: ' . $response);
            return false;
        }
        
        return json_decode($response, true);
    }
    
    private function handleGoogleUser($google_user, $state = null) {
        $users = $this->auth_manager->getAllUsers();
        
        // Check if user exists by email or Google ID
        foreach ($users as $user) {
            if ($user['email'] === $google_user['email'] || 
                (isset($user['google_id']) && $user['google_id'] === $google_user['id'])) {
                
                // Update Google info if needed
                $this->updateGoogleUser($user['user_id'], $google_user);
                
                return [
                    'success' => true,
                    'user' => $user,
                    'message' => 'Google Anmeldung erfolgreich'
                ];
            }
        }
        
        // Create new user from Google account
        return $this->createGoogleUser($google_user, $state);
    }
    
    private function updateGoogleUser($user_id, $google_user) {
        $users_file = __DIR__ . '/../data/users.json';
        $users = json_decode(file_get_contents($users_file), true) ?? [];
        
        foreach ($users as &$user) {
            if ($user['user_id'] === $user_id) {
                $user['google_id'] = $google_user['id'];
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
        
        file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    private function createGoogleUser($google_user, $state = null) {
        // Handle guest conversion if state contains guest data
        $guest_data = null;
        if ($state) {
            $decoded_state = json_decode(base64_decode($state), true);
            if ($decoded_state && isset($decoded_state['guest_user_id'])) {
                // Get guest user data for preservation
                $guest_data = $this->auth_manager->getUser($decoded_state['guest_user_id']);
            }
        }
        
        $user_data = [
            'email' => $google_user['email'],
            'username' => $google_user['name'] ?? 'User' . substr($google_user['id'], -6),
            'google_login' => true,
            'guest_data' => $guest_data,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $result = $this->auth_manager->registerUser($user_data);
        
        if ($result['success']) {
            // Update with Google specific data
            $users_file = __DIR__ . '/../data/users.json';
            $users = json_decode(file_get_contents($users_file), true) ?? [];
            
            foreach ($users as &$user) {
                if ($user['user_id'] === $result['user_id']) {
                    $user['google_id'] = $google_user['id'];
                    $user['google_email'] = $google_user['email'];
                    $user['google_name'] = $google_user['name'];
                    $user['google_picture'] = $google_user['picture'] ?? '';
                    $user['is_verified'] = true; // Google accounts are pre-verified
                    $user['auth_method'] = 'google';
                    break;
                }
            }
            
            file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            // Delete guest user if converted
            if ($guest_data) {
                $this->auth_manager->deleteUser($guest_data['user_id']);
            }
            
            $result['message'] = 'Google Konto erfolgreich verknüpft';
        }
        
        return $result;
    }
}
?>
