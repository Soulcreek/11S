<?php
$active = basename($_SERVER['PHP_SELF']);
?>
<div class="nav-links">
    <a href="dashboard.php" <?php echo ($active == 'dashboard.php') ? 'class="active"' : ''; ?>>
        <i class="fas fa-home"></i> Dashboard
    </a>
    <a href="question-management.php" <?php echo ($active == 'question-management.php') ? 'class="active"' : ''; ?>>
        <i class="fas fa-question-circle"></i> Fragen
    </a>
    <a href="user-management.php" <?php echo ($active == 'user-management.php' || $active == 'user-management-enhanced.php') ? 'class="active"' : ''; ?>>
        <i class="fas fa-users"></i> Benutzer
    </a>
    <a href="question-generator.php" <?php echo ($active == 'question-generator.php') ? 'class="active"' : ''; ?>>
        <i class="fas fa-magic"></i> KI Generator
    </a>
    <a href="statistics.php" <?php echo ($active == 'statistics.php') ? 'class="active"' : ''; ?>>
        <i class="fas fa-chart-bar"></i> Statistiken
    </a>
    <a href="settings.php" <?php echo ($active == 'settings.php') ? 'class="active"' : ''; ?>>
        <i class="fas fa-cog"></i> Einstellungen
    </a>
    <a href="index.php?logout=1" style="color: #ffcccb;">
        <i class="fas fa-sign-out-alt"></i> Abmelden
    </a>
</div>
