<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $required = ['full_name', 'username', 'email', 'password', 'role'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    $fullName = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email address");
    }
    
    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        throw new Exception("Username or email already exists");
    }
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new admin
    $stmt = $pdo->prepare("INSERT INTO admin_users (full_name, username, email, password_hash, role, is_active) 
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$fullName, $username, $email, $passwordHash, $role, $isActive]);
    
    echo json_encode(['success' => true, 'message' => 'Admin user created successfully']);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}