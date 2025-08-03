<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'stepcash_devflowstudio');
define('DB_USER', 'stepcash_devflowstudio'); 
define('DB_PASS', 'devflowstudio'); 

// Email configuration
define('SMTP_HOST', 'mail.devflowstudio.co.ke'); 
define('SMTP_PORT', 465); 
define('SMTP_USER', 'projects@devflowstudio.co.ke'); 
define('SMTP_PASS', 'devflowstudioadmis'); 
define('FROM_EMAIL', 'projects@devflowstudio.co.ke');
define('FROM_NAME', 'DevFlow Studio');
define('ADMIN_EMAIL', 'livingstoneapeli@gmail.com'); 
define('ADMIN_EMAIL2', 'joshuakaingu591@gmail.com'); 

// File upload configuration
define('UPLOAD_DIR', 'uploads/project_files/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); 
define('ALLOWED_TYPES', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'txt', 'zip']);

// ==== SECURE LOGIN ACCESS CONFIGURATION ====
define('SECURE_ACCESS_TOKEN', 'apelisolutions_2024_xyz789abc123');


// Allowed IP addresses for admin access 
define('ALLOWED_IPS', [
    '127.0.0.1',        
    '::1',              
    // '192.168.1.100',
    // '203.0.113.45',
]);

define('ADMIN_SECRET_PATH', 'apelisolutions_2024_xyz789abc123');

// Login attempt limits
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOGIN_LOCKOUT_TIME', 300); 

// ==== SECURITY FUNCTIONS ====

/**
 * Check if the current request has valid access token
 */
function hasValidAccessToken() {
    return isset($_GET['token']) && $_GET['token'] === SECURE_ACCESS_TOKEN;
}

/**
 * Check if the current IP is allowed (if IP restriction is enabled)
 */
function isIPAllowed() {
    if (empty(ALLOWED_IPS)) {
        return true; // No IP restriction
    }
    
    $userIP = $_SERVER['REMOTE_ADDR'] ?? '';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $userIP = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }
    
    return in_array($userIP, ALLOWED_IPS);
}

/**
 * Check if access to admin area is authorized
 */
function isAdminAccessAuthorized() {
    // Check token
    if (!hasValidAccessToken()) {
        return false;
    }
    
    // Check IP (if enabled)
    if (!isIPAllowed()) {
        return false;
    }
    
    return true;
}

/**
 * Log unauthorized access attempts
 */
function logUnauthorizedAccess() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $timestamp = date('Y-m-d H:i:s');
    $url = $_SERVER['REQUEST_URI'] ?? '';
    
    $logEntry = "[$timestamp] Unauthorized access attempt from IP: $ip, URL: $url, User-Agent: $userAgent\n";
    file_put_contents('logs/unauthorized_access.log', $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Generate secure admin URL
 */
function getSecureAdminURL($page = 'login') {
    $baseURL = 'https://devflow.stepcashier.com/'; 
    return $baseURL . ADMIN_SECRET_PATH . '/' . $page . '.php?token=' . SECURE_ACCESS_TOKEN;
}

// Create database connection
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Include PHPMailer
require 'vendor/autoload.php'; 

// Create logs directory if it doesn't exist
if (!file_exists('logs')) {
    mkdir('logs', 0755, true);
}
?>