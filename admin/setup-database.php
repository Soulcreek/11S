<?php
// File: admin/setup-database.php
// Description: Database setup and migration script for MySQL

require_once 'includes/DatabaseManager.php';

// Check if user is authorized (basic protection)
$auth_token = $_GET['token'] ?? '';
$expected_token = 'setup_' . date('Y-m-d'); // Simple daily token

if ($auth_token !== $expected_token) {
    die("Access denied. Use token: setup_" . date('Y-m-d'));
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - 11Seconds Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .setup-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .step-card {
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        .success { border-left-color: #28a745; }
        .warning { border-left-color: #ffc107; }
        .error { border-left-color: #dc3545; }
        .log-output {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 15px;
            font-family: monospace;
            font-size: 0.875rem;
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="setup-container">
            <div class="text-center mb-4">
                <h1 class="h2"><i class="fas fa-database text-primary"></i> 11Seconds Database Setup</h1>
                <p class="text-muted">Initialize MySQL database and tables</p>
            </div>

            <?php
            $setup_results = [];
            
            try {
                // Test database connection
                $db = DatabaseManager::getInstance();
                $connection_test = $db->testConnection();
                
                if ($connection_test['success']) {
                    $setup_results[] = [
                        'type' => 'success',
                        'title' => 'Database Connection',
                        'message' => 'Successfully connected to MySQL database',
                        'details' => $connection_test['data']
                    ];
                    
                    // Get database statistics
                    $stats = $db->getStats();
                    $setup_results[] = [
                        'type' => 'success',
                        'title' => 'Database Statistics',
                        'message' => 'Database initialized successfully',
                        'details' => $stats
                    ];
                    
                    // Check if tables exist and have data
                    $tables_info = [];
                    $table_names = ['users', 'user_stats', 'questions', 'game_sessions', 'sessions', 'audit_log'];
                    
                    foreach ($table_names as $table) {
                        try {
                            $count = $db->fetchOne("SELECT COUNT(*) as count FROM $table")['count'];
                            $tables_info[$table] = $count;
                        } catch (Exception $e) {
                            $tables_info[$table] = 'ERROR: ' . $e->getMessage();
                        }
                    }
                    
                    $setup_results[] = [
                        'type' => 'info',
                        'title' => 'Table Status',
                        'message' => 'Current table record counts',
                        'details' => $tables_info
                    ];
                    
                } else {
                    $setup_results[] = [
                        'type' => 'error',
                        'title' => 'Database Connection Failed',
                        'message' => $connection_test['message'],
                        'details' => null
                    ];
                }
                
            } catch (Exception $e) {
                $setup_results[] = [
                    'type' => 'error',
                    'title' => 'Setup Error',
                    'message' => $e->getMessage(),
                    'details' => null
                ];
            }
            
            // Display results
            foreach ($setup_results as $result):
                $card_class = $result['type'] === 'success' ? 'success' : 
                             ($result['type'] === 'error' ? 'error' : 
                             ($result['type'] === 'warning' ? 'warning' : ''));
                $icon = $result['type'] === 'success' ? 'check-circle' : 
                       ($result['type'] === 'error' ? 'exclamation-triangle' : 'info-circle');
            ?>
                <div class="card step-card <?= $card_class ?>">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-<?= $icon ?>"></i> <?= htmlspecialchars($result['title']) ?>
                        </h5>
                        <p class="card-text"><?= htmlspecialchars($result['message']) ?></p>
                        
                        <?php if ($result['details']): ?>
                            <div class="log-output">
                                <pre><?= htmlspecialchars(json_encode($result['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-cog"></i> Database Configuration</h5>
                    <p>Your MySQL database configuration:</p>
                    <div class="log-output">
                        <strong>Host:</strong> 10.35.233.76<br>
                        <strong>Port:</strong> 3306<br>
                        <strong>Database:</strong> quiz_game_db<br>
                        <strong>User:</strong> quiz_user<br>
                        <strong>Configuration File:</strong> /admin/data/db-config.json
                    </div>
                    
                    <div class="mt-3">
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-arrow-right"></i> Continue to Admin Login
                        </a>
                        <button class="btn btn-secondary" onclick="window.location.reload()">
                            <i class="fas fa-refresh"></i> Refresh Setup
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info mt-4">
                <h6><i class="fas fa-info-circle"></i> Next Steps:</h6>
                <ol class="mb-0">
                    <li>Update database credentials in <code>/admin/data/db-config.json</code></li>
                    <li>Access admin center at <code>/admin/</code></li>
                    <li>Login with: <strong>admin</strong> / <strong>admin123</strong></li>
                    <li>Change admin password immediately</li>
                    <li>Configure SMTP, SMS, and Google OAuth settings</li>
                </ol>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
