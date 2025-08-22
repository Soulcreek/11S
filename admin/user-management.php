<?php
// File: admin/user-management.php
// Description: User management interface

session_start();

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

$data_dir = __DIR__ . '/data';
$users_file = $data_dir . '/users.json';

// Load users
$users = [];
if (file_exists($users_file)) {
    $users = json_decode(file_get_contents($users_file), true) ?? [];
}

// Handle user actions
if ($_POST) {
    if (isset($_POST['delete_user']) && isset($_POST['username'])) {
        $username_to_delete = $_POST['username'];
        $users = array_filter($users, function($user) use ($username_to_delete) {
            return $user['username'] !== $username_to_delete;
        });
        $users = array_values($users); // Reindex array
        file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
        $success_message = "Benutzer '$username_to_delete' wurde gelöscht.";
    }
    
    if (isset($_POST['add_user'])) {
        $new_user = [
            'username' => trim($_POST['new_username']),
            'email' => trim($_POST['new_email']),
            'password' => password_hash(trim($_POST['new_password']), PASSWORD_DEFAULT),
            'registrationDate' => date('Y-m-d H:i:s'),
            'isGuest' => false,
            'stats' => [
                'gamesPlayed' => 0,
                'totalScore' => 0,
                'bestScore' => 0,
                'averageScore' => 0
            ]
        ];
        
        // Check if username already exists
        $username_exists = false;
        foreach ($users as $user) {
            if ($user['username'] === $new_user['username']) {
                $username_exists = true;
                break;
            }
        }
        
        if (!$username_exists && !empty($new_user['username']) && !empty($new_user['email'])) {
            $users[] = $new_user;
            file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
            $success_message = "Benutzer '{$new_user['username']}' wurde erfolgreich hinzugefügt.";
        } else {
            $error_message = "Benutzername existiert bereits oder ungültige Daten.";
        }
    }
}

// Sort users by registration date
usort($users, function($a, $b) {
    return strtotime($b['registrationDate'] ?? '1970-01-01') - strtotime($a['registrationDate'] ?? '1970-01-01');
});
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benutzerverwaltung - 11Seconds Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: #333;
        }
        
        .header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            transition: background 0.3s ease;
        }
        
        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .page-title {
            font-size: 28px;
            margin-bottom: 30px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stats-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        
        .stat-item {
            flex: 1;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        
        .actions-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }
        
        .add-user-form {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .add-btn {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            height: 42px;
        }
        
        .add-btn:hover {
            transform: translateY(-2px);
        }
        
        .users-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .users-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th,
        .users-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .users-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        
        .users-table tr:hover {
            background: #f8f9fa;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
        }
        
        .username {
            font-weight: bold;
            color: #333;
            margin-bottom: 2px;
        }
        
        .user-email {
            color: #666;
            font-size: 14px;
        }
        
        .user-stats {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .stat-item-small {
            font-size: 12px;
            color: #666;
        }
        
        .stat-value {
            font-weight: bold;
            color: #333;
        }
        
        .delete-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .delete-btn:hover {
            background: #c0392b;
        }
        
        .guest-badge {
            background: #f39c12;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: bold;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .no-users {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .add-user-form {
                grid-template-columns: 1fr;
            }
            
            .users-table {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">👥</div>
                <div class="logo-text">Benutzerverwaltung</div>
            </div>
            <div class="nav-links">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="question-management.php"><i class="fas fa-question-circle"></i> Fragen</a>
                <a href="index.php?logout=1"><i class="fas fa-sign-out-alt"></i> Abmelden</a>
            </div>
        </div>
    </header>

    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-users"></i>
            Benutzerverwaltung
        </h1>

        <?php if (isset($success_message)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-number"><?php echo count($users); ?></div>
                <div class="stat-label">Gesamt Benutzer</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count(array_filter($users, fn($u) => !($u['isGuest'] ?? false))); ?></div>
                <div class="stat-label">Registrierte</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count(array_filter($users, fn($u) => $u['isGuest'] ?? false)); ?></div>
                <div class="stat-label">Gäste</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo array_sum(array_column($users, 'stats')) ? array_sum(array_column(array_column($users, 'stats'), 'gamesPlayed')) : 0; ?></div>
                <div class="stat-label">Spiele gespielt</div>
            </div>
        </div>

        <div class="actions-section">
            <h2 class="section-title">Neuen Benutzer hinzufügen</h2>
            <form method="post" class="add-user-form">
                <div class="form-group">
                    <label for="new_username">Benutzername:</label>
                    <input type="text" id="new_username" name="new_username" required>
                </div>
                <div class="form-group">
                    <label for="new_email">E-Mail:</label>
                    <input type="email" id="new_email" name="new_email" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Passwort:</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <button type="submit" name="add_user" class="add-btn">
                    <i class="fas fa-plus"></i> Hinzufügen
                </button>
            </form>
        </div>

        <div class="users-section">
            <div class="users-header">
                <h2 class="section-title">Alle Benutzer (<?php echo count($users); ?>)</h2>
            </div>

            <?php if (empty($users)): ?>
                <div class="no-users">
                    <i class="fas fa-users" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                    <p>Noch keine Benutzer registriert.</p>
                </div>
            <?php else: ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Benutzer</th>
                            <th>Registriert am</th>
                            <th>Spielstatistiken</th>
                            <th>Typ</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                                        </div>
                                        <div class="user-details">
                                            <div class="username"><?php echo htmlspecialchars($user['username']); ?></div>
                                            <div class="user-email"><?php echo htmlspecialchars($user['email'] ?? 'Keine E-Mail'); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php echo date('d.m.Y H:i', strtotime($user['registrationDate'] ?? '1970-01-01')); ?>
                                </td>
                                <td>
                                    <div class="user-stats">
                                        <div class="stat-item-small">
                                            Spiele: <span class="stat-value"><?php echo $user['stats']['gamesPlayed'] ?? 0; ?></span>
                                        </div>
                                        <div class="stat-item-small">
                                            Punkte: <span class="stat-value"><?php echo $user['stats']['totalScore'] ?? 0; ?></span>
                                        </div>
                                        <div class="stat-item-small">
                                            Beste: <span class="stat-value"><?php echo $user['stats']['bestScore'] ?? 0; ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($user['isGuest'] ?? false): ?>
                                        <span class="guest-badge">Gast</span>
                                    <?php else: ?>
                                        Registriert
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" style="display: inline;" 
                                          onsubmit="return confirm('Sind Sie sicher, dass Sie diesen Benutzer löschen möchten?');">
                                        <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                                        <button type="submit" name="delete_user" class="delete-btn">
                                            <i class="fas fa-trash"></i> Löschen
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
