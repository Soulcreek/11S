<?php
class DatabaseManager {
    private $connection;
    private $config;
    
    public function __construct() {
        $this->loadConfig();
        $this->connect();
    }
    
    private function loadConfig() {
        $configFile = __DIR__ . '/../data/db-config.json';
        if (!file_exists($configFile)) {
            throw new Exception("Database configuration file not found: $configFile");
        }
        
        $configContent = file_get_contents($configFile);
        if ($configContent === false) {
            throw new Exception("Failed to read database configuration file");
        }
        
        $this->config = json_decode($configContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON in database configuration: " . json_last_error_msg());
        }
        
        // Set default values
        $this->config = array_merge([
            'host' => 'localhost',
            'port' => 3306,
            'database' => '11seconds',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4'
        ], $this->config);
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
            
            // Initialize tables silently
            $this->initializeTables();
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage(), 0);
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }
    
    private function initializeTables() {
        // Capture any unexpected output
        ob_start();
        
        try {
            $tables = [
                'users' => "CREATE TABLE IF NOT EXISTS users (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    email VARCHAR(100),
                    google_id VARCHAR(100) UNIQUE,
                    account_type ENUM('guest', 'registered', 'admin') DEFAULT 'registered',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    is_active BOOLEAN DEFAULT TRUE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                'user_stats' => "CREATE TABLE IF NOT EXISTS user_stats (
                    user_id INT PRIMARY KEY,
                    games_played INT DEFAULT 0,
                    total_score INT DEFAULT 0,
                    best_score INT DEFAULT 0,
                    questions_answered INT DEFAULT 0,
                    questions_correct INT DEFAULT 0,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                'sessions' => "CREATE TABLE IF NOT EXISTS sessions (
                    id VARCHAR(128) PRIMARY KEY,
                    user_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    expires_at TIMESTAMP NOT NULL,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    is_active BOOLEAN DEFAULT TRUE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                'game_sessions' => "CREATE TABLE IF NOT EXISTS game_sessions (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    score INT NOT NULL,
                    questions_answered INT NOT NULL,
                    questions_correct INT NOT NULL,
                    time_taken INT NOT NULL,
                    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    ip_address VARCHAR(45),
                    validated BOOLEAN DEFAULT FALSE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                'audit_log' => "CREATE TABLE IF NOT EXISTS audit_log (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT,
                    action VARCHAR(100) NOT NULL,
                    details JSON,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ];
            
            foreach ($tables as $tableName => $sql) {
                try {
                    $this->connection->exec($sql);
                } catch (PDOException $e) {
                    error_log("Failed to create table $tableName: " . $e->getMessage(), 0);
                }
            }
            
            // Create default admin user if needed
            $this->createDefaultAdmin();
            
        } finally {
            // Clean any unexpected output
            $output = ob_get_clean();
            if (!empty($output)) {
                error_log("Unexpected output during table initialization: " . $output, 0);
            }
        }
    }
    
    private function createDefaultAdmin() {
        try {
            // Check if admin user exists
            $stmt = $this->connection->prepare("SELECT COUNT(*) FROM users WHERE account_type = 'admin'");
            $stmt->execute();
            $adminCount = $stmt->fetchColumn();
            
            if ($adminCount == 0) {
                $passwordHash = password_hash('admin123', PASSWORD_ARGON2ID);
                $stmt = $this->connection->prepare("
                    INSERT INTO users (username, email, password_hash, account_type) 
                    VALUES (?, ?, ?, 'admin')
                ");
                $stmt->execute(['admin', 'admin@11seconds.de', $passwordHash]);
                
                $userId = $this->connection->lastInsertId();
                
                // Create user stats entry
                $stmt = $this->connection->prepare("INSERT INTO user_stats (user_id) VALUES (?)");
                $stmt->execute([$userId]);
                
                error_log("Default admin user created successfully", 0);
            }
        } catch (PDOException $e) {
            error_log("Failed to create default admin: " . $e->getMessage(), 0);
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
            error_log("Database query failed: " . $e->getMessage(), 0);
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
            
            // Check if tables exist first
            $tables = $this->connection->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            
            // User statistics
            if (in_array('users', $tables)) {
                $stats['total_users'] = $this->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
                $stats['active_users'] = $this->fetchOne("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'];
            } else {
                $stats['total_users'] = 0;
                $stats['active_users'] = 0;
            }
            
            // Question statistics  
            if (in_array('questions', $tables)) {
                $stats['total_questions'] = $this->fetchOne("SELECT COUNT(*) as count FROM questions")['count'];
                $stats['active_questions'] = $this->fetchOne("SELECT COUNT(*) as count FROM questions WHERE is_active = 1")['count'];
            } else {
                $stats['total_questions'] = 0;
                $stats['active_questions'] = 0;
            }
            
            // Game statistics
            if (in_array('game_sessions', $tables)) {
                $stats['total_games'] = $this->fetchOne("SELECT COUNT(*) as count FROM game_sessions")['count'];
                $stats['games_today'] = $this->fetchOne("SELECT COUNT(*) as count FROM game_sessions WHERE DATE(completed_at) = CURDATE()")['count'];
            } else {
                $stats['total_games'] = 0;
                $stats['games_today'] = 0;
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log("Failed to get database stats: " . $e->getMessage(), 0);
            return [
                'total_users' => 0,
                'active_users' => 0,
                'total_questions' => 0,
                'active_questions' => 0,
                'total_games' => 0,
                'games_today' => 0
            ];
        }
    }
}
?>
