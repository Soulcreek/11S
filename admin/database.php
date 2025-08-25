<?php
/**
 * 11Seconds Admin - Database Connection
 * Sichere MySQL-only Datenbank mit Environment-Config
 * Version: 2.0 - Grünes Glass Design System
 */

class Database {
    private static $instance = null;
    private $pdo;
    private $config;

    private function __construct() {
        $this->loadConfig();
        $this->connect();
        $this->initializeSchema();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadConfig() {
        // Sichere Konfiguration aus .env laden
        $configFile = __DIR__ . '/../config/.env';
        
        if (file_exists($configFile)) {
            $lines = file($configFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value);
                }
            }
        }

        $this->config = [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'database' => $_ENV['DB_NAME'] ?? 'k302164_11Sec_Data',
            'username' => $_ENV['DB_USER'] ?? 'k302164_11SecUser',
            'password' => $_ENV['DB_PASS'] ?? '',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4'
        ];
    }

    private function connect() {
        try {
            $dsn = "mysql:host={$this->config['host']};dbname={$this->config['database']};charset={$this->config['charset']}";
            $this->pdo = new PDO($dsn, $this->config['username'], $this->config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            throw new Exception("Database Connection Failed: " . $e->getMessage());
        }
    }

    private function initializeSchema() {
        // Auto-Schema: Tabellen erstellen falls nicht vorhanden
        $sql = "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                role ENUM('admin', 'user') DEFAULT 'user',
                active BOOLEAN DEFAULT true,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL,
                INDEX idx_username (username),
                INDEX idx_role (role)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS questions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                question TEXT NOT NULL,
                correct_answer VARCHAR(255) NOT NULL,
                wrong_answer1 VARCHAR(255) NOT NULL,
                wrong_answer2 VARCHAR(255) NOT NULL,
                wrong_answer3 VARCHAR(255) NOT NULL,
                category VARCHAR(100) DEFAULT 'General',
                difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
                active BOOLEAN DEFAULT true,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_category (category),
                INDEX idx_difficulty (difficulty),
                INDEX idx_active (active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS game_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                session_id VARCHAR(100) UNIQUE NOT NULL,
                player_name VARCHAR(100),
                score INT DEFAULT 0,
                questions_answered INT DEFAULT 0,
                start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                end_time TIMESTAMP NULL,
                INDEX idx_session (session_id),
                INDEX idx_score (score)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $this->pdo->exec($sql);
        // Ensure compatibility with older schemas (add missing columns)
        $this->ensureCompatibility();
        $this->createSecureUsers();
    }

    /**
     * Ensure schema compatibility with older installs.
     * Adds the `role` column to `users` if it does not exist.
     */
    private function ensureCompatibility() {
        try {
            $dbName = $this->config['database'];
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'");
            $stmt->execute([$dbName]);
            $row = $stmt->fetch();
            $hasRole = ($row && isset($row['cnt']) && $row['cnt'] > 0);
            if (! $hasRole) {
                // Add role column with safe default 'user'
                $this->pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('admin','user') NOT NULL DEFAULT 'user'");
            }
        } catch (PDOException $e) {
            // Log to file in admin folder if writable, otherwise ignore silently to avoid breaking startup
            $msg = "[SchemaCompat] " . $e->getMessage() . "\n";
            @file_put_contents(__DIR__ . '/schema-compat.log', date('[Y-m-d H:i:s] ') . $msg, FILE_APPEND | LOCK_EX);
        }
    }

    private function createSecureUsers() {
        // Sichere Admin-User erstellen
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            // NEUE SICHERE ADMIN-ZUGÄNGE
            $adminHash = password_hash('AdminSecure2024!', PASSWORD_DEFAULT);
            $testHash = password_hash('TestGame123!', PASSWORD_DEFAULT);
            
            $this->pdo->prepare("INSERT INTO users (username, email, password_hash, role, active) VALUES (?, ?, ?, 'admin', 1)")
                     ->execute(['administrator', 'admin@11seconds.de', $adminHash]);
                     
            $this->pdo->prepare("INSERT INTO users (username, email, password_hash, role, active) VALUES (?, ?, ?, 'user', 1)")
                     ->execute(['testuser', 'test@11seconds.de', $testHash]);
        }
    }

    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function queryOne($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function execute($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    public function getStats() {
        return [
            'users' => $this->queryOne("SELECT COUNT(*) as count FROM users")['count'],
            'questions' => $this->queryOne("SELECT COUNT(*) as count FROM questions")['count'],
            'sessions' => $this->queryOne("SELECT COUNT(*) as count FROM game_sessions WHERE end_time IS NOT NULL")['count'],
            'avg_score' => $this->queryOne("SELECT ROUND(AVG(score), 1) as avg FROM game_sessions WHERE score > 0")['avg'] ?? 0
        ];
        }
    }
