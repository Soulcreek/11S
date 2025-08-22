<?php
session_start();
require_once 'includes/DatabaseManager.php';
require_once 'includes/HybridDataManager.php';
require_once 'includes/SubdomainConfig.php';

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

// Initialize data manager (hybrid MySQL/JSON)
$dataManager = HybridDataManager::getInstance();
$config = new SubdomainConfig();

// Handle JSON fallback mode activation
if (isset($_GET['fallback']) && $_GET['fallback'] === 'json') {
    $dataManager = HybridDataManager::getInstance();
    echo '<div class="alert alert-info">
        📁 <strong>JSON Fallback Mode Activated</strong><br>
        The system is now running in JSON fallback mode. Data will be stored locally and synced to MySQL when available.
        <br><a href="dashboard.php" class="btn btn-sm btn-primary mt-2">Continue to Dashboard</a>
    </div>';
}

// Handle sync operation
if (isset($_POST['sync_to_mysql'])) {
    $syncResult = $dataManager->syncToMySQL();
    if ($syncResult['success']) {
        echo '<div class="alert alert-success">
            🔄 <strong>Sync Completed!</strong><br>
            Synced: ' . $syncResult['synced'] . ' operations<br>
            Pending: ' . $syncResult['pending'] . ' operations<br>
        </div>';
    } else {
        echo '<div class="alert alert-warning">
            ⚠️ <strong>Sync Failed:</strong> ' . $syncResult['error'] . '
        </div>';
    }
}

// Get statistics from hybrid manager
try {
    $stats = $dataManager->getStats();
    $questions_count = $stats['questions'] ?? 0;
    $users_count = $stats['users'] ?? 0;
    $total_sessions = $stats['sessions'] ?? 0;
    $avg_score = $stats['avg_score'] ?? 0;

    // Get recent activity
    $recent_sessions = $dataManager->query("
        SELECT gs.*, u.username 
        FROM game_sessions gs 
        LEFT JOIN user_stats u ON gs.user_id = u.id 
        ORDER BY gs.created_at DESC 
        LIMIT 10
    ");
} catch (Exception $e) {
    // If database is not initialized yet, show setup message
    $database_error = $e->getMessage();
    $questions_count = 0;
    $users_count = 0;
    $total_sessions = 0;
    $avg_score = 0;
    $recent_sessions = [];
}

$page_title = "Dashboard";
include 'includes/header.php';
?>

<?php if (isset($database_error)): ?>
<div class="alert alert-warning">
    <h4>Database Setup Required</h4>
    <p>The database tables need to be initialized. Click the button below to set up the database.</p>
    
    <?php
    // Handle database initialization if requested
    if (isset($_POST['init_database'])) {
        echo '<div class="alert alert-info">🔧 Initializing database...</div>';
        echo '<div class="console-output" style="background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; font-family: monospace; font-size: 12px;">';
        
        try {
            echo "📋 <strong>Step 1:</strong> Loading database configuration...<br>";
            flush();
            
            $config = json_decode(file_get_contents(__DIR__ . '/data/db-config.json'), true);
            echo "✓ Config loaded: {$config['host']}:{$config['port']} -> {$config['database']}<br>";
            echo "✓ Username: {$config['username']}<br>";
            flush();
            
            echo "<br>🔌 <strong>Step 2:</strong> Testing database connection...<br>";
            flush();
            
            $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
            $pdo = new PDO($dsn, $config['username'], $config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "✓ Base connection established<br>";
            flush();
            
            echo "🗄️ <strong>Step 3:</strong> Checking database existence...<br>";
            flush();
            
            $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$config['database']}'");
            if ($stmt->rowCount() > 0) {
                echo "✓ Database '{$config['database']}' exists<br>";
            } else {
                echo "❌ Database '{$config['database']}' does not exist!<br>";
                throw new Exception("Database {$config['database']} not found");
            }
            flush();
            
            echo "<br>🔧 <strong>Step 4:</strong> Connecting to target database...<br>";
            flush();
            
            $db = DatabaseManager::getInstance();
            echo "✓ DatabaseManager instance created<br>";
            flush();
            
            echo "<br>🏗️ <strong>Step 5:</strong> Creating database tables...<br>";
            flush();
            
            $db->initializeTables();
            echo "✓ Table creation process completed<br>";
            flush();
            
            echo "<br>📊 <strong>Step 6:</strong> Verifying table creation...<br>";
            flush();
            
            $tables = $pdo->query("SHOW TABLES FROM {$config['database']}")->fetchAll();
            echo "✓ Found " . count($tables) . " tables:<br>";
            foreach ($tables as $table) {
                echo "  - {$table[0]}<br>";
            }
            flush();
            
            echo "<br>📈 <strong>Step 7:</strong> Getting statistics...<br>";
            flush();
            
            $stats = $db->getStats();
            echo "✓ Statistics retrieved successfully<br>";
            flush();
            
            echo '</div>';
            echo '<div class="alert alert-success">
                🎉 <strong>Database initialized successfully!</strong><br><br>
                <strong>📊 Current Status:</strong><br>
                - Questions: ' . ($stats['questions'] ?? 0) . ' found<br>
                - Users: ' . ($stats['users'] ?? 0) . ' created<br>
                - Sessions: ' . ($stats['sessions'] ?? 0) . ' total<br>
                <br>
                <a href="dashboard.php" class="btn btn-primary">🔄 Refresh Dashboard</a>
            </div>';
            
        } catch (Exception $init_error) {
            echo '</div>';
            echo '<div class="alert alert-danger">
                ❌ <strong>Database initialization failed!</strong><br><br>
                <strong>Error Details:</strong><br>
                <code>' . htmlspecialchars($init_error->getMessage()) . '</code><br><br>
                <strong>Location:</strong> ' . htmlspecialchars(basename($init_error->getFile())) . ' (Line ' . $init_error->getLine() . ')<br><br>
                <strong>Stack Trace:</strong><br>
                <pre style="font-size: 10px; max-height: 200px; overflow-y: scroll;">' . htmlspecialchars($init_error->getTraceAsString()) . '</pre>
                <br>
                <strong>🔧 Troubleshooting:</strong><br>
                - Check database server is running<br>
                - Verify credentials in admin/data/db-config.json<br>
                - Ensure database k302164_11Sec_Data exists<br>
                - Check network connectivity to ' . htmlspecialchars($config['host'] ?? 'database server') . '<br>
                <br>
                <a href="#" onclick="location.reload()" class="btn btn-warning">🔄 Try Again</a>
                <a href="?fallback=json" class="btn btn-info">📁 Use JSON Fallback</a>
            </div>';
        }
    } else {
        echo '<form method="POST" style="margin-top: 15px;">
                <button type="submit" name="init_database" class="btn btn-primary">🚀 Initialize Database</button>
              </form>';
    }
    ?>
</div>
<?php endif; ?>

<!-- Data Manager Status -->
<div class="row mb-4">
    <div class="col-md-12">
        <?php if ($dataManager->isInFallbackMode()): ?>
            <div class="alert alert-warning">
                📁 <strong>JSON Fallback Mode Active</strong><br>
                MySQL is currently unavailable. Data is being stored locally in JSON files.<br>
                Pending sync operations: <?php echo count($dataManager->getPendingOperations()); ?><br>
                <div class="mt-2">
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="sync_to_mysql" class="btn btn-sm btn-primary">
                            🔄 Try Sync to MySQL
                        </button>
                    </form>
                    <button type="button" class="btn btn-sm btn-info" onclick="toggleSyncLog()">
                        📋 View Sync Log
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-success">
                🗄️ <strong>MySQL Database Active</strong><br>
                Connected successfully to the database server.<br>
                All operations are being stored directly in MySQL.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Sync Log (Hidden by default) -->
<div id="sync-log" style="display: none;" class="mb-4">
    <div class="card">
        <div class="card-header">
            <h5>🔍 System Log</h5>
        </div>
        <div class="card-body">
            <pre style="font-size: 12px; max-height: 300px; overflow-y: scroll; background: #f8f9fa; padding: 10px;"><?php 
                foreach ($dataManager->getSyncLog() as $logEntry) {
                    echo htmlspecialchars($logEntry) . "\n";
                }
            ?></pre>
            
            <?php if (!empty($dataManager->getPendingOperations())): ?>
            <h6 class="mt-3">⏳ Pending Operations:</h6>
            <ul style="font-size: 12px;">
                <?php foreach ($dataManager->getPendingOperations() as $op): ?>
                    <li><?php echo htmlspecialchars($op['operation']) . ' - ' . htmlspecialchars($op['timestamp']); ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleSyncLog() {
    const log = document.getElementById('sync-log');
    log.style.display = log.style.display === 'none' ? 'block' : 'none';
}
</script>

<div class="dashboard-grid">
    <div class="stat-card">
        <h3>Questions</h3>
        <div class="stat-number"><?php echo number_format($questions_count); ?></div>
        <div class="stat-label">Total Questions</div>
    </div>
    
    <div class="stat-card">
        <h3>Users</h3>
        <div class="stat-number"><?php echo number_format($users_count); ?></div>
        <div class="stat-label">Registered Users</div>
    </div>
    
    <div class="stat-card">
        <h3>Sessions</h3>
        <div class="stat-number"><?php echo number_format($total_sessions); ?></div>
        <div class="stat-label">Game Sessions</div>
    </div>
    
    <div class="stat-card">
        <h3>Average Score</h3>
        <div class="stat-number"><?php echo number_format($avg_score, 1); ?>%</div>
        <div class="stat-label">Overall Performance</div>
    </div>
</div>

<div class="dashboard-section">
    <h2>Quick Actions</h2>
    <div class="action-grid">
        <a href="<?php echo $config->adminUrl('question-manager'); ?>" class="action-btn">
            <i class="icon">📝</i>
            <span>Manage Questions</span>
        </a>
        
        <a href="<?php echo $config->adminUrl('user-management'); ?>" class="action-btn">
            <i class="icon">👥</i>
            <span>User Management</span>
        </a>
        
        <a href="<?php echo $config->adminUrl('statistics'); ?>" class="action-btn">
            <i class="icon">📊</i>
            <span>Detailed Statistics</span>
        </a>
        
        <a href="<?php echo $config->adminUrl('settings'); ?>" class="action-btn">
            <i class="icon">⚙️</i>
            <span>Settings</span>
        </a>
    </div>
</div>

<?php if (!empty($recent_sessions)): ?>
<div class="dashboard-section">
    <h2>Recent Activity</h2>
    <div class="activity-list">
        <?php foreach ($recent_sessions as $session): ?>
            <div class="activity-item">
                <div class="activity-info">
                    <strong><?php echo htmlspecialchars($session['username'] ?? 'Anonymous'); ?></strong>
                    <span>Score: <?php echo $session['score']; ?>%</span>
                </div>
                <div class="activity-time">
                    <?php echo date('M j, Y H:i', strtotime($session['created_at'])); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="dashboard-section">
    <h2>System Status</h2>
    <div class="status-grid">
        <div class="status-item">
            <span class="status-label">Database</span>
            <span class="status-value <?php echo isset($database_error) ? 'offline' : 'online'; ?>">
                <?php echo isset($database_error) ? 'Setup Required' : 'Connected'; ?>
            </span>
        </div>
        <div class="status-item">
            <span class="status-label">Questions Database</span>
            <span class="status-value online"><?php echo $questions_count; ?> loaded</span>
        </div>
        <div class="status-item">
            <span class="status-label">User System</span>
            <span class="status-value online">Active</span>
        </div>
        <div class="status-item">
            <span class="status-label">Session Management</span>
            <span class="status-value online">Running</span>
        </div>
    </div>
</div>

<style>
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
    border: 1px solid transparent;
}

.alert-warning {
    background-color: #fcf8e3;
    border-color: #faebcc;
    color: #8a6d3b;
}

.alert-info {
    background-color: #d1ecf1;
    border-color: #bee5eb;
    color: #0c5460;
}

.alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

.btn {
    display: inline-block;
    padding: 6px 12px;
    margin-bottom: 0;
    font-size: 14px;
    font-weight: normal;
    line-height: 1.42857143;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    cursor: pointer;
    border: 1px solid transparent;
    border-radius: 4px;
    text-decoration: none;
}

.btn-primary {
    color: #fff;
    background-color: #337ab7;
    border-color: #2e6da4;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-card h3 {
    margin: 0 0 10px 0;
    color: #666;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    color: #2196F3;
    margin-bottom: 5px;
}

.stat-label {
    color: #888;
    font-size: 12px;
}

.dashboard-section {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.dashboard-section h2 {
    margin: 0 0 20px 0;
    color: #333;
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.action-btn {
    display: flex;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s;
}

.action-btn:hover {
    background: #e9ecef;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    text-decoration: none;
    color: #333;
}

.action-btn .icon {
    font-size: 24px;
    margin-right: 10px;
}

.activity-list {
    max-height: 300px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-info strong {
    margin-right: 10px;
}

.activity-time {
    color: #666;
    font-size: 12px;
}

.status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
}

.status-label {
    font-weight: 500;
    color: #333;
}

.status-value {
    font-weight: bold;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
}

.status-value.online {
    background: #d4edda;
    color: #155724;
}

.status-value.offline {
    background: #f8d7da;
    color: #721c24;
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .action-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
