<?php
// File: admin/security-dashboard.php
// Description: Security monitoring and anti-cheat dashboard

session_start();

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

$data_dir = __DIR__ . '/data';
$security_flags_file = $data_dir . '/security_flags.json';
$score_submissions_file = $data_dir . '/score_submissions.json';
$user_sessions_file = $data_dir . '/user_sessions.json';

// Load security data
$security_flags = [];
if (file_exists($security_flags_file)) {
    $security_flags = json_decode(file_get_contents($security_flags_file), true) ?? [];
}

$score_submissions = [];
if (file_exists($score_submissions_file)) {
    $score_submissions = json_decode(file_get_contents($score_submissions_file), true) ?? [];
}

$user_sessions = [];
if (file_exists($user_sessions_file)) {
    $user_sessions = json_decode(file_get_contents($user_sessions_file), true) ?? [];
}

// Handle actions
$success_message = '';
$error_message = '';

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'resolve_flag':
            $flag_index = intval($_POST['flag_index']);
            if (isset($security_flags[$flag_index])) {
                $security_flags[$flag_index]['resolved'] = true;
                $security_flags[$flag_index]['resolved_by'] = 'admin';
                $security_flags[$flag_index]['resolved_at'] = time();
                file_put_contents($security_flags_file, json_encode($security_flags, JSON_PRETTY_PRINT));
                $success_message = 'Sicherheitswarnung als gelöst markiert.';
            }
            break;
            
        case 'ban_user':
            // Implementation for user banning
            $success_message = 'Benutzer gesperrt.';
            break;
    }
}

// Filter recent data
$recent_flags = array_filter($security_flags, fn($flag) => !$flag['resolved'] && $flag['timestamp'] > (time() - 86400));
$recent_submissions = array_slice(array_reverse($score_submissions), 0, 100);
$suspicious_submissions = array_filter($score_submissions, fn($sub) => !$sub['accepted']);

// Statistics
$total_flags = count($security_flags);
$unresolved_flags = count($recent_flags);
$suspicious_rate = count($suspicious_submissions) / max(count($score_submissions), 1) * 100;
$active_sessions = count(array_filter($user_sessions, fn($session) => $session['expires_at'] > time()));

// Top suspicious users
$user_flag_counts = [];
foreach ($security_flags as $flag) {
    if (!$flag['resolved']) {
        $user_id = $flag['user_id'];
        $user_flag_counts[$user_id] = ($user_flag_counts[$user_id] ?? 0) + 1;
    }
}
arsort($user_flag_counts);
$top_suspicious_users = array_slice($user_flag_counts, 0, 10, true);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sicherheits-Dashboard - 11Seconds Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #2ECC71;
            --secondary-green: #27AE60;
            --accent-green: #1ABC9C;
            --dark-green: #1e8449;
            --light-green: rgba(46, 204, 113, 0.1);
            --danger: #E74C3C;
            --warning: #F39C12;
            --info: #3498DB;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8fffe 0%, #e8f8f5 100%);
            color: #333;
        }
        
        .header {
            background: linear-gradient(45deg, var(--primary-green), var(--secondary-green));
            color: white;
            padding: 20px 0;
            box-shadow: 0 4px 20px rgba(46, 204, 113, 0.3);
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-icon {
            font-size: 28px;
        }
        
        .logo-text {
            font-size: 20px;
            font-weight: bold;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        
        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .page-title {
            font-size: 28px;
            margin-bottom: 30px;
            color: var(--dark-green);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            text-align: center;
            border-left: 4px solid var(--primary-green);
        }
        
        .stat-card.danger {
            border-left-color: var(--danger);
        }
        
        .stat-card.warning {
            border-left-color: var(--warning);
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 15px;
        }
        
        .stat-icon.danger {
            color: var(--danger);
        }
        
        .stat-icon.warning {
            color: var(--warning);
        }
        
        .stat-icon.success {
            color: var(--primary-green);
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: var(--dark-green);
            margin-bottom: 8px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            border-left: 4px solid var(--primary-green);
        }
        
        .section-title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 25px;
            color: var(--dark-green);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-danger {
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
        }
        
        .alert-warning {
            background: rgba(243, 156, 18, 0.1);
            border: 1px solid var(--warning);
            color: var(--warning);
        }
        
        .alert-time {
            font-size: 12px;
            color: #666;
            margin-left: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .table th {
            background: var(--light-green);
            font-weight: bold;
            color: var(--dark-green);
        }
        
        .table tr:hover {
            background: rgba(46, 204, 113, 0.05);
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-success {
            background: var(--primary-green);
            color: white;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .status-accepted {
            background: rgba(39, 174, 96, 0.2);
            color: var(--primary-green);
        }
        
        .status-rejected {
            background: rgba(231, 76, 60, 0.2);
            color: var(--danger);
        }
        
        .user-ranking {
            background: var(--light-green);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .ranking-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(46, 204, 113, 0.3);
        }
        
        .ranking-item:last-child {
            border-bottom: none;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: bold;
        }
        
        .success {
            background: rgba(39, 174, 96, 0.1);
            color: var(--primary-green);
            border: 1px solid var(--primary-green);
        }
        
        .error {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
            border: 1px solid var(--danger);
        }
        
        .auto-refresh {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--primary-green);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">🛡️</div>
                <div class="logo-text">Sicherheits-Dashboard</div>
            </div>
            <div class="nav-links">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="user-management-enhanced.php"><i class="fas fa-users"></i> Benutzer</a>
                <a href="question-management.php"><i class="fas fa-question-circle"></i> Fragen</a>
                <a href="media-management.php"><i class="fas fa-palette"></i> Branding</a>
                <a href="settings.php"><i class="fas fa-cog"></i> Einstellungen</a>
                <a href="index.php?logout=1"><i class="fas fa-sign-out-alt"></i> Abmelden</a>
            </div>
        </div>
    </header>

    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-shield-alt"></i>
            Sicherheits-Monitoring
        </h1>

        <?php if (!empty($success_message)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Security Statistics -->
        <div class="stats-grid">
            <div class="stat-card <?php echo $unresolved_flags > 0 ? 'danger' : ''; ?>">
                <div class="stat-icon <?php echo $unresolved_flags > 0 ? 'danger' : 'success'; ?>">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-number"><?php echo $unresolved_flags; ?></div>
                <div class="stat-label">Offene Warnungen</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-number"><?php echo number_format($suspicious_rate, 1); ?>%</div>
                <div class="stat-label">Verdächtige Rate</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo $active_sessions; ?></div>
                <div class="stat-label">Aktive Sessions</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-database"></i>
                </div>
                <div class="stat-number"><?php echo count($recent_submissions); ?></div>
                <div class="stat-label">Letzte Submissions</div>
            </div>
        </div>

        <!-- Security Alerts -->
        <?php if (!empty($recent_flags)): ?>
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-bell"></i> Aktuelle Sicherheitswarnungen
                </h2>

                <?php foreach (array_slice($recent_flags, 0, 10) as $index => $flag): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <strong>Benutzer <?php echo htmlspecialchars($flag['user_id']); ?>:</strong>
                            <?php echo htmlspecialchars($flag['reason']); ?>
                        </div>
                        <div class="alert-time">
                            <?php echo date('d.m.Y H:i', $flag['timestamp']); ?>
                        </div>
                        <form method="post" style="margin-left: 10px;">
                            <input type="hidden" name="action" value="resolve_flag">
                            <input type="hidden" name="flag_index" value="<?php echo array_search($flag, $security_flags); ?>">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check"></i> Lösen
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Top Suspicious Users -->
        <?php if (!empty($top_suspicious_users)): ?>
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-user-secret"></i> Verdächtige Benutzer
                </h2>

                <div class="user-ranking">
                    <?php foreach ($top_suspicious_users as $user_id => $flag_count): ?>
                        <div class="ranking-item">
                            <div>
                                <strong><?php echo htmlspecialchars($user_id); ?></strong>
                            </div>
                            <div>
                                <span class="status-badge status-rejected">
                                    <?php echo $flag_count; ?> Warnungen
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recent Submissions -->
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-list"></i> Letzte Score-Einreichungen
            </h2>

            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Benutzer</th>
                            <th>Score</th>
                            <th>Spielzeit</th>
                            <th>Fragen</th>
                            <th>Genauigkeit</th>
                            <th>Status</th>
                            <th>Zeitpunkt</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_submissions as $submission): ?>
                            <tr style="<?php echo !$submission['accepted'] ? 'background: rgba(231, 76, 60, 0.05);' : ''; ?>">
                                <td><?php echo htmlspecialchars($submission['user_id']); ?></td>
                                <td><?php echo number_format($submission['score']); ?></td>
                                <td><?php echo number_format($submission['game_time'], 1); ?>s</td>
                                <td><?php echo $submission['questions_answered']; ?></td>
                                <td>
                                    <?php 
                                        $accuracy = $submission['questions_answered'] > 0 ? 
                                            round(($submission['correct_answers'] / $submission['questions_answered']) * 100) : 0;
                                        echo $accuracy . '%';
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $submission['accepted'] ? 'status-accepted' : 'status-rejected'; ?>">
                                        <?php echo $submission['accepted'] ? 'Akzeptiert' : 'Abgelehnt'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d.m.Y H:i', $submission['timestamp']); ?></td>
                                <td>
                                    <?php if (!$submission['accepted']): ?>
                                        <button class="btn btn-danger" onclick="alert('Details: ' + <?php echo json_encode($submission['validation_result']['error'] ?? 'Unbekannter Fehler'); ?>)">
                                            <i class="fas fa-info-circle"></i> Details
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="auto-refresh">
        <i class="fas fa-sync-alt"></i> Auto-Refresh in <span id="countdown">30</span>s
    </div>

    <script>
        // Auto-refresh countdown
        let countdown = 30;
        const countdownElement = document.getElementById('countdown');
        
        setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                location.reload();
            }
        }, 1000);
        
        // Refresh security stats every 10 seconds
        setInterval(async () => {
            try {
                const response = await fetch('api/security/stats.php');
                const data = await response.json();
                
                if (data.success) {
                    // Update stats without full page reload
                    document.querySelector('.stats-grid .stat-number').textContent = data.unresolved_flags;
                }
            } catch (e) {
                console.log('Failed to update security stats');
            }
        }, 10000);
    </script>
</body>
</html>
