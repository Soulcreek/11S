<?php
// File: admin/question-generator.php
// Description: AI-powered question generator using Google Gemini API

session_start();

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

$data_dir = __DIR__ . '/data';
$questions_file = $data_dir . '/questions.json';
$config_file = $data_dir . '/config.json';

// Load configuration
$config = [];
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true) ?? [];
}

// Load questions data
$questions_data = ['questions' => [], 'categories' => []];
if (file_exists($questions_file)) {
    $questions_data = json_decode(file_get_contents($questions_file), true) ?? $questions_data;
}

$categories = $questions_data['categories'] ?? [
    'geography' => 'Geografie',
    'history' => 'Geschichte',
    'science' => 'Wissenschaft',
    'sports' => 'Sport',
    'entertainment' => 'Unterhaltung',
    'art_literature' => 'Kunst & Literatur',
    'nature' => 'Natur',
    'technology' => 'Technologie'
];

// Handle configuration save
if (isset($_POST['save_config'])) {
    $config['gemini_api_key'] = trim($_POST['gemini_api_key']);
    if (!is_dir($data_dir)) {
        mkdir($data_dir, 0755, true);
    }
    file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
    $config_success = "API-Konfiguration wurde gespeichert.";
}

// Handle question generation
$generated_questions = [];
$generation_error = '';
$generation_success = '';

if (isset($_POST['generate_questions'])) {
    $api_key = $config['gemini_api_key'] ?? '';
    $category = $_POST['category'] ?? 'science';
    $difficulty = $_POST['difficulty'] ?? 'medium';
    $count = min(max((int)$_POST['count'], 1), 20);
    $language = $_POST['language'] ?? 'german';
    $custom_topic = trim($_POST['custom_topic'] ?? '');
    
    if (empty($api_key)) {
        $generation_error = 'Bitte konfigurieren Sie zuerst den Google Gemini API-Schlüssel.';
    } else {
        $generated_questions = generateQuestionsWithGemini($api_key, $category, $difficulty, $count, $language, $custom_topic, $categories);
        if (empty($generated_questions)) {
            $generation_error = 'Fehler beim Generieren der Fragen. Überprüfen Sie den API-Schlüssel und versuchen Sie es erneut.';
        } else {
            $generation_success = count($generated_questions) . " Fragen wurden erfolgreich generiert.";
        }
    }
}

// Handle saving generated questions
if (isset($_POST['save_questions']) && isset($_POST['questions_json'])) {
    $questions_to_save = json_decode($_POST['questions_json'], true);
    $selected_indices = $_POST['selected_questions'] ?? [];
    
    $saved_count = 0;
    foreach ($selected_indices as $index) {
        if (isset($questions_to_save[$index])) {
            $question = $questions_to_save[$index];
            $question['id'] = uniqid();
            $question['createdAt'] = date('Y-m-d H:i:s');
            $question['source'] = 'ai_generated';
            
            $questions_data['questions'][] = $question;
            $saved_count++;
        }
    }
    
    file_put_contents($questions_file, json_encode($questions_data, JSON_PRETTY_PRINT));
    $save_success = "$saved_count Fragen wurden zur Datenbank hinzugefügt.";
    $generated_questions = []; // Clear generated questions after saving
}

function generateQuestionsWithGemini($api_key, $category, $difficulty, $count, $language, $custom_topic, $categories) {
    $category_name = $categories[$category] ?? $category;
    
    $difficulty_map = [
        'easy' => 'einfach',
        'medium' => 'mittel',
        'hard' => 'schwer'
    ];
    $difficulty_german = $difficulty_map[$difficulty] ?? 'mittel';
    
    $topic_instruction = !empty($custom_topic) ? 
        "Fokussiere dich auf das Thema: $custom_topic" : 
        "Verwende allgemeine Themen aus der Kategorie $category_name";
    
    $prompt = "
Erstelle genau $count Quiz-Fragen in deutscher Sprache für die Kategorie '$category_name' mit Schwierigkeitsgrad '$difficulty_german'.

$topic_instruction

Jede Frage soll:
- Klar und eindeutig formuliert sein
- Genau 4 Antwortoptionen haben
- Eine eindeutig richtige Antwort haben
- Für deutsche Spieler relevant und verständlich sein
- Dem Schwierigkeitsgrad '$difficulty_german' entsprechen

Antworte ausschließlich im folgenden JSON-Format:
[
  {
    \"question\": \"Die Frage hier?\",
    \"options\": [\"Option A\", \"Option B\", \"Option C\", \"Option D\"],
    \"correctAnswer\": 0,
    \"category\": \"$category\",
    \"difficulty\": \"$difficulty\"
  }
]

Keine zusätzlichen Erklärungen, nur das JSON-Array.
";

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $api_key;
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 2048,
        ]
    ];
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
            ],
            'content' => json_encode($data)
        ]
    ]);
    
    try {
        $response = file_get_contents($url, false, $context);
        if ($response === false) {
            return [];
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $generated_text = $result['candidates'][0]['content']['parts'][0]['text'];
            
            // Clean the text and extract JSON
            $generated_text = trim($generated_text);
            $generated_text = preg_replace('/^```json\s*/', '', $generated_text);
            $generated_text = preg_replace('/\s*```$/', '', $generated_text);
            
            $questions = json_decode($generated_text, true);
            
            if (is_array($questions)) {
                // Validate and clean questions
                $valid_questions = [];
                foreach ($questions as $question) {
                    if (isset($question['question'], $question['options'], $question['correctAnswer']) 
                        && is_array($question['options']) 
                        && count($question['options']) === 4
                        && is_int($question['correctAnswer']) 
                        && $question['correctAnswer'] >= 0 
                        && $question['correctAnswer'] < 4) {
                        
                        $valid_questions[] = [
                            'question' => trim($question['question']),
                            'options' => array_map('trim', $question['options']),
                            'correctAnswer' => $question['correctAnswer'],
                            'category' => $category,
                            'difficulty' => $difficulty
                        ];
                    }
                }
                return $valid_questions;
            }
        }
    } catch (Exception $e) {
        error_log("Gemini API Error: " . $e->getMessage());
    }
    
    return [];
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KI Fragen Generator - 11Seconds Admin</title>
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
        
        .section {
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
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
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: transform 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
        }
        
        .btn-secondary {
            background: linear-gradient(45deg, #6c757d, #5a6268);
        }
        
        .btn-danger {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
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
        
        .question-preview {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
        }
        
        .question-preview.selected {
            border-color: #4CAF50;
            background: rgba(76, 175, 80, 0.05);
        }
        
        .question-select {
            position: absolute;
            top: 15px;
            right: 15px;
        }
        
        .question-text {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
        }
        
        .question-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .option {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px solid transparent;
        }
        
        .option.correct {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
            font-weight: bold;
        }
        
        .question-meta {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #666;
        }
        
        .meta-badge {
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: bold;
        }
        
        .difficulty-easy { background: #d4edda; color: #155724; }
        .difficulty-medium { background: #fff3cd; color: #856404; }
        .difficulty-hard { background: #f8d7da; color: #721c24; }
        
        .generation-controls {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .loading i {
            font-size: 48px;
            animation: spin 2s linear infinite;
            margin-bottom: 20px;
            color: #667eea;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .select-all-controls {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .help-text {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">🪄</div>
                <div class="logo-text">KI Fragen Generator</div>
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
            <i class="fas fa-magic"></i>
            KI Fragen Generator
        </h1>

        <?php if (isset($config_success)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo $config_success; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($save_success)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo $save_success; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($generation_error)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $generation_error; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($generation_success)): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo $generation_success; ?>
            </div>
        <?php endif; ?>

        <!-- API Configuration -->
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-key"></i> API Konfiguration
            </h2>
            <form method="post">
                <div class="form-group">
                    <label for="gemini_api_key">Google Gemini API Schlüssel:</label>
                    <input type="password" id="gemini_api_key" name="gemini_api_key" 
                           value="<?php echo htmlspecialchars($config['gemini_api_key'] ?? ''); ?>" 
                           placeholder="Geben Sie hier Ihren Google Gemini API-Schlüssel ein">
                    <div class="help-text">
                        Sie benötigen einen API-Schlüssel von Google AI Studio: 
                        <a href="https://makersuite.google.com/app/apikey" target="_blank">
                            https://makersuite.google.com/app/apikey
                        </a>
                    </div>
                </div>
                <button type="submit" name="save_config" class="btn btn-secondary">
                    <i class="fas fa-save"></i> Konfiguration speichern
                </button>
                
                <div class="api-status <?php echo empty($config['gemini_api_key']) ? 'api-not-configured' : 'api-configured'; ?>">
                    <i class="fas <?php echo empty($config['gemini_api_key']) ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?>"></i>
                    <?php if (empty($config['gemini_api_key'])): ?>
                        API-Schlüssel nicht konfiguriert
                    <?php else: ?>
                        API-Schlüssel konfiguriert
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Question Generation -->
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-robot"></i> Fragen generieren
            </h2>
            <form method="post" id="generateForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="category">Kategorie:</label>
                        <select id="category" name="category" required>
                            <?php foreach ($categories as $key => $name): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($_POST['category'] ?? '') === $key ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="difficulty">Schwierigkeit:</label>
                        <select id="difficulty" name="difficulty" required>
                            <option value="easy" <?php echo ($_POST['difficulty'] ?? '') === 'easy' ? 'selected' : ''; ?>>Einfach</option>
                            <option value="medium" <?php echo ($_POST['difficulty'] ?? 'medium') === 'medium' ? 'selected' : ''; ?>>Mittel</option>
                            <option value="hard" <?php echo ($_POST['difficulty'] ?? '') === 'hard' ? 'selected' : ''; ?>>Schwer</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="count">Anzahl Fragen:</label>
                        <select id="count" name="count" required>
                            <option value="3" <?php echo ($_POST['count'] ?? '5') === '3' ? 'selected' : ''; ?>>3 Fragen</option>
                            <option value="5" <?php echo ($_POST['count'] ?? '5') === '5' ? 'selected' : ''; ?>>5 Fragen</option>
                            <option value="10" <?php echo ($_POST['count'] ?? '5') === '10' ? 'selected' : ''; ?>>10 Fragen</option>
                            <option value="15" <?php echo ($_POST['count'] ?? '5') === '15' ? 'selected' : ''; ?>>15 Fragen</option>
                            <option value="20" <?php echo ($_POST['count'] ?? '5') === '20' ? 'selected' : ''; ?>>20 Fragen</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="language">Sprache:</label>
                        <select id="language" name="language" required>
                            <option value="german" <?php echo ($_POST['language'] ?? 'german') === 'german' ? 'selected' : ''; ?>>Deutsch</option>
                            <option value="english" <?php echo ($_POST['language'] ?? 'german') === 'english' ? 'selected' : ''; ?>>Englisch</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label for="custom_topic">Spezifisches Thema (optional):</label>
                    <textarea id="custom_topic" name="custom_topic" rows="2" 
                              placeholder="z.B. 'Deutsche Bundesländer' oder 'Künstliche Intelligenz'"><?php echo htmlspecialchars($_POST['custom_topic'] ?? ''); ?></textarea>
                    <div class="help-text">
                        Lassen Sie dieses Feld leer für allgemeine Fragen der gewählten Kategorie.
                    </div>
                </div>
                
                <div class="generation-controls">
                    <button type="submit" name="generate_questions" class="btn btn-primary" 
                            <?php echo empty($config['gemini_api_key']) ? 'disabled' : ''; ?>>
                        <i class="fas fa-magic"></i> Fragen generieren
                    </button>
                </div>
            </form>
        </div>

        <!-- Generated Questions -->
        <?php if (!empty($generated_questions)): ?>
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-list-check"></i> Generierte Fragen (<?php echo count($generated_questions); ?>)
                </h2>
                
                <form method="post">
                    <div class="select-all-controls">
                        <div>
                            <button type="button" onclick="selectAll()" class="btn btn-secondary">
                                <i class="fas fa-check-double"></i> Alle auswählen
                            </button>
                            <button type="button" onclick="selectNone()" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Alle abwählen
                            </button>
                        </div>
                        <div>
                            <span id="selectedCount">0</span> von <?php echo count($generated_questions); ?> ausgewählt
                        </div>
                    </div>
                    
                    <?php foreach ($generated_questions as $index => $question): ?>
                        <div class="question-preview" id="question-<?php echo $index; ?>">
                            <div class="question-select">
                                <input type="checkbox" name="selected_questions[]" value="<?php echo $index; ?>" 
                                       onchange="updateSelection()" id="select-<?php echo $index; ?>">
                            </div>
                            
                            <div class="question-text"><?php echo htmlspecialchars($question['question']); ?></div>
                            
                            <div class="question-options">
                                <?php foreach ($question['options'] as $optIndex => $option): ?>
                                    <div class="option <?php echo $optIndex === $question['correctAnswer'] ? 'correct' : ''; ?>">
                                        <strong><?php echo chr(65 + $optIndex); ?>:</strong> <?php echo htmlspecialchars($option); ?>
                                        <?php if ($optIndex === $question['correctAnswer']): ?>
                                            <i class="fas fa-check" style="float: right;"></i>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="question-meta">
                                <span class="meta-badge <?php echo 'difficulty-' . $question['difficulty']; ?>">
                                    <?php 
                                    $diff_labels = ['easy' => 'Einfach', 'medium' => 'Mittel', 'hard' => 'Schwer'];
                                    echo $diff_labels[$question['difficulty']] ?? 'Mittel'; 
                                    ?>
                                </span>
                                <span class="meta-badge">
                                    <?php echo $categories[$question['category']] ?? 'Unbekannt'; ?>
                                </span>
                                <span>KI generiert</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <input type="hidden" name="questions_json" value="<?php echo htmlspecialchars(json_encode($generated_questions)); ?>">
                    
                    <div class="generation-controls">
                        <button type="submit" name="save_questions" class="btn" id="saveBtn" disabled>
                            <i class="fas fa-save"></i> Ausgewählte Fragen speichern
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function selectAll() {
            const checkboxes = document.querySelectorAll('input[name="selected_questions[]"]');
            checkboxes.forEach(cb => cb.checked = true);
            updateSelection();
        }
        
        function selectNone() {
            const checkboxes = document.querySelectorAll('input[name="selected_questions[]"]');
            checkboxes.forEach(cb => cb.checked = false);
            updateSelection();
        }
        
        function updateSelection() {
            const checkboxes = document.querySelectorAll('input[name="selected_questions[]"]');
            const selectedCount = document.querySelectorAll('input[name="selected_questions[]"]:checked').length;
            
            document.getElementById('selectedCount').textContent = selectedCount;
            document.getElementById('saveBtn').disabled = selectedCount === 0;
            
            // Update visual selection
            checkboxes.forEach((cb, index) => {
                const preview = document.getElementById('question-' + index);
                if (cb.checked) {
                    preview.classList.add('selected');
                } else {
                    preview.classList.remove('selected');
                }
            });
        }
        
        // Show loading state during generation
        document.getElementById('generateForm').addEventListener('submit', function() {
            const button = this.querySelector('button[name="generate_questions"]');
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generiere Fragen...';
            button.disabled = true;
        });
    </script>
</body>
</html>
