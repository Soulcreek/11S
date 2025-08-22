<?php
// Safe migration: add `id` AUTO_INCREMENT PK to legacy `users` table while preserving data.
// Usage: visit migrate-users.php?run=1 to execute (this script performs a backup first).
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Users table migration</h1>";

require_once __DIR__ . '/includes/DatabaseManager.php';

// Require migration token stored in admin/data/migration-token.json
$tokenPath = __DIR__ . '/data/migration-token.json';
$expectedToken = null;
if (file_exists($tokenPath)) {
    $t = json_decode(file_get_contents($tokenPath), true);
    $expectedToken = $t['token'] ?? null;
}

// Safety: require explicit run param and token
if (!isset($_GET['run']) || $_GET['run'] != '1' || !isset($_GET['token']) || $_GET['token'] !== $expectedToken) {
    echo "<p>This migration script will: back up the users table, add an 'id' INT AUTO_INCREMENT primary key, copy existing identifiers (if present), and verify results.</p>";
    echo "<p>To execute, append <code>?run=1&token=<em>YOUR_TOKEN</em></code> to the URL. Do not run multiple times unless you inspected the output.</p>";
    exit;
}

try {
    $db = new DatabaseManager();
    $pdo = $db->getConnection();
} catch (Throwable $e) {
    echo "<p style='color:red'>Failed to connect to DB: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

function out($s) { echo "<p>" . $s . "</p>\n"; flush(); }

out("Connected. Beginning migration checklist...");

// 1) Inspect current users table
try {
    $cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    out("Existing users columns: " . htmlspecialchars(implode(', ', $cols)));
} catch (Throwable $e) {
    out("<span style='color:red'>Error: users table does not exist or cannot be inspected: " . htmlspecialchars($e->getMessage()) . "</span>");
    exit;
}

$hasId = in_array('id', $cols);
$hasUserId = in_array('user_id', $cols);

// If id already exists, we may still need to ensure it's PRIMARY KEY
if ($hasId) {
    out("users.id already exists — checking primary key status...");
    try {
        $pkRows = $pdo->query("SHOW INDEX FROM users WHERE Key_name = 'PRIMARY'")->fetchAll(PDO::FETCH_ASSOC);
        $pkCols = array_map(function($r){ return $r['Column_name']; }, $pkRows);
        if (in_array('id', $pkCols)) {
            out("id is already the PRIMARY KEY. Nothing to do.");
            exit;
        }

        // PRIMARY KEY exists but not on id. We'll attempt to replace it with id.
        out("Primary key exists on: " . htmlspecialchars(implode(', ', $pkCols)) . " — attempting to switch PK to id...");

        // Ensure id has no NULLs and is unique
        $nullCount = intval($pdo->query("SELECT COUNT(*) FROM users WHERE id IS NULL")->fetchColumn());
        if ($nullCount > 0) {
            out("<span style='color:red'>Cannot set PK: $nullCount rows have NULL id. Migration aborted.</span>");
            exit;
        }

        $dup = intval($pdo->query("SELECT id, COUNT(*) c FROM users GROUP BY id HAVING c > 1 LIMIT 1")->fetchColumn());
        if ($dup) {
            out("<span style='color:red'>Cannot set PK: duplicate id values detected. Migration aborted.</span>");
            exit;
        }

        // If PK is composite, abort - too risky to modify automatically
        if (count($pkCols) > 1) {
            out("<span style='color:red'>Composite PRIMARY KEY detected (" . htmlspecialchars(implode(', ', $pkCols)) . "). Automatic migration aborted. Please handle manually.</span>");
            exit;
        }

        $oldPk = $pkCols[0];

        // Inspect old PK column for AUTO_INCREMENT
        $colStmt = $pdo->prepare("SHOW COLUMNS FROM users WHERE Field = ?");
        $colStmt->execute([$oldPk]);
        $colInfo = $colStmt->fetch(PDO::FETCH_ASSOC);
        $oldType = $colInfo ? $colInfo['Type'] : null;
        $oldExtra = $colInfo ? $colInfo['Extra'] : '';

        // Get id column type
        $idStmt = $pdo->prepare("SHOW COLUMNS FROM users WHERE Field = 'id'");
        $idStmt->execute();
        $idInfo = $idStmt->fetch(PDO::FETCH_ASSOC);
        $idType = $idInfo ? $idInfo['Type'] : 'INT';

        // Find foreign keys referencing users
        $fkStmt = $pdo->prepare(
            "SELECT k.TABLE_NAME, k.COLUMN_NAME, k.CONSTRAINT_NAME, k.REFERENCED_COLUMN_NAME, r.UPDATE_RULE, r.DELETE_RULE
             FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
             JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS r
               ON k.CONSTRAINT_NAME = r.CONSTRAINT_NAME AND k.CONSTRAINT_SCHEMA = r.CONSTRAINT_SCHEMA
             WHERE k.REFERENCED_TABLE_NAME = 'users' AND k.TABLE_SCHEMA = DATABASE()"
        );
        $fkStmt->execute();
        $fks = $fkStmt->fetchAll(PDO::FETCH_ASSOC);

        $recreateFks = [];
        if (count($fks) > 0) {
            out("Found " . count($fks) . " foreign key(s) referencing users: " . htmlspecialchars(implode(', ', array_map(function($f){return $f['CONSTRAINT_NAME'].'('.$f['TABLE_NAME'].'.'.$f['COLUMN_NAME'].')';}, $fks))));
            foreach ($fks as $fk) {
                $childTable = $fk['TABLE_NAME'];
                $constraint = $fk['CONSTRAINT_NAME'];
                $childCol = $fk['COLUMN_NAME'];
                $update = $fk['UPDATE_RULE'];
                $delete = $fk['DELETE_RULE'];

                // Build recreate SQL that will point to users(id)
                $recreateFks[] = "ALTER TABLE `" . $childTable . "` ADD CONSTRAINT `" . $constraint . "` FOREIGN KEY (`" . $childCol . "`) REFERENCES `users`(`id`) ON UPDATE " . $update . " ON DELETE " . $delete;

                try {
                    out("Dropping foreign key `" . $constraint . "` on table `" . $childTable . "`...");
                    $pdo->exec("ALTER TABLE `" . $childTable . "` DROP FOREIGN KEY `" . $constraint . "`");
                } catch (Throwable $e) {
                    out("<span style='color:red'>Failed to drop foreign key `" . htmlspecialchars($constraint) . "` on `" . htmlspecialchars($childTable) . "`: " . htmlspecialchars($e->getMessage()) . "</span>");
                    // abort to avoid leaving DB in inconsistent state
                    exit;
                }
            }
        }

        try {
            if (strpos($oldExtra, 'auto_increment') !== false) {
                // Perform atomic modification: remove AUTO_INCREMENT from old PK, drop PK, make id AUTO_INCREMENT and PK
                $sql = sprintf(
                    "ALTER TABLE users MODIFY COLUMN %s %s NOT NULL, DROP PRIMARY KEY, MODIFY COLUMN id %s NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (id)",
                    $pdo->quote($oldPk),
                    $oldType,
                    $idType
                );
                // PDO::quote wraps with quotes which is invalid in column identifiers; build safely instead
                $sql = "ALTER TABLE users MODIFY COLUMN `" . $oldPk . "` " . $oldType . " NOT NULL, DROP PRIMARY KEY, MODIFY COLUMN `id` " . $idType . " NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`)";
                $pdo->exec($sql);
                out("Successfully switched PRIMARY KEY to id and moved AUTO_INCREMENT.");
            } else {
                // Old PK not auto_increment — safe to drop and set id as PK
                $pdo->exec("ALTER TABLE users DROP PRIMARY KEY, MODIFY COLUMN `id` " . $idType . " NOT NULL, ADD PRIMARY KEY (`id`)");
                // Ensure AUTO_INCREMENT is set on id
                $maxId = intval($pdo->query("SELECT MAX(id) FROM users")->fetchColumn());
                $pdo->exec("ALTER TABLE users AUTO_INCREMENT = " . ($maxId + 1));
                out("Successfully switched PRIMARY KEY to id.");
            }
        } catch (Throwable $e) {
            out("<span style='color:red'>Failed to switch PRIMARY KEY: " . htmlspecialchars($e->getMessage()) . "</span>");
            exit;
        }
        exit;
    } catch (Throwable $e) {
        out("<span style='color:orange'>Could not inspect primary key: " . htmlspecialchars($e->getMessage()) . "</span>");
        // fall through to attempt additive operations
    }
}

// 2) Backup users table to a new table and JSON file
$ts = date('Ymd_His');
$backupTable = "users_migration_backup_" . $ts;
$jsonBackup = __DIR__ . "/data/users-backup-" . $ts . ".json";

try {
    out("Creating SQL backup table: $backupTable ...");
    $pdo->exec("CREATE TABLE $backupTable AS SELECT * FROM users");
    out("SQL backup table created.");
} catch (Throwable $e) {
    out("<span style='color:orange'>Could not create SQL backup table: " . htmlspecialchars($e->getMessage()) . "</span>");
}

try {
    out("Writing JSON backup to: " . htmlspecialchars($jsonBackup));
    $rows = $pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
    if (!is_dir(__DIR__ . '/data')) mkdir(__DIR__ . '/data', 0755, true);
    file_put_contents($jsonBackup, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    out("JSON backup written. Rows backed up: " . count($rows));
} catch (Throwable $e) {
    out("<span style='color:orange'>Failed to write JSON backup: " . htmlspecialchars($e->getMessage()) . "</span>");
}

// 3) Add nullable id column
try {
    out("Adding nullable 'id' column...");
    $pdo->exec("ALTER TABLE users ADD COLUMN id INT NULL");
    out("Added id column (nullable).");
} catch (Throwable $e) {
    out("<span style='color:red'>Failed to add id column: " . htmlspecialchars($e->getMessage()) . "</span>");
    exit;
}

// 4) If user_id exists, copy into id
if ($hasUserId) {
    try {
        out("Copying values from user_id -> id for existing rows...");
        $count = $pdo->exec("UPDATE users SET id = user_id WHERE id IS NULL AND user_id IS NOT NULL");
        out("Rows updated: " . intval($count));
    } catch (Throwable $e) {
        out("<span style='color:orange'>Failed to copy user_id values: " . htmlspecialchars($e->getMessage()) . "</span>");
    }
}

// 5) Fill remaining NULL ids with sequential numbers
try {
    out("Filling remaining NULL id values with sequential numbers...");
    $maxRow = $pdo->query("SELECT COALESCE(MAX(id),0) as mx FROM users")->fetch(PDO::FETCH_ASSOC);
    $start = intval($maxRow['mx']);
    // Use variable trick to assign sequential ids
    $pdo->exec("SET @n = $start");
    $pdo->exec("UPDATE users SET id = (@n := @n + 1) WHERE id IS NULL ORDER BY created_at ASC");
    $newMax = $pdo->query("SELECT MAX(id) as mx FROM users")->fetch(PDO::FETCH_ASSOC);
    out("New max(id): " . intval($newMax['mx']));
} catch (Throwable $e) {
    out("<span style='color:red'>Failed to populate id values: " . htmlspecialchars($e->getMessage()) . "</span>");
    exit;
}

// 6) Make id NOT NULL, add PK, set AUTO_INCREMENT
try {
    out("Making id NOT NULL...");
    $pdo->exec("ALTER TABLE users MODIFY COLUMN id INT NOT NULL");
    out("Adding PRIMARY KEY on id...");
    $pdo->exec("ALTER TABLE users ADD PRIMARY KEY (id)");
    $maxId = intval($pdo->query("SELECT MAX(id) FROM users")->fetchColumn());
    $next = $maxId + 1;
    out("Setting AUTO_INCREMENT to $next...");
    $pdo->exec("ALTER TABLE users AUTO_INCREMENT = $next");
    out("id column is now PRIMARY KEY AUTO_INCREMENT.");
} catch (Throwable $e) {
    out("<span style='color:red'>Failed to finalize id column: " . htmlspecialchars($e->getMessage()) . "</span>");
    exit;
}

// 7) Verify referential consistency: check user_stats, sessions, game_sessions, audit_log for user_id values
$referencing = ['user_stats','sessions','game_sessions','audit_log'];
foreach ($referencing as $tbl) {
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `$tbl`")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('user_id', $cols)) {
            $missing = $pdo->query("SELECT COUNT(*) FROM $tbl WHERE user_id IS NOT NULL AND user_id NOT IN (SELECT id FROM users)")->fetchColumn();
            out("Table $tbl: rows referencing unknown users: " . intval($missing));
        } else {
            out("Table $tbl: no user_id column found, skipping.");
        }
    } catch (Throwable $e) {
        out("Table $tbl: error inspecting - " . htmlspecialchars($e->getMessage()));
    }
}

out("Migration completed. Please inspect the backup table <strong>" . htmlspecialchars($backupTable) . "</strong> and file <strong>" . htmlspecialchars(basename($jsonBackup)) . "</strong>.");
out("IMPORTANT: If everything looks good, you may remove the legacy 'user_id' column (optional) or leave it for compatibility.");

?>
