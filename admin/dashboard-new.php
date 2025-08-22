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

$dataManager = HybridDataManager::getInstance();
$config = new SubdomainConfig();

// Get statistics from database
try {
    $stats = $dataManager->getStats();
    $questions_count = $stats['questions'] ?? 0;
    $users_count = $stats['users'] ?? 0;
    $total_sessions = $stats['sessions'] ?? 0;
    $avg_score = $stats['avg_score'] ?? 0;

    // Get recent activity
    $sql = <<<'SQL'
SELECT gs.*, u.username
FROM game_sessions gs
LEFT JOIN users u ON gs.user_id = u.id
ORDER BY gs.created_at DESC
LIMIT 10
SQL;

    $recent_sessions = $dataManager->query($sql);
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
    <p>The database tables need to be initialized. Please run the database setup first.</p>
    <a href="setup-database.php?token=setup_<?php echo date('Y-m-d'); ?>" class="btn btn-primary">
        Setup Database
    </a>
</div>
<?php endif; ?>

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
