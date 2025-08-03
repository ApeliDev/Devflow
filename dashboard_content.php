<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Total Projects Card -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Projects</p>
                <p class="text-2xl font-semibold text-gray-800">
                    <?= $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn() ?>
                </p>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
                <i class="bi bi-folder text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <!-- Pending Projects Card -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Pending Review</p>
                <p class="text-2xl font-semibold text-gray-800">
                    <?= $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'pending'")->fetchColumn() ?>
                </p>
            </div>
            <div class="bg-yellow-100 p-3 rounded-full">
                <i class="bi bi-hourglass text-yellow-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <!-- In Progress Projects Card -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">In Progress</p>
                <p class="text-2xl font-semibold text-gray-800">
                    <?= $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'in_progress'")->fetchColumn() ?>
                </p>
            </div>
            <div class="bg-purple-100 p-3 rounded-full">
                <i class="bi bi-gear text-purple-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <!-- Completed Projects Card -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Completed</p>
                <p class="text-2xl font-semibold text-gray-800">
                    <?= $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'completed'")->fetchColumn() ?>
                </p>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
                <i class="bi bi-check-circle text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Projects -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Projects</h3>
        
        <div class="space-y-4">
            <?php
            $recentProjects = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC LIMIT 5")->fetchAll();
            
            if (count($recentProjects) > 0): ?>
                <?php foreach ($recentProjects as $project): ?>
                <div class="flex items-center justify-between p-3 border rounded-md hover:bg-gray-50">
                    <div>
                        <p class="text-sm font-medium">#DS-<?= $project['id'] ?> - <?= htmlspecialchars($project['course']) ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($project['full_name']) ?></p>
                    </div>
                    <div>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?= $project['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                               ($project['status'] === 'in_progress' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800') ?>">
                            <?= ucfirst(str_replace('_', ' ', $project['status'])) ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-sm text-gray-500">No recent projects found.</p>
            <?php endif; ?>
            
            <div class="mt-4 text-right">
                <a href="projects.php" class="text-sm text-blue-600 hover:underline">View all projects</a>
            </div>
        </div>
    </div>
    
    <!-- Project Status Chart -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Project Status Distribution</h3>
        <canvas id="statusChart" height="200"></canvas>
    </div>
</div>

<script>
    // Project status chart
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Reviewed', 'In Progress', 'Completed', 'Cancelled'],
                datasets: [{
                    data: [
                        <?= $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'pending'")->fetchColumn() ?>,
                        <?= $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'reviewed'")->fetchColumn() ?>,
                        <?= $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'in_progress'")->fetchColumn() ?>,
                        <?= $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'completed'")->fetchColumn() ?>,
                        <?= $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'cancelled'")->fetchColumn() ?>
                    ],
                    backgroundColor: [
                        'rgba(234, 179, 8, 0.7)',
                        'rgba(59, 130, 246, 0.7)',
                        'rgba(139, 92, 246, 0.7)',
                        'rgba(34, 197, 94, 0.7)',
                        'rgba(239, 68, 68, 0.7)'
                    ],
                    borderColor: [
                        'rgba(234, 179, 8, 1)',
                        'rgba(59, 130, 246, 1)',
                        'rgba(139, 92, 246, 1)',
                        'rgba(34, 197, 94, 1)',
                        'rgba(239, 68, 68, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });
    });
</script>