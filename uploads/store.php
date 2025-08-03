<?php
require_once 'config.php';

header('Content-Type: application/json');

// Function to sanitize input data
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Function to send email
function sendEmail($to, $subject, $body, $attachments = []) {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($to);

        // Attachments
        foreach ($attachments as $attachment) {
            $mail->addAttachment($attachment['path'], $attachment['name']);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Function to send emails to multiple admins
function sendAdminEmails($subject, $body, $attachments = []) {
    $adminEmails = [ADMIN_EMAIL, ADMIN_EMAIL2];
    $results = [];
    
    foreach ($adminEmails as $adminEmail) {
        $results[] = sendEmail($adminEmail, $subject, $body, $attachments);
    }
    
    // Return true if at least one email was sent successfully
    return in_array(true, $results);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Validate required fields
        $required = ['fullName', 'email', 'institution', 'course', 'projectType', 'projectDescription'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }
        
        // Validate email
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }
        
        // Validate agreement
        if (empty($_POST['agreeTerms'])) {
            throw new Exception("You must agree to the academic integrity policy.");
        }
        
        // Prepare project data
        $projectData = [
            'full_name' => sanitizeInput($_POST['fullName']),
            'email' => sanitizeInput($_POST['email']),
            'institution' => sanitizeInput($_POST['institution']),
            'course' => sanitizeInput($_POST['course']),
            'project_type' => sanitizeInput($_POST['projectType']),
            'project_description' => sanitizeInput($_POST['projectDescription']),
            'needs_documentation' => !empty($_POST['req-documentation']) ? 1 : 0,
            'needs_comments' => !empty($_POST['req-comments']) ? 1 : 0,
            'needs_explanation' => !empty($_POST['req-explanation']) ? 1 : 0,
            'needs_testing' => !empty($_POST['req-testing']) ? 1 : 0,
            'needs_deployment' => !empty($_POST['req-deployment']) ? 1 : 0,
            'due_date' => !empty($_POST['dueDate']) ? date('Y-m-d', strtotime($_POST['dueDate'])) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ];
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Insert project data
        $stmt = $pdo->prepare("INSERT INTO projects (full_name, email, institution, course, project_type, project_description, needs_documentation, needs_comments, needs_explanation, needs_testing, needs_deployment, due_date, ip_address, user_agent) 
                              VALUES (:full_name, :email, :institution, :course, :project_type, :project_description, :needs_documentation, :needs_comments, :needs_explanation, :needs_testing, :needs_deployment, :due_date, :ip_address, :user_agent)");
        $stmt->execute($projectData);
        $projectId = $pdo->lastInsertId();
        
        // Process file uploads
        $uploadedFiles = [];
        if (!empty($_FILES['projectFiles']['name'][0])) {
            // Create upload directory if it doesn't exist
            if (!file_exists(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0777, true);
            }
            
            // Process each file
            foreach ($_FILES['projectFiles']['tmp_name'] as $key => $tmpName) {
                $fileName = $_FILES['projectFiles']['name'][$key];
                $fileSize = $_FILES['projectFiles']['size'][$key];
                $fileType = $_FILES['projectFiles']['type'][$key];
                $fileError = $_FILES['projectFiles']['error'][$key];
                
                // Validate file
                if ($fileError !== UPLOAD_ERR_OK) {
                    throw new Exception("Error uploading file: $fileName");
                }
                
                if ($fileSize > MAX_FILE_SIZE) {
                    throw new Exception("File too large: $fileName (max " . (MAX_FILE_SIZE / 1024 / 1024) . "MB)");
                }
                
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if (!in_array($fileExt, ALLOWED_TYPES)) {
                    throw new Exception("Invalid file type: $fileName");
                }
                
                // Generate unique filename
                $newFileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\.]/', '_', $fileName);
                $destination = UPLOAD_DIR . $newFileName;
                
                // Move uploaded file
                if (!move_uploaded_file($tmpName, $destination)) {
                    throw new Exception("Failed to move uploaded file: $fileName");
                }
                
                // Save file info to database
                $fileData = [
                    'project_id' => $projectId,
                    'file_name' => $fileName,
                    'file_path' => $destination,
                    'file_size' => $fileSize,
                    'file_type' => $fileType
                ];
                
                $stmt = $pdo->prepare("INSERT INTO project_files (project_id, file_name, file_path, file_size, file_type) 
                                      VALUES (:project_id, :file_name, :file_path, :file_size, :file_type)");
                $stmt->execute($fileData);
                
                $uploadedFiles[] = [
                    'path' => $destination,
                    'name' => $fileName
                ];
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Prepare email content
        $projectTypeLabels = [
            'assignment' => 'Coding Assignment',
            'capstone' => 'Capstone Project',
            'research' => 'Research Implementation',
            'teaching' => 'Teaching Tool',
            'thesis' => 'Thesis/Dissertation',
            'other' => 'Other Academic Project'
        ];
        
        $requirements = [];
        if ($projectData['needs_documentation']) $requirements[] = 'Detailed Documentation';
        if ($projectData['needs_comments']) $requirements[] = 'Code Comments';
        if ($projectData['needs_explanation']) $requirements[] = 'Concept Explanation';
        if ($projectData['needs_testing']) $requirements[] = 'Unit Testing';
        if ($projectData['needs_deployment']) $requirements[] = 'Deployment Help';
        
        $dueDateText = $projectData['due_date'] ? date('F j, Y', strtotime($projectData['due_date'])) : 'Not specified';
        $submissionDate = date('F j, Y \a\t g:i A');
        
        // Enhanced email template for user
        $userSubject = "✅ Project Request Received - DevFlow Studio";
        $userBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; }
                .section { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
                .highlight { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3; margin: 15px 0; border-radius: 0 5px 5px 0; }
                .requirements { background: #f3e5f5; padding: 15px; border-radius: 5px; }
                .footer { background: #2c3e50; color: white; padding: 20px; text-align: center; font-size: 14px; border-radius: 0 0 10px 10px; }
                h1 { margin: 0; font-size: 28px; }
                h2 { color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px; }
                h3 { color: #34495e; margin-top: 0; }
                .status { background: #4CAF50; color: white; padding: 8px 16px; border-radius: 20px; display: inline-block; font-weight: bold; }
                ul { padding-left: 20px; }
                li { margin: 5px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>🚀 DevFlow Studio</h1>
                <p style='margin: 10px 0 0 0; font-size: 18px;'>Your Project Request Has Been Received!</p>
            </div>
            
            <div class='content'>
                <div class='highlight'>
                    <strong>Hello {$projectData['full_name']}!</strong><br>
                    Thank you for choosing DevFlow Studio for your academic project. We've successfully received your request and our team will review it shortly.
                </div>
                
                <div class='section'>
                    <h2>📋 Project Summary</h2>
                    <p><strong>Project ID:</strong> #DS-$projectId</p>
                    <p><strong>Submission Date:</strong> $submissionDate</p>
                    <p><strong>Status:</strong> <span class='status'>Under Review</span></p>
                </div>
                
                <div class='section'>
                    <h2>🎓 Academic Details</h2>
                    <p><strong>Institution:</strong> {$projectData['institution']}</p>
                    <p><strong>Course/Program:</strong> {$projectData['course']}</p>
                    <p><strong>Project Type:</strong> {$projectTypeLabels[$projectData['project_type']]}</p>
                    <p><strong>Due Date:</strong> $dueDateText</p>
                </div>
                
                <div class='section requirements'>
                    <h3>📝 Requested Services</h3>
                    <ul>
                        <li>" . implode('</li><li>', $requirements ?: ['Standard development services']) . "</li>
                    </ul>
                </div>
                
                <div class='section'>
                    <h2>⏱️ What Happens Next?</h2>
                    <ol>
                        <li><strong>Review Process:</strong> Our team will analyze your requirements (within 3 hours)</li>
                        <li><strong>Proposal:</strong> You'll receive a detailed proposal with timeline and pricing</li>
                        <li><strong>Development:</strong> Once approved, we'll begin working on your project</li>
                        <li><strong>Delivery:</strong> Regular updates and final delivery before your due date</li>
                    </ol>
                </div>
                
                <div class='highlight'>
                    <strong>💡 Need immediate assistance?</strong><br>
                    Reply to this email or contact us directly. We're here to help ensure your academic success!
                </div>
            </div>
            
            <div class='footer'>
                <p><strong>DevFlow Studio - Academic Development Solutions</strong></p>
                <p>Professional • Reliable • Academic Integrity Focused</p>
            </div>
        </body>
        </html>
        ";
        
        // Enhanced email template for admins
        $adminSubject = "🔔 New Project Request: {$projectData['full_name']} - {$projectTypeLabels[$projectData['project_type']]}";
        
        // Build the urgent notice
        $urgentNotice = '';
        if ($projectData['due_date'] && strtotime($projectData['due_date']) <= strtotime('+7 days')) {
            $urgentNotice = "<div class='urgent'>
                <strong>⚠️ URGENT:</strong> This project has a due date within 7 days ($dueDateText)
            </div>";
        }
        
        // Build the files section
        $filesSection = '';
        if (count($uploadedFiles) > 0) {
            $filesSection = "<div class='section files-info'>
                <h2>📎 Attached Files</h2>
                <ul>";
            foreach ($uploadedFiles as $file) {
                $fileSize = round(filesize($file['path']) / 1024, 2);
                $filesSection .= "<li><strong>{$file['name']}</strong> ({$fileSize} KB)</li>";
            }
            $filesSection .= "</ul></div>";
        }
        
        $adminBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; max-width: 700px; margin: 0 auto; }
                .header { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); color: white; padding: 25px; text-align: center; }
                .content { background: #f8f9fa; padding: 25px; }
                .section { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
                .urgent { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .client-info { background: #e8f5e8; border-left: 4px solid #4CAF50; padding: 15px; }
                .project-desc { background: #f0f8ff; border-left: 4px solid #2196F3; padding: 15px; }
                .action-btn { background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
                .files-info { background: #fff2e6; border-left: 4px solid #ff9800; padding: 15px; }
                h1 { margin: 0; font-size: 24px; }
                h2 { color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 8px; }
                .meta-info { font-size: 12px; color: #666; background: #f1f3f4; padding: 10px; border-radius: 5px; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background: #f8f9fa; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>🚨 New Project Request Alert</h1>
                <p style='margin: 5px 0 0 0;'>DevFlow Studio Admin Panel</p>
            </div>
            
            <div class='content'>
                $urgentNotice
                
                <div class='section client-info'>
                    <h2>👤 Client Information</h2>
                    <table>
                        <tr><th>Name</th><td>{$projectData['full_name']}</td></tr>
                        <tr><th>Email</th><td><a href='mailto:{$projectData['email']}'>{$projectData['email']}</a></td></tr>
                        <tr><th>Institution</th><td>{$projectData['institution']}</td></tr>
                        <tr><th>Course</th><td>{$projectData['course']}</td></tr>
                        <tr><th>Submission</th><td>$submissionDate</td></tr>
                    </table>
                </div>
                
                <div class='section'>
                    <h2>📊 Project Overview</h2>
                    <table>
                        <tr><th>Project ID</th><td>#DS-$projectId</td></tr>
                        <tr><th>Type</th><td>{$projectTypeLabels[$projectData['project_type']]}</td></tr>
                        <tr><th>Due Date</th><td>$dueDateText</td></tr>
                        <tr><th>Files Attached</th><td>" . count($uploadedFiles) . " file(s)</td></tr>
                    </table>
                </div>
                
                <div class='section'>
                    <h2>🔧 Required Services</h2>
                    <ul style='columns: 2; column-gap: 20px;'>
                        <li>" . implode('</li><li>', $requirements ?: ['Standard development services']) . "</li>
                    </ul>
                </div>
                
                <div class='section project-desc'>
                    <h2>📝 Project Description</h2>
                    <p style='white-space: pre-wrap; font-family: Georgia, serif; font-style: italic;'>{$projectData['project_description']}</p>
                </div>
                
                $filesSection
                
                <div class='section' style='text-align: center; padding: 30px;'>
                    <h2>🎯 Quick Actions</h2>
                    <a href='http://devflowstudio.com/admin/projects/view.php?id=$projectId' class='action-btn'>View Full Details</a>
                    <a href='mailto:{$projectData['email']}?subject=Re: Your DevFlow Studio Project Request' class='action-btn' style='background: #28a745;'>Reply to Client</a>
                </div>
                
                <div class='meta-info'>
                    <strong>Technical Details:</strong><br>
                    IP Address: {$projectData['ip_address']} | User Agent: " . substr($projectData['user_agent'], 0, 100) . "...
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Send emails
        $userEmailSent = sendEmail($projectData['email'], $userSubject, $userBody);
        $adminEmailSent = sendAdminEmails($adminSubject, $adminBody, $uploadedFiles);
        
        if (!$userEmailSent || !$adminEmailSent) {
            error_log("Email sending failed for project ID: $projectId. User: " . ($userEmailSent ? 'Success' : 'Failed') . ", Admin: " . ($adminEmailSent ? 'Success' : 'Failed'));
        }
        
        $response['success'] = true;
        $response['message'] = 'Project submitted successfully! Check your email for confirmation.';
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $response['message'] = $e->getMessage();
        error_log("Project submission error: " . $e->getMessage());
    }
    
    echo json_encode($response);
    exit;
}

// If not a POST request
echo json_encode(['success' => false, 'message' => 'Invalid request method']);
?>