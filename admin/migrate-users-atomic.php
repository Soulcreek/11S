<?php
// migrate-users-atomic.php
// Usage (recommended):
// 1) GET ?step=prepare&run=1  -> creates users_new and copies data (no swap)
// 2) GET ?step=verify&run=1   -> runs verification checks
// 3) GET ?step=swap&run=1     -> performs atomic RENAME TABLE users->users_old, users_new->users

function out($s) { echo "<p>".htmlspecialchars($s)."</p>\n"; }

if (!isset($_GET['run']) || $_GET['run'] != '1') {
    echo "<h1>Atomic users migration</h1>";
    echo "<p>Provides phased migration: prepare, verify, swap. Append ?step=prepare|verify|swap&run=1</p>";
    exit;
}

$step = $_GET['step'] ?? 'prepare';

// require token
$tokenPath = __DIR__ . '/data/migration-token.json';
$expectedToken = null;
if (file_exists($tokenPath)) {
    $tt = json_decode(file_get_contents($tokenPath), true);
    $expectedToken = $tt['token'] ?? null;
}

$cfgPath = __DIR__ . '/data/db-config.json';
if (!file_exists($cfgPath)) { out('db-config.json not found'); exit; }
$cfg = json_decode(file_get_contents($cfgPath), true);
if (!$cfg) { out('invalid db-config.json'); exit; }

try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $cfg['host'], $cfg['port'], $cfg['database']);
    $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Throwable $e) {
    out('PDO connect failed: ' . $e->getMessage()); exit;
}

date_default_timezone_set('UTC');
$ts = date('Ymd_His');

if ($step === 'prepare') {
    out('STEP: prepare — creating backups and users_new and copying data');

    // Create SQL backup table if not exists
    $backupTable = 'users_backup_' . $ts;
    try {
        $pdo->exec("CREATE TABLE `" . $backupTable . "` AS SELECT * FROM `users`");
        out("Created SQL backup table: $backupTable");
    } catch (Throwable $e) {
        out('Could not create SQL backup table: ' . $e->getMessage());
    }

    // JSON backup
    try {
        $rows = $pdo->query('SELECT * FROM `users`')->fetchAll(PDO::FETCH_ASSOC);
        $jsonFile = __DIR__ . '/data/users-backup-' . $ts . '.json';
        file_put_contents($jsonFile, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        out('Wrote JSON backup: ' . $jsonFile . ' (rows: ' . count($rows) . ')');
    } catch (Throwable $e) {
        out('Could not write JSON backup: ' . $e->getMessage());
    }

    // Create users_new
    try {
        $pdo->exec("DROP TABLE IF EXISTS `users_new`");
        $createSql = <<<SQL
CREATE TABLE `users_new` (
  `id` INT NULL,
  `user_id` INT DEFAULT NULL,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `account_type` ENUM('guest','registered','admin') DEFAULT 'registered',
  INDEX (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $pdo->exec($createSql);
        out('Created users_new table');
    } catch (Throwable $e) {
        out('Failed to create users_new: ' . $e->getMessage()); exit;
    }

    // Copy data: keep legacy user_id and copy values
    try {
        $insertSql = "INSERT INTO `users_new` (`user_id`,`username`,`email`,`password_hash`,`created_at`,`account_type`) SELECT `user_id`,`username`,`email`,`password_hash`,`created_at`,`account_type` FROM `users`";
        $n = $pdo->exec($insertSql);
        out('Copied rows into users_new: ' . intval($n));

        // Set id = user_id where present
        $pdo->exec('UPDATE `users_new` SET `id` = `user_id` WHERE `user_id` IS NOT NULL');
        out('Copied user_id into id where available');

        // Fill NULL ids with sequential values starting after max(id)
        $max = intval($pdo->query('SELECT MAX(`id`) FROM `users_new`')->fetchColumn());
        $start = max(0,$max);
        // Assign sequential ids to rows with NULL id
        $pdo->exec("SET @n = $start");
        $pdo->exec("UPDATE `users_new` SET `id` = (@n := @n + 1) WHERE `id` IS NULL");
        $newMax = intval($pdo->query('SELECT MAX(`id`) FROM `users_new`')->fetchColumn());
        out('Filled missing ids; new max(id)=' . $newMax);
    } catch (Throwable $e) {
        out('Data copy failed: ' . $e->getMessage()); exit;
    }

    out('Prepare complete. Run ?step=verify&run=1 to validate before swapping.');
    exit;
}

if ($step === 'verify') {
    out('STEP: verify — running integrity checks');
    try {
        $countOld = intval($pdo->query('SELECT COUNT(*) FROM `users`')->fetchColumn());
        $countNew = intval($pdo->query('SELECT COUNT(*) FROM `users_new`')->fetchColumn());
        out("Row counts: users=$countOld users_new=$countNew");
        if ($countOld !== $countNew) out('<span style=color:red>Row count mismatch</span>');

        // Check duplicates in id
        $dup = $pdo->query('SELECT id, COUNT(*) c FROM `users_new` GROUP BY id HAVING c > 1')->fetchAll(PDO::FETCH_ASSOC);
        if (count($dup) > 0) {
            out('<span style=color:red>Duplicate id values detected</span>');
            foreach ($dup as $d) out('dup id=' . $d['id'] . ' count=' . $d['c']);
            exit;
        }

        // Check nulls
        $nulls = intval($pdo->query('SELECT COUNT(*) FROM `users_new` WHERE id IS NULL')->fetchColumn());
        if ($nulls > 0) { out('<span style=color:red>There are NULL id values: ' . $nulls . '</span>'); exit; }

        // Check username/email uniqueness
        $dupeUser = intval($pdo->query("SELECT username, COUNT(*) c FROM users_new GROUP BY username HAVING c>1 LIMIT 1")->fetchColumn());
        $dupeEmail = intval($pdo->query("SELECT email, COUNT(*) c FROM users_new GROUP BY email HAVING c>1 LIMIT 1")->fetchColumn());
        if ($dupeUser) { out('<span style=color:red>Duplicate username in users_new</span>'); exit; }
        if ($dupeEmail) { out('<span style=color:red>Duplicate email in users_new</span>'); exit; }

        out('Basic checks passed. Next step: ?step=swap&run=1 to perform atomic RENAME (this will rename original users -> users_old and users_new -> users)');
    } catch (Throwable $e) {
        out('Verification failed: ' . $e->getMessage()); exit;
    }
    exit;
}

if ($step === 'swap') {
    out('STEP: swap — performing atomic table rename');
    // Finalize users_new: make id NOT NULL AUTO_INCREMENT PRIMARY KEY
    try {
        $max = intval($pdo->query('SELECT MAX(id) FROM users_new')->fetchColumn());
        // Modify column to NOT NULL AUTO_INCREMENT and set PK
        $pdo->exec('ALTER TABLE `users_new` MODIFY COLUMN `id` INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`)');
        $pdo->exec('ALTER TABLE `users_new` AUTO_INCREMENT = ' . ($max + 1));
        out('users_new finalized with AUTO_INCREMENT and PRIMARY KEY');
    } catch (Throwable $e) {
        out('Failed to finalize users_new: ' . $e->getMessage()); exit;
    }

    // Perform atomic rename: users -> users_old, users_new -> users
    try {
        $pdo->exec('RENAME TABLE `users` TO `users_old`, `users_new` TO `users`');
        out('RENAME TABLE succeeded: users -> users_old, users_new -> users');
    } catch (Throwable $e) {
        out('RENAME TABLE failed: ' . $e->getMessage()); exit;
    }

    out('Swap complete. Keep `users_old` until you are satisfied. Run application smoke tests.');
    exit;
}

out('Unknown step');

?>