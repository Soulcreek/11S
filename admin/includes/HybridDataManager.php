<?php
// File: admin/includes/HybridDataManager.php
// Description: Hybrid data manager with MySQL primary and JSON fallback

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
            $this->primaryDb = DatabaseManager::getInstance();
            $this->fallbackMode = false;
            $this->log("✅ Primary MySQL connection established");
        } catch (Exception $e) {
            $this->fallbackMode = true;
            $this->log("⚠️ MySQL unavailable, switching to JSON fallback: " . $e->getMessage());
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
    }
    
    public function getStats() {
        if (!$this->fallbackMode && $this->primaryDb) {
            try {
                return $this->primaryDb->getStats();
            } catch (Exception $e) {
                $this->log("❌ MySQL stats failed, falling back to JSON: " . $e->getMessage());
                $this->fallbackMode = true;
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
        if (!$this->fallbackMode && $this->primaryDb) {
            try {
                return $this->primaryDb->query($sql, $params);
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
                $this->primaryDb = DatabaseManager::getInstance();
                $this->fallbackMode = false;
                $this->log("🔄 MySQL connection restored, starting sync...");
            } catch (Exception $e) {
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
}
