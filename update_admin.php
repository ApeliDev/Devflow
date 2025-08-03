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
    $required = ['admin_id', 'full_name', 'username', 'email', 'role'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    $adminId = $_POST['admin_id'];
    $fullName = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'] ?? null;
    $role = $_POST['role'];
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email address");
    }
    
    // Check if username or email already exists for another admin
    $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->execute([$username, $email, $adminId]);
    if ($stmt->fetch()) {
        throw new Exception("Username or email already exists for another admin");
    }
    
    // Update admin
    if ($password) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admin_users SET 
                              full_name = ?, username = ?, email = ?, password_hash = ?, role = ?, is_active = ? 
                              WHERE id = ?");
        $stmt->execute([$fullName, $username, $email, $passwordHash, $role, $isActive, $adminId]);
    } else {
        $stmt = $pdo->prepare("UPDATE admin_users SET 
                              full_name = ?, username = ?, email = ?, role = ?, is_active = ? 
                              WHERE id = ?");
        $stmt->execute([$fullName, $username, $email, $role, $isActive, $adminId]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Admin user updated successfully']);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}