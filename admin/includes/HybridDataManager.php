<?php
// File: admin/includes/HybridDataManager.php
// Description: Hybrid data manager with MySQL primary and JSON fallback

// Ensure DatabaseManager is available when this file is included directly
require_once __DIR__ . '/DatabaseManager.php';

class HybridDataManager {
    private static $instance = null;
    private $primaryDb = null;
    private $fallbackMode = false;
    private $jsonDataPath;
    private $syncLog = [];
    
    private function __construct() {
        $this->jsonDataPath = __DIR__ . '/../data/';
        $this->ensureJsonDataDir();
        $this->initializePrimary();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initializePrimary() {
        try {
            $this->primaryDb = new DatabaseManager();

            // Verify the connection with a lightweight test
            try {
                $test = $this->primaryDb->testConnection();
                if (!isset($test['success']) || $test['success'] !== true) {
                    throw new Exception('Test connection failed: ' . ($test['message'] ?? 'unknown'));
                }
            } catch (Exception $inner) {
                // If verification fails, treat as unavailable
                throw new Exception('Primary connection verification failed: ' . $inner->getMessage());
            }

            $this->fallbackMode = false;
            $this->log("✅ Primary MySQL connection established");
        } catch (Exception $e) {
            $this->fallbackMode = true;
            $this->log("⚠️ MySQL unavailable, switching to JSON fallback: " . $e->getMessage());
            // Persist a small log for easier debugging on the server
            $this->writeLogToFile("MySQL unavailable during initializePrimary: " . $e->getMessage());
        }
    }
    
    private function ensureJsonDataDir() {
        if (!file_exists($this->jsonDataPath)) {
            mkdir($this->jsonDataPath, 0755, true);
        }
        
        // Initialize default JSON files if they don't exist
        $defaultFiles = [
            'users.json' => [],
            'questions.json' => [],
            'game_sessions.json' => [],
            'user_stats.json' => [],
            'sync_status.json' => [
                'last_sync' => null,
                'sync_direction' => 'none',
                'pending_operations' => []
            ]
        ];
        
        foreach ($defaultFiles as $file => $defaultData) {
            $filePath = $this->jsonDataPath . $file;
            if (!file_exists($filePath)) {
                file_put_contents($filePath, json_encode($defaultData, JSON_PRETTY_PRINT));
            }
        }
    }
    
    private function log($message) {
        $this->syncLog[] = date('H:i:s') . " - " . $message;
        error_log("[HybridDataManager] " . $message);
        // Also write to hybrid trace log for easier inspection on the host
        $this->writeLogToFile($message);
    }

    private function writeLogToFile($message) {
        try {
            $path = $this->jsonDataPath . 'hybrid.log';
            $entry = date('Y-m-d H:i:s') . " - " . $message . "\n";
            // Ensure data directory exists
            if (!is_dir($this->jsonDataPath)) {
                mkdir($this->jsonDataPath, 0755, true);
            }
            file_put_contents($path, $entry, FILE_APPEND | LOCK_EX);
        } catch (Throwable $e) {
            // Best-effort logging - don't break main flow
        }
    }
    
    public function getStats() {
        if (!$this->fallbackMode && $this->primaryDb) {
            try {
                $dbStats = $this->primaryDb->getStats();
                
                // Calculate average score from game_sessions
                $avgScore = 0;
                try {
                    $result = $this->primaryDb->fetchOne("SELECT AVG(score) as avg_score FROM game_sessions WHERE score IS NOT NULL");
                    $avgScore = round($result['avg_score'] ?? 0, 2);
                } catch (Exception $e) {
                    $avgScore = 0;
                }
                
                // Map DatabaseManager keys to dashboard expected keys
                return [
                    'questions' => $dbStats['total_questions'] ?? 0,
                    'users' => $dbStats['total_users'] ?? 0,
                    'sessions' => $dbStats['total_games'] ?? 0,
                    'avg_score' => $avgScore
                ];
            } catch (Exception $e) {
                // Only fallback if it's a connection error, not missing tables
                if (strpos($e->getMessage(), "doesn't exist") === false && 
                    strpos($e->getMessage(), "Base table or view not found") === false) {
                    $this->log("❌ MySQL stats failed, falling back to JSON: " . $e->getMessage());
                    $this->fallbackMode = true;
                } else {
                    // Re-throw table missing errors so dashboard can handle them
                    throw $e;
                }
            }
        }
        
        return $this->getStatsFromJson();
    }
    
    private function getStatsFromJson() {
        $users = json_decode(file_get_contents($this->jsonDataPath . 'users.json'), true);
        $questions = json_decode(file_get_contents($this->jsonDataPath . 'questions.json'), true);
        $sessions = json_decode(file_get_contents($this->jsonDataPath . 'game_sessions.json'), true);
        
        $totalScore = 0;
        $totalSessions = count($sessions);
        
        foreach ($sessions as $session) {
            $totalScore += $session['score'] ?? 0;
        }
        
        $avgScore = $totalSessions > 0 ? $totalScore / $totalSessions : 0;
        
        return [
            'questions' => count($questions),
            'users' => count($users),
            'sessions' => $totalSessions,
            'avg_score' => round($avgScore, 2)
        ];
    }
    
    public function query($sql, $params = []) {
        // Sanitize common accidental string continuations: remove backslash-newline sequences
        // and trim leading/trailing whitespace. This prevents malformed SQL like:
        // "\
        //     SELECT ..." which can trigger SQL syntax errors on MySQL.
        try {
            if (is_string($sql)) {
                // Remove occurrences of backslash followed by optional spaces and a newline
                $sql = preg_replace('/\\\\\s*\n/', ' ', $sql);
                // Also collapse multiple consecutive whitespace/newlines into single space
                $sql = preg_replace('/[\r\n\s]+/', ' ', $sql);
                $sql = trim($sql);
            }
        } catch (Throwable $e) {
            // If regex fails for any reason, fall back to original SQL
        }
        if (!$this->fallbackMode && $this->primaryDb) {
            try {
                // Use fetchAll to get actual results instead of PDO statement
                return $this->primaryDb->fetchAll($sql, $params);
            } catch (Exception $e) {
                $this->log("❌ MySQL query failed: " . $e->getMessage());
                $this->fallbackMode = true;
            }
        }
        
        // Simplified JSON fallback for common queries
        if (strpos(strtolower($sql), 'game_sessions') !== false) {
            $sessions = json_decode(file_get_contents($this->jsonDataPath . 'game_sessions.json'), true);
            return array_slice(array_reverse($sessions), 0, 10); // Last 10 sessions
        }
        
        return [];
    }
    
    public function addUser($userData) {
        $success = false;
        
        // Try MySQL first
        if (!$this->fallbackMode && $this->primaryDb) {
            try {
                $result = $this->primaryDb->addUser($userData);
                $success = true;
                $this->log("✅ User added to MySQL: " . $userData['username']);
            } catch (Exception $e) {
                $this->log("❌ MySQL addUser failed: " . $e->getMessage());
                $this->fallbackMode = true;
            }
        }
        
        // Add to JSON (either as fallback or for sync)
        $users = json_decode(file_get_contents($this->jsonDataPath . 'users.json'), true);
        $userData['id'] = count($users) + 1;
        $userData['created_at'] = date('Y-m-d H:i:s');
        $users[] = $userData;
        
        if (file_put_contents($this->jsonDataPath . 'users.json', json_encode($users, JSON_PRETTY_PRINT))) {
            $this->log("✅ User added to JSON: " . $userData['username']);
            
            if (!$success) {
                $this->addPendingSync('addUser', $userData);
            }
            
            return $userData['id'];
        }
        
        return false;
    }
    
    private function addPendingSync($operation, $data) {
        $syncStatus = json_decode(file_get_contents($this->jsonDataPath . 'sync_status.json'), true);
        $syncStatus['pending_operations'][] = [
            'operation' => $operation,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($this->jsonDataPath . 'sync_status.json', json_encode($syncStatus, JSON_PRETTY_PRINT));
    }
    
    public function syncToMySQL() {
        if ($this->fallbackMode) {
            try {
                $this->primaryDb = new DatabaseManager();
                // Verify connection before leaving fallback mode
                $test = $this->primaryDb->testConnection();
                if (!isset($test['success']) || $test['success'] !== true) {
                    throw new Exception('Verification failed: ' . ($test['message'] ?? 'unknown'));
                }
                $this->fallbackMode = false;
                $this->log("🔄 MySQL connection restored, starting sync...");
            } catch (Exception $e) {
                $this->writeLogToFile("Sync attempt failed to reconnect MySQL: " . $e->getMessage());
                return ['success' => false, 'error' => 'MySQL still unavailable: ' . $e->getMessage()];
            }
        }
        
        $syncStatus = json_decode(file_get_contents($this->jsonDataPath . 'sync_status.json'), true);
        $synced = 0;
        $errors = [];
        
        foreach ($syncStatus['pending_operations'] as $key => $operation) {
            try {
                switch ($operation['operation']) {
                    case 'addUser':
                        $this->primaryDb->addUser($operation['data']);
                        $synced++;
                        unset($syncStatus['pending_operations'][$key]);
                        break;
                    // Add more operation types as needed
                }
            } catch (Exception $e) {
                $errors[] = "Failed to sync {$operation['operation']}: " . $e->getMessage();
            }
        }
        
        $syncStatus['last_sync'] = date('Y-m-d H:i:s');
        $syncStatus['pending_operations'] = array_values($syncStatus['pending_operations']);
        file_put_contents($this->jsonDataPath . 'sync_status.json', json_encode($syncStatus, JSON_PRETTY_PRINT));
        
        return [
            'success' => true,
            'synced' => $synced,
            'pending' => count($syncStatus['pending_operations']),
            'errors' => $errors
        ];
    }
    
    public function isInFallbackMode() {
        return $this->fallbackMode;
    }
    
    public function getSyncLog() {
        return $this->syncLog;
    }
    
    public function getPendingOperations() {
        $syncStatus = json_decode(file_get_contents($this->jsonDataPath . 'sync_status.json'), true);
        return $syncStatus['pending_operations'] ?? [];
    }

    /**
     * Attempt to disable JSON fallback by verifying MySQL connectivity.
     * Returns an array with success and message/error.
     */
    public function disableFallback() {
        try {
            $this->primaryDb = new DatabaseManager();
            $test = $this->primaryDb->testConnection();
            if (!isset($test['success']) || $test['success'] !== true) {
                throw new Exception('Verification failed: ' . ($test['message'] ?? 'unknown'));
            }
            $this->fallbackMode = false;
            $this->log("🔧 Fallback mode disabled by operator; MySQL confirmed.");
            $this->writeLogToFile("Fallback disabled via admin: MySQL verified");
            return ['success' => true, 'message' => 'Fallback disabled; using MySQL.'];
        } catch (Exception $e) {
            $this->writeLogToFile('Failed to disable fallback: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
