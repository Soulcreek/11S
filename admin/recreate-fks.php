<?php
// Recreate foreign keys from tables that have a user_id column to reference users(id).
// Requires token: ?run=1&token=...

function out($s){ echo "<p>".htmlspecialchars($s)."</p>\n"; }

$tokenPath = __DIR__ . '/data/migration-token.json';
$expectedToken = null;
if (file_exists($tokenPath)) {
    $tt = json_decode(file_get_contents($tokenPath), true);
    $expectedToken = $tt['token'] ?? null;
}
if (!isset($_GET['run']) || $_GET['run'] != '1' || !isset($_GET['token']) || $_GET['token'] !== $expectedToken) {
    echo '<h1>Recreate foreign keys</h1><p>Provide ?run=1&token=...</p>'; exit;
}

$cfg = json_decode(file_get_contents(__DIR__.'/data/db-config.json'), true);
try {
    $pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $cfg['host'], $cfg['port'], $cfg['database']), $cfg['username'], $cfg['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
} catch (Throwable $e) { out('DB connect failed: '.$e->getMessage()); exit; }

// Find tables with user_id column
$tables = $pdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE COLUMN_NAME='user_id' AND TABLE_SCHEMA=DATABASE()")->fetchAll(PDO::FETCH_COLUMN);
out('Found '.count($tables).' tables with user_id column');

foreach ($tables as $t) {
    // Skip users table itself
    if ($t === 'users') continue;
    // Check if FK already exists referencing users(id)
    $exists = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME='user_id' AND REFERENCED_TABLE_NAME='users' AND REFERENCED_COLUMN_NAME='id'");
    $exists->execute([$t]);
    if (intval($exists->fetchColumn()) > 0) { out("Table $t: FK already present, skipping"); continue; }

    // Create a constraint name
    $constraint = $t . '_fk_user_id';
    $sql = "ALTER TABLE `".$t."` ADD CONSTRAINT `".$constraint."` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE";
    try {
        $pdo->exec($sql);
        out("Table $t: FK created: $constraint");
    } catch (Throwable $e) {
        out("Table $t: failed to create FK: " . $e->getMessage());
    }
}

out('Done.');

?>
