<?php
session_start();
require_once 'database.php';

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$db = Database::getInstance();

// Get statistics
$user_stats = $db->queryOne("SELECT 
    COUNT(*) as total_users,
    COUNT(CASE WHEN role = 'admin' THEN 1 END) as admin_users,
    COUNT(CASE WHEN active = 1 THEN 1 END) as active_users
    FROM users");

$question_stats = $db->queryOne("SELECT 
    COUNT(*) as total_questions,
    COUNT(CASE WHEN active = 1 THEN 1 END) as active_questions
    FROM questions");

$session_stats = $db->queryOne("SELECT 
    COUNT(*) as total_sessions,
    COALESCE(AVG(score), 0) as avg_score,
    COALESCE(MAX(score), 0) as max_score
    FROM game_sessions");

// Get recent activity
$recent_users = $db->query("SELECT username, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$recent_sessions = $db->query("SELECT 
    u.username, 
    gs.score, 
    gs.session_start 
    FROM game_sessions gs 
    JOIN users u ON gs.user_id = u.id 
    ORDER BY gs.session_start DESC 
    LIMIT 5");
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>11Seconds Admin - Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .nav-links {
            display: flex;
            gap: 1rem;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .nav-links a.active {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.25);
        }
        
        .stat-card i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: white;
        }
        
        .stat-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .section h3 {
            margin-bottom: 1rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .activity-time {
            color: #666;
            font-size: 0.9rem;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>
            <i class="fas fa-gamepad"></i>
            11Seconds Admin
        </h1>
        <nav class="nav-links">
            <a href="dashboard.php" class="active">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="users.php">
                <i class="fas fa-users"></i> Users
            </a>
            <a href="questions.php">
                <i class="fas fa-question-circle"></i> Questions
            </a>
            <a href="?logout=1" style="color: #fca5a5;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </header>

    <div class="container">
        <div class="stats-grid">
            <div class="stat-card users">
                <i class="fas fa-users"></i>
                <div class="stat-number"><?php echo number_format($user_stats['total_users'] ?? 0); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            
            <div class="stat-card questions">
                <i class="fas fa-question-circle"></i>
                <div class="stat-number"><?php echo number_format($question_stats['active_questions'] ?? 0); ?></div>
                <div class="stat-label">Active Questions</div>
            </div>
            
            <div class="stat-card sessions">
                <i class="fas fa-play"></i>
                <div class="stat-number"><?php echo number_format($session_stats['total_sessions'] ?? 0); ?></div>
                <div class="stat-label">Game Sessions</div>
            </div>
            
            <div class="stat-card score">
                <i class="fas fa-trophy"></i>
                <div class="stat-number"><?php echo number_format($session_stats['avg_score'] ?? 0, 1); ?>%</div>
                <div class="stat-label">Average Score</div>
            </div>
        </div>

        <div class="section">
            <h3><i class="fas fa-rocket"></i> Quick Actions</h3>
            <div class="quick-actions">
                <a href="users.php" class="action-btn">
                    <i class="fas fa-user-plus"></i>
                    Add User
                </a>
                <a href="questions.php" class="action-btn">
                    <i class="fas fa-plus"></i>
                    Add Question
                </a>
                <a href="users.php" class="action-btn">
                    <i class="fas fa-users-cog"></i>
                    Manage Users
                </a>
                <a href="questions.php" class="action-btn">
                    <i class="fas fa-edit"></i>
                    Edit Questions
                </a>
            </div>
        </div>

        <div class="content-grid">
            <div class="section">
                <h3><i class="fas fa-user-plus"></i> Recent Users</h3>
                <?php if (!empty($recent_users)): ?>
                    <?php foreach ($recent_users as $user): ?>
                        <div class="activity-item">
                            <div class="activity-info">
                                <i class="fas fa-user"></i>
                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                            </div>
                            <div class="activity-time">
                                <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 2rem;">No users yet</p>
                <?php endif; ?>
            </div>

            <div class="section">
                <h3><i class="fas fa-play"></i> Recent Sessions</h3>
                <?php if (!empty($recent_sessions)): ?>
                    <?php foreach ($recent_sessions as $session): ?>
                        <div class="activity-item">
                            <div class="activity-info">
                                <i class="fas fa-play"></i>
                                <strong><?php echo htmlspecialchars($session['username']); ?></strong>
                                <span>scored <?php echo $session['score']; ?>%</span>
                            </div>
                            <div class="activity-time">
                                <?php echo date('M j, H:i', strtotime($session['session_start'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 2rem;">No sessions yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
