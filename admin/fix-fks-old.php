<?php
// fix-fks-old.php
// Drops foreign keys that reference users_old and recreates them to reference users(id).
// Usage: ?run=1&token=...

function out($s){ echo "<p>".htmlspecialchars($s)."</p>\n"; }

$tokenPath = __DIR__ . '/data/migration-token.json';
$expectedToken = null;
if (file_exists($tokenPath)) {
    $tt = json_decode(file_get_contents($tokenPath), true);
    $expectedToken = $tt['token'] ?? null;
}
if (!isset($_GET['run']) || $_GET['run'] != '1' || !isset($_GET['token']) || $_GET['token'] !== $expectedToken) {
    echo '<h1>Fix FKs referencing users_old</h1><p>Provide ?run=1&token=...</p>'; exit;
}

$cfg = json_decode(file_get_contents(__DIR__.'/data/db-config.json'), true);
try {
    $pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $cfg['host'], $cfg['port'], $cfg['database']), $cfg['username'], $cfg['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
} catch (Throwable $e) { out('DB connect failed: '.$e->getMessage()); exit; }

// Find FKs that reference users_old
$q = $pdo->prepare("SELECT k.TABLE_NAME, k.CONSTRAINT_NAME, k.COLUMN_NAME, k.REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME, r.UPDATE_RULE, r.DELETE_RULE FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE k JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS r ON k.CONSTRAINT_NAME = r.CONSTRAINT_NAME AND k.CONSTRAINT_SCHEMA = r.CONSTRAINT_SCHEMA WHERE k.REFERENCED_TABLE_NAME = 'users_old' AND k.TABLE_SCHEMA = DATABASE()");
$q->execute();
$fks = $q->fetchAll(PDO::FETCH_ASSOC);
out('Found '.count($fks).' foreign keys referencing users_old');

foreach ($fks as $fk) {
    $child = $fk['TABLE_NAME'];
    $constraint = $fk['CONSTRAINT_NAME'];
    $col = $fk['COLUMN_NAME'];
    $update = $fk['UPDATE_RULE'];
    $delete = $fk['DELETE_RULE'];
    try {
        out("Dropping FK $constraint on $child...");
        $pdo->exec("ALTER TABLE `".$child."` DROP FOREIGN KEY `".$constraint."`");
    } catch (Throwable $e) {
        out("Failed to drop FK $constraint on $child: " . $e->getMessage());
        continue;
    }

    // Create new constraint name
    $newName = $child . '_fk_user_id';
    $sql = "ALTER TABLE `".$child."` ADD CONSTRAINT `".$newName."` FOREIGN KEY (`".$col."`) REFERENCES `users`(`id`) ON UPDATE " . $update . " ON DELETE " . $delete;
    try {
        $pdo->exec($sql);
        out("Created FK $newName on $child -> users(id)");
    } catch (Throwable $e) {
        out("Failed to create FK $newName on $child: " . $e->getMessage());
    }
}

out('Done.');

?>
