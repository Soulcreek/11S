<?php
header('Content-Type: application/json');
// Read DB config
$cfgPath = __DIR__ . '/data/db-config.json';
if (!file_exists($cfgPath)) {
    echo json_encode(['error' => 'db-config.json not found']);
    exit;
}
$cfg = json_decode(file_get_contents($cfgPath), true);
if (!$cfg) {
    echo json_encode(['error' => 'invalid db-config.json']);
    exit;
}
try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $cfg['host'], $cfg['port'], $cfg['database']);
    $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Throwable $e) {
    echo json_encode(['error' => 'pdo_connect_failed', 'message' => $e->getMessage()]);
    exit;
}

$tables = ['users','highscores','solo_games'];
$out = ['ok' => true, 'tables' => []];
foreach ($tables as $t) {
    try {
        $idx = $pdo->query("SHOW INDEX FROM `" . $t . "`")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $idx = ['error' => $e->getMessage()];
    }
    try {
        $create = $pdo->query("SHOW CREATE TABLE `" . $t . "`")->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $create = ['Create Table' => 'ERROR: ' . $e->getMessage()];
    }
    $out['tables'][$t] = ['indexes' => $idx, 'create' => $create];
}

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

?>