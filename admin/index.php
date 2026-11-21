<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
requireAdmin();

// ----- Overall Stats -----
$totalUsers     = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='user'")->fetch_assoc()['c'];
$totalDocuments = $conn->query("SELECT COUNT(*) AS c FROM documents WHERE status='active'")->fetch_assoc()['c'];
$totalCategories= $conn->query("SELECT COUNT(*) AS c FROM categories")->fetch_assoc()['c'];
$totalStorage   = $conn->query("SELECT COALESCE(SUM(file_size),0) AS s FROM documents WHERE status='active'")->fetch_assoc()['s'];
$blockedUsers   = $conn->query("SELECT COUNT(*) AS c FROM users WHERE status='blocked'")->fetch_assoc()['c'];
$todayUploads   = $conn->query("SELECT COUNT(*) AS c FROM documents WHERE DATE(uploaded_at) = CURDATE()")->fetch_assoc()['c'];

// ----- Recent Activity Logs -----
$recentLogs = $conn->query("SELECT al.*, u.full_name FROM activity_logs al
                             JOIN users u ON al.user_id = u.user_id
                             ORDER BY al.created_at DESC LIMIT 8");

// ----- Uploads per Category -----
$catStats = $conn->query("SELECT c.category_name, COUNT(d.document_id) AS total
                           FROM categories c
                           LEFT JOIN documents d ON c.category_id = d.category_id AND d.status='active'
                           GROUP BY c.category_id ORDER BY total DESC");

// ----- Top 5 users by upload count -----
$topUsers = $conn->query("SELECT u.full_name, COUNT(d.document_id) AS total
                           FROM users u
                           LEFT JOIN documents d ON u.user_id = d.user_id AND d.status='active'
                           WHERE u.role='user'
                           GROUP BY u.user_id ORDER BY total DESC LIMIT 5");

$pageTitle = "Admin Dashboard";
require_once 'includes/admin-header.php';
?>

<h3 class="fw-bold mb-4"><i class="bi bi-speedometer2"></i> Admin Dashboard</h3>

<div class="row g-4 mb-4">
    <div class="col-md-2 col-sm-6">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="stat-icon bg-primary mb-2"><i class="bi bi-people-fill"></i></div>
                <h4><?php echo $totalUsers; ?></h4><small class="text-muted">Total Users</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-6">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="stat-icon bg-success mb-2"><i class="bi bi-files"></i></div>
                <h4><?php echo $totalDocuments; ?></h4><small class="text-muted">Total Documents</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-6">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="stat-icon bg-warning mb-2"><i class="bi bi-tags-fill"></i></div>
                <h4><?php echo $totalCategories; ?></h4><small class="text-muted">Categories</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-6">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="stat-icon bg-info mb-2"><i class="bi bi-hdd-fill"></i></div>
                <h4><?php echo formatSize($totalStorage); ?></h4><small class="text-muted">Total Storage</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-6">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="stat-icon bg-danger mb-2"><i class="bi bi-person-x-fill"></i></div>
                <h4><?php echo $blockedUsers; ?></h4><small class="text-muted">Blocked Users</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-6">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="stat-icon bg-secondary mb-2"><i class="bi bi-cloud-upload-fill"></i></div>
                <h4><?php echo $todayUploads; ?></h4><small class="text-muted">Uploads Today</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold"><i class="bi bi-pie-chart-fill"></i> Documents by Category</div>
            <ul class="list-group list-group-flush">
                <?php if ($catStats->num_rows > 0): while ($c = $catStats->fetch_assoc()): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <?php echo sanitize($c['category_name']); ?>
                    <span class="badge bg-primary rounded-pill"><?php echo $c['total']; ?></span>
                </li>
                <?php endwhile; else: ?>
                    <li class="list-group-item text-muted">No data available.</li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-bold"><i class="bi bi-trophy-fill"></i> Top Contributors</div>
            <ul class="list-group list-group-flush">
                <?php if ($topUsers->num_rows > 0): while ($u = $topUsers->fetch_assoc()): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <?php echo sanitize($u['full_name']); ?>
                    <span class="badge bg-success rounded-pill"><?php echo $u['total']; ?> docs</span>
                </li>
                <?php endwhile; else: ?>
                    <li class="list-group-item text-muted">No data available.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold"><i class="bi bi-activity"></i> Recent Activity</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>User</th><th>Action</th><th>Module</th><th>Time</th></tr></thead>
                    <tbody>
                    <?php if ($recentLogs->num_rows > 0): while ($log = $recentLogs->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo sanitize($log['full_name']); ?></td>
                            <td><?php echo sanitize($log['action']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo sanitize($log['module']); ?></span></td>
                            <td><small class="text-muted"><?php echo date('d M Y, h:i A', strtotime($log['created_at'])); ?></small></td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No activity yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
