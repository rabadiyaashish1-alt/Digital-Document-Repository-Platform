<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];

// ----- Total Documents -----
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM documents WHERE user_id = ? AND status = 'active'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$totalDocs = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// ----- Total Storage Used -----
$stmt = $conn->prepare("SELECT COALESCE(SUM(file_size),0) AS total_size FROM documents WHERE user_id = ? AND status = 'active'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$storageUsed = $stmt->get_result()->fetch_assoc()['total_size'];
$stmt->close();

// ----- Total Categories Used by this user -----
$stmt = $conn->prepare("SELECT COUNT(DISTINCT category_id) AS total FROM documents WHERE user_id = ? AND status='active' AND category_id IS NOT NULL");
$stmt->bind_param("i", $userId);
$stmt->execute();
$totalCategories = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// ----- Documents uploaded this month -----
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM documents WHERE user_id = ? AND status='active' AND MONTH(uploaded_at) = MONTH(CURDATE()) AND YEAR(uploaded_at) = YEAR(CURDATE())");
$stmt->bind_param("i", $userId);
$stmt->execute();
$monthDocs = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// ----- Recent Uploads (last 5) -----
$stmt = $conn->prepare("SELECT d.document_id, d.document_name, d.file_type, d.file_size, d.uploaded_at, c.category_name
                         FROM documents d
                         LEFT JOIN categories c ON d.category_id = c.category_id
                         WHERE d.user_id = ? AND d.status = 'active'
                         ORDER BY d.uploaded_at DESC LIMIT 5");
$stmt->bind_param("i", $userId);
$stmt->execute();
$recentUploads = $stmt->get_result();
$stmt->close();

// ----- Document Count Per Category (for chart / list) -----
$stmt = $conn->prepare("SELECT c.category_name, COUNT(d.document_id) AS total
                         FROM categories c
                         LEFT JOIN documents d ON c.category_id = d.category_id AND d.user_id = ? AND d.status = 'active'
                         GROUP BY c.category_id ORDER BY total DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$categoryStats = $stmt->get_result();
$stmt->close();

$pageTitle = "Dashboard";
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold"><i class="bi bi-speedometer2"></i> Welcome, <?php echo sanitize($_SESSION['full_name']); ?>!</h3>
    <a href="upload.php" class="btn btn-primary"><i class="bi bi-cloud-upload"></i> Upload New Document</a>
</div>

<!-- Statistic Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-primary"><i class="bi bi-files"></i></div>
                <div class="ms-3">
                    <h4 class="mb-0"><?php echo $totalDocs; ?></h4>
                    <small class="text-muted">Total Documents</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-success"><i class="bi bi-cloud-upload-fill"></i></div>
                <div class="ms-3">
                    <h4 class="mb-0"><?php echo $monthDocs; ?></h4>
                    <small class="text-muted">Uploads This Month</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-warning"><i class="bi bi-tags-fill"></i></div>
                <div class="ms-3">
                    <h4 class="mb-0"><?php echo $totalCategories; ?></h4>
                    <small class="text-muted">Categories Used</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-info"><i class="bi bi-hdd-fill"></i></div>
                <div class="ms-3">
                    <h4 class="mb-0"><?php echo formatSize($storageUsed); ?></h4>
                    <small class="text-muted">Storage Used</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Uploads -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold"><i class="bi bi-clock-history"></i> Recent Uploads</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>Document</th><th>Category</th><th>Size</th><th>Date</th><th></th></tr>
                        </thead>
                        <tbody>
                        <?php if ($recentUploads->num_rows > 0): ?>
                            <?php while ($doc = $recentUploads->fetch_assoc()): ?>
                            <tr>
                                <td><i class="bi <?php echo getFileIcon($doc['file_type']); ?>"></i> <?php echo sanitize($doc['document_name']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo sanitize($doc['category_name'] ?? 'Uncategorized'); ?></span></td>
                                <td><?php echo formatSize($doc['file_size']); ?></td>
                                <td><?php echo date('d M Y', strtotime($doc['uploaded_at'])); ?></td>
                                <td><a href="documents.php?view=<?php echo $doc['document_id']; ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No documents uploaded yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Breakdown -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold"><i class="bi bi-pie-chart-fill"></i> Documents by Category</div>
            <ul class="list-group list-group-flush">
                <?php if ($categoryStats->num_rows > 0): $categoryStats->data_seek(0); ?>
                    <?php while ($cat = $categoryStats->fetch_assoc()): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo sanitize($cat['category_name']); ?>
                        <span class="badge bg-primary rounded-pill"><?php echo $cat['total']; ?></span>
                    </li>
                    <?php endwhile; ?>
                <?php else: ?>
                    <li class="list-group-item text-muted">No categories yet.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
