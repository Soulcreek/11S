<?php
/**
 * 11Seconds Admin - Session Manager
 * Redirect to login if not authenticated
 */

// Check if this is an AJAX/API request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
$isApi = strpos($_SERVER['REQUEST_URI'], 'api.php') !== false;

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
$isAuthenticated = isset($_SESSION['admin_user']) && $_SESSION['admin_user'];

// If not authenticated and accessing protected content
if (!$isAuthenticated && !$isApi) {
    // If it's the login page, allow access
    $currentFile = basename($_SERVER['SCRIPT_FILENAME']);
    if ($currentFile === 'login.php') {
        return; // Allow access to login page
    }
    
    // For AJAX requests, return JSON error
    if ($isAjax) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }
    
    // For regular requests, redirect to login
    header('Location: login.php');
    exit;
}

// If authenticated and trying to access login page, redirect to admin
if ($isAuthenticated && basename($_SERVER['SCRIPT_FILENAME']) === 'login.php') {
    header('Location: index.html');
    exit;
}

// Session timeout check (4 hours)
$timeout = 4 * 3600; // 4 hours in seconds
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $timeout)) {
    session_destroy();
    
    if ($isAjax) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Session expired']);
        exit;
    }
    
    header('Location: login.php?expired=1');
    exit;
}

// Update last activity time
if ($isAuthenticated) {
    $_SESSION['last_activity'] = time();
}
?>
