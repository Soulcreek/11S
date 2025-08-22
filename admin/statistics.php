<?php
session_start();
require_once 'includes/DatabaseManager.php';
require_once 'includes/HybridDataManager.php';
require_once 'includes/SubdomainConfig.php';

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

$dataManager = HybridDataManager::getInstance();
$config = new SubdomainConfig();

// Get detailed statistics
try {
    $stats = $dataManager->getStats();
    
    // Get user statistics
    $user_stats = $dataManager->query("
        SELECT 
            COUNT(*) as total_users,
            COUNT(CASE WHEN account_type = 'user' THEN 1 END) as regular_users,
            COUNT(CASE WHEN account_type = 'admin' THEN 1 END) as admin_users,
            COUNT(CASE WHEN verified = TRUE THEN 1 END) as verified_users,
            COUNT(CASE WHEN google_id IS NOT NULL THEN 1 END) as google_users
        FROM users
    ")[0];
    
    // Get question statistics
    $question_stats = $dataManager->query("
        SELECT 
            COUNT(*) as total_questions,
            COUNT(CASE WHEN difficulty = 'easy' THEN 1 END) as easy_questions,
            COUNT(CASE WHEN difficulty = 'medium' THEN 1 END) as medium_questions,
            COUNT(CASE WHEN difficulty = 'hard' THEN 1 END) as hard_questions,
            COUNT(DISTINCT category) as total_categories
        FROM questions
    ")[0];
    
    // Get recent game sessions
    $sql_recent = <<<'SQL'
SELECT gs.*, u.username
FROM game_sessions gs
LEFT JOIN users u ON gs.user_id = u.id
ORDER BY gs.created_at DESC
LIMIT 20
SQL;

    $recent_games = $dataManager->query($sql_recent);
    
    // Get top performers
    $top_performers = $dataManager->query("
        SELECT u.username, us.best_score, us.games_played, us.average_score
        FROM user_stats us
        JOIN users u ON us.user_id = u.id
        WHERE us.games_played > 0
        ORDER BY us.best_score DESC, us.average_score DESC
        LIMIT 10
    ");
    
} catch (Exception $e) {
    $error_message = "Fehler beim Laden der Statistiken: " . $e->getMessage();
    $stats = $user_stats = $question_stats = [];
    $recent_games = $top_performers = [];
}

$page_title = "Statistiken";
include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-chart-bar"></i> Statistiken & Analytics</h1>
    <p>Detaillierte Auswertungen und Berichte</p>
</div>

<?php if (isset($error_message)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
</div>
<?php else: ?>

<!-- Overview Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-content">
            <h3><?php echo number_format($user_stats['total_users'] ?? 0); ?></h3>
            <p>Gesamte Benutzer</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-question-circle"></i></div>
        <div class="stat-content">
            <h3><?php echo number_format($question_stats['total_questions'] ?? 0); ?></h3>
            <p>Fragen im System</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-gamepad"></i></div>
        <div class="stat-content">
            <h3><?php echo number_format($stats['sessions'] ?? 0); ?></h3>
            <p>Gespielte Spiele</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-star"></i></div>
        <div class="stat-content">
            <h3><?php echo number_format($stats['avg_score'] ?? 0, 1); ?>%</h3>
            <p>Durchschnittsscore</p>
        </div>
    </div>
</div>

<!-- Detailed Statistics -->
<div class="stats-section">
    <h2><i class="fas fa-users"></i> Benutzerstatistiken</h2>
    <div class="stats-details">
        <div class="detail-item">
            <span class="label">Reguläre Benutzer:</span>
            <span class="value"><?php echo number_format($user_stats['regular_users'] ?? 0); ?></span>
        </div>
        <div class="detail-item">
            <span class="label">Administratoren:</span>
            <span class="value"><?php echo number_format($user_stats['admin_users'] ?? 0); ?></span>
        </div>
        <div class="detail-item">
            <span class="label">Verifizierte Benutzer:</span>
            <span class="value"><?php echo number_format($user_stats['verified_users'] ?? 0); ?></span>
        </div>
        <div class="detail-item">
            <span class="label">Google-Anmeldungen:</span>
            <span class="value"><?php echo number_format($user_stats['google_users'] ?? 0); ?></span>
        </div>
    </div>
</div>

<div class="stats-section">
    <h2><i class="fas fa-question-circle"></i> Fragenstatistiken</h2>
    <div class="stats-details">
        <div class="detail-item">
            <span class="label">Einfache Fragen:</span>
            <span class="value"><?php echo number_format($question_stats['easy_questions'] ?? 0); ?></span>
        </div>
        <div class="detail-item">
            <span class="label">Mittlere Fragen:</span>
            <span class="value"><?php echo number_format($question_stats['medium_questions'] ?? 0); ?></span>
        </div>
        <div class="detail-item">
            <span class="label">Schwere Fragen:</span>
            <span class="value"><?php echo number_format($question_stats['hard_questions'] ?? 0); ?></span>
        </div>
        <div class="detail-item">
            <span class="label">Kategorien:</span>
            <span class="value"><?php echo number_format($question_stats['total_categories'] ?? 0); ?></span>
        </div>
    </div>
</div>

<!-- Top Performers -->
<?php if (!empty($top_performers)): ?>
<div class="stats-section">
    <h2><i class="fas fa-trophy"></i> Top Performer</h2>
    <div class="top-performers">
        <?php foreach ($top_performers as $index => $performer): ?>
            <div class="performer-item">
                <div class="rank">#<?php echo $index + 1; ?></div>
                <div class="performer-info">
                    <strong><?php echo htmlspecialchars($performer['username']); ?></strong>
                    <span>Bester Score: <?php echo $performer['best_score']; ?>% | 
                          Durchschnitt: <?php echo number_format($performer['average_score'], 1); ?>% | 
                          Spiele: <?php echo $performer['games_played']; ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Recent Games -->
<?php if (!empty($recent_games)): ?>
<div class="stats-section">
    <h2><i class="fas fa-clock"></i> Letzte Spiele</h2>
    <div class="recent-games">
        <?php foreach (array_slice($recent_games, 0, 10) as $game): ?>
            <div class="game-item">
                <div class="game-info">
                    <strong><?php echo htmlspecialchars($game['username'] ?? 'Gast'); ?></strong>
                    <span>Score: <?php echo $game['score']; ?>% | 
                          Richtig: <?php echo $game['questions_correct']; ?>/<?php echo $game['questions_answered']; ?> | 
                          Zeit: <?php echo gmdate('i:s', $game['time_taken']); ?></span>
                </div>
                <div class="game-time">
                    <?php echo date('d.m.Y H:i', strtotime($game['created_at'])); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<style>
.page-header {
    margin-bottom: 2rem;
}

.page-header h1 {
    color: #333;
    margin-bottom: 0.5rem;
}

.page-header p {
    color: #666;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    font-size: 2.5rem;
    color: #667eea;
}

.stat-content h3 {
    font-size: 2rem;
    margin: 0;
    color: #333;
}

.stat-content p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.stats-section {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
}

.stats-section h2 {
    margin-bottom: 1rem;
    color: #333;
}

.stats-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 6px;
}

.label {
    font-weight: 500;
    color: #555;
}

.value {
    font-weight: bold;
    color: #333;
}

.top-performers, .recent-games {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.performer-item, .game-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.rank {
    font-size: 1.2rem;
    font-weight: bold;
    color: #667eea;
    min-width: 2rem;
}

.performer-info, .game-info {
    flex: 1;
}

.performer-info strong, .game-info strong {
    display: block;
    color: #333;
}

.performer-info span, .game-info span {
    color: #666;
    font-size: 0.9rem;
}

.game-time {
    color: #666;
    font-size: 0.85rem;
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>

<?php include 'includes/footer.php'; ?>
