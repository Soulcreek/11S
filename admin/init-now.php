<?php
// One-click DB initializer (safe, idempotent)
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>One-click Database Initializer</h1>";
$configFile = __DIR__ . '/data/db-config.json';
if (!file_exists($configFile)) {
    echo "<p style='color:red'>Database config not found: {$configFile}</p>";
    exit;
}
$config = json_decode(file_get_contents($configFile), true);
if (!$config) {
    echo "<p style='color:red'>Invalid JSON config.</p>";
    exit;
}

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $config['host'], $config['port'], $config['database'], $config['charset'] ?? 'utf8mb4');
try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "<p>Connected to {$config['database']} at {$config['host']}:{$config['port']}</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}
// --- Preliminary: inspect existing `users` table and fix obvious schema gaps ---
try {
    $usersExist = false;
    $tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll(PDO::FETCH_COLUMN);
    if (count($tables) > 0) {
        $usersExist = true;
    }

    if ($usersExist) {
        $rowCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "<p>Existing `users` table detected (rows: {$rowCount}). Inspecting columns...</p>";
        $cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Columns: " . htmlspecialchars(implode(', ', $cols)) . "</p>";

        // Ensure account_type exists
        if (!in_array('account_type', $cols)) {
            try {
                echo "<p>Adding missing column <strong>account_type</strong>...</p>";
                $pdo->exec("ALTER TABLE users ADD COLUMN account_type ENUM('guest','registered','admin') DEFAULT 'registered'");
                echo "<p style='color:green'>account_type added</p>";
            } catch (Exception $e) {
                echo "<p style='color:orange'>Could not add account_type: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }

        // If id is missing and table is empty, it's safest to DROP and recreate users table
        if (!in_array('id', $cols)) {
            if ($rowCount === 0) {
                try {
                    echo "<p>Empty users table without 'id' detected — dropping and recreating users table to ensure correct schema...</p>";
                    $pdo->exec("DROP TABLE IF EXISTS users");
                    echo "<p style='color:green'>Dropped empty users table.</p>";
                    // mark as not existing so the CREATE statement will run below
                    $usersExist = false;
                } catch (Exception $e) {
                    echo "<p style='color:orange'>Failed to drop users table: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            } else {
                echo "<p style='color:orange'>users table has data; cannot auto-fix missing 'id'. Please perform a manual migration.</p>";
            }
        }
    }
} catch (Exception $e) {
    echo "<p style='color:orange'>Pre-check failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

$statements = [
    'users' => "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        email VARCHAR(100),
        google_id VARCHAR(100) UNIQUE,
        account_type ENUM('guest','registered','admin') DEFAULT 'registered',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT TRUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Create without foreign key constraints to avoid issues if users.id is missing
    'user_stats' => "CREATE TABLE IF NOT EXISTS user_stats (
        user_id INT PRIMARY KEY,
        games_played INT DEFAULT 0,
        total_score INT DEFAULT 0,
        best_score INT DEFAULT 0,
        questions_answered INT DEFAULT 0,
        questions_correct INT DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'sessions' => "CREATE TABLE IF NOT EXISTS sessions (
        id VARCHAR(128) PRIMARY KEY,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        is_active BOOLEAN DEFAULT TRUE
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
        validated BOOLEAN DEFAULT FALSE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'audit_log' => "CREATE TABLE IF NOT EXISTS audit_log (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        action VARCHAR(100) NOT NULL,
        details JSON,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

echo "<div style='background:#f8f9fa;padding:12px;border-left:4px solid #007bff;font-family:monospace;'>";
foreach ($statements as $name => $sql) {
    echo "<p>Creating/ensuring table <strong>{$name}</strong>... ";
    try {
        $pdo->exec($sql);
        echo "<span style='color:green'>OK</span></p>";
    } catch (Exception $e) {
        echo "<span style='color:red'>FAIL: " . htmlspecialchars($e->getMessage()) . "</span></p>";
    }
    flush();
    usleep(150000); // small delay so output streams reliably
}

// Create default admin if missing
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE account_type = 'admin'");
    $adminCount = $stmt->fetchColumn();
    if ($adminCount == 0) {
        echo "<p>Creating default admin user... ";
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $ins = $pdo->prepare("INSERT INTO users (username, email, password_hash, account_type) VALUES (?, ?, ?, 'admin')");
        $ins->execute(['admin', 'admin@11seconds.de', $passwordHash]);
        $userId = $pdo->lastInsertId();
        $ins2 = $pdo->prepare("INSERT INTO user_stats (user_id) VALUES (?)");
        $ins2->execute([$userId]);
        echo "<span style='color:green'>OK</span></p>";
    } else {
        echo "<p>Default admin already exists.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Failed to ensure admin: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Final verification
try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Final table count: <strong>" . count($tables) . "</strong></p>";
    echo "<ul>";
    foreach ($tables as $t) echo "<li>" . htmlspecialchars($t) . "</li>";
    echo "</ul>";
    echo "</div>";
    echo "<p><a href='dashboard.php' class='btn btn-primary'>Return to Dashboard</a></p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Verification failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?>
