<?php
// File: admin/question-management.php
// Description: Question management interface

session_start();

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

$data_dir = __DIR__ . '/data';
$questions_file = $data_dir . '/questions.json';

// Load questions
$questions_data = ['questions' => [], 'categories' => []];
if (file_exists($questions_file)) {
    $questions_data = json_decode(file_get_contents($questions_file), true) ?? $questions_data;
}

$questions = $questions_data['questions'] ?? [];
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

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_question'])) {
        $new_question = [
            'id' => uniqid(),
            'question' => trim($_POST['question']),
            'options' => [
                trim($_POST['option_a']),
                trim($_POST['option_b']),
                trim($_POST['option_c']),
                trim($_POST['option_d'])
            ],
            'correctAnswer' => (int)$_POST['correct_answer'],
            'category' => $_POST['category'],
            'difficulty' => $_POST['difficulty'],
            'createdAt' => date('Y-m-d H:i:s'),
            'source' => 'manual'
        ];
        
        if (!empty($new_question['question']) && !empty(array_filter($new_question['options']))) {
            $questions[] = $new_question;
            $questions_data['questions'] = $questions;
            file_put_contents($questions_file, json_encode($questions_data, JSON_PRETTY_PRINT));
            $success_message = "Frage wurde erfolgreich hinzugefügt.";
        } else {
            $error_message = "Bitte füllen Sie alle Felder aus.";
        }
    }
    
    if (isset($_POST['delete_question']) && isset($_POST['question_id'])) {
        $question_id = $_POST['question_id'];
        $questions = array_filter($questions, function($q) use ($question_id) {
            return $q['id'] !== $question_id;
        });
        $questions_data['questions'] = array_values($questions);
        file_put_contents($questions_file, json_encode($questions_data, JSON_PRETTY_PRINT));
        $success_message = "Frage wurde gelöscht.";
    }
    
    if (isset($_POST['add_category'])) {
        $category_key = strtolower(trim(str_replace(' ', '_', $_POST['category_key'])));
        $category_name = trim($_POST['category_name']);
        
        if (!empty($category_key) && !empty($category_name) && !isset($categories[$category_key])) {
            $categories[$category_key] = $category_name;
            $questions_data['categories'] = $categories;
            file_put_contents($questions_file, json_encode($questions_data, JSON_PRETTY_PRINT));
            $success_message = "Kategorie '$category_name' wurde hinzugefügt.";
        } else {
            $error_message = "Kategorie existiert bereits oder ungültige Daten.";
        }
    }
}

// Filter and search
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? 'all';
$difficulty_filter = $_GET['difficulty'] ?? 'all';

$filtered_questions = $questions;

if (!empty($search)) {
    $filtered_questions = array_filter($filtered_questions, function($q) use ($search) {
        return stripos($q['question'], $search) !== false ||
               stripos(implode(' ', $q['options']), $search) !== false;
    });
}

if ($category_filter !== 'all') {
    $filtered_questions = array_filter($filtered_questions, function($q) use ($category_filter) {
        return $q['category'] === $category_filter;
    });
}

if ($difficulty_filter !== 'all') {
    $filtered_questions = array_filter($filtered_questions, function($q) use ($difficulty_filter) {
        return $q['difficulty'] === $difficulty_filter;
    });
}

// Sort by creation date (newest first)
usort($filtered_questions, function($a, $b) {
    return strtotime($b['createdAt'] ?? '1970-01-01') - strtotime($a['createdAt'] ?? '1970-01-01');
});

// Statistics
$stats = [
    'total' => count($questions),
    'easy' => count(array_filter($questions, fn($q) => ($q['difficulty'] ?? '') === 'easy')),
    'medium' => count(array_filter($questions, fn($q) => ($q['difficulty'] ?? '') === 'medium')),
    'hard' => count(array_filter($questions, fn($q) => ($q['difficulty'] ?? '') === 'hard')),
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fragen verwalten - 11Seconds Admin</title>
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
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            text-align: center;
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            color: #666;
            margin-top: 5px;
            font-size: 14px;
        }
        
        .actions-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            grid-template-columns: 1fr 1fr;
            gap: 15px;
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
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px;
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
        
        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .correct-answer {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
        }
        
        .correct-answer input[type="radio"] {
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
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: linear-gradient(45deg, #667eea, #764ba2);
        }
        
        .btn-danger {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .filters-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }
        
        .questions-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .questions-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .question-card {
            padding: 25px;
            border-bottom: 1px solid #eee;
        }
        
        .question-card:last-child {
            border-bottom: none;
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .question-text {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
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
        
        .question-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 15px 0;
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
        
        .question-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
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
        
        .no-questions {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .quick-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .quick-btn {
            background: white;
            border: 2px solid #667eea;
            color: #667eea;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.2s ease;
        }
        
        .quick-btn:hover {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">❓</div>
                <div class="logo-text">Fragen verwalten</div>
            </div>
            <?php include __DIR__ . '/includes/nav.php'; ?>
        </div>
    </header>

    <div class="container">
        <h1 class="page-title">
            <i class="fas fa-question-circle"></i>
            Fragen verwalten
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
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Gesamt Fragen</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['easy']; ?></div>
                <div class="stat-label">Einfach</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['medium']; ?></div>
                <div class="stat-label">Mittel</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['hard']; ?></div>
                <div class="stat-label">Schwer</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count($categories); ?></div>
                <div class="stat-label">Kategorien</div>
            </div>
        </div>

        <div class="quick-actions">
            <a href="question-generator.php" class="quick-btn">
                <i class="fas fa-magic"></i> KI Fragen Generator
            </a>
            <a href="backup.php" class="quick-btn">
                <i class="fas fa-download"></i> Fragen exportieren
            </a>
        </div>

        <div class="actions-row">
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-plus"></i> Neue Frage hinzufügen
                </h2>
                <form method="post">
                    <div class="form-group full-width">
                        <label for="question">Frage:</label>
                        <textarea id="question" name="question" required placeholder="Geben Sie hier die Frage ein..."></textarea>
                    </div>

                    <div class="options-grid">
                        <div class="form-group">
                            <label for="option_a">Option A:</label>
                            <input type="text" id="option_a" name="option_a" required>
                        </div>
                        <div class="form-group">
                            <label for="option_b">Option B:</label>
                            <input type="text" id="option_b" name="option_b" required>
                        </div>
                        <div class="form-group">
                            <label for="option_c">Option C:</label>
                            <input type="text" id="option_c" name="option_c" required>
                        </div>
                        <div class="form-group">
                            <label for="option_d">Option D:</label>
                            <input type="text" id="option_d" name="option_d" required>
                        </div>
                    </div>

                    <div class="correct-answer">
                        <strong>Richtige Antwort:</strong>
                        <label><input type="radio" name="correct_answer" value="0" required> A</label>
                        <label><input type="radio" name="correct_answer" value="1"> B</label>
                        <label><input type="radio" name="correct_answer" value="2"> C</label>
                        <label><input type="radio" name="correct_answer" value="3"> D</label>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="category">Kategorie:</label>
                            <select id="category" name="category" required>
                                <?php foreach ($categories as $key => $name): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="difficulty">Schwierigkeit:</label>
                            <select id="difficulty" name="difficulty" required>
                                <option value="easy">Einfach</option>
                                <option value="medium">Mittel</option>
                                <option value="hard">Schwer</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="add_question" class="btn">
                        <i class="fas fa-plus"></i> Frage hinzufügen
                    </button>
                </form>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-tags"></i> Kategorien
                </h2>
                <div style="margin-bottom: 20px;">
                    <?php foreach ($categories as $key => $name): ?>
                        <div class="meta-badge" style="display: inline-block; margin: 5px;">
                            <?php echo $name; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form method="post">
                    <div class="form-group">
                        <label for="category_key">Schlüssel:</label>
                        <input type="text" id="category_key" name="category_key" required placeholder="z.B. politics">
                    </div>
                    <div class="form-group">
                        <label for="category_name">Name:</label>
                        <input type="text" id="category_name" name="category_name" required placeholder="z.B. Politik">
                    </div>
                    <button type="submit" name="add_category" class="btn btn-secondary">
                        <i class="fas fa-plus"></i> Kategorie hinzufügen
                    </button>
                </form>
            </div>
        </div>

        <div class="filters">
            <form method="get" class="filters-row">
                <div class="form-group">
                    <label for="search">Suche:</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Frage oder Antwort suchen...">
                </div>
                <div class="form-group">
                    <label for="category">Kategorie:</label>
                    <select id="category" name="category">
                        <option value="all">Alle Kategorien</option>
                        <?php foreach ($categories as $key => $name): ?>
                            <option value="<?php echo $key; ?>" <?php echo $category_filter === $key ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="difficulty">Schwierigkeit:</label>
                    <select id="difficulty" name="difficulty">
                        <option value="all">Alle</option>
                        <option value="easy" <?php echo $difficulty_filter === 'easy' ? 'selected' : ''; ?>>Einfach</option>
                        <option value="medium" <?php echo $difficulty_filter === 'medium' ? 'selected' : ''; ?>>Mittel</option>
                        <option value="hard" <?php echo $difficulty_filter === 'hard' ? 'selected' : ''; ?>>Schwer</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-search"></i> Filtern
                </button>
            </form>
        </div>

        <div class="questions-section">
            <div class="questions-header">
                <h2 class="section-title">
                    Alle Fragen (<?php echo count($filtered_questions); ?>)
                </h2>
            </div>

            <?php if (empty($filtered_questions)): ?>
                <div class="no-questions">
                    <i class="fas fa-question-circle" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                    <p>Keine Fragen gefunden.</p>
                </div>
            <?php else: ?>
                <?php foreach ($filtered_questions as $question): ?>
                    <div class="question-card">
                        <div class="question-header">
                            <div>
                                <div class="question-text"><?php echo htmlspecialchars($question['question']); ?></div>
                                <div class="question-meta">
                                    <span class="meta-badge <?php echo 'difficulty-' . ($question['difficulty'] ?? 'medium'); ?>">
                                        <?php 
                                        $diff_labels = ['easy' => 'Einfach', 'medium' => 'Mittel', 'hard' => 'Schwer'];
                                        echo $diff_labels[$question['difficulty'] ?? 'medium'] ?? 'Mittel'; 
                                        ?>
                                    </span>
                                    <span class="meta-badge">
                                        <?php echo $categories[$question['category']] ?? 'Unbekannt'; ?>
                                    </span>
                                    <span><?php echo date('d.m.Y H:i', strtotime($question['createdAt'] ?? '1970-01-01')); ?></span>
                                </div>
                            </div>
                            <div class="question-actions">
                                <form method="post" style="display: inline;" 
                                      onsubmit="return confirm('Sind Sie sicher, dass Sie diese Frage löschen möchten?');">
                                    <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                    <button type="submit" name="delete_question" class="btn btn-danger btn-small">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="question-options">
                            <?php foreach ($question['options'] as $index => $option): ?>
                                <div class="option <?php echo $index === ($question['correctAnswer'] ?? 0) ? 'correct' : ''; ?>">
                                    <strong><?php echo chr(65 + $index); ?>:</strong> <?php echo htmlspecialchars($option); ?>
                                    <?php if ($index === ($question['correctAnswer'] ?? 0)): ?>
                                        <i class="fas fa-check" style="float: right;"></i>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
