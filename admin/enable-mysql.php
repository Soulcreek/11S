<?php
// enable-mysql.php
// Usage: ?run=1&token=...  (token stored in admin/data/migration-token.json)

function out($s){ echo "<p>".htmlspecialchars($s)."</p>\n"; }

$tokenPath = __DIR__ . '/data/migration-token.json';
$expectedToken = null;
if (file_exists($tokenPath)) {
    $tt = json_decode(file_get_contents($tokenPath), true);
    $expectedToken = $tt['token'] ?? null;
}
if (!isset($_GET['run']) || $_GET['run'] != '1' || !isset($_GET['token']) || $_GET['token'] !== $expectedToken) {
    echo '<h1>Enable MySQL (disable JSON fallback)</h1><p>Provide ?run=1&token=...</p>'; exit;
}

require_once __DIR__ . '/includes/HybridDataManager.php';

try {
    $hm = HybridDataManager::getInstance();
} catch (Throwable $e) {
    out('Failed to instantiate HybridDataManager: ' . $e->getMessage()); exit;
}

out('Calling disableFallback()...');
$res = $hm->disableFallback();
if (isset($res['success']) && $res['success'] === true) {
    out('disableFallback: success - ' . ($res['message'] ?? ''));
    out('Running syncToMySQL() to flush pending JSON ops...');
    $sync = $hm->syncToMySQL();
    out('Sync result: ' . htmlspecialchars(json_encode($sync)));
} else {
    out('disableFallback failed: ' . htmlspecialchars(json_encode($res)));
    out('Attempting syncToMySQL anyway...');
    $sync = $hm->syncToMySQL();
    out('Sync result: ' . htmlspecialchars(json_encode($sync)));
}

out('Final status: fallback=' . ($hm->isInFallbackMode() ? 'true' : 'false'));
out('Pending operations: ' . count($hm->getPendingOperations()));

?>
