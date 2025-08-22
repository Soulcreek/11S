<?php
// File: admin/media-management.php
// Description: Media upload and branding management

session_start();

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

$data_dir = __DIR__ . '/data';
$uploads_dir = __DIR__ . '/uploads';
$config_file = $data_dir . '/config.json';

// Ensure directories exist
if (!is_dir($data_dir)) {
    mkdir($data_dir, 0755, true);
}
if (!is_dir($uploads_dir)) {
    mkdir($uploads_dir, 0755, true);
}

// Load configuration
$config = [];
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true) ?? [];
}

// Default branding settings
if (!isset($config['branding'])) {
    $config['branding'] = [
        'primary_color' => '#2ECC71',
        'secondary_color' => '#27AE60',
        'accent_color' => '#1ABC9C',
        'logo_url' => '',
        'favicon_url' => '',
        'site_name' => '11Seconds Quiz Game',
        'admin_site_name' => '11Seconds Admin Center'
    ];
}

$success_message = '';
$error_message = '';

// Handle file uploads
if (isset($_POST['upload_media'])) {
    $upload_type = $_POST['upload_type'] ?? '';
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
    $max_file_size = 5 * 1024 * 1024; // 5MB
    
    if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['media_file'];
        $file_type = $file['type'];
        $file_size = $file['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            $error_message = 'Nur Bilddateien sind erlaubt (JPEG, PNG, GIF, SVG, WebP).';
        } elseif ($file_size > $max_file_size) {
            $error_message = 'Datei ist zu groß (max. 5MB).';
        } else {
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = $upload_type . '_' . uniqid() . '.' . $file_extension;
            $filepath = $uploads_dir . '/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Update config with new file path
                $relative_path = 'uploads/' . $filename;
                
                switch ($upload_type) {
                    case 'logo':
                        // Remove old logo if exists
                        if (!empty($config['branding']['logo_url'])) {
                            $old_file = __DIR__ . '/' . $config['branding']['logo_url'];
                            if (file_exists($old_file)) {
                                unlink($old_file);
                            }
                        }
                        $config['branding']['logo_url'] = $relative_path;
                        break;
                    case 'favicon':
                        // Remove old favicon if exists
                        if (!empty($config['branding']['favicon_url'])) {
                            $old_file = __DIR__ . '/' . $config['branding']['favicon_url'];
                            if (file_exists($old_file)) {
                                unlink($old_file);
                            }
                        }
                        $config['branding']['favicon_url'] = $relative_path;
                        break;
                }
                
                file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
                $success_message = ucfirst($upload_type) . ' wurde erfolgreich hochgeladen.';
            } else {
                $error_message = 'Fehler beim Hochladen der Datei.';
            }
        }
    } else {
        $error_message = 'Keine Datei ausgewählt oder Upload-Fehler.';
    }
}

// Handle branding settings update
if (isset($_POST['update_branding'])) {
    $config['branding']['primary_color'] = $_POST['primary_color'];
    $config['branding']['secondary_color'] = $_POST['secondary_color'];
    $config['branding']['accent_color'] = $_POST['accent_color'];
    $config['branding']['site_name'] = trim($_POST['site_name']);
    $config['branding']['admin_site_name'] = trim($_POST['admin_site_name']);
    
    file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
    $success_message = 'Branding-Einstellungen wurden gespeichert.';
}

// Handle file deletion
if (isset($_POST['delete_file'])) {
    $file_type = $_POST['file_type'];
    
    if ($file_type === 'logo' && !empty($config['branding']['logo_url'])) {
        $file_path = __DIR__ . '/' . $config['branding']['logo_url'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $config['branding']['logo_url'] = '';
        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
        $success_message = 'Logo wurde gelöscht.';
    } elseif ($file_type === 'favicon' && !empty($config['branding']['favicon_url'])) {
        $file_path = __DIR__ . '/' . $config['branding']['favicon_url'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $config['branding']['favicon_url'] = '';
        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
        $success_message = 'Favicon wurde gelöscht.';
    }
}

// Get uploaded files list
$uploaded_files = [];
if (is_dir($uploads_dir)) {
    $files = scandir($uploads_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && is_file($uploads_dir . '/' . $file)) {
            $uploaded_files[] = [
                'name' => $file,
                'url' => 'uploads/' . $file,
                'size' => filesize($uploads_dir . '/' . $file),
                'type' => mime_content_type($uploads_dir . '/' . $file),
                'modified' => filemtime($uploads_dir . '/' . $file)
            ];
        }
    }
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
    <title>Medien & Branding - 11Seconds Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: <?php echo $config['branding']['primary_color']; ?>;
            --secondary-green: <?php echo $config['branding']['secondary_color']; ?>;
            --accent-green: <?php echo $config['branding']['accent_color']; ?>;
            --light-green: rgba(46, 204, 113, 0.1);
            --dark-green: #1e8449;
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
        
        .upload-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .upload-card {
            background: var(--light-green);
            padding: 25px;
            border-radius: 12px;
            border: 2px dashed var(--primary-green);
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .upload-card:hover {
            border-color: var(--secondary-green);
            transform: translateY(-2px);
        }
        
        .upload-icon {
            font-size: 48px;
            color: var(--primary-green);
            margin-bottom: 15px;
        }
        
        .upload-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: var(--dark-green);
        }
        
        .upload-description {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-input-label {
            background: var(--primary-green);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .file-input-label:hover {
            background: var(--secondary-green);
            transform: translateY(-2px);
        }
        
        .current-file {
            margin-top: 15px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        
        .current-file img {
            max-width: 150px;
            max-height: 100px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .current-file-info {
            font-size: 14px;
            color: #666;
        }
        
        .btn {
            background: linear-gradient(45deg, var(--primary-green), var(--secondary-green));
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
        }
        
        .btn-danger:hover {
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(45deg, var(--accent-green), var(--secondary-green));
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--dark-green);
        }
        
        .form-group input,
        .form-group select {
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-green);
            box-shadow: 0 0 10px rgba(46, 204, 113, 0.2);
        }
        
        .color-input {
            height: 50px !important;
            cursor: pointer;
        }
        
        .color-preview {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-left: 10px;
            vertical-align: middle;
            border: 2px solid #ddd;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: bold;
        }
        
        .success {
            background: rgba(46, 204, 113, 0.1);
            color: var(--dark-green);
            border: 1px solid var(--primary-green);
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .files-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .file-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .file-card:hover {
            transform: translateY(-5px);
        }
        
        .file-preview {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
            background: #f8f9fa;
        }
        
        .file-info {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .file-name {
            font-weight: bold;
            color: var(--dark-green);
            margin-bottom: 5px;
            word-break: break-all;
        }
        
        .color-palette {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .palette-item {
            text-align: center;
            cursor: pointer;
        }
        
        .palette-color {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-bottom: 5px;
            border: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .palette-color:hover {
            transform: scale(1.1);
            border-color: #ddd;
        }
        
        .palette-label {
            font-size: 12px;
            color: #666;
        }
        
        .brand-preview {
            background: linear-gradient(45deg, var(--primary-green), var(--secondary-green));
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-top: 20px;
            text-align: center;
        }
        
        .brand-preview h3 {
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .brand-preview p {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <?php if (!empty($config['branding']['logo_url'])): ?>
                    <img src="<?php echo htmlspecialchars($config['branding']['logo_url']); ?>" 
                         alt="Logo" style="height: 32px; width: auto;">
                <?php else: ?>
                    <div class="logo-icon">🎨</div>
                <?php endif; ?>
                <div class="logo-text">Medien & Branding</div>
            </div>
            <div class="nav-links">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="question-management.php"><i class="fas fa-question-circle"></i> Fragen</a>
                <a href="user-management.php"><i class="fas fa-users"></i> Benutzer</a>
                <a href="settings.php"><i class="fas fa-cog"></i> Einstellungen</a>
                <a href="index.php?logout=1"><i class="fas fa-sign-out-alt"></i> Abmelden</a>
            </div>
        </div>
    </header>

    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-palette"></i>
            Medien & Branding verwalten
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

        <!-- Media Upload Section -->
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-upload"></i> Medien hochladen
            </h2>
            
            <div class="upload-grid">
                <!-- Logo Upload -->
                <div class="upload-card">
                    <div class="upload-icon">
                        <i class="fas fa-image"></i>
                    </div>
                    <h3 class="upload-title">Logo hochladen</h3>
                    <p class="upload-description">
                        Laden Sie Ihr Firmenlogo hoch. Empfohlene Größe: 200x60px
                    </p>
                    
                    <form method="post" enctype="multipart/form-data" style="margin-bottom: 15px;">
                        <div class="file-input-wrapper">
                            <input type="file" name="media_file" class="file-input" accept="image/*" required>
                            <label class="file-input-label">
                                <i class="fas fa-upload"></i> Datei auswählen
                            </label>
                        </div>
                        <input type="hidden" name="upload_type" value="logo">
                        <br>
                        <button type="submit" name="upload_media" class="btn">
                            <i class="fas fa-save"></i> Logo hochladen
                        </button>
                    </form>

                    <?php if (!empty($config['branding']['logo_url'])): ?>
                        <div class="current-file">
                            <img src="<?php echo htmlspecialchars($config['branding']['logo_url']); ?>" alt="Current Logo">
                            <div class="current-file-info">
                                <strong>Aktuelles Logo</strong><br>
                                <?php echo basename($config['branding']['logo_url']); ?>
                            </div>
                            <form method="post" style="margin-top: 10px;">
                                <input type="hidden" name="file_type" value="logo">
                                <button type="submit" name="delete_file" class="btn btn-danger" 
                                        onclick="return confirm('Logo wirklich löschen?')">
                                    <i class="fas fa-trash"></i> Löschen
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Favicon Upload -->
                <div class="upload-card">
                    <div class="upload-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3 class="upload-title">Favicon hochladen</h3>
                    <p class="upload-description">
                        Laden Sie ein Favicon hoch. Empfohlene Größe: 32x32px oder 16x16px
                    </p>
                    
                    <form method="post" enctype="multipart/form-data" style="margin-bottom: 15px;">
                        <div class="file-input-wrapper">
                            <input type="file" name="media_file" class="file-input" accept="image/*" required>
                            <label class="file-input-label">
                                <i class="fas fa-upload"></i> Datei auswählen
                            </label>
                        </div>
                        <input type="hidden" name="upload_type" value="favicon">
                        <br>
                        <button type="submit" name="upload_media" class="btn">
                            <i class="fas fa-save"></i> Favicon hochladen
                        </button>
                    </form>

                    <?php if (!empty($config['branding']['favicon_url'])): ?>
                        <div class="current-file">
                            <img src="<?php echo htmlspecialchars($config['branding']['favicon_url']); ?>" alt="Current Favicon">
                            <div class="current-file-info">
                                <strong>Aktuelles Favicon</strong><br>
                                <?php echo basename($config['branding']['favicon_url']); ?>
                            </div>
                            <form method="post" style="margin-top: 10px;">
                                <input type="hidden" name="file_type" value="favicon">
                                <button type="submit" name="delete_file" class="btn btn-danger" 
                                        onclick="return confirm('Favicon wirklich löschen?')">
                                    <i class="fas fa-trash"></i> Löschen
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Branding Settings -->
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-palette"></i> Branding-Einstellungen
            </h2>
            
            <form method="post">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="site_name">Website-Name:</label>
                        <input type="text" id="site_name" name="site_name" 
                               value="<?php echo htmlspecialchars($config['branding']['site_name']); ?>" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="admin_site_name">Admin-Center Name:</label>
                        <input type="text" id="admin_site_name" name="admin_site_name" 
                               value="<?php echo htmlspecialchars($config['branding']['admin_site_name']); ?>" required>
                    </div>
                </div>

                <h3 style="margin-bottom: 20px; color: var(--dark-green);">
                    <i class="fas fa-fill-drip"></i> Farbschema
                </h3>

                <!-- Color Palette Presets -->
                <div class="color-palette">
                    <div class="palette-item" onclick="setColorScheme('#2ECC71', '#27AE60', '#1ABC9C')">
                        <div class="palette-color" style="background: linear-gradient(45deg, #2ECC71, #27AE60);"></div>
                        <div class="palette-label">Standard Grün</div>
                    </div>
                    <div class="palette-item" onclick="setColorScheme('#00BF63', '#00A854', '#00E676')">
                        <div class="palette-color" style="background: linear-gradient(45deg, #00BF63, #00A854);"></div>
                        <div class="palette-label">Lebendiges Grün</div>
                    </div>
                    <div class="palette-item" onclick="setColorScheme('#4CAF50', '#388E3C', '#66BB6A')">
                        <div class="palette-color" style="background: linear-gradient(45deg, #4CAF50, #388E3C);"></div>
                        <div class="palette-label">Material Grün</div>
                    </div>
                    <div class="palette-item" onclick="setColorScheme('#8BC34A', '#689F38', '#9CCC65')">
                        <div class="palette-color" style="background: linear-gradient(45deg, #8BC34A, #689F38);"></div>
                        <div class="palette-label">Helles Grün</div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="primary_color">Primärfarbe:</label>
                        <input type="color" id="primary_color" name="primary_color" class="color-input"
                               value="<?php echo $config['branding']['primary_color']; ?>">
                        <span class="color-preview" style="background-color: <?php echo $config['branding']['primary_color']; ?>;"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="secondary_color">Sekundärfarbe:</label>
                        <input type="color" id="secondary_color" name="secondary_color" class="color-input"
                               value="<?php echo $config['branding']['secondary_color']; ?>">
                        <span class="color-preview" style="background-color: <?php echo $config['branding']['secondary_color']; ?>;"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="accent_color">Akzentfarbe:</label>
                        <input type="color" id="accent_color" name="accent_color" class="color-input"
                               value="<?php echo $config['branding']['accent_color']; ?>">
                        <span class="color-preview" style="background-color: <?php echo $config['branding']['accent_color']; ?>;"></span>
                    </div>
                </div>

                <button type="submit" name="update_branding" class="btn">
                    <i class="fas fa-save"></i> Branding speichern
                </button>
            </form>

            <!-- Brand Preview -->
            <div class="brand-preview" id="brandPreview">
                <h3><?php echo htmlspecialchars($config['branding']['admin_site_name']); ?></h3>
                <p>So wird Ihr Branding aussehen</p>
            </div>
        </div>

        <!-- Media Gallery -->
        <?php if (!empty($uploaded_files)): ?>
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-images"></i> Medien-Galerie (<?php echo count($uploaded_files); ?>)
                </h2>
                
                <div class="files-grid">
                    <?php foreach ($uploaded_files as $file): ?>
                        <div class="file-card">
                            <?php if (strpos($file['type'], 'image/') === 0): ?>
                                <img src="<?php echo htmlspecialchars($file['url']); ?>" 
                                     alt="<?php echo htmlspecialchars($file['name']); ?>" class="file-preview">
                            <?php else: ?>
                                <div class="file-preview" style="display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-file" style="font-size: 48px; color: #ddd;"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="file-name"><?php echo htmlspecialchars($file['name']); ?></div>
                            <div class="file-info">
                                <?php echo formatBytes($file['size']); ?><br>
                                <?php echo date('d.m.Y H:i', $file['modified']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function setColorScheme(primary, secondary, accent) {
            document.getElementById('primary_color').value = primary;
            document.getElementById('secondary_color').value = secondary;
            document.getElementById('accent_color').value = accent;
            
            // Update color previews
            updateColorPreviews();
        }
        
        function updateColorPreviews() {
            const primary = document.getElementById('primary_color').value;
            const secondary = document.getElementById('secondary_color').value;
            const accent = document.getElementById('accent_color').value;
            
            // Update CSS variables
            document.documentElement.style.setProperty('--primary-green', primary);
            document.documentElement.style.setProperty('--secondary-green', secondary);
            document.documentElement.style.setProperty('--accent-green', accent);
            
            // Update preview
            const brandPreview = document.getElementById('brandPreview');
            brandPreview.style.background = `linear-gradient(45deg, ${primary}, ${secondary})`;
        }
        
        // Update colors on input change
        document.getElementById('primary_color').addEventListener('input', updateColorPreviews);
        document.getElementById('secondary_color').addEventListener('input', updateColorPreviews);
        document.getElementById('accent_color').addEventListener('input', updateColorPreviews);
    </script>
</body>
</html>
