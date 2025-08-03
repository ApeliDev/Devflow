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
        // Validate required fields - matching HTML form names exactly
        $required = ['fullName', 'email', 'institution', 'course', 'projectType', 'projectDescription'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields. Missing: $field");
            }
        }
        
        // Validate email
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }
        
        // Validate agreement - matching HTML form name exactly
        if (empty($_POST['agreeTerms'])) {
            throw new Exception("You must agree to the academic integrity policy.");
        }
        
        // Process requirements array - matching HTML checkbox names
        $requirements = isset($_POST['requirements']) ? $_POST['requirements'] : [];
        
        // Prepare project data with exact field name matching
        $projectData = [
            'full_name' => sanitizeInput($_POST['fullName']),
            'email' => sanitizeInput($_POST['email']),
            'institution' => sanitizeInput($_POST['institution']),
            'course' => sanitizeInput($_POST['course']),
            'project_type' => sanitizeInput($_POST['projectType']),
            'project_description' => sanitizeInput($_POST['projectDescription']),
            'needs_documentation' => in_array('detailed_documentation', $requirements) ? 1 : 0,
            'needs_comments' => in_array('code_comments', $requirements) ? 1 : 0,
            'needs_explanation' => in_array('concept_explanation', $requirements) ? 1 : 0,
            'needs_testing' => in_array('unit_testing', $requirements) ? 1 : 0,
            'needs_deployment' => in_array('deployment_help', $requirements) ? 1 : 0,
            'due_date' => !empty($_POST['dueDate']) ? date('Y-m-d', strtotime($_POST['dueDate'])) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Validate project type against allowed values
        $allowedProjectTypes = ['assignment', 'capstone', 'research', 'teaching', 'thesis', 'other'];
        if (!in_array($projectData['project_type'], $allowedProjectTypes)) {
            throw new Exception("Invalid project type selected.");
        }
        
        // Validate due date if provided
        if (!empty($_POST['dueDate'])) {
            $dueDate = strtotime($_POST['dueDate']);
            if ($dueDate === false || $dueDate < strtotime('today')) {
                throw new Exception("Please enter a valid future due date.");
            }
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Insert project data
        $stmt = $pdo->prepare("
            INSERT INTO projects (
                full_name, 
                email, 
                institution, 
                course, 
                project_type, 
                project_description, 
                needs_documentation, 
                needs_comments, 
                needs_explanation, 
                needs_testing, 
                needs_deployment, 
                due_date, 
                ip_address, 
                user_agent,
                created_at
            ) VALUES (
                :full_name, 
                :email, 
                :institution, 
                :course, 
                :project_type, 
                :project_description, 
                :needs_documentation, 
                :needs_comments, 
                :needs_explanation, 
                :needs_testing, 
                :needs_deployment, 
                :due_date, 
                :ip_address, 
                :user_agent,
                :created_at
            )
        ");
        
        if (!$stmt->execute($projectData)) {
            throw new Exception("Failed to save project data to database.");
        }
        
        $projectId = $pdo->lastInsertId();
        
        // Process file uploads - matching HTML form name exactly
        $uploadedFiles = [];
        if (!empty($_FILES['projectFiles']['name'][0])) {
            // Create upload directory if it doesn't exist
            if (!file_exists(UPLOAD_DIR)) {
                if (!mkdir(UPLOAD_DIR, 0755, true)) {
                    throw new Exception("Failed to create upload directory.");
                }
            }
            
            // Validate file upload directory is writable
            if (!is_writable(UPLOAD_DIR)) {
                throw new Exception("Upload directory is not writable.");
            }
            
            // Process each file
            $fileCount = count($_FILES['projectFiles']['tmp_name']);
            for ($i = 0; $i < $fileCount; $i++) {
                $tmpName = $_FILES['projectFiles']['tmp_name'][$i];
                $fileName = $_FILES['projectFiles']['name'][$i];
                $fileSize = $_FILES['projectFiles']['size'][$i];
                $fileType = $_FILES['projectFiles']['type'][$i];
                $fileError = $_FILES['projectFiles']['error'][$i];
                
                // Skip empty file slots
                if (empty($fileName) || empty($tmpName)) {
                    continue;
                }
                
                // Validate file upload
                if ($fileError !== UPLOAD_ERR_OK) {
                    $errorMessages = [
                        UPLOAD_ERR_INI_SIZE => 'File is too large (server limit)',
                        UPLOAD_ERR_FORM_SIZE => 'File is too large (form limit)',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
                    ];
                    
                    $errorMsg = isset($errorMessages[$fileError]) ? $errorMessages[$fileError] : 'Unknown upload error';
                    throw new Exception("Error uploading file '$fileName': $errorMsg");
                }
                
                // Validate file size
                if ($fileSize > MAX_FILE_SIZE) {
                    $maxSizeMB = round(MAX_FILE_SIZE / 1024 / 1024, 1);
                    throw new Exception("File '$fileName' is too large. Maximum size is {$maxSizeMB}MB.");
                }
                
                // Validate file type
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if (!in_array($fileExt, ALLOWED_TYPES)) {
                    $allowedTypesStr = implode(', ', ALLOWED_TYPES);
                    throw new Exception("Invalid file type for '$fileName'. Allowed types: $allowedTypesStr");
                }
                
                // Additional MIME type validation
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $tmpName);
                finfo_close($finfo);
                
                $allowedMimes = [
                    'pdf' => 'application/pdf',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'doc' => 'application/msword',
                    'txt' => 'text/plain',
                    'zip' => 'application/zip'
                ];
                
                if (isset($allowedMimes[$fileExt]) && $mimeType !== $allowedMimes[$fileExt]) {
                    throw new Exception("File '$fileName' appears to be corrupted or has an invalid format.");
                }
                
                // Generate secure filename
                $fileBaseName = pathinfo($fileName, PATHINFO_FILENAME);
                $fileBaseName = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $fileBaseName);
                $fileBaseName = substr($fileBaseName, 0, 100); // Limit length
                $newFileName = uniqid() . '_' . time() . '_' . $fileBaseName . '.' . $fileExt;
                $destination = UPLOAD_DIR . $newFileName;
                
                // Move uploaded file
                if (!move_uploaded_file($tmpName, $destination)) {
                    throw new Exception("Failed to save uploaded file: $fileName");
                }
                
                // Set proper file permissions
                chmod($destination, 0644);
                
                // Save file info to database
                $fileData = [
                    'project_id' => $projectId,
                    'file_name' => $fileName,
                    'file_path' => $destination,
                    'file_size' => $fileSize,
                    'file_type' => $mimeType,
                    'file_extension' => $fileExt,
                    'uploaded_at' => date('Y-m-d H:i:s')
                ];
                
                $stmt = $pdo->prepare("
                    INSERT INTO project_files (
                        project_id, 
                        file_name, 
                        file_path, 
                        file_size, 
                        file_type, 
                        file_extension,
                        uploaded_at
                    ) VALUES (
                        :project_id, 
                        :file_name, 
                        :file_path, 
                        :file_size, 
                        :file_type, 
                        :file_extension,
                        :uploaded_at
                    )
                ");
                
                if (!$stmt->execute($fileData)) {
                    throw new Exception("Failed to save file information for: $fileName");
                }
                
                $uploadedFiles[] = [
                    'path' => $destination,
                    'name' => $fileName,
                    'size' => $fileSize
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
        
        $requirementLabels = [];
        if ($projectData['needs_documentation']) $requirementLabels[] = 'Detailed Documentation';
        if ($projectData['needs_comments']) $requirementLabels[] = 'Code Comments';
        if ($projectData['needs_explanation']) $requirementLabels[] = 'Concept Explanation';
        if ($projectData['needs_testing']) $requirementLabels[] = 'Unit Testing';
        if ($projectData['needs_deployment']) $requirementLabels[] = 'Deployment Help';
        
        $dueDateText = $projectData['due_date'] ? date('F j, Y', strtotime($projectData['due_date'])) : 'Not specified';
        $submissionDate = date('F j, Y \a\t g:i A');
        
    

        $userSubject = "Project Request Received - DevFlow Studio (#DS-$projectId)";
        $userBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
                
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body { 
                    font-family: 'Inter', sans-serif; 
                    line-height: 1.6; 
                    color: #1a1a1a; 
                    background: linear-gradient(135deg, #f0f8f0 0%, #e8f5e8 100%);
                    min-height: 100vh;
                    padding: 20px 0;
                    display: flex;
                    justify-content: center;
                    align-items: flex-start;
                }
                
                .email-wrapper {
                    width: 100%;
                    max-width: 650px;
                    margin: 0 auto;
                    background: transparent;
                }
                
                .email-container { 
                    background: #ffffff; 
                    border-radius: 20px; 
                    overflow: hidden; 
                    box-shadow: 0 20px 40px rgba(0,0,0,0.08), 0 8px 16px rgba(0,0,0,0.04);
                    border: 1px solid rgba(34, 139, 34, 0.1);
                    margin: 0 auto;
                }
                
                .header { 
                    background: linear-gradient(135deg, #228b22 0%, #32cd32 50%, #ffd700 100%); 
                    color: white; 
                    padding: 50px 40px; 
                    text-align: center; 
                    position: relative;
                    overflow: hidden;
                }
                
                .header::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\"><defs><pattern id=\"grain\" width=\"100\" height=\"100\" patternUnits=\"userSpaceOnUse\"><circle cx=\"25\" cy=\"25\" r=\"1\" fill=\"rgba(255,255,255,0.1)\"/><circle cx=\"75\" cy=\"75\" r=\"1\" fill=\"rgba(255,255,255,0.05)\"/><circle cx=\"50\" cy=\"10\" r=\"0.5\" fill=\"rgba(255,255,255,0.08)\"/></pattern></defs><rect width=\"100\" height=\"100\" fill=\"url(%23grain)\"/></svg>');
                    opacity: 0.3;
                }
                
                .header-content {
                    position: relative;
                    z-index: 1;
                }
                
                .content { 
                    padding: 40px; 
                }
                
                .section { 
                    margin-bottom: 30px; 
                }
                
                .icon { 
                    display: inline-block; 
                    width: 28px; 
                    height: 28px; 
                    margin-right: 12px; 
                    vertical-align: middle; 
                    background-size: contain; 
                    background-repeat: no-repeat; 
                }
                
                .status-badge { 
                    background: linear-gradient(135deg, #32cd32, #228b22); 
                    color: white; 
                    padding: 12px 24px; 
                    border-radius: 25px; 
                    font-size: 14px; 
                    font-weight: 600; 
                    display: inline-block; 
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    box-shadow: 0 4px 12px rgba(50, 205, 50, 0.3);
                }
                
                .card { 
                    background: #ffffff; 
                    border-radius: 16px; 
                    padding: 30px; 
                    margin-bottom: 25px; 
                    box-shadow: 0 4px 20px rgba(0,0,0,0.04); 
                    border: 2px solid transparent;
                    background-clip: padding-box;
                    position: relative;
                    transition: all 0.3s ease;
                }
                
                .card::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    border-radius: 16px;
                    padding: 2px;
                    background: linear-gradient(135deg, #ffd700, #32cd32, #228b22);
                    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
                    -webkit-mask-composite: exclude;
                    mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
                    mask-composite: exclude;
                }
                
                .card:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 30px rgba(0,0,0,0.08);
                }
                
                .requirements-list { 
                    columns: 2; 
                    column-gap: 30px; 
                    list-style: none;
                }
                
                .requirements-list li { 
                    margin-bottom: 12px; 
                    padding-left: 20px;
                    position: relative;
                }
                
                .requirements-list li::before {
                    content: '✓';
                    position: absolute;
                    left: 0;
                    color: #32cd32;
                    font-weight: bold;
                    font-size: 16px;
                }
                
                .divider { 
                    height: 2px; 
                    background: linear-gradient(to right, transparent, rgba(50, 205, 50, 0.3), rgba(255, 215, 0, 0.3), transparent); 
                    margin: 25px 0; 
                    border-radius: 1px;
                }
                
                .footer { 
                    background: linear-gradient(135deg, #1a5d1a 0%, #2d7d2d 100%); 
                    color: #e8f5e8; 
                    padding: 40px; 
                    text-align: center; 
                    font-size: 14px; 
                }
                
                .social-links { 
                    margin: 25px 0; 
                }
                
                .social-links a { 
                    display: inline-block; 
                    margin: 0 12px; 
                    padding: 8px;
                    border-radius: 50%;
                    background: rgba(255, 215, 0, 0.1);
                    transition: all 0.3s ease;
                }
                
                .social-links a:hover {
                    background: rgba(255, 215, 0, 0.2);
                    transform: translateY(-2px);
                }
                
                .footer-links { 
                    margin-bottom: 25px; 
                }
                
                .footer-links a { 
                    color: #e8f5e8; 
                    text-decoration: none; 
                    margin: 0 15px; 
                    font-weight: 500;
                    transition: color 0.3s ease;
                }
                
                .footer-links a:hover { 
                    color: #ffd700; 
                }
                
                h1 { 
                    margin: 0 0 15px 0; 
                    font-size: 32px; 
                    font-weight: 700; 
                    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                
                h2 { 
                    color: #228b22; 
                    font-size: 22px; 
                    font-weight: 600; 
                    margin: 0 0 20px 0; 
                    display: flex;
                    align-items: center;
                }
                
                p { 
                    margin: 0 0 16px 0; 
                    color: #333;
                }
                
                ul, ol { 
                    padding-left: 25px; 
                    margin: 0 0 16px 0; 
                }
                
                ol li {
                    margin-bottom: 12px;
                    color: #555;
                }
                
                .text-primary { 
                    color: #228b22 !important; 
                }
                
                .text-center { 
                    text-align: center; 
                }
                
                .mb-4 { 
                    margin-bottom: 25px; 
                }
                
                .mt-4 { 
                    margin-top: 25px; 
                }
                
                .highlight-card {
                    background: linear-gradient(135deg, rgba(50, 205, 50, 0.05), rgba(255, 215, 0, 0.05));
                    border-left: 4px solid #ffd700;
                }
                
                .logo-placeholder {
                    background: linear-gradient(135deg, #228b22, #ffd700);
                    color: white;
                    padding: 15px 30px;
                    border-radius: 12px;
                    font-weight: 700;
                    font-size: 18px;
                    letter-spacing: 1px;
                    display: inline-block;
                    margin-bottom: 20px;
                }
                
                @media (max-width: 600px) {
                    .email-wrapper {
                        padding: 0 15px;
                    }
                    
                    .content {
                        padding: 25px;
                    }
                    
                    .header {
                        padding: 35px 25px;
                    }
                    
                    h1 {
                        font-size: 26px;
                    }
                    
                    .requirements-list {
                        columns: 1;
                    }
                    
                    .card {
                        padding: 20px;
                    }
                }
            </style>
        </head>
        <body>
            <div class='email-wrapper'>
                <div class='email-container'>
                    <div class='header'>
                        <div class='header-content'>
                            <h1>Your Project Request Has Been Received</h1>
                            <p style='font-size: 16px; margin: 0; opacity: 0.9;'>We're excited to work with you on your academic project</p>
                        </div>
                    </div>
                    
                    <div class='content'>
                        <div class='card highlight-card'>
                            <div class='text-center mb-4'>
                                <span class='status-badge'>Under Review</span>
                            </div>
                            <p>Hello <strong>{$projectData['full_name']}</strong>,</p>
                            <p>Thank you for choosing DevFlow Studio for your academic project. We've received your request and our team will review it shortly.</p>
                        </div>
                        
                        <div class='card'>
                            <h2><span class='icon' style='background-image: url(\"https://img.icons8.com/fluency/48/32cd32/approval.png\");'></span> Project Summary</h2>
                            <div class='divider'></div>
                            <p><strong>Project ID:</strong> #DS-$projectId</p>
                            <p><strong>Submission Date:</strong> $submissionDate</p>
                            <p><strong>Due Date:</strong> $dueDateText</p>
                        </div>
                        
                        <div class='card'>
                            <h2><span class='icon' style='background-image: url(\"https://img.icons8.com/color/48/ffd700/university.png\");'></span> Academic Details</h2>
                            <div class='divider'></div>
                            <p><strong>Institution:</strong> {$projectData['institution']}</p>
                            <p><strong>Course/Program:</strong> {$projectData['course']}</p>
                            <p><strong>Project Type:</strong> {$projectTypeLabels[$projectData['project_type']]}</p>
                        </div>
                        
                        <div class='card'>
                            <h2><span class='icon' style='background-image: url(\"https://img.icons8.com/color/48/32cd32/task-completed.png\");'></span> Requested Services</h2>
                            <div class='divider'></div>
                            <ul class='requirements-list'>
                                <li>" . implode('</li><li>', $requirementLabels ?: ['Standard development services']) . "</li>
                            </ul>
                        </div>
                        
                        <div class='card'>
                            <h2><span class='icon' style='background-image: url(\"https://img.icons8.com/color/48/ffd700/clock.png\");'></span> Next Steps</h2>
                            <div class='divider'></div>
                            <ol>
                                <li><strong>Review Process:</strong> Our team will analyze your requirements (within 3 hours)</li>
                                <li><strong>Proposal:</strong> You'll receive a detailed proposal with timeline and pricing</li>
                                <li><strong>Development:</strong> Once approved, we'll begin working on your project</li>
                                <li><strong>Delivery:</strong> Regular updates and final delivery before your due date</li>
                            </ol>
                        </div>
                        
                        <div class='card highlight-card text-center'>
                            <h2 class='text-primary'>Need immediate assistance?</h2>
                            <p style='font-size: 16px; color: #555;'>Reply to this email or contact us directly. We're here to help ensure your academic success!</p>
                        </div>
                    </div>
                    
                    <div class='footer'>
                        <div class='logo-placeholder'>
                            DevFlow Studio
                        </div>
                        
                        <div class='social-links'>
                            <a href='#'><img src='https://img.icons8.com/fluency/24/ffffff/facebook-new.png' alt='Facebook'></a>
                            <a href='#'><img src='https://img.icons8.com/fluency/24/ffffff/twitter.png' alt='Twitter'></a>
                            <a href='#'><img src='https://img.icons8.com/fluency/24/ffffff/linkedin.png' alt='LinkedIn'></a>
                            <a href='#'><img src='https://img.icons8.com/fluency/24/ffffff/instagram-new.png' alt='Instagram'></a>
                        </div>
                        
                        <div class='footer-links'>
                            <a href='#'>Our Services</a>
                            <a href='#'>Privacy Policy</a>
                            <a href='#'>Terms of Service</a>
                            <a href='#'>Contact Us</a>
                        </div>
                        
                        <p style='margin-bottom: 10px;'>© " . date('Y') . " DevFlow Studio. All rights reserved.</p>
                        <p style='font-size: 12px; color: #b8d4b8;'>This email was sent to {$projectData['email']} because you requested our services.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";

        // Enhanced admin email template matching user email theme
        $adminSubject = "New Project Request: {$projectData['full_name']} - {$projectTypeLabels[$projectData['project_type']]} (#DS-$projectId)";

        // Build the urgent notice
        $urgentNotice = '';
        if ($projectData['due_date'] && strtotime($projectData['due_date']) <= strtotime('+7 days')) {
            $daysLeft = ceil((strtotime($projectData['due_date']) - time()) / 86400);
            $urgentNotice = "<div class='card urgent-card'>
                <div class='urgent-badge'>⚠️ URGENT</div>
                <p><strong>This project has a due date in $daysLeft day(s) ($dueDateText)</strong></p>
                <p style='color: #dc3545; font-size: 14px; margin-bottom: 0;'>Immediate attention required!</p>
            </div>";
        }

        // Build the files section
        $filesSection = '';
        if (count($uploadedFiles) > 0) {
            $filesSection = "<div class='card'>
                <h2><span class='icon' style='background-image: url(\"https://img.icons8.com/color/48/32cd32/attach.png\");'></span> Attached Files</h2>
                <div class='divider'></div>
                <ul class='files-list'>";
            foreach ($uploadedFiles as $file) {
                $fileSize = round($file['size'] / 1024, 2);
                $filesSection .= "<li><strong>{$file['name']}</strong> <span class='file-size'>({$fileSize} KB)</span></li>";
            }
            $filesSection .= "</ul></div>";
        }

        $adminBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
                
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body { 
                    font-family: 'Inter', sans-serif; 
                    line-height: 1.6; 
                    color: #1a1a1a; 
                    background: linear-gradient(135deg, #f0f8ff 0%, #e6f3ff 100%);
                    min-height: 100vh;
                    padding: 20px 0;
                    display: flex;
                    justify-content: center;
                    align-items: flex-start;
                }
                
                .email-wrapper {
                    width: 100%;
                    max-width: 700px;
                    margin: 0 auto;
                    background: transparent;
                }
                
                .email-container { 
                    background: #ffffff; 
                    border-radius: 20px; 
                    overflow: hidden; 
                    box-shadow: 0 20px 40px rgba(0,0,0,0.08), 0 8px 16px rgba(0,0,0,0.04);
                    border: 1px solid rgba(67, 97, 238, 0.1);
                    margin: 0 auto;
                }
                
                .header { 
                    background: linear-gradient(135deg, #4361ee 0%, #7209b7 50%, #f72585 100%); 
                    color: white; 
                    padding: 50px 40px; 
                    text-align: center; 
                    position: relative;
                    overflow: hidden;
                }
                
                .header::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\"><defs><pattern id=\"grain\" width=\"100\" height=\"100\" patternUnits=\"userSpaceOnUse\"><circle cx=\"25\" cy=\"25\" r=\"1\" fill=\"rgba(255,255,255,0.1)\"/><circle cx=\"75\" cy=\"75\" r=\"1\" fill=\"rgba(255,255,255,0.05)\"/><circle cx=\"50\" cy=\"10\" r=\"0.5\" fill=\"rgba(255,255,255,0.08)\"/></pattern></defs><rect width=\"100\" height=\"100\" fill=\"url(%23grain)\"/></svg>');
                    opacity: 0.3;
                }
                
                .header-content {
                    position: relative;
                    z-index: 1;
                }
                
                .admin-badge {
                    background: rgba(255, 255, 255, 0.2);
                    color: white;
                    padding: 8px 20px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: 600;
                    display: inline-block;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    margin-bottom: 15px;
                    border: 1px solid rgba(255, 255, 255, 0.3);
                }
                
                .content { 
                    padding: 40px; 
                }
                
                .section { 
                    margin-bottom: 30px; 
                }
                
                .icon { 
                    display: inline-block; 
                    width: 28px; 
                    height: 28px; 
                    margin-right: 12px; 
                    vertical-align: middle; 
                    background-size: contain; 
                    background-repeat: no-repeat; 
                }
                
                .urgent-badge { 
                    background: linear-gradient(135deg, #ff6b6b, #ee5a24); 
                    color: white; 
                    padding: 12px 24px; 
                    border-radius: 25px; 
                    font-size: 14px; 
                    font-weight: 600; 
                    display: inline-block; 
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    box-shadow: 0 4px 12px rgba(238, 90, 36, 0.3);
                    margin-bottom: 15px;
                }
                
                .card { 
                    background: #ffffff; 
                    border-radius: 16px; 
                    padding: 30px; 
                    margin-bottom: 25px; 
                    box-shadow: 0 4px 20px rgba(0,0,0,0.04); 
                    border: 2px solid transparent;
                    background-clip: padding-box;
                    position: relative;
                    transition: all 0.3s ease;
                }
                
                .card::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    border-radius: 16px;
                    padding: 2px;
                    background: linear-gradient(135deg, #4361ee, #7209b7, #f72585);
                    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
                    -webkit-mask-composite: exclude;
                    mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
                    mask-composite: exclude;
                }
                
                .urgent-card {
                    background: linear-gradient(135deg, rgba(255, 107, 107, 0.05), rgba(238, 90, 36, 0.05));
                    border-left: 4px solid #ff6b6b;
                    text-align: center;
                }
                
                .urgent-card::before {
                    background: linear-gradient(135deg, #ff6b6b, #ee5a24);
                }
                
                .client-card::before {
                    background: linear-gradient(135deg, #00d2d3, #54a0ff);
                }
                
                .project-card::before {
                    background: linear-gradient(135deg, #5f27cd, #341f97);
                }
                
                .action-card {
                    background: linear-gradient(135deg, rgba(67, 97, 238, 0.05), rgba(114, 9, 183, 0.05));
                    text-align: center;
                }
                
                .card:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 30px rgba(0,0,0,0.08);
                }
                
                .info-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                
                .info-table th {
                    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
                    color: #495057;
                    font-weight: 600;
                    padding: 15px;
                    text-align: left;
                    border-bottom: 2px solid #dee2e6;
                    width: 35%;
                    font-size: 14px;
                }
                
                .info-table td {
                    padding: 15px;
                    border-bottom: 1px solid #e9ecef;
                    color: #333;
                }
                
                .info-table tr:hover {
                    background: rgba(67, 97, 238, 0.02);
                }
                
                .files-list {
                    list-style: none;
                    padding: 0;
                }
                
                .files-list li {
                    padding: 12px 0;
                    border-bottom: 1px solid #f1f3f4;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                
                .files-list li:last-child {
                    border-bottom: none;
                }
                
                .file-size {
                    color: #6c757d;
                    font-size: 13px;
                    font-weight: 500;
                }
                
                .requirements-list { 
                    columns: 2; 
                    column-gap: 30px; 
                    list-style: none;
                }
                
                .requirements-list li { 
                    margin-bottom: 12px; 
                    padding-left: 20px;
                    position: relative;
                }
                
                .requirements-list li::before {
                    content: '✓';
                    position: absolute;
                    left: 0;
                    color: #4361ee;
                    font-weight: bold;
                    font-size: 16px;
                }
                
                .btn {
                    display: inline-block;
                    padding: 14px 28px;
                    background: linear-gradient(135deg, #4361ee, #7209b7);
                    color: white;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: 600;
                    margin: 8px;
                    transition: all 0.3s ease;
                    font-size: 14px;
                    letter-spacing: 0.5px;
                }
                
                .btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(67, 97, 238, 0.3);
                }
                
                .btn-secondary {
                    background: linear-gradient(135deg, #6c757d, #495057);
                }
                
                .btn-secondary:hover {
                    box-shadow: 0 8px 20px rgba(108, 117, 125, 0.3);
                }
                
                .divider { 
                    height: 2px; 
                    background: linear-gradient(to right, transparent, rgba(67, 97, 238, 0.3), rgba(247, 37, 133, 0.3), transparent); 
                    margin: 25px 0; 
                    border-radius: 1px;
                }
                
                .footer { 
                    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); 
                    color: #ecf0f1; 
                    padding: 40px; 
                    text-align: center; 
                    font-size: 14px; 
                }
                
                .meta-info {
                    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
                    padding: 20px;
                    border-radius: 12px;
                    font-size: 12px;
                    color: #6c757d;
                    margin-top: 20px;
                    border-left: 4px solid #dee2e6;
                }
                
                .logo-placeholder {
                    background: linear-gradient(135deg, #4361ee, #f72585);
                    color: white;
                    padding: 15px 30px;
                    border-radius: 12px;
                    font-weight: 700;
                    font-size: 18px;
                    letter-spacing: 1px;
                    display: inline-block;
                    margin-bottom: 20px;
                }
                
                h1 { 
                    margin: 0 0 15px 0; 
                    font-size: 32px; 
                    font-weight: 700; 
                    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                
                h2 { 
                    color: #4361ee; 
                    font-size: 22px; 
                    font-weight: 600; 
                    margin: 0 0 20px 0; 
                    display: flex;
                    align-items: center;
                }
                
                p { 
                    margin: 0 0 16px 0; 
                    color: #333;
                }
                
                .description-text {
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 8px;
                    border-left: 4px solid #4361ee;
                    white-space: pre-wrap;
                    font-family: 'Courier New', monospace;
                    font-size: 14px;
                    line-height: 1.6;
                }
                
                @media (max-width: 600px) {
                    .email-wrapper {
                        padding: 0 15px;
                    }
                    
                    .content {
                        padding: 25px;
                    }
                    
                    .header {
                        padding: 35px 25px;
                    }
                    
                    h1 {
                        font-size: 26px;
                    }
                    
                    .requirements-list {
                        columns: 1;
                    }
                    
                    .card {
                        padding: 20px;
                    }
                    
                    .info-table th {
                        width: 40%;
                        font-size: 12px;
                    }
                    
                    .btn {
                        display: block;
                        margin: 10px 0;
                    }
                }
            </style>
        </head>
        <body>
            <div class='email-wrapper'>
                <div class='email-container'>
                    <div class='header'>
                        <div class='header-content'>
                            <div class='admin-badge'>Admin Notification</div>
                            <h1>New Project Request Received</h1>
                            <p style='font-size: 16px; margin: 0; opacity: 0.9;'>Requires your immediate attention and review</p>
                        </div>
                    </div>
                    
                    <div class='content'>
                        $urgentNotice
                        
                        <div class='card client-card'>
                            <h2><span class='icon' style='background-image: url(\"https://img.icons8.com/color/48/4361ee/user.png\");'></span> Client Information</h2>
                            <div class='divider'></div>
                            <table class='info-table'>
                                <tr><th>Full Name</th><td>{$projectData['full_name']}</td></tr>
                                <tr><th>Email Address</th><td><a href='mailto:{$projectData['email']}' style='color: #4361ee; text-decoration: none;'>{$projectData['email']}</a></td></tr>
                                <tr><th>Institution</th><td>{$projectData['institution']}</td></tr>
                                <tr><th>Course/Program</th><td>{$projectData['course']}</td></tr>
                                <tr><th>Submission Date</th><td>$submissionDate</td></tr>
                            </table>
                        </div>
                        
                        <div class='card project-card'>
                            <h2><span class='icon' style='background-image: url(\"https://img.icons8.com/color/48/7209b7/project.png\");'></span> Project Overview</h2>
                            <div class='divider'></div>
                            <table class='info-table'>
                                <tr><th>Project ID</th><td><strong>#DS-$projectId</strong></td></tr>
                                <tr><th>Project Type</th><td>{$projectTypeLabels[$projectData['project_type']]}</td></tr>
                                <tr><th>Due Date</th><td><strong>$dueDateText</strong></td></tr>
                                <tr><th>Files Attached</th><td>" . count($uploadedFiles) . " file(s)</td></tr>
                            </table>
                        </div>
                        
                        <div class='card'>
                            <h2><span class='icon' style='background-image: url(\"https://img.icons8.com/color/48/f72585/task-completed.png\");'></span> Requested Services</h2>
                            <div class='divider'></div>
                            <ul class='requirements-list'>
                                <li>" . implode('</li><li>', $requirementLabels ?: ['Standard development services']) . "</li>
                            </ul>
                        </div>
                        
                        <div class='card'>
                            <h2><span class='icon' style='background-image: url(\"https://img.icons8.com/color/48/4361ee/document.png\");'></span> Project Description</h2>
                            <div class='divider'></div>
                            <div class='description-text'>{$projectData['project_description']}</div>
                        </div>
                        
                        $filesSection
                        
                        <div class='card action-card'>
                            <h2><span class='icon' style='background-image: url(\"https://img.icons8.com/color/48/f72585/rocket.png\");'></span> Quick Actions</h2>
                            <div class='divider'></div>
                            <a href='mailto:{$projectData['email']}?subject=Re: Your DevFlow Studio Project Request (#DS-$projectId)' class='btn'>Reply to Client</a>
                            <a href='#' class='btn btn-secondary'>View in Dashboard</a>
                        </div>
                        
                        <div class='meta-info'>
                            <strong>Technical Information:</strong><br>
                            <strong>IP Address:</strong> {$projectData['ip_address']}<br>
                            <strong>User Agent:</strong> " . substr($projectData['user_agent'], 0, 100) . "...<br>
                            <strong>Timestamp:</strong> " . date('Y-m-d H:i:s T') . "
                        </div>
                    </div>
                    
                    <div class='footer'>
                        <div class='logo-placeholder'>
                            DevFlow Studio
                        </div>
                        
                        <p style='margin-bottom: 10px; font-weight: 500;'>Admin Notification System</p>
                        <p style='font-size: 12px; color: #bdc3c7;'>This is an automated notification. Please do not reply to this email.</p>
                        <p style='margin-top: 15px;'>© " . date('Y') . " DevFlow Studio. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";

        // Send emails
        $userEmailSent = sendEmail($projectData['email'], $userSubject, $userBody);
        $adminEmailSent = sendAdminEmails($adminSubject, $adminBody, $uploadedFiles);
        
        // Log email results
        if (!$userEmailSent || !$adminEmailSent) {
            error_log("Email sending failed for project ID: $projectId. User: " . ($userEmailSent ? 'Success' : 'Failed') . ", Admin: " . ($adminEmailSent ? 'Success' : 'Failed'));
        }
        
        $response['success'] = true;
        $response['message'] = 'Project submitted successfully! Check your email for confirmation.';
        $response['project_id'] = $projectId;
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        $response['message'] = $e->getMessage();
        error_log("Project submission error: " . $e->getMessage() . " | POST data: " . print_r($_POST, true));
        
        // Clean up any uploaded files if database insertion failed
        if (!empty($uploadedFiles)) {
            foreach ($uploadedFiles as $file) {
                if (file_exists($file['path'])) {
                    unlink($file['path']);
                }
            }
        }
    }
    
    echo json_encode($response);
    exit;
}

// If not a POST request, return error
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed. Only POST requests are accepted.']);
?>