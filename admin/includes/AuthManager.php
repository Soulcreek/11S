<?php
// File: admin/includes/AuthManager.php
// Description: Comprehensive authentication and user management system

class AuthManager {
    private $users_file;
    private $sessions_file;
    private $config_file;
    private $config;
    private $data_dir;
    
    public function __construct() {
        $this->data_dir = __DIR__ . '/../data';
        $this->users_file = $this->data_dir . '/users.json';
        $this->sessions_file = $this->data_dir . '/sessions.json';
        $this->config_file = $this->data_dir . '/auth-config.json';
        
        // Ensure directories exist
        if (!is_dir($this->data_dir)) {
            mkdir($this->data_dir, 0755, true);
        }
        
        // Load configuration
        $this->config = $this->loadConfig();
    }
    
    private function loadConfig() {
        if (!file_exists($this->config_file)) {
            return [];
        }
        return json_decode(file_get_contents($this->config_file), true) ?? [];
    }
    
    private function saveUsers($users) {
        file_put_contents($this->users_file, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    private function loadUsers() {
        if (!file_exists($this->users_file)) {
            return [];
        }
        return json_decode(file_get_contents($this->users_file), true) ?? [];
    }
    
    private function saveSessions($sessions) {
        file_put_contents($this->sessions_file, json_encode($sessions, JSON_PRETTY_PRINT));
    }
    
    private function loadSessions() {
        if (!file_exists($this->sessions_file)) {
            return [];
        }
        return json_decode(file_get_contents($this->sessions_file), true) ?? [];
    }
    
    private function generateUserId() {
        return 'user_' . uniqid() . '_' . time();
    }
    
    private function generateGuestId() {
        return 'guest_' . rand(1000, 9999) . '_' . time();
    }
    
    private function generateVerificationCode() {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    private function hashPassword($password, $salt = null) {
        if ($salt === null) {
            $salt = bin2hex(random_bytes(16));
        }
        return [
            'hash' => hash_pbkdf2('sha256', $password, $salt, 10000),
            'salt' => $salt
        ];
    }
    
    private function verifyPassword($password, $hash, $salt) {
        $computed_hash = hash_pbkdf2('sha256', $password, $salt, 10000);
        return hash_equals($hash, $computed_hash);
    }
    
    private function validatePassword($password) {
        $requirements = $this->config['security']['password_requirements'] ?? [];
        
        $errors = [];
        
        if (strlen($password) < ($requirements['min_length'] ?? 8)) {
            $errors[] = 'Passwort muss mindestens ' . ($requirements['min_length'] ?? 8) . ' Zeichen lang sein';
        }
        
        if (($requirements['require_uppercase'] ?? false) && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Passwort muss mindestens einen Großbuchstaben enthalten';
        }
        
        if (($requirements['require_lowercase'] ?? false) && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Passwort muss mindestens einen Kleinbuchstaben enthalten';
        }
        
        if (($requirements['require_numbers'] ?? false) && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Passwort muss mindestens eine Zahl enthalten';
        }
        
        if (($requirements['require_special'] ?? false) && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Passwort muss mindestens ein Sonderzeichen enthalten';
        }
        
        return $errors;
    }
    
    private function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    private function validatePhone($phone) {
        // Simple international phone number validation
        return preg_match('/^\+?[1-9]\d{10,14}$/', preg_replace('/[^\d+]/', '', $phone));
    }
    
    private function sendVerificationEmail($email, $code, $username) {
        $smtp_config = $this->config['auth']['email_smtp'] ?? [];
        
        if (empty($smtp_config['username']) || empty($smtp_config['password'])) {
            error_log('SMTP configuration missing');
            return false;
        }
        
        $subject = '11Seconds Quiz - E-Mail Bestätigung';
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f8fffe; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
                .header { text-align: center; margin-bottom: 30px; }
                .logo { font-size: 32px; font-weight: bold; color: #2ECC71; margin-bottom: 10px; }
                .code { background: #2ECC71; color: white; padding: 15px 30px; border-radius: 8px; font-size: 24px; font-weight: bold; letter-spacing: 3px; margin: 20px 0; display: inline-block; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>🎯 11Seconds Quiz</div>
                    <h2 style='color: #27AE60; margin: 0;'>E-Mail Bestätigung</h2>
                </div>
                
                <p>Hallo " . htmlspecialchars($username) . ",</p>
                
                <p>vielen Dank für deine Registrierung bei 11Seconds Quiz! Bitte bestätige deine E-Mail-Adresse mit folgendem Code:</p>
                
                <div style='text-align: center;'>
                    <div class='code'>" . $code . "</div>
                </div>
                
                <p>Der Code ist 5 Minuten gültig. Falls du dich nicht registriert hast, kannst du diese E-Mail ignorieren.</p>
                
                <p>Viel Spaß beim Quiz!</p>
                <p>Dein 11Seconds Team</p>
                
                <div class='footer'>
                    <p>Diese E-Mail wurde automatisch generiert. Bitte antworte nicht auf diese E-Mail.</p>
                </div>
            </div>
        </body>
        </html>";
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $smtp_config['from_name'] . ' <' . $smtp_config['from_email'] . '>',
            'Reply-To: ' . $smtp_config['from_email'],
            'X-Mailer: PHP/' . phpversion()
        ];
        
        return mail($email, $subject, $body, implode("\r\n", $headers));
    }
    
    private function sendVerificationSMS($phone, $code) {
        $sms_config = $this->config['auth']['sms'] ?? [];
        
        if (empty($sms_config['account_sid']) || empty($sms_config['auth_token'])) {
            error_log('SMS configuration missing');
            return false;
        }
        
        $message = "11Seconds Quiz Bestätigung: " . $code . " (5 Min. gültig)";
        
        // Twilio API call would go here
        // For now, we'll log it for testing
        error_log("SMS to {$phone}: {$message}");
        
        return true; // Simulate success
    }
    
    private function checkRateLimit($action, $identifier) {
        $rate_limits = $this->config['security']['rate_limiting'] ?? [];
        $sessions = $this->loadSessions();
        
        $key = $action . '_' . $identifier;
        $now = time();
        $window = 60; // 1 minute
        
        if (!isset($sessions['rate_limits'][$key])) {
            $sessions['rate_limits'][$key] = [];
        }
        
        // Clean old attempts
        $sessions['rate_limits'][$key] = array_filter(
            $sessions['rate_limits'][$key],
            function($timestamp) use ($now, $window) {
                return ($now - $timestamp) < $window;
            }
        );
        
        $attempts = count($sessions['rate_limits'][$key]);
        $limit = $rate_limits[$action . '_attempts_per_minute'] ?? 10;
        
        if ($attempts >= $limit) {
            return false;
        }
        
        $sessions['rate_limits'][$key][] = $now;
        $this->saveSessions($sessions);
        
        return true;
    }
    
    public function createGuestUser() {
        $guest_id = $this->generateGuestId();
        $guest_number = rand(1000, 9999);
        
        $guest_user = [
            'user_id' => $guest_id,
            'username' => 'Gast#' . $guest_number,
            'email' => null,
            'phone' => null,
            'type' => 'guest',
            'is_verified' => false,
            'created_at' => time(),
            'expires_at' => time() + ($this->config['game']['guest_session_duration'] ?? 7200),
            'stats' => [
                'total_games' => 0,
                'total_score' => 0,
                'best_score' => 0,
                'average_score' => 0,
                'correct_answers' => 0,
                'total_answers' => 0,
                'total_time_played' => 0,
                'achievements' => [],
                'level' => 1,
                'xp' => 0
            ],
            'security' => [
                'session_token' => bin2hex(random_bytes(32)),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'last_activity' => time()
            ]
        ];
        
        return $guest_user;
    }
    
    public function registerUser($data) {
        if (!$this->checkRateLimit('registration', $data['ip'] ?? 'unknown')) {
            return ['success' => false, 'error' => 'Zu viele Registrierungsversuche. Bitte warte eine Minute.'];
        }
        
        $users = $this->loadUsers();
        
        // Validation
        if (empty($data['email']) && empty($data['phone'])) {
            return ['success' => false, 'error' => 'E-Mail oder Telefonnummer ist erforderlich.'];
        }
        
        if (!empty($data['email'])) {
            if (!$this->validateEmail($data['email'])) {
                return ['success' => false, 'error' => 'Ungültige E-Mail-Adresse.'];
            }
            
            // Check if email already exists
            foreach ($users as $user) {
                if ($user['email'] === $data['email']) {
                    return ['success' => false, 'error' => 'E-Mail-Adresse bereits registriert.'];
                }
            }
        }
        
        if (!empty($data['phone'])) {
            if (!$this->validatePhone($data['phone'])) {
                return ['success' => false, 'error' => 'Ungültige Telefonnummer.'];
            }
            
            // Check if phone already exists
            foreach ($users as $user) {
                if ($user['phone'] === $data['phone']) {
                    return ['success' => false, 'error' => 'Telefonnummer bereits registriert.'];
                }
            }
        }
        
        // Password validation
        if (!empty($data['password'])) {
            $password_errors = $this->validatePassword($data['password']);
            if (!empty($password_errors)) {
                return ['success' => false, 'error' => implode(' ', $password_errors)];
            }
        }
        
        // Generate user
        $user_id = $this->generateUserId();
        $username = !empty($data['username']) ? $data['username'] : 'User' . substr($user_id, -6);
        
        // Check if username exists
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                $username .= '_' . rand(100, 999);
                break;
            }
        }
        
        $verification_code = $this->generateVerificationCode();
        $password_data = !empty($data['password']) ? $this->hashPassword($data['password']) : null;
        
        $new_user = [
            'user_id' => $user_id,
            'username' => $username,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'type' => 'registered',
            'is_verified' => false,
            'verification_code' => $verification_code,
            'verification_expires' => time() + ($this->config['auth']['verification_code_expiry'] ?? 300),
            'password_hash' => $password_data['hash'] ?? null,
            'password_salt' => $password_data['salt'] ?? null,
            'created_at' => time(),
            'updated_at' => time(),
            'login_attempts' => 0,
            'locked_until' => 0,
            'stats' => [
                'total_games' => 0,
                'total_score' => 0,
                'best_score' => 0,
                'average_score' => 0,
                'correct_answers' => 0,
                'total_answers' => 0,
                'total_time_played' => 0,
                'achievements' => [],
                'level' => 1,
                'xp' => 0
            ],
            'security' => [
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'created_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'last_login' => null,
                'last_activity' => time()
            ]
        ];
        
        // If guest user is being converted, preserve stats
        if (!empty($data['guest_data'])) {
            $new_user['stats'] = $data['guest_data']['stats'] ?? $new_user['stats'];
        }
        
        $users[] = $new_user;
        $this->saveUsers($users);
        
        // Send verification
        $verification_sent = false;
        if (!empty($data['email'])) {
            $verification_sent = $this->sendVerificationEmail($data['email'], $verification_code, $username);
        } elseif (!empty($data['phone'])) {
            $verification_sent = $this->sendVerificationSMS($data['phone'], $verification_code);
        }
        
        return [
            'success' => true,
            'user_id' => $user_id,
            'username' => $username,
            'verification_sent' => $verification_sent,
            'message' => 'Registrierung erfolgreich. Bestätigungscode wurde gesendet.'
        ];
    }
    
    public function verifyUser($user_id, $code) {
        $users = $this->loadUsers();
        
        foreach ($users as &$user) {
            if ($user['user_id'] === $user_id) {
                if ($user['is_verified']) {
                    return ['success' => false, 'error' => 'Benutzer bereits bestätigt.'];
                }
                
                if ($user['verification_expires'] < time()) {
                    return ['success' => false, 'error' => 'Bestätigungscode abgelaufen.'];
                }
                
                if ($user['verification_code'] !== $code) {
                    return ['success' => false, 'error' => 'Ungültiger Bestätigungscode.'];
                }
                
                $user['is_verified'] = true;
                $user['verification_code'] = null;
                $user['verification_expires'] = null;
                $user['updated_at'] = time();
                
                $this->saveUsers($users);
                
                return ['success' => true, 'message' => 'E-Mail erfolgreich bestätigt.'];
            }
        }
        
        return ['success' => false, 'error' => 'Benutzer nicht gefunden.'];
    }
    
    public function authenticateUser($identifier, $password) {
        if (!$this->checkRateLimit('login', $identifier)) {
            return ['success' => false, 'error' => 'Zu viele Anmeldeversuche. Bitte warte eine Minute.'];
        }
        
        $users = $this->loadUsers();
        
        foreach ($users as &$user) {
            if ($user['email'] === $identifier || $user['username'] === $identifier) {
                // Check if account is locked
                if ($user['locked_until'] > time()) {
                    $remaining = ceil(($user['locked_until'] - time()) / 60);
                    return ['success' => false, 'error' => "Konto gesperrt für {$remaining} Minuten."];
                }
                
                if (!$this->verifyPassword($password, $user['password_hash'], $user['password_salt'])) {
                    $user['login_attempts']++;
                    
                    $max_attempts = $this->config['auth']['max_login_attempts'] ?? 5;
                    if ($user['login_attempts'] >= $max_attempts) {
                        $user['locked_until'] = time() + ($this->config['auth']['lockout_duration'] ?? 900);
                    }
                    
                    $this->saveUsers($users);
                    return ['success' => false, 'error' => 'Ungültiges Passwort.'];
                }
                
                if (!$user['is_verified']) {
                    return ['success' => false, 'error' => 'E-Mail nicht bestätigt. Bitte bestätige deine E-Mail-Adresse.'];
                }
                
                // Successful login
                $user['login_attempts'] = 0;
                $user['locked_until'] = 0;
                $user['security']['last_login'] = time();
                $user['security']['last_activity'] = time();
                $user['security']['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $user['security']['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                
                $this->saveUsers($users);
                
                return [
                    'success' => true,
                    'user' => $user,
                    'message' => 'Anmeldung erfolgreich.'
                ];
            }
        }
        
        return ['success' => false, 'error' => 'Benutzer nicht gefunden.'];
    }
    
    public function updateUserStats($user_id, $stats_update) {
        $users = $this->loadUsers();
        
        foreach ($users as &$user) {
            if ($user['user_id'] === $user_id) {
                // Security check for score manipulation
                if (isset($stats_update['score']) && $this->config['game']['highscore_verification']) {
                    if (!$this->validateScoreSubmission($user, $stats_update)) {
                        return ['success' => false, 'error' => 'Verdächtige Spielaktivität erkannt.'];
                    }
                }
                
                // Update stats
                foreach ($stats_update as $key => $value) {
                    if (isset($user['stats'][$key]) && is_numeric($value)) {
                        $user['stats'][$key] = $value;
                    }
                }
                
                // Recalculate derived stats
                if ($user['stats']['total_games'] > 0) {
                    $user['stats']['average_score'] = round($user['stats']['total_score'] / $user['stats']['total_games'], 2);
                }
                
                if ($user['stats']['total_answers'] > 0) {
                    $user['stats']['accuracy'] = round(($user['stats']['correct_answers'] / $user['stats']['total_answers']) * 100, 2);
                }
                
                $user['security']['last_activity'] = time();
                $user['updated_at'] = time();
                
                $this->saveUsers($users);
                
                return ['success' => true, 'user' => $user];
            }
        }
        
        return ['success' => false, 'error' => 'Benutzer nicht gefunden.'];
    }
    
    private function validateScoreSubmission($user, $stats_update) {
        // Anti-cheat measures
        $anti_cheat = $this->config['game']['anti_cheat'] ?? [];
        
        // Time validation
        if ($anti_cheat['time_validation'] ?? false) {
            $expected_min_time = 11; // 11 seconds minimum per game
            if (isset($stats_update['game_time']) && $stats_update['game_time'] < $expected_min_time) {
                return false;
            }
        }
        
        // Score reasonableness check
        if (isset($stats_update['score'])) {
            $max_reasonable_score = 1000; // Adjust based on your game
            if ($stats_update['score'] > $max_reasonable_score) {
                return false;
            }
        }
        
        return true;
    }
    
    public function getUser($user_id) {
        $users = $this->loadUsers();
        
        foreach ($users as $user) {
            if ($user['user_id'] === $user_id) {
                // Remove sensitive data
                unset($user['password_hash'], $user['password_salt'], $user['verification_code']);
                return $user;
            }
        }
        
        return null;
    }
    
    public function getAllUsers() {
        $users = $this->loadUsers();
        
        // Remove sensitive data
        foreach ($users as &$user) {
            unset($user['password_hash'], $user['password_salt'], $user['verification_code']);
        }
        
        return $users;
    }
    
    public function deleteUser($user_id) {
        $users = $this->loadUsers();
        
        $users = array_filter($users, function($user) use ($user_id) {
            return $user['user_id'] !== $user_id;
        });
        
        $this->saveUsers(array_values($users));
        
        return ['success' => true, 'message' => 'Benutzer gelöscht.'];
    }
}
?>
