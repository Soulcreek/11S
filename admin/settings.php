<?php
// File: admin/settings.php
// Description: System settings and configuration

session_start();

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

$data_dir = __DIR__ . '/data';
$config_file = $data_dir . '/config.json';

// Load configuration
$config = [
    'gemini_api_key' => '',
    'system_settings' => [
        'site_name' => '11Seconds Quiz Game',
        'max_questions_per_game' => 20,
        'default_time_per_question' => 11,
        'enable_guest_mode' => true,
        'enable_multiplayer' => true,
        'max_players_multiplayer' => 4
    ],
    'security_settings' => [
        'password_min_length' => 4,
        'max_login_attempts' => 5,
        'session_timeout' => 3600
    ]
];

if (file_exists($config_file)) {
    $loaded_config = json_decode(file_get_contents($config_file), true) ?? [];
    $config = array_merge($config, $loaded_config);
}

// Handle form submission
if ($_POST) {
    if (isset($_POST['save_settings'])) {
        // Update system settings
        $config['system_settings']['site_name'] = trim($_POST['site_name']);
        $config['system_settings']['max_questions_per_game'] = max(1, min(50, (int)$_POST['max_questions_per_game']));
        $config['system_settings']['default_time_per_question'] = max(5, min(30, (int)$_POST['default_time_per_question']));
        $config['system_settings']['enable_guest_mode'] = isset($_POST['enable_guest_mode']);
        $config['system_settings']['enable_multiplayer'] = isset($_POST['enable_multiplayer']);
        $config['system_settings']['max_players_multiplayer'] = max(2, min(8, (int)$_POST['max_players_multiplayer']));
        
        // Update security settings
        $config['security_settings']['password_min_length'] = max(3, min(20, (int)$_POST['password_min_length']));
        $config['security_settings']['max_login_attempts'] = max(3, min(10, (int)$_POST['max_login_attempts']));
        $config['security_settings']['session_timeout'] = max(300, min(86400, (int)$_POST['session_timeout']));
        
        // Save configuration
        if (!is_dir($data_dir)) {
            mkdir($data_dir, 0755, true);
        }
        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
        $success_message = 'Einstellungen wurden erfolgreich gespeichert.';
    }
    
    if (isset($_POST['save_api_settings'])) {
        $config['gemini_api_key'] = trim($_POST['gemini_api_key']);
        
        if (!is_dir($data_dir)) {
            mkdir($data_dir, 0755, true);
        }
        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
        $api_success = 'API-Einstellungen wurden gespeichert.';
    }
}

// Load system statistics
$users_file = $data_dir . '/users.json';
$questions_file = $data_dir . '/questions.json';

$stats = [
    'users_count' => 0,
    'questions_count' => 0,
    'disk_usage' => 0
];

if (file_exists($users_file)) {
    $users = json_decode(file_get_contents($users_file), true) ?? [];
    $stats['users_count'] = count($users);
    $stats['disk_usage'] += filesize($users_file);
}

if (file_exists($questions_file)) {
    $questions_data = json_decode(file_get_contents($questions_file), true) ?? [];
    $stats['questions_count'] = count($questions_data['questions'] ?? []);
    $stats['disk_usage'] += filesize($questions_file);
}

function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System-Einstellungen - 11Seconds Admin</title>
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
            max-width: 1200px;
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
            max-width: 1200px;
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
        
        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .section.full-width {
            grid-column: 1 / -1;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        
        .btn {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: transform 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: linear-gradient(45deg, #667eea, #764ba2);
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
        
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-icon {
            font-size: 36px;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            line-height: 1.4;
        }
        
        .api-status {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            padding: 10px;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .api-configured {
            background: #d4edda;
            color: #155724;
        }
        
        .api-not-configured {
            background: #fff3cd;
            color: #856404;
        }
        
        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">⚙️</div>
                <div class="logo-text">System-Einstellungen</div>
            </div>
            <?php include __DIR__ . '/includes/nav.php'; ?>
        </div>
    </header>

    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-cog"></i>
            System-Einstellungen
        </h1>

        <?php if (isset($success_message)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($api_success)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo $api_success; ?>
            </div>
        <?php endif; ?>

        <!-- System Overview -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo $stats['users_count']; ?></div>
                <div class="stat-label">Benutzer</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div class="stat-number"><?php echo $stats['questions_count']; ?></div>
                <div class="stat-label">Fragen</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-hdd"></i>
                </div>
                <div class="stat-number"><?php echo formatBytes($stats['disk_usage']); ?></div>
                <div class="stat-label">Speichernutzung</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-server"></i>
                </div>
                <div class="stat-number"><?php echo phpversion(); ?></div>
                <div class="stat-label">PHP Version</div>
            </div>
        </div>

        <div class="settings-grid">
            <!-- System Settings -->
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-sliders-h"></i> System-Einstellungen
                </h2>
                <form method="post">
                    <div class="form-group">
                        <label for="site_name">Website-Name:</label>
                        <input type="text" id="site_name" name="site_name" 
                               value="<?php echo htmlspecialchars($config['system_settings']['site_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="max_questions_per_game">Max. Fragen pro Spiel:</label>
                        <input type="number" id="max_questions_per_game" name="max_questions_per_game" 
                               min="1" max="50" value="<?php echo $config['system_settings']['max_questions_per_game']; ?>" required>
                        <div class="help-text">Maximale Anzahl der Fragen in einem Spiel (1-50)</div>
                    </div>

                    <div class="form-group">
                        <label for="default_time_per_question">Standard Zeit pro Frage (Sekunden):</label>
                        <input type="number" id="default_time_per_question" name="default_time_per_question" 
                               min="5" max="30" value="<?php echo $config['system_settings']['default_time_per_question']; ?>" required>
                        <div class="help-text">Standard-Zeitlimit für jede Frage (5-30 Sekunden)</div>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="enable_guest_mode" name="enable_guest_mode" 
                               <?php echo $config['system_settings']['enable_guest_mode'] ? 'checked' : ''; ?>>
                        <label for="enable_guest_mode">Gastmodus aktivieren</label>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="enable_multiplayer" name="enable_multiplayer" 
                               <?php echo $config['system_settings']['enable_multiplayer'] ? 'checked' : ''; ?>>
                        <label for="enable_multiplayer">Mehrspieler-Modus aktivieren</label>
                    </div>

                    <div class="form-group">
                        <label for="max_players_multiplayer">Max. Spieler im Mehrspieler-Modus:</label>
                        <input type="number" id="max_players_multiplayer" name="max_players_multiplayer" 
                               min="2" max="8" value="<?php echo $config['system_settings']['max_players_multiplayer']; ?>" required>
                        <div class="help-text">Maximale Anzahl der Spieler im Mehrspieler-Modus (2-8)</div>
                    </div>

                    <button type="submit" name="save_settings" class="btn">
                        <i class="fas fa-save"></i> Einstellungen speichern
                    </button>
                </form>
            </div>

            <!-- Security Settings -->
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-shield-alt"></i> Sicherheits-Einstellungen
                </h2>
                <form method="post">
                    <div class="form-group">
                        <label for="password_min_length">Minimale Passwort-Länge:</label>
                        <input type="number" id="password_min_length" name="password_min_length" 
                               min="3" max="20" value="<?php echo $config['security_settings']['password_min_length']; ?>" required>
                        <div class="help-text">Mindestanzahl der Zeichen für Benutzerpasswörter (3-20)</div>
                    </div>

                    <div class="form-group">
                        <label for="max_login_attempts">Max. Anmeldeversuche:</label>
                        <input type="number" id="max_login_attempts" name="max_login_attempts" 
                               min="3" max="10" value="<?php echo $config['security_settings']['max_login_attempts']; ?>" required>
                        <div class="help-text">Maximale Anmeldeversuche vor Sperrung (3-10)</div>
                    </div>

                    <div class="form-group">
                        <label for="session_timeout">Session-Timeout (Sekunden):</label>
                        <select id="session_timeout" name="session_timeout" required>
                            <option value="1800" <?php echo $config['security_settings']['session_timeout'] == 1800 ? 'selected' : ''; ?>>30 Minuten</option>
                            <option value="3600" <?php echo $config['security_settings']['session_timeout'] == 3600 ? 'selected' : ''; ?>>1 Stunde</option>
                            <option value="7200" <?php echo $config['security_settings']['session_timeout'] == 7200 ? 'selected' : ''; ?>>2 Stunden</option>
                            <option value="14400" <?php echo $config['security_settings']['session_timeout'] == 14400 ? 'selected' : ''; ?>>4 Stunden</option>
                            <option value="28800" <?php echo $config['security_settings']['session_timeout'] == 28800 ? 'selected' : ''; ?>>8 Stunden</option>
                        </select>
                        <div class="help-text">Zeitspanne bis zur automatischen Abmeldung</div>
                    </div>

                    <button type="submit" name="save_settings" class="btn">
                        <i class="fas fa-save"></i> Einstellungen speichern
                    </button>
                </form>
            </div>
        </div>

        <!-- API Settings -->
        <div class="section full-width">
            <h2 class="section-title">
                <i class="fas fa-key"></i> API-Konfiguration
            </h2>
            <form method="post">
                <div class="form-group">
                    <label for="gemini_api_key">Google Gemini API-Schlüssel:</label>
                    <input type="password" id="gemini_api_key" name="gemini_api_key" 
                           value="<?php echo htmlspecialchars($config['gemini_api_key']); ?>" 
                           placeholder="Geben Sie hier Ihren Google Gemini API-Schlüssel ein">
                    <div class="help-text">
                        Benötigt für die KI-basierte Fragengenerierung. 
                        Erhalten Sie einen kostenlosen API-Schlüssel bei: 
                        <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a>
                    </div>
                    
                    <div class="api-status <?php echo empty($config['gemini_api_key']) ? 'api-not-configured' : 'api-configured'; ?>">
                        <i class="fas <?php echo empty($config['gemini_api_key']) ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?>"></i>
                        <?php if (empty($config['gemini_api_key'])): ?>
                            API-Schlüssel nicht konfiguriert - KI-Funktionen sind deaktiviert
                        <?php else: ?>
                            API-Schlüssel konfiguriert - KI-Funktionen verfügbar
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" name="save_api_settings" class="btn btn-secondary">
                    <i class="fas fa-key"></i> API-Einstellungen speichern
                </button>
            </form>
        </div>
    </div>
</body>
</html>
