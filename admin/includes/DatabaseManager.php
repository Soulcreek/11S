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
            // Ensure users table schema is acceptable before creating dependent tables
            $this->ensureUsersTableSchema();

            // Create core tables (safe forms without FK dependencies first)
            $createStatements = [];

            $createStatements['users'] = "CREATE TABLE IF NOT EXISTS users (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    email VARCHAR(100),
                    google_id VARCHAR(100) UNIQUE,
                    account_type ENUM('guest', 'registered', 'admin') DEFAULT 'registered',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    is_active BOOLEAN DEFAULT TRUE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            // user_stats without FK constraint; we will add FK later if safe
            $createStatements['user_stats'] = "CREATE TABLE IF NOT EXISTS user_stats (
                    user_id INT PRIMARY KEY,
                    games_played INT DEFAULT 0,
                    total_score INT DEFAULT 0,
                    best_score INT DEFAULT 0,
                    questions_answered INT DEFAULT 0,
                    questions_correct INT DEFAULT 0,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $createStatements['sessions'] = "CREATE TABLE IF NOT EXISTS sessions (
                    id VARCHAR(128) PRIMARY KEY,
                    user_id INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    expires_at TIMESTAMP NULL,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    is_active BOOLEAN DEFAULT TRUE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $createStatements['game_sessions'] = "CREATE TABLE IF NOT EXISTS game_sessions (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT,
                    score INT,
                    questions_answered INT,
                    questions_correct INT,
                    time_taken INT,
                    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    ip_address VARCHAR(45),
                    validated BOOLEAN DEFAULT FALSE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $createStatements['audit_log'] = "CREATE TABLE IF NOT EXISTS audit_log (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT,
                    action VARCHAR(100) NOT NULL,
                    details JSON,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            foreach ($createStatements as $tableName => $sql) {
                try {
                    $this->connection->exec($sql);
                } catch (PDOException $e) {
                    error_log("Failed to create table $tableName: " . $e->getMessage(), 0);
                }
            }

            // If users.id exists, try to add foreign key constraints where appropriate
            if ($this->tableHasColumn('users', 'id')) {
                // Add FK for user_stats
                try {
                    $this->connection->exec("ALTER TABLE user_stats ADD CONSTRAINT fk_user_stats_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
                } catch (PDOException $e) {
                    // Ignore if constraint exists or cannot be added
                    error_log("Could not add FK user_stats->users: " . $e->getMessage(), 0);
                }

                try {
                    $this->connection->exec("ALTER TABLE sessions ADD CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
                } catch (PDOException $e) {
                    error_log("Could not add FK sessions->users: " . $e->getMessage(), 0);
                }

                try {
                    $this->connection->exec("ALTER TABLE game_sessions ADD CONSTRAINT fk_game_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
                } catch (PDOException $e) {
                    error_log("Could not add FK game_sessions->users: " . $e->getMessage(), 0);
                }

                try {
                    $this->connection->exec("ALTER TABLE audit_log ADD CONSTRAINT fk_audit_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
                } catch (PDOException $e) {
                    error_log("Could not add FK audit_log->users: " . $e->getMessage(), 0);
                }
            } else {
                error_log("users.id column missing: skipping FK creation. Manual migration required to add PK to users if needed.", 0);
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
            // Check if users table exists
            $tables = $this->connection->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('users', $tables)) {
                return;
            }

            // Check if admin user exists
            $stmt = $this->connection->prepare("SELECT COUNT(*) FROM users WHERE account_type = 'admin'");
            $stmt->execute();
            $adminCount = $stmt->fetchColumn();

            if ($adminCount == 0) {
                $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $this->connection->prepare(
                    "INSERT INTO users (username, email, password_hash, account_type) VALUES (?, ?, ?, 'admin')"
                );
                try {
                    $stmt->execute(['admin', 'admin@11seconds.de', $passwordHash]);
                } catch (PDOException $e) {
                    error_log("Failed to insert default admin: " . $e->getMessage(), 0);
                    return;
                }

                // Try to get the new user id; if not available, try to locate by username
                $userId = 0;
                try {
                    $userId = $this->connection->lastInsertId();
                } catch (Exception $e) {
                    $userId = 0;
                }

                if (empty($userId)) {
                    $row = $this->fetchOne("SELECT id FROM users WHERE username = ? LIMIT 1", ['admin']);
                    $userId = $row['id'] ?? 0;
                }

                if (!empty($userId)) {
                    try {
                        $stmt = $this->connection->prepare("INSERT IGNORE INTO user_stats (user_id) VALUES (?)");
                        $stmt->execute([$userId]);
                    } catch (PDOException $e) {
                        error_log("Failed to create user_stats for default admin: " . $e->getMessage(), 0);
                    }
                }

                error_log("Default admin user created successfully", 0);
            }
        } catch (PDOException $e) {
            error_log("Failed to create default admin: " . $e->getMessage(), 0);
        }
    }

    /**
     * Check whether a table has a specific column
     */
    private function tableHasColumn($table, $column) {
        try {
            $stmt = $this->connection->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
            $stmt->execute([$column]);
            $row = $stmt->fetch();
            return !empty($row);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Ensure users table has required schema; if users is missing id column and empty, recreate safely.
     */
    private function ensureUsersTableSchema() {
        try {
            // If users table doesn't exist, nothing to do
            $tables = $this->connection->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('users', $tables)) {
                return;
            }

            $hasId = $this->tableHasColumn('users', 'id');
            $hasAccountType = $this->tableHasColumn('users', 'account_type');

            // If account_type missing, try to add it
            if (!$hasAccountType) {
                try {
                    $this->connection->exec("ALTER TABLE users ADD COLUMN account_type ENUM('guest','registered','admin') DEFAULT 'registered'");
                } catch (PDOException $e) {
                    error_log('Could not add account_type to users: ' . $e->getMessage(), 0);
                }
            }

            if (!$hasId) {
                // Check if table is empty; if empty we can safely recreate
                $count = 0;
                try {
                    $row = $this->fetchOne("SELECT COUNT(*) as c FROM users");
                    $count = $row['c'] ?? 0;
                } catch (Exception $e) {
                    $count = 0;
                }

                if ($count == 0) {
                    // Drop and recreate users table with proper id PK
                    try {
                        $this->connection->exec("DROP TABLE IF EXISTS users");
                        $this->connection->exec("CREATE TABLE users (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            username VARCHAR(50) UNIQUE NOT NULL,
                            password_hash VARCHAR(255) NOT NULL,
                            email VARCHAR(100),
                            google_id VARCHAR(100) UNIQUE,
                            account_type ENUM('guest','registered','admin') DEFAULT 'registered',
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            is_active BOOLEAN DEFAULT TRUE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                        error_log('Recreated empty users table with id column', 0);
                    } catch (PDOException $e) {
                        error_log('Failed to recreate users table: ' . $e->getMessage(), 0);
                    }
                } else {
                    // Table has rows but no id column: cannot safely fix automatically
                    error_log('users table has rows but is missing id column — manual migration required', 0);
                }
            }
        } catch (PDOException $e) {
            error_log('Error while ensuring users schema: ' . $e->getMessage(), 0);
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
                // Check if is_active column exists
                try {
                    $stats['active_users'] = $this->fetchOne("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'];
                } catch (Exception $e) {
                    // If is_active column doesn't exist, assume all users are active
                    $stats['active_users'] = $stats['total_users'];
                }
            } else {
                $stats['total_users'] = 0;
                $stats['active_users'] = 0;
            }
            
            // Question statistics  
            if (in_array('questions', $tables)) {
                $stats['total_questions'] = $this->fetchOne("SELECT COUNT(*) as count FROM questions")['count'];
                // Check if is_active column exists
                try {
                    $stats['active_questions'] = $this->fetchOne("SELECT COUNT(*) as count FROM questions WHERE is_active = 1")['count'];
                } catch (Exception $e) {
                    // If is_active column doesn't exist, assume all questions are active
                    $stats['active_questions'] = $stats['total_questions'];
                }
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
