<?php

require_once 'auth.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Get admin info
$adminId = $_SESSION['admin_id'];
$adminRole = $_SESSION['admin_role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DevFlow Studio - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .sidebar-collapsed {
            width: 80px !important;
            overflow: hidden;
        }
        .sidebar-collapsed .sidebar-text {
            display: none;
        }
        .sidebar-collapsed .logo-text {
            display: none;
        }
        .sidebar-collapsed .menu-title {
            display: none;
        }
        .sidebar-collapsed .sidebar-icon {
            margin-right: 0;
        }
        .sidebar-collapsed .sidebar-item {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
        }
        .sidebar-hover:hover {
            width: 260px !important;
            z-index: 1000;
        }
        .sidebar-hover:hover .sidebar-text {
            display: inline;
        }
        .sidebar-hover:hover .logo-text {
            display: inline;
        }
        .sidebar-hover:hover .menu-title {
            display: block;
        }
        .sidebar-hover:hover .sidebar-item {
            justify-content: flex-start;
            padding-left: 1rem;
            padding-right: 1rem;
        }
        .transition-all {
            transition-property: all;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
        }
        @media (max-width: 768px) {
            .sidebar-mobile {
                position: fixed;
                z-index: 50;
                transform: translateX(-100%);
            }
            .sidebar-mobile-show {
                transform: translateX(0);
            }
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 40;
            }
            .sidebar-overlay-show {
                display: block;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Mobile Sidebar Overlay -->
    <div id="sidebarOverlay" class="sidebar-overlay"></div>
    
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div id="sidebar" class="bg-gradient-to-b from-gray-900 to-gray-800 text-white w-64 flex-shrink-0 fixed lg:relative h-full z-50 sidebar-mobile sidebar-collapsed lg:sidebar-hover transition-all duration-300 ease-in-out">
            <div class="p-4 border-b border-gray-700 flex items-center justify-between">
                <div class="flex items-center">
                    <div class="bg-blue-600 p-2 rounded-lg">
                        <i class="bi bi-code-slash text-xl"></i>
                    </div>
                    <h1 class="text-xl font-bold ml-3 logo-text">DevFlow Studio</h1>
                </div>
                <button id="sidebarToggle" class="lg:hidden text-gray-400 hover:text-white">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            
            <div class="p-4 flex items-center space-x-3 border-b border-gray-700">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['admin_name']) ?>&background=random" 
                     alt="Admin" class="h-10 w-10 rounded-full">
                <div class="sidebar-text">
                    <p class="text-sm font-medium"><?= htmlspecialchars($_SESSION['admin_name']) ?></p>
                    <p class="text-xs text-gray-400"><?= ucfirst($adminRole) ?></p>
                </div>
            </div>
            
            <nav class="p-4 overflow-y-auto h-[calc(100%-180px)]">
                <div class="mb-6">
                    <p class="text-gray-400 uppercase text-xs font-bold mb-2 menu-title">Main</p>
                    <a href="dashboard.php" class="flex items-center py-3 px-3 text-blue-300 hover:bg-gray-700 rounded-lg sidebar-item transition-colors duration-200">
                        <i class="bi bi-speedometer2 sidebar-icon text-lg"></i>
                        <span class="sidebar-text ml-2">Dashboard</span>
                    </a>
                </div>
                
                <div class="mb-6">
                    <p class="text-gray-400 uppercase text-xs font-bold mb-2 menu-title">Projects</p>
                    <a href="projects.php" class="flex items-center py-3 px-3 hover:bg-gray-700 rounded-lg sidebar-item transition-colors duration-200">
                        <i class="bi bi-folder sidebar-icon text-lg"></i>
                        <span class="sidebar-text ml-2">All Projects</span>
                    </a>
                    <a href="projects.php?status=pending" class="flex items-center py-3 px-3 hover:bg-gray-700 rounded-lg sidebar-item transition-colors duration-200">
                        <i class="bi bi-hourglass-split sidebar-icon text-lg"></i>
                        <span class="sidebar-text ml-2">Pending Review</span>
                    </a>
                    <a href="projects.php?status=in_progress" class="flex items-center py-3 px-3 hover:bg-gray-700 rounded-lg sidebar-item transition-colors duration-200">
                        <i class="bi bi-gear sidebar-icon text-lg"></i>
                        <span class="sidebar-text ml-2">In Progress</span>
                    </a>
                    <a href="projects.php?status=completed" class="flex items-center py-3 px-3 hover:bg-gray-700 rounded-lg sidebar-item transition-colors duration-200">
                        <i class="bi bi-check-circle sidebar-icon text-lg"></i>
                        <span class="sidebar-text ml-2">Completed</span>
                    </a>
                </div>
                
                <?php if ($adminRole === 'admin' || $adminRole === 'manager'): ?>
                <div class="mb-6">
                    <p class="text-gray-400 uppercase text-xs font-bold mb-2 menu-title">Administration</p>
                    <a href="admins.php" class="flex items-center py-3 px-3 hover:bg-gray-700 rounded-lg sidebar-item transition-colors duration-200">
                        <i class="bi bi-people sidebar-icon text-lg"></i>
                        <span class="sidebar-text ml-2">Admin Users</span>
                    </a>
                    <a href="clients.php" class="flex items-center py-3 px-3 hover:bg-gray-700 rounded-lg sidebar-item transition-colors duration-200">
                        <i class="bi bi-person-vcard sidebar-icon text-lg"></i>
                        <span class="sidebar-text ml-2">Clients</span>
                    </a>
                    <a href="settings.php" class="flex items-center py-3 px-3 hover:bg-gray-700 rounded-lg sidebar-item transition-colors duration-200">
                        <i class="bi bi-gear sidebar-icon text-lg"></i>
                        <span class="sidebar-text ml-2">Settings</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="mb-6">
                    <p class="text-gray-400 uppercase text-xs font-bold mb-2 menu-title">Support</p>
                    <a href="help.php" class="flex items-center py-3 px-3 hover:bg-gray-700 rounded-lg sidebar-item transition-colors duration-200">
                        <i class="bi bi-question-circle sidebar-icon text-lg"></i>
                        <span class="sidebar-text ml-2">Help Center</span>
                    </a>
                </div>
                
                <div>
                    <a href="logout.php" class="flex items-center py-3 px-3 text-red-400 hover:bg-gray-700 rounded-lg sidebar-item transition-colors duration-200">
                        <i class="bi bi-box-arrow-left sidebar-icon text-lg"></i>
                        <span class="sidebar-text ml-2">Logout</span>
                    </a>
                </div>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 overflow-auto h-screen">
            <header class="bg-white shadow-sm sticky top-0 z-40">
                <div class="flex justify-between items-center p-4">
                    <div class="flex items-center">
                        <button id="mobileSidebarToggle" class="lg:hidden mr-4 text-gray-600 hover:text-blue-600">
                            <i class="bi bi-list text-2xl"></i>
                        </button>
                        <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <button id="notificationsBtn" class="text-gray-600 hover:text-blue-600 relative">
                                <i class="bi bi-bell text-xl"></i>
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">3</span>
                            </button>
                            <div id="notificationsDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg z-50 border border-gray-200">
                                <div class="p-4 border-b">
                                    <div class="flex justify-between items-center">
                                        <h3 class="text-lg font-medium">Notifications</h3>
                                        <button class="text-blue-600 text-sm">Mark all as read</button>
                                    </div>
                                </div>
                                <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                                    <a href="#" class="block p-3 hover:bg-gray-50">
                                        <div class="flex items-start">
                                            <div class="bg-blue-100 p-2 rounded-full mr-3">
                                                <i class="bi bi-file-earmark-text text-blue-600"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium">New project submitted</p>
                                                <p class="text-xs text-gray-500">2 hours ago</p>
                                            </div>
                                        </div>
                                    </a>
                                    <a href="#" class="block p-3 hover:bg-gray-50">
                                        <div class="flex items-start">
                                            <div class="bg-purple-100 p-2 rounded-full mr-3">
                                                <i class="bi bi-people text-purple-600"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium">New client registered</p>
                                                <p class="text-xs text-gray-500">5 hours ago</p>
                                            </div>
                                        </div>
                                    </a>
                                    <a href="#" class="block p-3 hover:bg-gray-50">
                                        <div class="flex items-start">
                                            <div class="bg-yellow-100 p-2 rounded-full mr-3">
                                                <i class="bi bi-exclamation-triangle text-yellow-600"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium">Project review overdue</p>
                                                <p class="text-xs text-gray-500">1 day ago</p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="p-3 text-center bg-gray-50">
                                    <a href="#" class="text-sm text-blue-600 hover:underline">View all notifications</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="relative">
                            <button id="profileDropdownBtn" class="flex items-center space-x-2 focus:outline-none">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['admin_name']) ?>&background=random" 
                                     alt="Admin" class="h-8 w-8 rounded-full">
                                <span class="hidden md:inline text-sm font-medium"><?= htmlspecialchars($_SESSION['admin_name']) ?></span>
                                <i class="bi bi-chevron-down text-xs hidden md:inline"></i>
                            </button>
                            <div id="profileDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 border border-gray-200">
                                <div class="py-1">
                                    <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Your Profile</a>
                                    <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                                    <div class="border-t border-gray-200"></div>
                                    <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Sign out</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <main class="p-4 md:p-6 bg-gray-50 min-h-[calc(100%-64px)]">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
                    <!-- Total Projects Card -->
                    <div class="bg-white rounded-xl shadow-sm p-4 md:p-6 border border-gray-100 hover:border-blue-200 transition-colors duration-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Projects</p>
                                <p class="text-2xl font-semibold text-gray-800 mt-1">
                                    <?= $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn() ?>
                                </p>
                                <p class="text-xs text-gray-500 mt-1">+5 from last week</p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="bi bi-folder text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pending Projects Card -->
                    <div class="bg-white rounded-xl shadow-sm p-4 md:p-6 border border-gray-100 hover:border-yellow-200 transition-colors duration-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Pending Review</p>
                                <p class="text-2xl font-semibold text-gray-800 mt-1">
                                    <?= $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'pending'")->fetchColumn() ?>
                                </p>
                                <p class="text-xs text-gray-500 mt-1">+2 from yesterday</p>
                            </div>
                            <div class="bg-yellow-100 p-3 rounded-full">
                                <i class="bi bi-hourglass text-yellow-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- In Progress Projects Card -->
                    <div class="bg-white rounded-xl shadow-sm p-4 md:p-6 border border-gray-100 hover:border-purple-200 transition-colors duration-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">In Progress</p>
                                <p class="text-2xl font-semibold text-gray-800 mt-1">
                                    <?= $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'in_progress'")->fetchColumn() ?>
                                </p>
                                <p class="text-xs text-gray-500 mt-1">3 due this week</p>
                            </div>
                            <div class="bg-purple-100 p-3 rounded-full">
                                <i class="bi bi-gear text-purple-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Completed Projects Card -->
                    <div class="bg-white rounded-xl shadow-sm p-4 md:p-6 border border-gray-100 hover:border-green-200 transition-colors duration-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Completed</p>
                                <p class="text-2xl font-semibold text-gray-800 mt-1">
                                    <?= $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'completed'")->fetchColumn() ?>
                                </p>
                                <p class="text-xs text-gray-500 mt-1">+10% from last month</p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <i class="bi bi-check-circle text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
                    <!-- Recent Projects -->
                    <div class="bg-white rounded-xl shadow-sm p-4 md:p-6 border border-gray-100 lg:col-span-2">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Recent Projects</h3>
                            <a href="projects.php" class="text-sm text-blue-600 hover:underline">View all</a>
                        </div>
                        
                        <div class="space-y-3">
                            <?php
                            $recentProjects = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC LIMIT 5")->fetchAll();
                            
                            if (count($recentProjects) > 0): ?>
                                <?php foreach ($recentProjects as $project): ?>
                                <a href="project.php?id=<?= $project['id'] ?>" class="block p-3 border rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium">#DS-<?= $project['id'] ?> - <?= htmlspecialchars($project['course']) ?></p>
                                            <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($project['full_name']) ?></p>
                                        </div>
                                        <div>
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?= $project['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                   ($project['status'] === 'in_progress' ? 'bg-purple-100 text-purple-800' : 
                                                   ($project['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800')) ?>">
                                                <?= ucfirst(str_replace('_', ' ', $project['status'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center mt-2 text-xs text-gray-500">
                                        <i class="bi bi-calendar mr-1"></i>
                                        <span><?= date('M d, Y', strtotime($project['created_at'])) ?></span>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-8">
                                    <i class="bi bi-folder-x text-3xl text-gray-400"></i>
                                    <p class="text-sm text-gray-500 mt-2">No recent projects found</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Project Status Chart -->
                    <div class="bg-white rounded-xl shadow-sm p-4 md:p-6 border border-gray-100">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Project Status</h3>
                        <canvas id="statusChart" height="250"></canvas>
                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <div class="flex items-center">
                                <span class="w-3 h-3 bg-blue-500 rounded-full mr-2"></span>
                                <span class="text-xs text-gray-600">Pending (<?= $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'pending'")->fetchColumn() ?>)</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-3 h-3 bg-purple-500 rounded-full mr-2"></span>
                                <span class="text-xs text-gray-600">In Progress (<?= $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'in_progress'")->fetchColumn() ?>)</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                                <span class="text-xs text-gray-600">Completed (<?= $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'completed'")->fetchColumn() ?>)</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-3 h-3 bg-gray-500 rounded-full mr-2"></span>
                                <span class="text-xs text-gray-600">Other (<?= $pdo->query("SELECT COUNT(*) FROM projects WHERE status NOT IN ('pending', 'in_progress', 'completed')")->fetchColumn() ?>)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-xl shadow-sm p-4 md:p-6 border border-gray-100 mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Activity</h3>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="bg-blue-100 p-2 rounded-full mr-3">
                                <i class="bi bi-file-earmark-text text-blue-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium">Project #DS-124 was submitted</p>
                                <p class="text-xs text-gray-500 mt-1">Web Development course project by John Doe</p>
                                <p class="text-xs text-gray-400 mt-1">2 hours ago</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="bg-green-100 p-2 rounded-full mr-3">
                                <i class="bi bi-check-circle text-green-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium">Project #DS-120 was completed</p>
                                <p class="text-xs text-gray-500 mt-1">Mobile App Development project</p>
                                <p class="text-xs text-gray-400 mt-1">1 day ago</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="bg-purple-100 p-2 rounded-full mr-3">
                                <i class="bi bi-people text-purple-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium">New client registered</p>
                                <p class="text-xs text-gray-500 mt-1">Sarah Johnson from Tech University</p>
                                <p class="text-xs text-gray-400 mt-1">2 days ago</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 text-center">
                        <a href="activity.php" class="text-sm text-blue-600 hover:underline">View all activity</a>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Sidebar toggle functionality
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        // Toggle sidebar on desktop
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('sidebar-collapsed');
        });
        
        // Toggle sidebar on mobile
        mobileSidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('sidebar-mobile-show');
            sidebarOverlay.classList.toggle('sidebar-overlay-show');
        });
        
        // Close sidebar when clicking overlay
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('sidebar-mobile-show');
            sidebarOverlay.classList.remove('sidebar-overlay-show');
        });
        
        // Toggle notifications dropdown
        const notificationsBtn = document.getElementById('notificationsBtn');
        const notificationsDropdown = document.getElementById('notificationsDropdown');
        
        notificationsBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationsDropdown.classList.toggle('hidden');
        });
        
        // Toggle profile dropdown
        const profileDropdownBtn = document.getElementById('profileDropdownBtn');
        const profileDropdown = document.getElementById('profileDropdown');
        
        profileDropdownBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('hidden');
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function() {
            notificationsDropdown.classList.add('hidden');
            profileDropdown.classList.add('hidden');
        });
        
        // Prevent dropdowns from closing when clicking inside them
        notificationsDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        profileDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        
        // Project status chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('statusChart').getContext('2d');
            const statusChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'In Progress', 'Completed', 'Other'],
                    datasets: [{
                        data: [
                            <?= $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'pending'")->fetchColumn() ?>,
                            <?= $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'in_progress'")->fetchColumn() ?>,
                            <?= $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'completed'")->fetchColumn() ?>,
                            <?= $pdo->query("SELECT COUNT(*) FROM projects WHERE status NOT IN ('pending', 'in_progress', 'completed')")->fetchColumn() ?>
                        ],
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.7)',
                            'rgba(139, 92, 246, 0.7)',
                            'rgba(34, 197, 94, 0.7)',
                            'rgba(156, 163, 175, 0.7)'
                        ],
                        borderColor: [
                            'rgba(59, 130, 246, 1)',
                            'rgba(139, 92, 246, 1)',
                            'rgba(34, 197, 94, 1)',
                            'rgba(156, 163, 175, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    cutout: '70%'
                }
            });
        });
    </script>
</body>
</html>