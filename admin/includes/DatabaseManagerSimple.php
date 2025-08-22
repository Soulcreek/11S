<?php
// File: admin/includes/DatabaseManagerSimple.php
// Simplified version for testing

class DatabaseManagerSimple {
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
        $configFile = __DIR__ . '/../data/db-config.json';
        
        if (file_exists($configFile)) {
            $this->config = json_decode(file_get_contents($configFile), true);
        } else {
            throw new Exception("Database config file not found");
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
            
            $this->connection = new PDO($dsn, $this->config['username'], $this->config['password']);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function getStats() {
        try {
            // Simple stats from existing tables
            $stats = [
                'questions' => 0,
                'users' => 0,
                'sessions' => 0,
                'avg_score' => 0
            ];
            
            // Count questions
            try {
                $stmt = $this->connection->query("SELECT COUNT(*) FROM questions");
                $stats['questions'] = $stmt->fetchColumn();
            } catch (Exception $e) {
                // Table might not exist
            }
            
            // Count users
            try {
                $stmt = $this->connection->query("SELECT COUNT(*) FROM users");
                $stats['users'] = $stmt->fetchColumn();
            } catch (Exception $e) {
                // Table might not exist
            }
            
            return $stats;
            
        } catch (Exception $e) {
            throw new Exception("Database query failed: " . $e->getMessage());
        }
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }
    
    public function initializeTables() {
        // Simple initialization - just create missing core tables
        $tables = [
            'users' => "CREATE TABLE IF NOT EXISTS users (
                id INT PRIMARY KEY AUTO_INCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                email VARCHAR(100),
                google_id VARCHAR(100),
                account_type ENUM('guest', 'registered', 'admin') DEFAULT 'registered',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            'game_sessions' => "CREATE TABLE IF NOT EXISTS game_sessions (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                score INT NOT NULL,
                questions_answered INT NOT NULL,
                questions_correct INT NOT NULL,
                completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        ];
        
        foreach ($tables as $tableName => $sql) {
            try {
                $this->connection->exec($sql);
            } catch (PDOException $e) {
                error_log("Failed to create table $tableName: " . $e->getMessage());
            }
        }
        
        // Create default admin user
        $this->createDefaultAdmin();
    }
    
    private function createDefaultAdmin() {
        try {
            $stmt = $this->connection->prepare("SELECT COUNT(*) FROM users WHERE account_type = 'admin'");
            $stmt->execute();
            
            if ($stmt->fetchColumn() == 0) {
                $stmt = $this->connection->prepare("INSERT INTO users (username, password_hash, account_type) VALUES (?, ?, 'admin')");
                $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT)]);
            }
        } catch (Exception $e) {
            error_log("Failed to create admin user: " . $e->getMessage());
        }
    }
}
?>
