<?php
// File: admin/backup.php
// Description: Data backup and export functionality

session_start();

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

$data_dir = __DIR__ . '/data';

// Handle export requests
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    switch ($export_type) {
        case 'users':
            exportUsers();
            break;
        case 'questions':
            exportQuestions();
            break;
        case 'all':
            exportAll();
            break;
    }
}

function exportUsers() {
    global $data_dir;
    
    $users_file = $data_dir . '/users.json';
    if (!file_exists($users_file)) {
        die('Keine Benutzerdaten gefunden.');
    }
    
    $users = json_decode(file_get_contents($users_file), true) ?? [];
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="11seconds-users-' . date('Y-m-d-H-i-s') . '.json"');
    header('Content-Length: ' . strlen(json_encode($users, JSON_PRETTY_PRINT)));
    
    echo json_encode($users, JSON_PRETTY_PRINT);
    exit;
}

function exportQuestions() {
    global $data_dir;
    
    $questions_file = $data_dir . '/questions.json';
    if (!file_exists($questions_file)) {
        die('Keine Fragendaten gefunden.');
    }
    
    $questions_data = json_decode(file_get_contents($questions_file), true) ?? [];
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="11seconds-questions-' . date('Y-m-d-H-i-s') . '.json"');
    header('Content-Length: ' . strlen(json_encode($questions_data, JSON_PRETTY_PRINT)));
    
    echo json_encode($questions_data, JSON_PRETTY_PRINT);
    exit;
}

function exportAll() {
    global $data_dir;
    
    $backup_data = [
        'export_date' => date('Y-m-d H:i:s'),
        'version' => '1.0',
        'users' => [],
        'questions' => []
    ];
    
    // Load users
    $users_file = $data_dir . '/users.json';
    if (file_exists($users_file)) {
        $backup_data['users'] = json_decode(file_get_contents($users_file), true) ?? [];
    }
    
    // Load questions
    $questions_file = $data_dir . '/questions.json';
    if (file_exists($questions_file)) {
        $backup_data['questions'] = json_decode(file_get_contents($questions_file), true) ?? [];
    }
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="11seconds-complete-backup-' . date('Y-m-d-H-i-s') . '.json"');
    header('Content-Length: ' . strlen(json_encode($backup_data, JSON_PRETTY_PRINT)));
    
    echo json_encode($backup_data, JSON_PRETTY_PRINT);
    exit;
}

// Load statistics
$users_file = $data_dir . '/users.json';
$questions_file = $data_dir . '/questions.json';

$users = [];
$questions_data = ['questions' => [], 'categories' => []];

if (file_exists($users_file)) {
    $users = json_decode(file_get_contents($users_file), true) ?? [];
}

if (file_exists($questions_file)) {
    $questions_data = json_decode(file_get_contents($questions_file), true) ?? $questions_data;
}

$stats = [
    'users_total' => count($users),
    'questions_total' => count($questions_data['questions']),
    'data_size' => formatBytes(filesize($users_file) + filesize($questions_file))
];

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
    <title>Daten-Backup - 11Seconds Admin</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-icon {
            font-size: 36px;
            color: #667eea;
            margin-bottom: 15px;
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
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .export-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .export-card {
            border: 2px solid #eee;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .export-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
        }
        
        .export-icon {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .export-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        
        .export-description {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .export-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s ease;
        }
        
        .export-btn:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .info-box {
            background: #e8f4fd;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .info-box h3 {
            color: #0c5460;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-box p {
            color: #0c5460;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        
        .info-box ul {
            color: #0c5460;
            margin-left: 20px;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .warning-box h3 {
            color: #856404;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .warning-box p {
            color: #856404;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">💾</div>
                <div class="logo-text">Daten-Backup</div>
            </div>
            <div class="nav-links">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="question-management.php"><i class="fas fa-question-circle"></i> Fragen</a>
                <a href="user-management.php"><i class="fas fa-users"></i> Benutzer</a>
                <a href="index.php?logout=1"><i class="fas fa-sign-out-alt"></i> Abmelden</a>
            </div>
        </div>
    </header>

    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-download"></i>
            Daten-Backup & Export
        </h1>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo $stats['users_total']; ?></div>
                <div class="stat-label">Benutzer</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div class="stat-number"><?php echo $stats['questions_total']; ?></div>
                <div class="stat-label">Fragen</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-hdd"></i>
                </div>
                <div class="stat-number"><?php echo $stats['data_size']; ?></div>
                <div class="stat-label">Datengröße</div>
            </div>
        </div>

        <div class="info-box">
            <h3>
                <i class="fas fa-info-circle"></i>
                Über Daten-Backups
            </h3>
            <p>
                Erstellen Sie regelmäßig Backups Ihrer 11Seconds Quiz-Daten, um Datenverlust zu vermeiden. 
                Die Backups enthalten alle Benutzerinformationen, Fragen und Einstellungen.
            </p>
            <p><strong>Backup-Inhalte:</strong></p>
            <ul>
                <li>Alle registrierten Benutzer und ihre Spielstatistiken</li>
                <li>Komplette Fragendatenbank mit allen Kategorien</li>
                <li>System-Konfigurationen und Einstellungen</li>
                <li>Metadaten wie Erstellungsdatum und Version</li>
            </ul>
        </div>

        <div class="warning-box">
            <h3>
                <i class="fas fa-exclamation-triangle"></i>
                Wichtige Hinweise
            </h3>
            <p>
                <strong>Datenschutz:</strong> Backup-Dateien enthalten persönliche Benutzerdaten. 
                Bewahren Sie diese sicher auf und geben Sie sie nicht an Dritte weiter.
            </p>
            <p>
                <strong>Passwörter:</strong> Benutzerpasswörter sind gehasht und können nicht 
                im Klartext eingesehen werden.
            </p>
        </div>

        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-download"></i>
                Export-Optionen
            </h2>
            
            <div class="export-grid">
                <div class="export-card">
                    <div class="export-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="export-title">Benutzer exportieren</h3>
                    <p class="export-description">
                        Exportiert alle registrierten Benutzer inklusive ihrer Spielstatistiken 
                        und Profilinformationen als JSON-Datei.
                    </p>
                    <a href="?export=users" class="export-btn">
                        <i class="fas fa-download"></i> Benutzer herunterladen
                    </a>
                </div>

                <div class="export-card">
                    <div class="export-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <h3 class="export-title">Fragen exportieren</h3>
                    <p class="export-description">
                        Exportiert die komplette Fragendatenbank mit allen Kategorien, 
                        Schwierigkeitsgraden und Antworten als JSON-Datei.
                    </p>
                    <a href="?export=questions" class="export-btn">
                        <i class="fas fa-download"></i> Fragen herunterladen
                    </a>
                </div>

                <div class="export-card">
                    <div class="export-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3 class="export-title">Vollständiges Backup</h3>
                    <p class="export-description">
                        Erstellt ein komplettes Backup aller Daten inklusive Benutzer, 
                        Fragen und System-Konfigurationen.
                    </p>
                    <a href="?export=all" class="export-btn">
                        <i class="fas fa-download"></i> Alles herunterladen
                    </a>
                </div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-history"></i>
                Backup-Zeitplan (Empfohlen)
            </h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <h4 style="color: #667eea; margin-bottom: 10px;">
                        <i class="fas fa-calendar-day"></i> Täglich
                    </h4>
                    <p style="color: #666; font-size: 14px;">
                        Automatische lokale Backups für kritische Anwendungen
                    </p>
                </div>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <h4 style="color: #667eea; margin-bottom: 10px;">
                        <i class="fas fa-calendar-week"></i> Wöchentlich
                    </h4>
                    <p style="color: #666; font-size: 14px;">
                        Vollständige Backups zur externen Speicherung
                    </p>
                </div>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <h4 style="color: #667eea; margin-bottom: 10px;">
                        <i class="fas fa-calendar-alt"></i> Monatlich
                    </h4>
                    <p style="color: #666; font-size: 14px;">
                        Archiv-Backups für langfristige Aufbewahrung
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
