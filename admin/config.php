<?php
// Database Configuration
// DO NOT COMMIT PASSWORDS TO VERSION CONTROL!

// Read from environment variables (recommended)
if (file_exists(__DIR__ . '/../config/.env')) {
    $envFile = file_get_contents(__DIR__ . '/../config/.env');
    $lines = explode("\n", $envFile);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Database configuration with fallback
return [
    'host' => $_ENV['DB_HOST'] ?? '10.35.233.76',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'database' => $_ENV['DB_NAME'] ?? 'k302164_11Sec_Data',
    'username' => $_ENV['DB_USER'] ?? 'k302164_11SecUser',
    'password' => $_ENV['DB_PASS'] ?? null, // MUST be set in .env file
    'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4'
];
