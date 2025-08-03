<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

try {
    $adminId = $_GET['id'] ?? 0;
    
    if (empty($adminId)) {
        throw new Exception("Admin ID is required");
    }
    
    $stmt = $pdo->prepare("SELECT id, full_name, username, email, role, is_active FROM admin_users WHERE id = ?");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        throw new Exception("Admin not found");
    }
    
    echo json_encode(['success' => true, 'admin' => $admin]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}