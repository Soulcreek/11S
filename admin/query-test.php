<?php
// Test HybridDataManager queries
require_once 'includes/HybridDataManager.php';

echo "<h1>HybridDataManager Query Tests</h1>";

try {
    $dataManager = HybridDataManager::getInstance();
    
    echo "<h2>1. User Statistics Query:</h2>";
    try {
        $userQuery = "
            SELECT 
                COUNT(*) as total_users,
                COUNT(CASE WHEN account_type = 'user' THEN 1 END) as regular_users,
                COUNT(CASE WHEN account_type = 'admin' THEN 1 END) as admin_users,
                COUNT(CASE WHEN account_type = 'registered' THEN 1 END) as registered_users
            FROM users
        ";
        $userResult = $dataManager->query($userQuery);
        echo "<pre>" . htmlspecialchars(json_encode($userResult, JSON_PRETTY_PRINT)) . "</pre>";
    } catch (Exception $e) {
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<h2>2. Question Statistics Query:</h2>";
    try {
        $questionQuery = "
            SELECT 
                COUNT(*) as total_questions,
                COUNT(CASE WHEN difficulty = 'easy' THEN 1 END) as easy_questions,
                COUNT(CASE WHEN difficulty = 'medium' THEN 1 END) as medium_questions,
                COUNT(CASE WHEN difficulty = 'hard' THEN 1 END) as hard_questions,
                COUNT(DISTINCT category) as total_categories
            FROM questions
        ";
        $questionResult = $dataManager->query($questionQuery);
        echo "<pre>" . htmlspecialchars(json_encode($questionResult, JSON_PRETTY_PRINT)) . "</pre>";
    } catch (Exception $e) {
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<h2>3. Simple count queries:</h2>";
    try {
        $simpleUsers = $dataManager->query("SELECT COUNT(*) as count FROM users");
        echo "<p>Simple users query result:</p>";
        echo "<pre>" . htmlspecialchars(json_encode($simpleUsers, JSON_PRETTY_PRINT)) . "</pre>";
        
        $simpleQuestions = $dataManager->query("SELECT COUNT(*) as count FROM questions");
        echo "<p>Simple questions query result:</p>";
        echo "<pre>" . htmlspecialchars(json_encode($simpleQuestions, JSON_PRETTY_PRINT)) . "</pre>";
    } catch (Exception $e) {
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<h2>4. getStats() method result:</h2>";
    try {
        $stats = $dataManager->getStats();
        echo "<pre>" . htmlspecialchars(json_encode($stats, JSON_PRETTY_PRINT)) . "</pre>";
    } catch (Exception $e) {
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
