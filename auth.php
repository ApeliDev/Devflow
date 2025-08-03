<?php
session_start();
require_once 'config.php';

// ==== SECURITY CHECK - Token-based access control ====
if (!isAdminAccessAuthorized()) {
    // Log the unauthorized attempt
    logUnauthorizedAccess();
    
    // Show generic 404 error
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>Page Not Found</title></head>
    <body style="font-family: Arial, sans-serif; text-align: center; padding: 50px;">
        <h1>404 - Page Not Found</h1>
        <p>The requested page could not be found.</p>
    </body>
    </html>
    <?php
    exit;
}

// Check if user is authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    // Redirect to login with token
    header('Location: login.php?token=' . SECURE_ACCESS_TOKEN);
    exit;
}

// Optional: Check session timeout (30 minutes of inactivity)
$timeout_duration = 1800; // 30 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // Session expired
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['timeout_message'] = 'Your session has expired. Please login again.';
    header('Location: login.php?token=' . SECURE_ACCESS_TOKEN);
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Optional: Verify session token matches current token
if (isset($_SESSION['secure_token']) && $_SESSION['secure_token'] !== SECURE_ACCESS_TOKEN) {
    // Token mismatch - possible security issue
    session_unset();
    session_destroy();
    header('Location: login.php?token=' . SECURE_ACCESS_TOKEN);
    exit;
}

// Function to generate secure URLs for navigation
function secureUrl($page) {
    return $page . '?token=' . SECURE_ACCESS_TOKEN;
}