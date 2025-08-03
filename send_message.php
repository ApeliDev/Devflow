<?php
require_once 'auth.php';

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
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validate input
    if (empty($projectId) || empty($subject) || empty($message)) {
        throw new Exception('All fields are required');
    }
    
    // Check if project exists
    $stmt = $pdo->prepare("SELECT id, email FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);
    $project = $stmt->fetch();
    
    if (!$project) {
        throw new Exception('Project not found');
    }
    
    // Insert message into database
    $stmt = $pdo->prepare("INSERT INTO admin_messages (project_id, admin_id, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$projectId, $_SESSION['admin_id'], $subject, $message]);
    
    // Send email to client
    $emailSubject = "DevFlow Studio: " . $subject;
    $emailBody = "
        <p>Dear {$project['full_name']},</p>
        <p>{$message}</p>
        <p>You can view this message in your project dashboard.</p>
        <p>Best regards,<br>DevFlow Studio Team</p>
    ";
    
    // Use your existing sendEmail function
    $emailSent = sendEmail($project['email'], $emailSubject, $emailBody);
    
    if (!$emailSent) {
        error_log("Failed to send email for project ID: $projectId");
    }
    
    echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}