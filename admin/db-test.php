<?php
// Simple DB connectivity test for the admin area.
// Reads ../config/.env and attempts a PDO connection. Outputs minimal, masked info.

header('Content-Type: text/plain; charset=utf-8');

// Global error/exception handlers to capture unexpected failures in server environment
set_error_handler(function($errno, $errstr, $errfile, $errline){
    $msg = sprintf("[ERROR] %s in %s on line %d\n", $errstr, $errfile, $errline);
    @file_put_contents(__DIR__ . '/db-test-debug.log', date('[Y-m-d H:i:s] ') . $msg, FILE_APPEND | LOCK_EX);
    // Let PHP internal handler also run for fatal errors
});
set_exception_handler(function($e){
    $msg = sprintf("[EXCEPTION] %s in %s on line %d\nStack: %s\n", $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
    @file_put_contents(__DIR__ . '/db-test-debug.log', date('[Y-m-d H:i:s] ') . $msg, FILE_APPEND | LOCK_EX);
    header('Content-Type: text/plain; charset=utf-8');
    echo "ERROR: An internal exception occurred. Check admin/db-test-debug.log on the server for details.\n";
    exit(1);
});

$envPath = realpath(__DIR__ . '/../config/.env');
if (! $envPath || ! file_exists($envPath)) {
    echo "ERROR: config/.env not found at expected location (../config/.env)\n";
    exit(1);
}

function parse_dotenv($path) {
    $out = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($k, $v) = explode('=', $line, 2);
        $k = trim($k); $v = trim($v);
        // remove surrounding quotes
        if ((substr($v,0,1) === '"' && substr($v,-1) === '"') || (substr($v,0,1) === "'" && substr($v,-1) === "'")) {
            $v = substr($v,1,-1);
        }
        $out[$k] = $v;
    }
    return $out;
}

$env = parse_dotenv($envPath);

$dbHost = $env['DB_HOST'] ?? 'localhost';
$dbPort = $env['DB_PORT'] ?? 3306;
$dbName = $env['DB_NAME'] ?? '';
$dbUser = $env['DB_USER'] ?? '';
$dbPass = $env['DB_PASS'] ?? '';
$dbCharset = $env['DB_CHARSET'] ?? 'utf8mb4';

// Mask password for display
$mask = function($s){
    if ($s === '') return '(empty)';
    $len = strlen($s);
    if ($len <= 4) return str_repeat('*', $len);
    return substr($s,0,2) . str_repeat('*', max(4,$len-4)) . substr($s,-2);
};

echo "DB_HOST: {$dbHost}:{$dbPort}\n";
echo "DB_NAME: {$dbName}\n";
echo "DB_USER: {$dbUser}\n";
echo "DB_PASS: " . $mask($dbPass) . "\n";
echo "DB_CHARSET: {$dbCharset}\n";
echo "\nAttempting PDO connection...\n";

try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$dbCharset}";
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5,
    ];
    $pdo = new PDO($dsn, $dbUser, $dbPass, $opts);
    echo "Connection: SUCCESS\n";
    // Quick sanity query
    $stmt = $pdo->query('SELECT 1 as ok');
    $row = $stmt->fetch();
    echo "Sanity query result: " . ($row['ok'] ?? 'n/a') . "\n";
} catch (PDOException $e) {
    echo "Connection: FAILED\n";
    echo "PDO Error: " . $e->getMessage() . "\n";
    // don't expose full stack
}

exit(0);

?>
<?php
// db-test.php - quick DB connectivity tester (reads ../config/.env)
// WARNING: This file prints error messages for debugging. Remove it after use.
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($k,$v) = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
    }
}
loadEnv(__DIR__ . '/../config/.env');
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$name = $_ENV['DB_NAME'] ?? '';
$user = $_ENV['DB_USER'] ?? '';
$pass = $_ENV['DB_PASS'] ?? '';
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
$dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
header('Content-Type: text/plain');
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "DB OK: connected to {$name} at {$host}:{$port}\n";
    // Optional: show current user and host as seen by server
    $row = $pdo->query("SELECT CURRENT_USER() as cu, USER() as u")->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo "CURRENT_USER: " . ($row['cu'] ?? '') . "\n";
        echo "USER(): " . ($row['u'] ?? '') . "\n";
    }
} catch (PDOException $e) {
    echo "DB ERROR: " . $e->getMessage() . "\n";
}
?>
