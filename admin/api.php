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
    
    // 🔐 AUTHENTICATION
    case 'login':
        if ($method !== 'POST') sendError('POST required');
        
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            sendError('Username and password required');
        }
        
        $user = $db->queryOne("SELECT * FROM users WHERE username = ? AND active = 1", [$username]);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            sendError('Invalid credentials');
        }
        
        if ($user['role'] !== 'admin') {
            sendError('Admin access required');
        }
        
        // Set session
        $_SESSION['admin_user'] = $user;
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
                'POST /api.php?action=login' => 'Admin login',
                'GET /api.php?action=logout' => 'Admin logout',
                'GET /api.php?action=stats' => 'Dashboard statistics',
                'GET /api.php?action=users' => 'List users',
                'POST /api.php?action=users' => 'Create user',
                'GET /api.php?action=questions' => 'List questions',
                'POST /api.php?action=questions' => 'Create question',
                'GET /api.php?action=sessions' => 'List game sessions',
                'GET /api.php?action=settings' => 'Get settings',
                'GET /api.php?action=game-question' => 'Get random question for game'
            ],
            'version' => '2.0',
            'status' => 'Green Glass Design System Active'
        ], '11Seconds Admin API - Ready');
        break;
}
?>
