<?php
require_once 'auth.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';

// Build query based on filters
$query = "SELECT p.*, COUNT(f.id) as file_count 
          FROM projects p 
          LEFT JOIN project_files f ON p.id = f.project_id";

$whereClauses = [];
$params = [];

if ($statusFilter !== 'all') {
    $whereClauses[] = "p.status = ?";
    $params[] = $statusFilter;
}

if (!empty($searchQuery)) {
    $whereClauses[] = "(p.full_name LIKE ? OR p.email LIKE ? OR p.project_description LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

if (!empty($whereClauses)) {
    $query .= " WHERE " . implode(" AND ", $whereClauses);
}

$query .= " GROUP BY p.id ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll();
?>

<!-- Include the dashboard layout -->
<?php include('dashboard_header.php'); ?>

<main class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">
            <?= ucfirst($statusFilter) ?> Projects
        </h2>
        <div class="flex space-x-4">
            <div class="relative">
                <input type="text" placeholder="Search projects..." id="searchInput" 
                       class="pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       value="<?= htmlspecialchars($searchQuery) ?>">
                <i class="bi bi-search absolute left-3 top-2.5 text-gray-400"></i>
            </div>
            <select id="statusFilter" class="border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Projects</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="reviewed" <?= $statusFilter === 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Files</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($projects as $project): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">#DS-<?= $project['id'] ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                        <?= strtoupper(substr($project['full_name'], 0, 1)) ?>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($project['full_name']) ?></div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($project['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($project['course']) ?></div>
                            <div class="text-sm text-gray-500 truncate max-w-xs"><?= htmlspecialchars($project['project_description']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php 
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'reviewed' => 'bg-blue-100 text-blue-800',
                                'in_progress' => 'bg-purple-100 text-purple-800',
                                'completed' => 'bg-green-100 text-green-800',
                                'cancelled' => 'bg-red-100 text-red-800'
                            ];
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusColors[$project['status']] ?>">
                                <?= ucfirst(str_replace('_', ' ', $project['status'])) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= $project['file_count'] ?> file(s)
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= $project['due_date'] ? date('M j, Y', strtotime($project['due_date'])) : 'Not set' ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="project_detail.php?id=<?= $project['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                            <a href="#" class="text-green-600 hover:text-green-900" onclick="openMessageModal(<?= $project['id'] ?>, '<?= htmlspecialchars($project['full_name']) ?>')">Message</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Message Modal -->
<div id="messageModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium">Send Message</h3>
            <button onclick="closeMessageModal()" class="text-gray-500 hover:text-gray-700">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="messageForm" method="POST" action="send_message.php">
            <input type="hidden" name="project_id" id="messageProjectId">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                <p id="messageRecipient" class="font-medium"></p>
            </div>
            <div class="mb-4">
                <label for="messageSubject" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                <input type="text" name="subject" id="messageSubject" required 
                       class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="mb-4">
                <label for="messageContent" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                <textarea name="message" id="messageContent" rows="5" required
                          class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeMessageModal()" 
                        class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Send Message
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Filter projects
    document.getElementById('statusFilter').addEventListener('change', function() {
        const status = this.value;
        const search = document.getElementById('searchInput').value;
        window.location.href = `projects.php?status=${status}&search=${encodeURIComponent(search)}`;
    });

    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const status = document.getElementById('statusFilter').value;
            const search = this.value;
            window.location.href = `projects.php?status=${status}&search=${encodeURIComponent(search)}`;
        }
    });

    // Message modal functions
    function openMessageModal(projectId, recipientName) {
        document.getElementById('messageProjectId').value = projectId;
        document.getElementById('messageRecipient').textContent = recipientName;
        document.getElementById('messageModal').classList.remove('hidden');
    }

    function closeMessageModal() {
        document.getElementById('messageModal').classList.add('hidden');
        document.getElementById('messageForm').reset();
    }

    // Handle message form submission
    document.getElementById('messageForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin mr-2"></i> Sending...';
        submitBtn.disabled = true;
        
        try {
            const response = await fetch('send_message.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Failed to send message');
            }
            
            alert('Message sent successfully!');
            closeMessageModal();
        } catch (error) {
            alert(`Error: ${error.message}`);
        } finally {
            submitBtn.innerHTML = originalBtnText;
            submitBtn.disabled = false;
        }
    });
</script>

<?php include('dashboard_footer.php'); ?>