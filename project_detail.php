<?php
require_once 'auth.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$projectId = $_GET['id'] ?? 0;

// Get project details
$stmt = $pdo->prepare("SELECT p.*, 
                      (SELECT COUNT(*) FROM project_files WHERE project_id = p.id) as file_count,
                      (SELECT COUNT(*) FROM admin_messages WHERE project_id = p.id) as message_count
                      FROM projects p WHERE p.id = ?");
$stmt->execute([$projectId]);
$project = $stmt->fetch();

if (!$project) {
    header('Location: projects.php');
    exit;
}

// Get project files
$stmt = $pdo->prepare("SELECT * FROM project_files WHERE project_id = ?");
$stmt->execute([$projectId]);
$files = $stmt->fetchAll();

// Get project updates
$stmt = $pdo->prepare("SELECT u.*, a.full_name as admin_name 
                       FROM project_updates u
                       LEFT JOIN admin_users a ON u.admin_id = a.id
                       WHERE u.project_id = ?
                       ORDER BY u.created_at DESC");
$stmt->execute([$projectId]);
$updates = $stmt->fetchAll();

// Get project messages
$stmt = $pdo->prepare("SELECT m.*, a.full_name as admin_name 
                        FROM admin_messages m
                        JOIN admin_users a ON m.admin_id = a.id
                        WHERE m.project_id = ?
                        ORDER BY m.created_at DESC");
$stmt->execute([$projectId]);
$messages = $stmt->fetchAll();
?>

<?php include('dashboard_header.php'); ?>

<main class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800">Project #DS-<?= $project['id'] ?></h2>
            <div class="flex items-center space-x-4 mt-2">
                <?php 
                $statusColors = [
                    'pending' => 'bg-yellow-100 text-yellow-800',
                    'reviewed' => 'bg-blue-100 text-blue-800',
                    'in_progress' => 'bg-purple-100 text-purple-800',
                    'completed' => 'bg-green-100 text-green-800',
                    'cancelled' => 'bg-red-100 text-red-800'
                ];
                ?>
                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full <?= $statusColors[$project['status']] ?>">
                    <?= ucfirst(str_replace('_', ' ', $project['status'])) ?>
                </span>
                <span class="text-sm text-gray-600">
                    Submitted: <?= date('M j, Y \a\t g:i A', strtotime($project['created_at'])) ?>
                </span>
            </div>
        </div>
        <div class="flex space-x-3">
            <button onclick="openStatusModal()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center">
                <i class="bi bi-pencil-square mr-2"></i> Update Status
            </button>
            <button onclick="openMessageModal(<?= $project['id'] ?>, '<?= htmlspecialchars($project['full_name']) ?>')" 
                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 flex items-center">
                <i class="bi bi-envelope mr-2"></i> Send Message
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Project Details -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Project Details</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Client Information</h4>
                        <div class="mt-2">
                            <p class="text-sm font-medium"><?= htmlspecialchars($project['full_name']) ?></p>
                            <p class="text-sm text-gray-600"><?= htmlspecialchars($project['email']) ?></p>
                            <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($project['institution']) ?></p>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Academic Information</h4>
                        <div class="mt-2">
                            <p class="text-sm font-medium"><?= htmlspecialchars($project['course']) ?></p>
                            <p class="text-sm text-gray-600 capitalize"><?= str_replace('_', ' ', $project['project_type']) ?></p>
                            <p class="text-sm text-gray-600 mt-1">
                                Due: <?= $project['due_date'] ? date('M j, Y', strtotime($project['due_date'])) : 'Not set' ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6">
                    <h4 class="text-sm font-medium text-gray-500">Requirements</h4>
                    <div class="mt-2 bg-gray-50 p-4 rounded-md">
                        <p class="text-sm whitespace-pre-wrap"><?= htmlspecialchars($project['project_description']) ?></p>
                    </div>
                </div>
                
                <div class="mt-6">
                    <h4 class="text-sm font-medium text-gray-500">Additional Services</h4>
                    <div class="mt-2">
                        <div class="grid grid-cols-2 gap-2">
                            <?php if ($project['needs_documentation']): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Detailed Documentation
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($project['needs_comments']): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Code Comments
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($project['needs_explanation']): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                Concept Explanation
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($project['needs_testing']): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Unit Testing
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($project['needs_deployment']): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Deployment Help
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Project Files -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Project Files (<?= $project['file_count'] ?>)</h3>
                
                <?php if (count($files) > 0): ?>
                <div class="space-y-3">
                    <?php foreach ($files as $file): ?>
                    <div class="flex items-center justify-between p-3 border rounded-md">
                        <div class="flex items-center">
                            <?php 
                            $iconClass = 'bi-file-earmark';
                            $iconColor = 'text-gray-500';
                            
                            if (strpos($file['file_type'], 'image') !== false) {
                                $iconClass = 'bi-file-image';
                                $iconColor = 'text-blue-500';
                            } elseif (strpos($file['file_type'], 'pdf') !== false) {
                                $iconClass = 'bi-file-earmark-pdf';
                                $iconColor = 'text-red-500';
                            } elseif (strpos($file['file_type'], 'word') !== false) {
                                $iconClass = 'bi-file-earmark-word';
                                $iconColor = 'text-blue-600';
                            } elseif (strpos($file['file_type'], 'zip') !== false) {
                                $iconClass = 'bi-file-earmark-zip';
                                $iconColor = 'text-yellow-500';
                            }
                            ?>
                            <i class="bi <?= $iconClass ?> text-xl <?= $iconColor ?> mr-3"></i>
                            <div>
                                <p class="text-sm font-medium"><?= htmlspecialchars($file['file_name']) ?></p>
                                <p class="text-xs text-gray-500"><?= round($file['file_size'] / 1024 / 1024, 2) ?> MB • <?= strtoupper($file['file_extension']) ?></p>
                            </div>
                        </div>
                        <a href="<?= htmlspecialchars($file['file_path']) ?>" download 
                           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Download
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-sm text-gray-500">No files uploaded for this project.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Project Activity -->
        <div class="space-y-6">
            <!-- Status Updates -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Status History</h3>
                
                <div class="space-y-4">
                    <?php if (count($updates) > 0): ?>
                        <?php foreach ($updates as $update): ?>
                        <div class="border-l-4 pl-4 py-1 <?= 
                            $update['status'] === 'completed' ? 'border-green-500' : 
                            ($update['status'] === 'cancelled' ? 'border-red-500' : 
                            ($update['status'] === 'in_progress' ? 'border-blue-500' : 'border-yellow-500')) ?>">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium capitalize"><?= str_replace('_', ' ', $update['status']) ?></p>
                                    <?php if ($update['admin_name']): ?>
                                    <p class="text-xs text-gray-500">by <?= htmlspecialchars($update['admin_name']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs text-gray-500"><?= date('M j, g:i A', strtotime($update['created_at'])) ?></p>
                            </div>
                            <?php if ($update['message']): ?>
                            <p class="text-sm text-gray-700 mt-1"><?= htmlspecialchars($update['message']) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-sm text-gray-500">No status updates yet.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Messages -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Messages (<?= $project['message_count'] ?>)</h3>
                
                <div class="space-y-4">
                    <?php if (count($messages) > 0): ?>
                        <?php foreach ($messages as $message): ?>
                        <div class="border rounded-md p-3">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium"><?= htmlspecialchars($message['subject']) ?></p>
                                    <p class="text-xs text-gray-500">by <?= htmlspecialchars($message['admin_name']) ?></p>
                                </div>
                                <p class="text-xs text-gray-500"><?= date('M j, g:i A', strtotime($message['created_at'])) ?></p>
                            </div>
                            <p class="text-sm text-gray-700 mt-2"><?= htmlspecialchars($message['message']) ?></p>
                            <?php if ($message['is_read']): ?>
                            <p class="text-xs text-gray-500 mt-2">Read by client</p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-sm text-gray-500">No messages sent yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Status Update Modal -->
<div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium">Update Project Status</h3>
            <button onclick="closeStatusModal()" class="text-gray-500 hover:text-gray-700">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="statusForm" method="POST" action="update_status.php">
            <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
            <div class="mb-4">
                <label for="statusSelect" class="block text-sm font-medium text-gray-700 mb-1">New Status</label>
                <select name="status" id="statusSelect" required 
                        class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select status...</option>
                    <option value="pending" <?= $project['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="reviewed" <?= $project['status'] === 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                    <option value="in_progress" <?= $project['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="completed" <?= $project['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= $project['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="statusMessage" class="block text-sm font-medium text-gray-700 mb-1">Message (Optional)</label>
                <textarea name="message" id="statusMessage" rows="3"
                          class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeStatusModal()" 
                        class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Update Status
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Message Modal (same as in projects.php) -->
<div id="messageModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <!-- Modal content same as in projects.php -->
</div>

<script>
    // Status modal functions
    function openStatusModal() {
        document.getElementById('statusModal').classList.remove('hidden');
    }

    function closeStatusModal() {
        document.getElementById('statusModal').classList.add('hidden');
    }

    // Handle status form submission
    document.getElementById('statusForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin mr-2"></i> Updating...';
        submitBtn.disabled = true;
        
        try {
            const response = await fetch('update_status.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Failed to update status');
            }
            
            alert('Status updated successfully!');
            closeStatusModal();
            window.location.reload();
        } catch (error) {
            alert(`Error: ${error.message}`);
        } finally {
            submitBtn.innerHTML = originalBtnText;
            submitBtn.disabled = false;
        }
    });

    // Message modal functions (same as in projects.php)
    function openMessageModal(projectId, recipientName) {
        document.getElementById('messageProjectId').value = projectId;
        document.getElementById('messageRecipient').textContent = recipientName;
        document.getElementById('messageModal').classList.remove('hidden');
    }

    function closeMessageModal() {
        document.getElementById('messageModal').classList.add('hidden');
        document.getElementById('messageForm').reset();
    }
</script>

<?php include('dashboard_footer.php'); ?>**/