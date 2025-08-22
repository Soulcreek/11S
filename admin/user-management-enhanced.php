<?php
// File: admin/user-management-enhanced.php
// Description: Enhanced user management with security features and Google Auth

session_start();

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/includes/AuthManager.php';
require_once __DIR__ . '/includes/GoogleAuth.php';

$data_dir = __DIR__ . '/data';
$users_file = $data_dir . '/users.json';
$config_file = $data_dir . '/auth-config.json';

// Ensure directories exist
if (!is_dir($data_dir)) {
    mkdir($data_dir, 0755, true);
}

// Load configuration
$config = [];
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true) ?? [];
}

$auth_manager = new AuthManager();
$success_message = '';
$error_message = '';

// Handle actions
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'delete_user':
            if (isset($_POST['user_id'])) {
                $result = $auth_manager->deleteUser($_POST['user_id']);
                if ($result['success']) {
                    $success_message = $result['message'];
                } else {
                    $error_message = $result['error'] ?? 'Fehler beim Löschen';
                }
            }
            break;
            
        case 'verify_user':
            if (isset($_POST['user_id'])) {
                $users = json_decode(file_get_contents($users_file), true) ?? [];
                foreach ($users as &$user) {
                    if ($user['user_id'] === $_POST['user_id']) {
                        $user['is_verified'] = true;
                        $user['updated_at'] = time();
                        break;
                    }
                }
                file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $success_message = 'Benutzer manuell verifiziert.';
            }
            break;
            
        case 'unlock_user':
            if (isset($_POST['user_id'])) {
                $users = json_decode(file_get_contents($users_file), true) ?? [];
                foreach ($users as &$user) {
                    if ($user['user_id'] === $_POST['user_id']) {
                        $user['locked_until'] = 0;
                        $user['login_attempts'] = 0;
                        $user['updated_at'] = time();
                        break;
                    }
                }
                file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $success_message = 'Benutzerkonto entsperrt.';
            }
            break;
            
        case 'reset_stats':
            if (isset($_POST['user_id'])) {
                $users = json_decode(file_get_contents($users_file), true) ?? [];
                foreach ($users as &$user) {
                    if ($user['user_id'] === $_POST['user_id']) {
                        $user['stats'] = [
                            'total_games' => 0,
                            'total_score' => 0,
                            'best_score' => 0,
                            'average_score' => 0,
                            'correct_answers' => 0,
                            'total_answers' => 0,
                            'total_time_played' => 0,
                            'achievements' => [],
                            'level' => 1,
                            'xp' => 0
                        ];
                        $user['updated_at'] = time();
                        break;
                    }
                }
                file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $success_message = 'Benutzerstatistiken zurückgesetzt.';
            }
            break;
    }
}

// Get all users with enhanced data
$users = $auth_manager->getAllUsers();

// Sort by creation date (newest first)
usort($users, function($a, $b) {
    return ($b['created_at'] ?? 0) - ($a['created_at'] ?? 0);
});

// Calculate statistics
$total_users = count($users);
$verified_users = count(array_filter($users, fn($u) => $u['is_verified'] ?? false));
$guest_users = count(array_filter($users, fn($u) => ($u['type'] ?? 'registered') === 'guest'));
$google_users = count(array_filter($users, fn($u) => isset($u['google_id'])));
$locked_users = count(array_filter($users, fn($u) => ($u['locked_until'] ?? 0) > time()));

// Security statistics
$recent_users = count(array_filter($users, fn($u) => ($u['created_at'] ?? 0) > (time() - 86400)));
$active_users = count(array_filter($users, fn($u) => ($u['security']['last_activity'] ?? 0) > (time() - 3600)));

// Pagination
$page = intval($_GET['page'] ?? 1);
$per_page = 20;
$total_pages = ceil($total_users / $per_page);
$offset = ($page - 1) * $per_page;
$users_page = array_slice($users, $offset, $per_page);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erweiterte Benutzerverwaltung - 11Seconds Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #2ECC71;
            --secondary-green: #27AE60;
            --accent-green: #1ABC9C;
            --dark-green: #1e8449;
            --light-green: rgba(46, 204, 113, 0.1);
            --success: #27AE60;
            --warning: #F39C12;
            --danger: #E74C3C;
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
            border-left: 4px solid var(--primary-green);
            text-align: center;
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 15px;
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
        
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-size: 12px;
            color: #666;
            font-weight: bold;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 8px 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        
        .users-table th,
        .users-table td {
            padding: 15px 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .users-table th {
            background: var(--light-green);
            font-weight: bold;
            color: var(--dark-green);
            position: sticky;
            top: 0;
        }
        
        .users-table tr:hover {
            background: rgba(46, 204, 113, 0.05);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: bold;
            color: var(--dark-green);
        }
        
        .user-email {
            font-size: 12px;
            color: #666;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .badge-verified {
            background: rgba(39, 174, 96, 0.2);
            color: var(--success);
        }
        
        .badge-unverified {
            background: rgba(243, 156, 18, 0.2);
            color: var(--warning);
        }
        
        .badge-guest {
            background: rgba(52, 152, 219, 0.2);
            color: var(--info);
        }
        
        .badge-google {
            background: rgba(231, 76, 60, 0.2);
            color: var(--danger);
        }
        
        .badge-locked {
            background: rgba(231, 76, 60, 0.2);
            color: var(--danger);
        }
        
        .actions {
            display: flex;
            gap: 8px;
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
            background: var(--success);
            color: white;
        }
        
        .btn-warning {
            background: var(--warning);
            color: white;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-info {
            background: var(--info);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: bold;
        }
        
        .success {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success);
            border: 1px solid var(--success);
        }
        
        .error {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
            border: 1px solid var(--danger);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }
        
        .pagination a,
        .pagination span {
            padding: 10px 15px;
            border: 2px solid var(--primary-green);
            border-radius: 8px;
            text-decoration: none;
            color: var(--primary-green);
            font-weight: bold;
        }
        
        .pagination a:hover,
        .pagination .current {
            background: var(--primary-green);
            color: white;
        }
        
        .stats-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            background: var(--light-green);
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .stat-detail {
            text-align: center;
        }
        
        .stat-detail-value {
            font-size: 18px;
            font-weight: bold;
            color: var(--dark-green);
        }
        
        .stat-detail-label {
            font-size: 12px;
            color: #666;
        }
        
        .security-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .security-warning {
            color: var(--danger);
        }
        
        .user-type-icon {
            margin-left: 5px;
        }
        
        @media (max-width: 768px) {
            .users-table {
                font-size: 12px;
            }
            
            .users-table th,
            .users-table td {
                padding: 8px 6px;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">👥</div>
                <div class="logo-text">Erweiterte Benutzerverwaltung</div>
            </div>
            <div class="nav-links">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="question-management.php"><i class="fas fa-question-circle"></i> Fragen</a>
                <a href="media-management.php"><i class="fas fa-palette"></i> Branding</a>
                <a href="settings.php"><i class="fas fa-cog"></i> Einstellungen</a>
                <a href="index.php?logout=1"><i class="fas fa-sign-out-alt"></i> Abmelden</a>
            </div>
        </div>
    </header>

    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-users-cog"></i>
            Erweiterte Benutzerverwaltung
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

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">Gesamt Benutzer</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-number"><?php echo $verified_users; ?></div>
                <div class="stat-label">Verifiziert</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
                <div class="stat-number"><?php echo $guest_users; ?></div>
                <div class="stat-label">Gäste</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fab fa-google"></i></div>
                <div class="stat-number"><?php echo $google_users; ?></div>
                <div class="stat-label">Google Login</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-lock"></i></div>
                <div class="stat-number"><?php echo $locked_users; ?></div>
                <div class="stat-label">Gesperrt</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-pulse"></i></div>
                <div class="stat-number"><?php echo $active_users; ?></div>
                <div class="stat-label">Aktiv (1h)</div>
            </div>
        </div>

        <!-- User Management -->
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-table"></i> Benutzer verwalten
            </h2>

            <div class="filters">
                <div class="filter-group">
                    <label>Status</label>
                    <select id="statusFilter" onchange="filterUsers()">
                        <option value="">Alle</option>
                        <option value="verified">Verifiziert</option>
                        <option value="unverified">Nicht verifiziert</option>
                        <option value="guest">Gäste</option>
                        <option value="google">Google</option>
                        <option value="locked">Gesperrt</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Suchen</label>
                    <input type="text" id="searchFilter" placeholder="Name oder E-Mail..." onkeyup="filterUsers()">
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="users-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>Benutzer</th>
                            <th>Status</th>
                            <th>Typ</th>
                            <th>Erstellt</th>
                            <th>Letzte Aktivität</th>
                            <th>Statistiken</th>
                            <th>Sicherheit</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users_page as $user): ?>
                            <tr data-user-type="<?php echo $user['type'] ?? 'registered'; ?>" 
                                data-verified="<?php echo ($user['is_verified'] ?? false) ? 'verified' : 'unverified'; ?>"
                                data-locked="<?php echo ($user['locked_until'] ?? 0) > time() ? 'locked' : 'unlocked'; ?>"
                                data-google="<?php echo isset($user['google_id']) ? 'google' : 'regular'; ?>">
                                
                                <td>
                                    <div class="user-info">
                                        <?php if (isset($user['google_picture']) && !empty($user['google_picture'])): ?>
                                            <img src="<?php echo htmlspecialchars($user['google_picture']); ?>" 
                                                 alt="Avatar" class="user-avatar">
                                        <?php else: ?>
                                            <div class="user-avatar" style="background: var(--primary-green); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="user-details">
                                            <div class="user-name">
                                                <?php echo htmlspecialchars($user['username']); ?>
                                                <?php if (isset($user['google_id'])): ?>
                                                    <i class="fab fa-google user-type-icon" title="Google Account"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="user-email">
                                                <?php echo htmlspecialchars($user['email'] ?? 'Keine E-Mail'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                
                                <td>
                                    <?php if ($user['is_verified'] ?? false): ?>
                                        <span class="badge badge-verified">
                                            <i class="fas fa-check"></i> Verifiziert
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-unverified">
                                            <i class="fas fa-clock"></i> Ausstehend
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php if (($user['type'] ?? 'registered') === 'guest'): ?>
                                        <span class="badge badge-guest">
                                            <i class="fas fa-user-clock"></i> Gast
                                        </span>
                                    <?php elseif (isset($user['google_id'])): ?>
                                        <span class="badge badge-google">
                                            <i class="fab fa-google"></i> Google
                                        </span>
                                    <?php else: ?>
                                        <span class="badge" style="background: var(--light-green); color: var(--dark-green);">
                                            <i class="fas fa-user"></i> Registriert
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php echo date('d.m.Y H:i', $user['created_at'] ?? 0); ?>
                                    <?php if (($user['created_at'] ?? 0) > (time() - 86400)): ?>
                                        <span class="badge" style="background: rgba(52, 152, 219, 0.2); color: var(--info); margin-left: 5px;">NEU</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php if (isset($user['security']['last_activity'])): ?>
                                        <?php 
                                            $last_activity = $user['security']['last_activity'];
                                            $diff = time() - $last_activity;
                                            if ($diff < 3600) {
                                                echo '<span style="color: var(--success);">Aktiv</span>';
                                            } elseif ($diff < 86400) {
                                                echo '<span style="color: var(--warning);">' . round($diff/3600) . 'h</span>';
                                            } else {
                                                echo '<span style="color: #666;">' . round($diff/86400) . 'd</span>';
                                            }
                                        ?>
                                    <?php else: ?>
                                        <span style="color: #999;">Nie</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <div class="stats-details">
                                        <div class="stat-detail">
                                            <div class="stat-detail-value"><?php echo $user['stats']['total_games'] ?? 0; ?></div>
                                            <div class="stat-detail-label">Spiele</div>
                                        </div>
                                        <div class="stat-detail">
                                            <div class="stat-detail-value"><?php echo $user['stats']['best_score'] ?? 0; ?></div>
                                            <div class="stat-detail-label">Bester Score</div>
                                        </div>
                                        <div class="stat-detail">
                                            <div class="stat-detail-value"><?php echo $user['stats']['level'] ?? 1; ?></div>
                                            <div class="stat-detail-label">Level</div>
                                        </div>
                                    </div>
                                </td>
                                
                                <td>
                                    <div class="security-info">
                                        <?php if (($user['locked_until'] ?? 0) > time()): ?>
                                            <span class="security-warning">
                                                <i class="fas fa-lock"></i> Gesperrt
                                            </span>
                                        <?php else: ?>
                                            <span style="color: var(--success);">
                                                <i class="fas fa-shield-alt"></i> OK
                                            </span>
                                        <?php endif; ?>
                                        <br>
                                        <small>Versuche: <?php echo $user['login_attempts'] ?? 0; ?></small>
                                    </div>
                                </td>
                                
                                <td>
                                    <div class="actions">
                                        <?php if (!($user['is_verified'] ?? false)): ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="action" value="verify_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                <button type="submit" class="btn btn-success" title="Manuell verifizieren">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if (($user['locked_until'] ?? 0) > time()): ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="action" value="unlock_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                <button type="submit" class="btn btn-warning" title="Entsperren">
                                                    <i class="fas fa-unlock"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="action" value="reset_stats">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <button type="submit" class="btn btn-info" 
                                                    onclick="return confirm('Statistiken zurücksetzen?')" title="Stats zurücksetzen">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        </form>
                                        
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <button type="submit" class="btn btn-danger" 
                                                    onclick="return confirm('Benutzer wirklich löschen?')" title="Löschen">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>">
                            <i class="fas fa-chevron-left"></i> Zurück
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>">
                            Weiter <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function filterUsers() {
            const statusFilter = document.getElementById('statusFilter').value;
            const searchFilter = document.getElementById('searchFilter').value.toLowerCase();
            const rows = document.querySelectorAll('#usersTable tbody tr');
            
            rows.forEach(row => {
                let showRow = true;
                
                // Status filter
                if (statusFilter) {
                    const userType = row.dataset.userType;
                    const verified = row.dataset.verified;
                    const locked = row.dataset.locked;
                    const google = row.dataset.google;
                    
                    switch (statusFilter) {
                        case 'verified':
                            showRow = verified === 'verified';
                            break;
                        case 'unverified':
                            showRow = verified === 'unverified';
                            break;
                        case 'guest':
                            showRow = userType === 'guest';
                            break;
                        case 'google':
                            showRow = google === 'google';
                            break;
                        case 'locked':
                            showRow = locked === 'locked';
                            break;
                    }
                }
                
                // Search filter
                if (showRow && searchFilter) {
                    const userText = row.textContent.toLowerCase();
                    showRow = userText.includes(searchFilter);
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        }
        
        // Auto-refresh page every 30 seconds for live updates
        setInterval(() => {
            if (!document.hidden) {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>
