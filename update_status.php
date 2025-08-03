<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $projectId = $_POST['project_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    $message = trim($_POST['message'] ?? '');
    
    // Validate input
    if (empty($projectId) || empty($status)) {
        throw new Exception('Project ID and status are required');
    }
    
    if (!in_array($status, ['pending', 'reviewed', 'in_progress', 'completed', 'cancelled'])) {
        throw new Exception('Invalid status value');
    }
    
    // Check if project exists
    $stmt = $pdo->prepare("SELECT id, status, email, full_name FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);
    $project = $stmt->fetch();
    
    if (!$project) {
        throw new Exception('Project not found');
    }
    
    // Update project status
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("UPDATE projects SET status = ? WHERE id = ?");
    $stmt->execute([$status, $projectId]);
    
    // Record status update
    $stmt = $pdo->prepare("INSERT INTO project_updates (project_id, admin_id, status, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$projectId, $_SESSION['admin_id'], $status, $message]);
    
    $pdo->commit();
    
    // Send email notification if status changed significantly
    if ($project['status'] !== $status && in_array($status, ['completed', 'cancelled'])) {
        $statusLabels = [
            'completed' => 'Completed',
            'cancelled' => 'Cancelled'
        ];
        
        $emailSubject = "DevFlow Studio: Project Status Update";
        $emailBody = "
            <p>Dear {$project['full_name']},</p>
            <p>Your project status has been updated to: <strong>{$statusLabels[$status]}</strong></p>
            " . ($message ? "<p>Message from our team:<br>{$message}</p>" : "") . "
            <p>You can view your project details in your dashboard.</p>
            <p>Best regards,<br>DevFlow Studio Team</p>
        ";
        
        sendEmail($project['email'], $emailSubject, $emailBody);
    }
    
    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}