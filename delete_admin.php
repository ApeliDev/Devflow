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
    $adminId = $_GET['id'] ?? 0;
    
    if (empty($adminId)) {
        throw new Exception("Admin ID is required");
    }
    
    // Prevent deleting yourself
    if ($adminId == $_SESSION['admin_id']) {
        throw new Exception("You cannot delete your own account");
    }
    
    // Delete admin
    $stmt = $pdo->prepare("DELETE FROM admin_users WHERE id = ?");
    $stmt->execute([$adminId]);
    
    echo json_encode(['success' => true, 'message' => 'Admin user deleted successfully']);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}