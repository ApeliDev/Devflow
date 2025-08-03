<?php
session_start();
require_once 'config.php';

// ==== SECURITY CHECK - Token-based access control ====
if (!isAdminAccessAuthorized()) {
    // Log the unauthorized attempt
    logUnauthorizedAccess();
    
    // Show generic 404 error to hide the existence of this page
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

// Initialize error message and security parameters
$error = '';
$max_attempts = 5;
$lockout_time = 300; // 5 minutes in seconds

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $ip = $_SERVER['REMOTE_ADDR'];
    
    try {
        // Check login attempts
        if (isIpBlocked($ip)) {
            $error = "Too many failed attempts. Please try again later.";
        } else {
            // Get admin user with case-sensitive username check
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE BINARY username = ? AND is_active = TRUE");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Successful login
                handleSuccessfulLogin($admin, $ip);
                // Redirect to dashboard with secure token
                header('Location: dashboard.php?token=' . SECURE_ACCESS_TOKEN);
                exit;
            } else {
                // Failed login
                logFailedAttempt($ip, $username);
                $remaining_attempts = $max_attempts - getAttemptCount($ip);
                $error = "Invalid credentials. " . ($remaining_attempts > 0 ? 
                    "{$remaining_attempts} attempts remaining." : "Account temporarily locked.");
            }
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "System error. Please try again later.";
    }
}

/**
 * Checks if IP is temporarily blocked
 */
function isIpBlocked($ip) {
    global $pdo, $max_attempts, $lockout_time;
    
    $stmt = $pdo->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip = ?");
    $stmt->execute([$ip]);
    $attempt = $stmt->fetch();
    
    if ($attempt && $attempt['attempts'] >= $max_attempts) {
        $last_attempt = strtotime($attempt['last_attempt']);
        $current_time = time();
        return ($current_time - $last_attempt) < $lockout_time;
    }
    return false;
}

/**
 * Gets current attempt count for IP
 */
function getAttemptCount($ip) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT attempts FROM login_attempts WHERE ip = ?");
    $stmt->execute([$ip]);
    $attempt = $stmt->fetch();
    
    return $attempt ? $attempt['attempts'] : 0;
}

/**
 * Handles successful login
 */
function handleSuccessfulLogin($admin, $ip) {
    global $pdo;
    
    // Reset attempts
    $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?")->execute([$ip]);
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Set session data
    $_SESSION = [
        'admin_id' => $admin['id'],
        'admin_name' => $admin['full_name'],
        'admin_role' => $admin['role'],
        'last_activity' => time(),
        'ip_address' => $ip,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'authenticated' => true,
        'secure_token' => SECURE_ACCESS_TOKEN // Store token in session for verification
    ];
    
    // Update last login
    $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);
    
    // Log successful login
    $pdo->prepare("INSERT INTO admin_login_logs 
                  (admin_id, ip_address, username_attempted, success, user_agent) 
                  VALUES (?, ?, ?, 1, ?)")
        ->execute([$admin['id'], $ip, $admin['username'], $_SERVER['HTTP_USER_AGENT']]);
}

/**
 * Logs failed login attempts
 */
function logFailedAttempt($ip, $username) {
    global $pdo;
    
    try {
        // Update or create attempt record
        $stmt = $pdo->prepare("INSERT INTO login_attempts 
                              (ip, username, attempts, last_attempt) 
                              VALUES (?, ?, 1, NOW())
                              ON DUPLICATE KEY UPDATE 
                              attempts = attempts + 1, last_attempt = NOW()");
        $stmt->execute([$ip, $username]);
        
        // Log failed attempt
        $pdo->prepare("INSERT INTO admin_login_logs 
                      (admin_id, ip_address, username_attempted, success, user_agent) 
                      VALUES (NULL, ?, ?, 0, ?)")
            ->execute([$ip, $username, $_SERVER['HTTP_USER_AGENT']]);
    } catch (PDOException $e) {
        error_log("Failed to log login attempt: " . $e->getMessage());
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DevFlow Studio - Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            light: '#d4e8a5',
                            DEFAULT: '#8db600',
                            dark: '#5a7d00',
                        },
                        secondary: {
                            light: '#fdf3d8',
                            DEFAULT: '#ffd700',
                            dark: '#c7a500',
                        },
                        accent: {
                            DEFAULT: '#f5f5f5',
                        }
                    },
                    fontFamily: {
                        sans: ['"Inter"', 'sans-serif'],
                    },
                    animation: {
                        'float': 'float 3s ease-in-out infinite',
                        'glow': 'glow 2s ease-in-out infinite alternate',
                        'slide-up': 'slide-up 0.5s ease-out',
                        'fade-in': 'fade-in 0.6s ease-out',
                    },
                    keyframes: {
                        'float': {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-10px)' },
                        },
                        'glow': {
                            '0%': { boxShadow: '0 0 20px rgba(141, 182, 0, 0.3)' },
                            '100%': { boxShadow: '0 0 30px rgba(141, 182, 0, 0.6), 0 0 40px rgba(255, 215, 0, 0.3)' },
                        },
                        'slide-up': {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                        'fade-in': {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .input-field {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .input-field:focus {
            border-color: #8db600;
            box-shadow: 0 0 0 3px rgba(141, 182, 0, 0.2);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #8db600 0%, #5a7d00 100%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(141, 182, 0, 0.2);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .logo-glow {
            filter: drop-shadow(0 0 10px rgba(141, 182, 0, 0.3));
        }
        
        .error-shake {
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .password-toggle {
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6b7280;
        }
        
        .password-toggle:hover {
            color: #4b5563;
        }
    </style>
</head>
<body class="font-sans min-h-screen flex items-center justify-center p-4">
    <!-- Main Login Container -->
    <div class="glass-effect rounded-xl shadow-xl overflow-hidden w-full max-w-md animate-slide-up">
        <!-- Header Section -->
        <div class="p-8 pb-6">
            <div class="text-center mb-8 animate-fade-in">
                <div class="flex justify-center mb-6">
                    <div class="relative">
                        <div class="absolute inset-0 bg-primary rounded-full blur-xl opacity-20 animate-glow"></div>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" 
                             class="w-16 h-16 text-primary logo-glow relative z-10 animate-float">
                            <path fill-rule="evenodd" d="M14.615 1.595a.75.75 0 01.359.852L12.982 9.75h7.268a.75.75 0 01.548 1.262l-10.5 11.25a.75.75 0 01-1.272-.71l1.992-7.302H3.75a.75.75 0 01-.548-1.262l10.5-11.25a.75.75 0 01.913-.143z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    Welcome Back
                </h1>
                <p class="text-gray-600">Sign in to your admin dashboard</p>
                <div class="w-16 h-1 bg-gradient-to-r from-primary to-secondary rounded-full mx-auto mt-4"></div>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 rounded-lg mb-6 flex items-center error-shake">
                    <div class="flex-shrink-0">
                        <i class="bi bi-exclamation-triangle-fill text-red-500 text-lg mr-3"></i>
                    </div>
                    <div>
                        <p class="font-medium"><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <form action="login.php?token=<?= SECURE_ACCESS_TOKEN ?>" method="POST" class="space-y-6">
                <div class="space-y-2">
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-person text-gray-400"></i>
                        </div>
                        <input type="text" id="username" name="username" required 
                               class="pl-10 w-full px-4 py-3 input-field rounded-lg focus:outline-none text-gray-800"
                               placeholder="Enter your username"
                               autocomplete="username">
                    </div>
                </div>
                
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <a href="forgot-password.php?token=<?= SECURE_ACCESS_TOKEN ?>" class="text-xs text-primary hover:text-primary-dark">Forgot password?</a>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="password" name="password" required 
                               class="pl-10 w-full px-4 py-3 input-field rounded-lg focus:outline-none text-gray-800 pr-10"
                               placeholder="Enter your password"
                               autocomplete="current-password">
                        <div class="password-toggle absolute right-0 pr-3" onclick="togglePassword()">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" id="remember" name="remember" class="rounded text-primary focus:ring-primary">
                    <label for="remember" class="ml-2 block text-sm text-gray-600">Remember me</label>
                </div>
                
                <div class="pt-2">
                    <button type="submit" 
                            class="w-full btn-primary text-white font-semibold py-3 px-6 rounded-lg flex items-center justify-center hover:shadow-md">
                        <i class="bi bi-box-arrow-in-right mr-2"></i> 
                        Sign In
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Footer Section -->
        <div class="bg-gray-50 px-8 py-4 border-t border-gray-100">
            <div class="text-center">
                <p class="text-sm text-gray-500">
                    <i class="bi bi-shield-lock text-primary mr-1"></i>
                    Secure admin portal - Unauthorized access prohibited
                </p>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }
        
        // Focus the username field on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>