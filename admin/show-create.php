<?php
// show-create.php?table=game_sessions&run=1&token=...
function out($s){ echo "<pre>".htmlspecialchars($s)."</pre>\n"; }
$tokenPath = __DIR__ . '/data/migration-token.json';
$expectedToken = null;
if (file_exists($tokenPath)) {
    $tt = json_decode(file_get_contents($tokenPath), true);
    $expectedToken = $tt['token'] ?? null;
}
if (!isset($_GET['run']) || $_GET['run'] != '1' || !isset($_GET['token']) || $_GET['token'] !== $expectedToken || !isset($_GET['table'])) {
    echo '<h1>Show CREATE TABLE</h1><p>Usage: ?table=NAME&run=1&token=...</p>'; exit;
}
$table = preg_replace('/[^a-z0-9_]/i','', $_GET['table']);
require_once __DIR__ . '/includes/DatabaseManager.php';
try { $db = new DatabaseManager(); $pdo = $db->getConnection(); } catch(Throwable $e){ out('DB connect failed: '.$e->getMessage()); exit; }
try {
    $row = $pdo->query('SHOW CREATE TABLE `'.$table.'`')->fetch(PDO::FETCH_ASSOC);
    out($row['Create Table'] ?? 'No create found');
} catch(Throwable $e) { out('Error: '.$e->getMessage()); }

?>
