<?php
// File: web/api/auth/guest.php
// Description: Create guest user endpoint

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once '../../../admin/includes/AuthManager.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid input data');
    }
    
    $auth_manager = new AuthManager();
    $guest_user = $auth_manager->createGuestUser();
    
    // Add client info
    $guest_user['security']['ip_address'] = $input['ip'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $guest_user['security']['user_agent'] = $input['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // Save guest user temporarily (for session tracking)
    $guests_file = '../../../admin/data/guest_sessions.json';
    $guests = [];
    if (file_exists($guests_file)) {
        $guests = json_decode(file_get_contents($guests_file), true) ?? [];
    }
    
    $guests[$guest_user['user_id']] = $guest_user;
    file_put_contents($guests_file, json_encode($guests, JSON_PRETTY_PRINT));
    
    // Clean up expired guests
    $now = time();
    $guests = array_filter($guests, function($guest) use ($now) {
        return ($guest['expires_at'] ?? 0) > $now;
    });
    file_put_contents($guests_file, json_encode($guests, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'success' => true,
        'user' => $guest_user,
        'message' => 'Gastkonto erfolgreich erstellt'
    ]);
    
} catch (Exception $e) {
    error_log('Guest creation error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Fehler beim Erstellen des Gastkontos'
    ]);
}
?>
