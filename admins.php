<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Get all admin users
$admins = $pdo->query("SELECT * FROM admin_users ORDER BY created_at DESC")->fetchAll();
?>

<?php include('dashboard_header.php'); ?>

<main class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">Admin Users</h2>
        <button onclick="openAddAdminModal()" 
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center">
            <i class="bi bi-plus-circle mr-2"></i> Add Admin
        </button>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                        <?= strtoupper(substr($admin['full_name'], 0, 1)) ?>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($admin['full_name']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= htmlspecialchars($admin['username']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= htmlspecialchars($admin['email']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?= $admin['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 
                                   ($admin['role'] === 'manager' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') ?>">
                                <?= ucfirst($admin['role']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?= $admin['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $admin['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= $admin['last_login'] ? date('M j, Y', strtotime($admin['last_login'])) : 'Never' ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="openEditAdminModal(<?= $admin['id'] ?>)" 
                                    class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                            <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                            <button onclick="confirmDeleteAdmin(<?= $admin['id'] ?>)" 
                                    class="text-red-600 hover:text-red-900">Delete</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Add Admin Modal -->
<div id="addAdminModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium">Add New Admin</h3>
            <button onclick="closeAddAdminModal()" class="text-gray-500 hover:text-gray-700">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="addAdminForm" method="POST" action="add_admin.php">
            <div class="space-y-4">
                <div>
                    <label for="fullName" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="full_name" id="fullName" required 
                           class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" id="username" required 
                           class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" id="email" required 
                           class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" id="password" required 
                           class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select name="role" id="role" required 
                            class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="admin">Admin</option>
                        <option value="manager">Manager</option>
                        <option value="support" selected>Support</option>
                    </select>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="isActive" checked 
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="isActive" class="ml-2 block text-sm text-gray-700">Active</label>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeAddAdminModal()" 
                        class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Add Admin
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Admin Modal -->
<div id="editAdminModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium">Edit Admin</h3>
            <button onclick="closeEditAdminModal()" class="text-gray-500 hover:text-gray-700">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="editAdminForm" method="POST" action="update_admin.php">
            <input type="hidden" name="admin_id" id="editAdminId">
            <div class="space-y-4">
                <div>
                    <label for="editFullName" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="full_name" id="editFullName" required 
                           class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="editUsername" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" id="editUsername" required 
                           class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="editEmail" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" id="editEmail" required 
                           class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="editPassword" class="block text-sm font-medium text-gray-700 mb-1">Password (Leave blank to keep current)</label>
                    <input type="password" name="password" id="editPassword" 
                           class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="editRole" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select name="role" id="editRole" required 
                            class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="admin">Admin</option>
                        <option value="manager">Manager</option>
                        <option value="support">Support</option>
                    </select>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="editIsActive" 
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="editIsActive" class="ml-2 block text-sm text-gray-700">Active</label>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeEditAdminModal()" 
                        class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Update Admin
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Admin modal functions
    function openAddAdminModal() {
        document.getElementById('addAdminModal').classList.remove('hidden');
    }

    function closeAddAdminModal() {
        document.getElementById('addAdminModal').classList.add('hidden');
        document.getElementById('addAdminForm').reset();
    }

    function openEditAdminModal(adminId) {
        // Fetch admin data via AJAX and populate the form
        fetch(`get_admin.php?id=${adminId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const admin = data.admin;
                    document.getElementById('editAdminId').value = admin.id;
                    document.getElementById('editFullName').value = admin.full_name;
                    document.getElementById('editUsername').value = admin.username;
                    document.getElementById('editEmail').value = admin.email;
                    document.getElementById('editRole').value = admin.role;
                    document.getElementById('editIsActive').checked = admin.is_active;
                    document.getElementById('editAdminModal').classList.remove('hidden');
                } else {
                    alert(data.message || 'Failed to load admin data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load admin data');
            });
    }

    function closeEditAdminModal() {
        document.getElementById('editAdminModal').classList.add('hidden');
    }

    function confirmDeleteAdmin(adminId) {
        if (confirm('Are you sure you want to delete this admin? This action cannot be undone.')) {
            fetch(`delete_admin.php?id=${adminId}`, { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Admin deleted successfully');
                        window.location.reload();
                    } else {
                        alert(data.message || 'Failed to delete admin');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to delete admin');
                });
        }
    }

    // Handle form submissions
    document.getElementById('addAdminForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin mr-2"></i> Adding...';
        submitBtn.disabled = true;
        
        try {
            const response = await fetch('add_admin.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Failed to add admin');
            }
            
            alert('Admin added successfully!');
            closeAddAdminModal();
            window.location.reload();
        } catch (error) {
            alert(`Error: ${error.message}`);
        } finally {
            submitBtn.innerHTML = originalBtnText;
            submitBtn.disabled = false;
        }
    });

    document.getElementById('editAdminForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin mr-2"></i> Updating...';
        submitBtn.disabled = true;
        
        try {
            const response = await fetch('update_admin.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Failed to update admin');
            }
            
            alert('Admin updated successfully!');
            closeEditAdminModal();
            window.location.reload();
        } catch (error) {
            alert(`Error: ${error.message}`);
        } finally {
            submitBtn.innerHTML = originalBtnText;
            submitBtn.disabled = false;
        }
    });
</script>

<?php include('dashboard_footer.php'); ?>