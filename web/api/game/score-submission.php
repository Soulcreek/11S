<?php
// File: web/api/game/score-submission.php
// Description: Secure score submission with anti-cheat measures

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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

class ScoreValidator {
    private $config;
    private $auth_manager;
    
    public function __construct() {
        $config_file = '../../../admin/data/auth-config.json';
        $this->config = file_exists($config_file) ? 
            json_decode(file_get_contents($config_file), true) : [];
        $this->auth_manager = new AuthManager();
    }
    
    public function validateScoreSubmission($user_id, $score_data) {
        $validation_errors = [];
        
        // Get user data
        $user = $this->auth_manager->getUser($user_id);
        if (!$user) {
            return ['valid' => false, 'error' => 'Benutzer nicht gefunden'];
        }
        
        // Time validation
        if ($this->config['game']['anti_cheat']['time_validation'] ?? true) {
            $time_validation = $this->validateGameTime($score_data);
            if (!$time_validation['valid']) {
                $validation_errors[] = $time_validation['error'];
            }
        }
        
        // Score reasonableness check
        $score_validation = $this->validateScore($score_data, $user);
        if (!$score_validation['valid']) {
            $validation_errors[] = $score_validation['error'];
        }
        
        // Answer pattern detection
        if ($this->config['game']['anti_cheat']['answer_pattern_detection'] ?? true) {
            $pattern_validation = $this->validateAnswerPattern($score_data, $user);
            if (!$pattern_validation['valid']) {
                $validation_errors[] = $pattern_validation['error'];
            }
        }
        
        // Session integrity check
        if ($this->config['game']['anti_cheat']['session_integrity_check'] ?? true) {
            $session_validation = $this->validateSessionIntegrity($user_id, $score_data);
            if (!$session_validation['valid']) {
                $validation_errors[] = $session_validation['error'];
            }
        }
        
        // Rate limiting
        $rate_validation = $this->validateSubmissionRate($user_id);
        if (!$rate_validation['valid']) {
            $validation_errors[] = $rate_validation['error'];
        }
        
        if (empty($validation_errors)) {
            return ['valid' => true];
        } else {
            return [
                'valid' => false, 
                'error' => implode('; ', $validation_errors),
                'suspicious_activity' => true
            ];
        }
    }
    
    private function validateGameTime($score_data) {
        $game_time = $score_data['game_time'] ?? 0;
        $questions_count = $score_data['questions_answered'] ?? 1;
        
        // Each question should take at least 1 second (minimum realistic time)
        $minimum_time = $questions_count * 1;
        
        // Maximum reasonable time (5 minutes per question)
        $maximum_time = $questions_count * 300;
        
        if ($game_time < $minimum_time) {
            return [
                'valid' => false,
                'error' => 'Spiel zu schnell abgeschlossen'
            ];
        }
        
        if ($game_time > $maximum_time) {
            return [
                'valid' => false,
                'error' => 'Spiel zu lange gedauert'
            ];
        }
        
        return ['valid' => true];
    }
    
    private function validateScore($score_data, $user) {
        $score = $score_data['score'] ?? 0;
        $questions_answered = $score_data['questions_answered'] ?? 1;
        $correct_answers = $score_data['correct_answers'] ?? 0;
        
        // Score should not exceed maximum possible
        $max_possible_score = $questions_answered * 100; // Assuming 100 points per question
        
        if ($score > $max_possible_score) {
            return [
                'valid' => false,
                'error' => 'Score überschreitet Maximum'
            ];
        }
        
        // Score should match correct answers logic
        if ($correct_answers > $questions_answered) {
            return [
                'valid' => false,
                'error' => 'Mehr richtige Antworten als Fragen'
            ];
        }
        
        // Check for unrealistic improvement
        $previous_best = $user['stats']['best_score'] ?? 0;
        if ($score > $previous_best * 3 && $previous_best > 0) {
            // Allow but flag for review
            $this->flagForReview($user['user_id'], 'Dramatic score improvement', $score_data);
        }
        
        return ['valid' => true];
    }
    
    private function validateAnswerPattern($score_data, $user) {
        $answers = $score_data['answers'] ?? [];
        $response_times = $score_data['response_times'] ?? [];
        
        if (empty($answers) || empty($response_times)) {
            return ['valid' => true]; // Skip if no detailed data
        }
        
        // Check for bot-like patterns
        $avg_response_time = array_sum($response_times) / count($response_times);
        
        // Too consistent response times (possible bot)
        $variance = 0;
        foreach ($response_times as $time) {
            $variance += pow($time - $avg_response_time, 2);
        }
        $variance /= count($response_times);
        
        if ($variance < 0.1 && $avg_response_time < 2) {
            return [
                'valid' => false,
                'error' => 'Verdächtige Antwortmuster erkannt'
            ];
        }
        
        // Too many super-fast correct answers
        $fast_correct = 0;
        for ($i = 0; $i < count($answers); $i++) {
            if ($answers[$i] === true && ($response_times[$i] ?? 0) < 0.5) {
                $fast_correct++;
            }
        }
        
        if ($fast_correct > count($answers) * 0.8) {
            return [
                'valid' => false,
                'error' => 'Zu viele extrem schnelle richtige Antworten'
            ];
        }
        
        return ['valid' => true];
    }
    
    private function validateSessionIntegrity($user_id, $score_data) {
        $session_data = $score_data['session_data'] ?? [];
        
        // Check for required session markers
        if (empty($session_data['start_time']) || empty($session_data['user_agent'])) {
            return [
                'valid' => false,
                'error' => 'Fehlende Session-Daten'
            ];
        }
        
        // Validate session duration
        $session_duration = time() - $session_data['start_time'];
        $game_time = $score_data['game_time'] ?? 0;
        
        if ($session_duration < $game_time * 0.8) {
            return [
                'valid' => false,
                'error' => 'Session-Zeit stimmt nicht mit Spielzeit überein'
            ];
        }
        
        return ['valid' => true];
    }
    
    private function validateSubmissionRate($user_id) {
        $submissions_file = '../../../admin/data/score_submissions.json';
        $submissions = [];
        
        if (file_exists($submissions_file)) {
            $submissions = json_decode(file_get_contents($submissions_file), true) ?? [];
        }
        
        // Check submissions in last 5 minutes
        $recent_submissions = array_filter($submissions, function($sub) use ($user_id) {
            return $sub['user_id'] === $user_id && 
                   $sub['timestamp'] > (time() - 300);
        });
        
        if (count($recent_submissions) > 10) {
            return [
                'valid' => false,
                'error' => 'Zu viele Score-Einreichungen'
            ];
        }
        
        return ['valid' => true];
    }
    
    private function flagForReview($user_id, $reason, $data) {
        $flags_file = '../../../admin/data/security_flags.json';
        $flags = [];
        
        if (file_exists($flags_file)) {
            $flags = json_decode(file_get_contents($flags_file), true) ?? [];
        }
        
        $flags[] = [
            'user_id' => $user_id,
            'reason' => $reason,
            'data' => $data,
            'timestamp' => time(),
            'resolved' => false
        ];
        
        file_put_contents($flags_file, json_encode($flags, JSON_PRETTY_PRINT));
    }
    
    public function recordSubmission($user_id, $score_data, $validation_result) {
        $submissions_file = '../../../admin/data/score_submissions.json';
        $submissions = [];
        
        if (file_exists($submissions_file)) {
            $submissions = json_decode(file_get_contents($submissions_file), true) ?? [];
        }
        
        $submission = [
            'user_id' => $user_id,
            'score' => $score_data['score'] ?? 0,
            'game_time' => $score_data['game_time'] ?? 0,
            'questions_answered' => $score_data['questions_answered'] ?? 0,
            'correct_answers' => $score_data['correct_answers'] ?? 0,
            'timestamp' => time(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'validation_result' => $validation_result,
            'accepted' => $validation_result['valid']
        ];
        
        $submissions[] = $submission;
        
        // Keep only last 1000 submissions
        if (count($submissions) > 1000) {
            $submissions = array_slice($submissions, -1000);
        }
        
        file_put_contents($submissions_file, json_encode($submissions, JSON_PRETTY_PRINT));
        
        return $submission;
    }
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid input data');
    }
    
    // Get user from session token or user ID
    $user_id = null;
    $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    if (strpos($auth_header, 'Bearer ') === 0) {
        $session_token = substr($auth_header, 7);
        
        // Validate session
        $sessions_file = '../../../admin/data/user_sessions.json';
        if (file_exists($sessions_file)) {
            $sessions = json_decode(file_get_contents($sessions_file), true) ?? [];
            
            if (isset($sessions[$session_token])) {
                $session = $sessions[$session_token];
                if ($session['expires_at'] > time()) {
                    $user_id = $session['user_id'];
                }
            }
        }
    } else {
        // Guest user or direct user ID
        $user_id = $input['user_id'] ?? null;
    }
    
    if (!$user_id) {
        throw new Exception('Benutzer nicht authentifiziert');
    }
    
    // Validate score submission
    $validator = new ScoreValidator();
    $validation_result = $validator->validateScoreSubmission($user_id, $input);
    
    // Record submission (even if invalid for analysis)
    $submission_record = $validator->recordSubmission($user_id, $input, $validation_result);
    
    if (!$validation_result['valid']) {
        echo json_encode([
            'success' => false,
            'error' => $validation_result['error'],
            'suspicious_activity' => $validation_result['suspicious_activity'] ?? false
        ]);
        exit;
    }
    
    // Update user stats if validation passed
    $auth_manager = new AuthManager();
    $stats_update = [
        'total_games' => ($input['stats']['total_games'] ?? 0),
        'total_score' => ($input['stats']['total_score'] ?? 0),
        'best_score' => max($input['score'] ?? 0, $input['stats']['best_score'] ?? 0),
        'correct_answers' => ($input['stats']['correct_answers'] ?? 0),
        'total_answers' => ($input['stats']['total_answers'] ?? 0),
        'total_time_played' => ($input['stats']['total_time_played'] ?? 0)
    ];
    
    $update_result = $auth_manager->updateUserStats($user_id, $stats_update);
    
    if ($update_result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Score erfolgreich gespeichert',
            'user' => $update_result['user'],
            'submission_id' => $submission_record['timestamp'] . '_' . $user_id
        ]);
    } else {
        throw new Exception($update_result['error']);
    }
    
} catch (Exception $e) {
    error_log('Score submission error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
