<?php
// File: admin/includes/DatabaseManager.php
// Description: MySQL database connection and management for admin center

class DatabaseManager {
    private static $instance = null;
    private $connection;
    private $config;
    
    private function __construct() {
        $this->loadConfig();
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadConfig() {
        // Load database configuration from environment or config file
        $configFile = __DIR__ . '/../data/db-config.json';
        
        if (file_exists($configFile)) {
            $this->config = json_decode(file_get_contents($configFile), true);
        } else {
            // Default configuration - should be updated via admin interface
            $this->config = [
                'host' => '10.35.233.76',
                'port' => 3306,
                'username' => 'quiz_user',
                'password' => 'your_password_here',
                'database' => 'quiz_game_db',
                'charset' => 'utf8mb4'
            ];
            
            // Save default config for editing
            $this->saveConfig();
        }
    }
    
    private function connect() {
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                $this->config['host'],
                $this->config['port'],
                $this->config['database'],
                $this->config['charset']
            );
            
            $this->connection = new PDO($dsn, $this->config['username'], $this->config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
            
            // Initialize tables if they don't exist
            $this->initializeTables();
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }
    
    private function initializeTables() {
        $tables = [
            'users' => "
                CREATE TABLE IF NOT EXISTS users (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    email VARCHAR(100) UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    phone_number VARCHAR(20),
                    account_type ENUM('guest', 'registered', 'admin') DEFAULT 'registered',
                    verified BOOLEAN DEFAULT FALSE,
                    verification_code VARCHAR(10),
                    verification_expires DATETIME,
                    failed_login_attempts INT DEFAULT 0,
                    locked_until DATETIME NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    last_login TIMESTAMP NULL,
                    is_active BOOLEAN DEFAULT TRUE,
                    google_id VARCHAR(100) UNIQUE NULL,
                    INDEX idx_username (username),
                    INDEX idx_email (email),
                    INDEX idx_google_id (google_id),
                    INDEX idx_account_type (account_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'user_stats' => "
                CREATE TABLE IF NOT EXISTS user_stats (
                    user_id INT PRIMARY KEY,
                    games_played INT DEFAULT 0,
                    total_score INT DEFAULT 0,
                    best_score INT DEFAULT 0,
                    average_score DECIMAL(5,2) DEFAULT 0.00,
                    total_time_played INT DEFAULT 0,
                    streak_current INT DEFAULT 0,
                    streak_best INT DEFAULT 0,
                    questions_answered INT DEFAULT 0,
                    questions_correct INT DEFAULT 0,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'sessions' => "
                CREATE TABLE IF NOT EXISTS sessions (
                    id VARCHAR(128) PRIMARY KEY,
                    user_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    expires_at TIMESTAMP NOT NULL,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    is_active BOOLEAN DEFAULT TRUE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_user_id (user_id),
                    INDEX idx_expires_at (expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'questions' => "
                CREATE TABLE IF NOT EXISTS questions (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    text TEXT NOT NULL,
                    answer_a VARCHAR(255) NOT NULL,
                    answer_b VARCHAR(255) NOT NULL,
                    answer_c VARCHAR(255) NOT NULL,
                    answer_d VARCHAR(255) NOT NULL,
                    correct_answer TINYINT NOT NULL,
                    category VARCHAR(50) DEFAULT 'General',
                    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
                    points INT DEFAULT 10,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    created_by INT,
                    is_active BOOLEAN DEFAULT TRUE,
                    times_asked INT DEFAULT 0,
                    times_correct INT DEFAULT 0,
                    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                    INDEX idx_category (category),
                    INDEX idx_difficulty (difficulty),
                    INDEX idx_active (is_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'game_sessions' => "
                CREATE TABLE IF NOT EXISTS game_sessions (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    score INT NOT NULL,
                    questions_answered INT NOT NULL,
                    questions_correct INT NOT NULL,
                    time_taken INT NOT NULL,
                    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    ip_address VARCHAR(45),
                    validated BOOLEAN DEFAULT FALSE,
                    validation_flags JSON,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_user_id (user_id),
                    INDEX idx_score (score),
                    INDEX idx_completed_at (completed_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'audit_log' => "
                CREATE TABLE IF NOT EXISTS audit_log (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT,
                    action VARCHAR(100) NOT NULL,
                    details JSON,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                    INDEX idx_user_id (user_id),
                    INDEX idx_action (action),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ];
        
        foreach ($tables as $tableName => $sql) {
            try {
                $this->connection->exec($sql);
            } catch (PDOException $e) {
                error_log("Failed to create table $tableName: " . $e->getMessage());
            }
        }
        
        // Create default admin user if it doesn't exist
        $this->createDefaultAdmin();
    }
    
    private function createDefaultAdmin() {
        try {
            $stmt = $this->connection->prepare("SELECT COUNT(*) FROM users WHERE account_type = 'admin'");
            $stmt->execute();
            $adminCount = $stmt->fetchColumn();
            
            if ($adminCount == 0) {
                $passwordHash = password_hash('admin123', PASSWORD_ARGON2ID);
                $stmt = $this->connection->prepare("
                    INSERT INTO users (username, email, password_hash, account_type, verified) 
                    VALUES (?, ?, ?, 'admin', TRUE)
                ");
                $stmt->execute(['admin', 'admin@11seconds.de', $passwordHash]);
                
                $userId = $this->connection->lastInsertId();
                
                // Create user stats entry
                $stmt = $this->connection->prepare("INSERT INTO user_stats (user_id) VALUES (?)");
                $stmt->execute([$userId]);
                
                error_log("Default admin user created successfully");
            }
        } catch (PDOException $e) {
            error_log("Failed to create default admin: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query failed: " . $e->getMessage());
            throw new Exception("Database query failed: " . $e->getMessage());
        }
    }
    
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    public function execute($sql, $params = []) {
        $this->query($sql, $params);
        return $this->connection->lastInsertId();
    }
    
    public function updateConfig($newConfig) {
        $this->config = array_merge($this->config, $newConfig);
        $this->saveConfig();
        
        // Reconnect with new settings
        $this->connect();
    }
    
    private function saveConfig() {
        $configFile = __DIR__ . '/../data/db-config.json';
        $dataDir = dirname($configFile);
        
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        
        file_put_contents($configFile, json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    public function testConnection() {
        try {
            $result = $this->fetchOne("SELECT 'MySQL Connection OK' as status, VERSION() as version, NOW() as timestamp");
            return [
                'success' => true,
                'message' => 'Database connection successful',
                'data' => $result
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    public function getStats() {
        try {
            $stats = [];
            
            // User statistics
            $stats['total_users'] = $this->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
            $stats['active_users'] = $this->fetchOne("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'];
            $stats['verified_users'] = $stats['total_users'] > 0 ? 
                $this->fetchOne("SELECT COUNT(*) as count FROM users WHERE verified = 1")['count'] : 0;
            
            // Question statistics
            $stats['total_questions'] = $this->fetchOne("SELECT COUNT(*) as count FROM questions")['count'];
            $stats['active_questions'] = $stats['total_questions'] > 0 ?
                $this->fetchOne("SELECT COUNT(*) as count FROM questions WHERE is_active = 1")['count'] : 0;
            
            // Game statistics
            $stats['total_games'] = $this->fetchOne("SELECT COUNT(*) as count FROM game_sessions")['count'];
            $stats['games_today'] = $stats['total_games'] > 0 ?
                $this->fetchOne("SELECT COUNT(*) as count FROM game_sessions WHERE DATE(completed_at) = CURDATE()")['count'] : 0;
            
            return $stats;
        } catch (Exception $e) {
            error_log("Failed to get database stats: " . $e->getMessage());
            return [
                'total_users' => 0,
                'active_users' => 0,
                'verified_users' => 0,
                'total_questions' => 0,
                'active_questions' => 0,
                'total_games' => 0,
                'games_today' => 0
            ];
        }
    }
}
?>
