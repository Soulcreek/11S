<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>11 Seconds Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1rem 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
        }
        
        .logo-icon {
            font-size: 1.8rem;
            margin-right: 0.5rem;
        }
        
        .logo-text {
            font-size: 1.4rem;
            font-weight: 600;
        }
        
        .nav-links {
            display: flex;
            gap: 1rem;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .nav-links a:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .nav-links a.active {
            background: rgba(255,255,255,0.2);
        }
        
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem 2rem;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">🎯</div>
                <div class="logo-text">11 Seconds Admin</div>
            </div>
            <div class="nav-links">
                <a href="dashboard.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'class="active"' : ''; ?>>
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="question-management.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'question-management.php') ? 'class="active"' : ''; ?>>
                    <i class="fas fa-question-circle"></i> Fragen
                </a>
                <a href="user-management.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'user-management.php') ? 'class="active"' : ''; ?>>
                    <i class="fas fa-users"></i> Benutzer
                </a>
                <a href="statistics.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'statistics.php') ? 'class="active"' : ''; ?>>
                    <i class="fas fa-chart-bar"></i> Statistiken
                </a>
                <a href="settings.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'class="active"' : ''; ?>>
                    <i class="fas fa-cog"></i> Einstellungen
                </a>
                <a href="index.php?logout=1" style="color: #ffcccb;">
                    <i class="fas fa-sign-out-alt"></i> Abmelden
                </a>
            </div>
        </div>
    </header>
    
    <main class="main-content">
