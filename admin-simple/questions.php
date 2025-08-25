<?php
session_start();
require_once 'database.php';

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance();
$message = '';
$message_type = '';

// Handle question actions
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create':
            $question = trim($_POST['question']);
            $correct_answer = trim($_POST['correct_answer']);
            $wrong_answers = [
                trim($_POST['wrong1']),
                trim($_POST['wrong2']),
                trim($_POST['wrong3'])
            ];
            $category = $_POST['category'];
            $difficulty = $_POST['difficulty'];
            
            if (!empty($question) && !empty($correct_answer) && !empty($wrong_answers[0])) {
                try {
                    $db->execute("INSERT INTO questions (question, correct_answer, wrong_answers, category, difficulty) VALUES (?, ?, ?, ?, ?)", 
                                [$question, $correct_answer, json_encode($wrong_answers), $category, $difficulty]);
                    $message = "Question created successfully!";
                    $message_type = 'success';
                } catch (Exception $e) {
                    $message = "Error creating question: " . $e->getMessage();
                    $message_type = 'error';
                }
            }
            break;
            
        case 'delete':
            $question_id = (int)$_POST['question_id'];
            if ($question_id > 0) {
                $db->execute("DELETE FROM questions WHERE id = ?", [$question_id]);
                $message = "Question deleted successfully!";
                $message_type = 'success';
            }
            break;
            
        case 'toggle_status':
            $question_id = (int)$_POST['question_id'];
            if ($question_id > 0) {
                $db->execute("UPDATE questions SET active = NOT active WHERE id = ?", [$question_id]);
                $message = "Question status updated!";
                $message_type = 'success';
            }
            break;
    }
}

// Get all questions
$questions = $db->query("SELECT * FROM questions ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>11Seconds Admin - Question Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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
        
        .section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .section h2 {
            margin-bottom: 1.5rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .message {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        label {
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #555;
        }
        
        input, select, textarea {
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            font-family: inherit;
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        
        .btn-warning:hover {
            background: #d97706;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        .question-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .question-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1);
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .question-text {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            flex: 1;
            margin-right: 1rem;
        }
        
        .question-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .meta-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .category-badge {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .difficulty-easy {
            background: #d1fae5;
            color: #065f46;
        }
        
        .difficulty-medium {
            background: #fef3c7;
            color: #92400e;
        }
        
        .difficulty-hard {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .answers {
            margin-bottom: 1rem;
        }
        
        .answer {
            padding: 0.5rem;
            margin: 0.25rem 0;
            border-radius: 5px;
        }
        
        .answer.correct {
            background: #d1fae5;
            color: #065f46;
            font-weight: 500;
        }
        
        .answer.wrong {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
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
            <a href="dashboard.php">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="users.php">
                <i class="fas fa-users"></i> Users
            </a>
            <a href="questions.php" class="active">
                <i class="fas fa-question-circle"></i> Questions
            </a>
            <a href="?logout=1" style="color: #fca5a5;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </header>

    <div class="container">
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="section">
            <h2><i class="fas fa-plus-circle"></i> Create New Question</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group full-width">
                    <label for="question">Question</label>
                    <textarea id="question" name="question" placeholder="Enter your question here..." required></textarea>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="correct_answer">Correct Answer</label>
                        <input type="text" id="correct_answer" name="correct_answer" required>
                    </div>
                    <div class="form-group">
                        <label for="wrong1">Wrong Answer 1</label>
                        <input type="text" id="wrong1" name="wrong1" required>
                    </div>
                    <div class="form-group">
                        <label for="wrong2">Wrong Answer 2</label>
                        <input type="text" id="wrong2" name="wrong2">
                    </div>
                    <div class="form-group">
                        <label for="wrong3">Wrong Answer 3</label>
                        <input type="text" id="wrong3" name="wrong3">
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="general">General</option>
                            <option value="science">Science</option>
                            <option value="history">History</option>
                            <option value="sports">Sports</option>
                            <option value="entertainment">Entertainment</option>
                            <option value="geography">Geography</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="difficulty">Difficulty</label>
                        <select id="difficulty" name="difficulty">
                            <option value="easy">Easy</option>
                            <option value="medium">Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Question
                </button>
            </form>
        </div>

        <div class="section">
            <h2><i class="fas fa-list"></i> All Questions (<?php echo count($questions); ?>)</h2>
            
            <?php if (!empty($questions)): ?>
                <?php foreach ($questions as $question): ?>
                    <div class="question-card">
                        <div class="question-header">
                            <div class="question-text">
                                <?php echo htmlspecialchars($question['question']); ?>
                            </div>
                            <div class="actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                    <button type="submit" class="btn btn-warning btn-sm">
                                        <i class="fas fa-toggle-<?php echo $question['active'] ? 'on' : 'off'; ?>"></i>
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this question?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="question-meta">
                            <span class="meta-badge category-badge">
                                <?php echo ucfirst($question['category']); ?>
                            </span>
                            <span class="meta-badge difficulty-<?php echo $question['difficulty']; ?>">
                                <?php echo ucfirst($question['difficulty']); ?>
                            </span>
                            <span class="meta-badge status-<?php echo $question['active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $question['active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                        
                        <div class="answers">
                            <div class="answer correct">
                                ✅ <?php echo htmlspecialchars($question['correct_answer']); ?>
                            </div>
                            <?php 
                            $wrong_answers = json_decode($question['wrong_answers'], true);
                            if (is_array($wrong_answers)): 
                            ?>
                                <?php foreach ($wrong_answers as $wrong): ?>
                                    <?php if (!empty($wrong)): ?>
                                        <div class="answer wrong">
                                            ❌ <?php echo htmlspecialchars($wrong); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 2rem;">No questions found. Create your first question!</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
