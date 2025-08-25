<?php
/**
 * 11Seconds Admin API - Backend für Green Glass Admin Center
 * Version: 2.0 - Sichere PHP API mit Session Management
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'database.php';

// Error handling
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function sendSuccess($data = [], $message = 'Success') {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $message]);
    exit;
}

// Authentication check
function requireAuth() {
    if (!isset($_SESSION['admin_user']) || !$_SESSION['admin_user']) {
        sendError('Authentication required', 401);
    }
}

// Get database instance
try {
    $db = Database::getInstance();
} catch (Exception $e) {
    sendError('Database connection failed: ' . $e->getMessage(), 500);
}

// Route handling
$action = $_GET['action'] ?? $_POST['action'] ?? 'help';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    
    // 🧪 DATABASE TEST
    case 'test':
        try {
            $db = Database::getInstance();
            $result = $db->queryOne("SELECT 1 as test_value");
            sendSuccess(['database' => 'connected', 'test_result' => $result], 'Database test successful');
        } catch (Exception $e) {
            sendError('Database test failed: ' . $e->getMessage(), 500);
        }
        break;
    

    case 'tail-api-log':
        // guarded endpoint for quick diagnostics
        $token = $_GET['token'] ?? '';
        $cfgToken = $_ENV['ADMIN_RESET_TOKEN'] ?? '';
        if (!$token || !$cfgToken || !hash_equals($cfgToken, $token)) {
            sendError('Unauthorized', 401);
        }
        $logFile = __DIR__ . '/api-error.log';
        if (!is_file($logFile)) {
            sendSuccess(['log' => 'no log file']);
        }
        $lines = @file($logFile, FILE_IGNORE_NEW_LINES) ?: [];
        $tail = array_slice($lines, -200);
        sendSuccess(['log' => implode("\n", $tail)]);
        break;

    case 'health':
        // Public, non-destructive readiness probe
        $resp = [ 'ok' => true, 'version' => '2.0', 'timestamp' => gmdate('c') ];
        try {
            $one = $db->queryOne('SELECT 1 AS v');
            $resp['database'] = ['connected' => ($one && intval($one['v']) === 1)];
            if (!$resp['database']['connected']) { $resp['ok'] = false; }
        } catch (Throwable $e) {
            $resp['database'] = ['connected' => false];
            $resp['ok'] = false;
        }
        sendSuccess($resp);
        break;

    case 'integrity-lite':
        // Public, read-only integrity snapshot (no writes, no schema details)
        $out = [ 'ok' => true, 'checks' => [] ];
        try {
            $db->queryOne('SELECT 1 AS v');
            $out['checks'][] = ['name' => 'connection', 'ok' => true];
        } catch (Throwable $e) {
            $out['checks'][] = ['name' => 'connection', 'ok' => false];
            $out['ok'] = false;
        }
        foreach (['users','questions','game_sessions'] as $tbl) {
            try {
                $row = $db->queryOne("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?", [$tbl]);
                $present = $row && intval($row['c']) > 0;
                $out['checks'][] = ['name' => "table:$tbl", 'ok' => $present];
                if (!$present) { $out['ok'] = false; }
            } catch (Throwable $e) {
                $out['checks'][] = ['name' => "table:$tbl", 'ok' => false];
                $out['ok'] = false;
            }
        }
        try {
            $cs = $db->queryOne('SHOW VARIABLES LIKE "character_set_connection"');
            $coll = $db->queryOne('SHOW VARIABLES LIKE "collation_connection"');
            $out['checks'][] = ['name' => 'charset', 'ok' => (bool)($cs['Value'] ?? $cs['value'] ?? null)];
            $out['checks'][] = ['name' => 'collation', 'ok' => (bool)($coll['Value'] ?? $coll['value'] ?? null)];
        } catch (Throwable $e) {
            $out['checks'][] = ['name' => 'charset', 'ok' => false];
            $out['checks'][] = ['name' => 'collation', 'ok' => false];
        }
        sendSuccess($out);
        break;

    case 'integrity-check':
        // Allow if admin session or with token (ADMIN_RESET_TOKEN)
        $isAdmin = isset($_SESSION['admin_user']) && ($_SESSION['admin_user']['role'] ?? '') === 'admin';
        $token = $_GET['token'] ?? '';
        $cfgToken = $_ENV['ADMIN_RESET_TOKEN'] ?? '';
        if (!$isAdmin && (!$token || !$cfgToken || !hash_equals($cfgToken, $token))) {
            sendError('Unauthorized', 401);
        }

        $result = [
            'ok' => true,
            'checks' => []
        ];
        // 1) Connection sanity
        try {
            $one = $db->queryOne('SELECT 1 AS v');
            $result['checks'][] = ['name' => 'connection', 'ok' => ($one && intval($one['v']) === 1) ? true : false];
            if (!$one) { $result['ok'] = false; }
        } catch (Throwable $e) {
            $result['checks'][] = ['name' => 'connection', 'ok' => false, 'error' => $e->getMessage()];
            $result['ok'] = false;
        }

        // 2) Required tables/columns
        $required = [
            'users' => ['id','username','email','password_hash','role','active','created_at','updated_at','last_login'],
            'questions' => ['id','question_text|text|content','active','created_at','updated_at'],
            'game_sessions' => ['id','user_id','score|points','created_at']
        ];
        foreach ($required as $table => $columns) {
            try {
                $exists = $db->queryOne("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?", [$table]);
                $present = $exists && intval($exists['c']) > 0;
                $missingCols = [];
                if ($present) {
                    $colRows = $db->query("SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ?", [$table]);
                    $presentCols = array_map(function($r){ return strtolower($r['column_name']); }, $colRows ?: []);
                    foreach ($columns as $colSpec) {
                        // allow alternatives with |
                        $alts = array_map('trim', explode('|', strtolower($colSpec)));
                        $has = false;
                        foreach ($alts as $alt) { if (in_array($alt, $presentCols, true)) { $has = true; break; } }
                        if (!$has) { $missingCols[] = $colSpec; }
                    }
                }
                $ok = $present && count($missingCols) === 0;
                $result['checks'][] = ['name' => "schema:$table", 'ok' => $ok, 'missing' => $missingCols];
                if (!$ok) { $result['ok'] = false; }
            } catch (Throwable $e) {
                $result['checks'][] = ['name' => "schema:$table", 'ok' => false, 'error' => $e->getMessage()];
                $result['ok'] = false;
            }
        }

        // 3) Write probe using a TEMPORARY table (no persistent side effects)
        try {
            $db->execute('CREATE TEMPORARY TABLE IF NOT EXISTS diag_tmp (v INT) ENGINE=MEMORY');
            $db->execute('INSERT INTO diag_tmp (v) VALUES (42)');
            $probe = $db->queryOne('SELECT COUNT(*) AS c, MAX(v) AS maxv FROM diag_tmp');
            $ok = $probe && intval($probe['c']) >= 1 && intval($probe['maxv']) === 42;
            $result['checks'][] = ['name' => 'write_probe', 'ok' => $ok];
            if (!$ok) { $result['ok'] = false; }
        } catch (Throwable $e) {
            $result['checks'][] = ['name' => 'write_probe', 'ok' => false, 'error' => $e->getMessage()];
            $result['ok'] = false;
        }

        // 4) Character set / collation
        try {
            $cs = $db->queryOne('SHOW VARIABLES LIKE "character_set_connection"');
            $coll = $db->queryOne('SHOW VARIABLES LIKE "collation_connection"');
            $charset = $cs['Value'] ?? ($cs['value'] ?? null);
            $collation = $coll['Value'] ?? ($coll['value'] ?? null);
            $result['checks'][] = ['name' => 'charset', 'ok' => is_string($charset), 'value' => $charset];
            $result['checks'][] = ['name' => 'collation', 'ok' => is_string($collation), 'value' => $collation];
        } catch (Throwable $e) {
            $result['checks'][] = ['name' => 'charset', 'ok' => false, 'error' => $e->getMessage()];
        }

        sendSuccess($result);
        break;
    // 🔐 AUTHENTICATION
    case 'login':
        if ($method !== 'POST') sendError('POST required');
        try {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                sendError('Username and password required');
            }

            $identity = trim($username);
            $user = $db->queryOne("SELECT * FROM users WHERE active = 1 AND (username = ? OR email = ?) LIMIT 1", [$identity, $identity]);

            $hash = is_array($user ?? null) && array_key_exists('password_hash', $user) ? $user['password_hash'] : null;
            if (!$user || !is_string($hash) || $hash === '' || !password_verify($password, $hash)) {
                sendError('Invalid credentials');
            }

            if (($user['role'] ?? 'user') !== 'admin') {
                sendError('Admin access required');
            }

            // Set session
            $_SESSION['admin_user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ];
            $_SESSION['login_time'] = time();

            // Update last login
            $db->execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);

            sendSuccess([
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ], 'Login successful');
        } catch (Throwable $ex) {
            // Minimal diagnostic log on server, avoid leaking details to client
            @file_put_contents(__DIR__ . '/api-error.log', date('[Y-m-d H:i:s] ') . 'LOGIN_ERROR: ' . $ex->getMessage() . "\n", FILE_APPEND | LOCK_EX);
            sendError('Login failed', 500);
        }
        break;

    // 🛠 ADMIN RESET (token-gated)
    case 'reset-admin':
        if ($method !== 'POST') sendError('POST required');

        // Token from .env must match
        $provided = $_POST['token'] ?? $_GET['token'] ?? '';
        $expected = $_ENV['ADMIN_RESET_TOKEN'] ?? '';
        if (empty($expected) || $provided !== $expected) {
            sendError('Unauthorized', 401);
        }

        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || strlen($password) < 8) {
            sendError('Username and password (>=8 chars) required');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $existing = $db->queryOne("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1", [$username, $email ?: $username]);

        if ($existing) {
            $ok = $db->execute(
                "UPDATE users SET username = ?, email = ?, password_hash = ?, role = 'admin', active = 1 WHERE id = ?",
                [$username, $email, $hash, $existing['id']]
            );
            sendSuccess(['id' => $existing['id'], 'updated' => (bool)$ok], 'Admin user updated');
        } else {
            $ok = $db->execute(
                "INSERT INTO users (username, email, password_hash, role, active) VALUES (?, ?, ?, 'admin', 1)",
                [$username, $email, $hash]
            );
            sendSuccess(['id' => $db->lastInsertId(), 'created' => (bool)$ok], 'Admin user created');
        }
        break;
    
    case 'logout':
        session_destroy();
        sendSuccess([], 'Logged out successfully');
        break;
    
    case 'check-auth':
        requireAuth();
        sendSuccess([
            'user' => [
                'username' => $_SESSION['admin_user']['username'],
                'role' => $_SESSION['admin_user']['role']
            ]
        ]);
        break;
    
    // 📊 DASHBOARD STATS
    case 'stats':
        requireAuth();
        $stats = $db->getStats();
        sendSuccess($stats);
        break;
    
    // 👥 USER MANAGEMENT
    case 'users':
        requireAuth();
        if ($method === 'GET') {
            $users = $db->query("SELECT id, username, email, role, active, created_at, last_login FROM users ORDER BY created_at DESC");
            sendSuccess($users);
        }
        elseif ($method === 'POST') {
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'user';
            
            if (empty($username) || empty($password)) {
                sendError('Username and password required');
            }
            
            // Check if username exists
            $existing = $db->queryOne("SELECT id FROM users WHERE username = ?", [$username]);
            if ($existing) {
                sendError('Username already exists');
            }
            
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $success = $db->execute(
                "INSERT INTO users (username, email, password_hash, role, active) VALUES (?, ?, ?, ?, 1)",
                [$username, $email, $passwordHash, $role]
            );
            
            if ($success) {
                sendSuccess(['id' => $db->lastInsertId()], 'User created successfully');
            } else {
                sendError('Failed to create user');
            }
        }
        break;
    
    case 'user':
        requireAuth();
        $userId = $_GET['id'] ?? $_POST['id'] ?? 0;
        
        if ($method === 'DELETE') {
            if ($userId == $_SESSION['admin_user']['id']) {
                sendError('Cannot delete your own account');
            }
            
            $success = $db->execute("UPDATE users SET active = 0 WHERE id = ?", [$userId]);
            sendSuccess([], $success ? 'User deactivated' : 'Failed to deactivate user');
        }
        elseif ($method === 'PUT') {
            parse_str(file_get_contents('php://input'), $data);
            $username = $data['username'] ?? '';
            $email = $data['email'] ?? '';
            $role = $data['role'] ?? 'user';
            
            $success = $db->execute(
                "UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?",
                [$username, $email, $role, $userId]
            );
            
            sendSuccess([], $success ? 'User updated' : 'Failed to update user');
        }
        break;
    
    // ❓ QUESTION MANAGEMENT
    case 'questions':
        requireAuth();
        if ($method === 'GET') {
            $questions = $db->query("SELECT * FROM questions ORDER BY created_at DESC LIMIT 100");
            sendSuccess($questions);
        }
        elseif ($method === 'POST') {
            $question = $_POST['question'] ?? '';
            $correct = $_POST['correct_answer'] ?? '';
            $wrong1 = $_POST['wrong_answer1'] ?? '';
            $wrong2 = $_POST['wrong_answer2'] ?? '';
            $wrong3 = $_POST['wrong_answer3'] ?? '';
            $category = $_POST['category'] ?? 'General';
            $difficulty = $_POST['difficulty'] ?? 'medium';
            
            if (empty($question) || empty($correct) || empty($wrong1) || empty($wrong2) || empty($wrong3)) {
                sendError('All fields required');
            }
            
            $success = $db->execute(
                "INSERT INTO questions (question, correct_answer, wrong_answer1, wrong_answer2, wrong_answer3, category, difficulty) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$question, $correct, $wrong1, $wrong2, $wrong3, $category, $difficulty]
            );
            
            sendSuccess(['id' => $db->lastInsertId()], $success ? 'Question added' : 'Failed to add question');
        }
        break;
    
    case 'question':
        requireAuth();
        $questionId = $_GET['id'] ?? $_POST['id'] ?? 0;
        
        if ($method === 'DELETE') {
            $success = $db->execute("UPDATE questions SET active = 0 WHERE id = ?", [$questionId]);
            sendSuccess([], $success ? 'Question deactivated' : 'Failed to deactivate question');
        }
        elseif ($method === 'PUT') {
            parse_str(file_get_contents('php://input'), $data);
            $success = $db->execute(
                "UPDATE questions SET question = ?, correct_answer = ?, wrong_answer1 = ?, wrong_answer2 = ?, wrong_answer3 = ?, category = ?, difficulty = ? WHERE id = ?",
                [$data['question'], $data['correct_answer'], $data['wrong_answer1'], $data['wrong_answer2'], $data['wrong_answer3'], $data['category'], $data['difficulty'], $questionId]
            );
            
            sendSuccess([], $success ? 'Question updated' : 'Failed to update question');
        }
        break;
    
    // 🎯 GAME SESSIONS
    case 'sessions':
        requireAuth();
        $sessions = $db->query("SELECT * FROM game_sessions ORDER BY start_time DESC LIMIT 50");
        sendSuccess($sessions);
        break;
    
    // ⚙️ SETTINGS
    case 'settings':
        requireAuth();
        if ($method === 'GET') {
            // Return current settings
            sendSuccess([
                'game_duration' => 11,
                'questions_per_game' => 10,
                'difficulty_levels' => ['easy', 'medium', 'hard'],
                'maintenance_mode' => false
            ]);
        }
        elseif ($method === 'POST') {
            // Save settings (implement as needed)
            sendSuccess([], 'Settings saved');
        }
        break;
    
    // 🎮 GAME API (for testing)
    case 'game-question':
        // Public endpoint for getting random questions
        $difficulty = $_GET['difficulty'] ?? 'medium';
        $question = $db->queryOne(
            "SELECT id, question, correct_answer, wrong_answer1, wrong_answer2, wrong_answer3 FROM questions WHERE difficulty = ? AND active = 1 ORDER BY RAND() LIMIT 1",
            [$difficulty]
        );
        
        if ($question) {
            // Shuffle answers
            $answers = [
                $question['correct_answer'],
                $question['wrong_answer1'],
                $question['wrong_answer2'],
                $question['wrong_answer3']
            ];
            shuffle($answers);
            
            sendSuccess([
                'id' => $question['id'],
                'question' => $question['question'],
                'answers' => $answers,
                'correct_answer' => $question['correct_answer']
            ]);
        } else {
            sendError('No questions found');
        }
        break;
    
    // 📋 HELP
    case 'help':
    default:
        sendSuccess([
            'endpoints' => [
                'GET /api.php?action=health' => 'Public readiness (DB ping only)',
                'GET /api.php?action=integrity-lite' => 'Public read-only integrity snapshot',
                'POST /api.php?action=login' => 'Admin login',
                'GET /api.php?action=logout' => 'Admin logout',
                'GET /api.php?action=stats' => 'Dashboard statistics',
                'GET /api.php?action=users' => 'List users',
                'POST /api.php?action=users' => 'Create user',
                'GET /api.php?action=questions' => 'List questions',
                'POST /api.php?action=questions' => 'Create question',
                'GET /api.php?action=sessions' => 'List game sessions',
                'GET /api.php?action=settings' => 'Get settings',
                'GET /api.php?action=game-question' => 'Get random question for game',
                'GET /api.php?action=integrity-check&token=...' => 'Full integrity incl. write probe (token required)',
                'GET /api.php?action=tail-api-log&token=...' => 'Last 200 lines of server API log (token required)'
            ],
            'version' => '2.0',
            'status' => 'Green Glass Design System Active'
        ], '11Seconds Admin API - Ready');
        break;
}
?>
