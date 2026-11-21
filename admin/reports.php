<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
requireAdmin();

// ----- Filter by date range -----
$dateFrom = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : date('Y-m-01');
$dateTo   = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : date('Y-m-d');

$stmt = $conn->prepare("SELECT d.document_name, d.file_type, d.file_size, d.uploaded_at, u.full_name, c.category_name
                         FROM documents d
                         JOIN users u ON d.user_id = u.user_id
                         LEFT JOIN categories c ON d.category_id = c.category_id
                         WHERE d.status='active' AND DATE(d.uploaded_at) BETWEEN ? AND ?
                         ORDER BY d.uploaded_at DESC");
$stmt->bind_param("ss", $dateFrom, $dateTo);
$stmt->execute();
$reportData = $stmt->get_result();
$stmt->close();

// ----- Summary counts by file type -----
$typeStats = $conn->query("SELECT file_type, COUNT(*) AS total FROM documents WHERE status='active' GROUP BY file_type ORDER BY total DESC");

// ----- Monthly upload trend (last 6 months) -----
$trend = $conn->query("SELECT ANY_VALUE(DATE_FORMAT(uploaded_at, '%b %Y')) AS month, COUNT(*) AS total
                        FROM documents WHERE status='active'
                        AND uploaded_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                        GROUP BY DATE_FORMAT(uploaded_at, '%Y-%m')
                        ORDER BY MIN(uploaded_at) ASC");

$pageTitle = "Upload Reports";
require_once 'includes/admin-header.php';
?>

<h3 class="fw-bold mb-4"><i class="bi bi-bar-chart-fill"></i> Upload Reports & Analytics</h3>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">Documents by File Type</div>
            <ul class="list-group list-group-flush">
                <?php while ($t = $typeStats->fetch_assoc()): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-uppercase"><i class="bi <?php echo getFileIcon($t['file_type']); ?>"></i> <?php echo sanitize($t['file_type']); ?></span>
                    <span class="badge bg-primary rounded-pill"><?php echo $t['total']; ?></span>
                </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">Upload Trend (Last 6 Months)</div>
            <ul class="list-group list-group-flush">
                <?php if ($trend->num_rows > 0): while ($m = $trend->fetch_assoc()): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <?php echo sanitize($m['month']); ?>
                    <span class="badge bg-success rounded-pill"><?php echo $m['total']; ?></span>
                </li>
                <?php endwhile; else: ?>
                    <li class="list-group-item text-muted">No data available.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">From Date</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo sanitize($dateFrom); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">To Date</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo sanitize($dateTo); ?>">
            </div>
            <div class="col-md-4 d-grid">
                <button class="btn btn-primary"><i class="bi bi-filter"></i> Generate Report</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold d-flex justify-content-between">
        <span>Detailed Upload Report (<?php echo $reportData->num_rows; ?> records)</span>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i> Print</button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>Document</th><th>Uploaded By</th><th>Category</th><th>Type</th><th>Size</th><th>Date</th></tr></thead>
                <tbody>
                <?php if ($reportData->num_rows > 0): while ($r = $reportData->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo sanitize($r['document_name']); ?></td>
                        <td><?php echo sanitize($r['full_name']); ?></td>
                        <td><?php echo sanitize($r['category_name'] ?? 'Uncategorized'); ?></td>
                        <td class="text-uppercase"><?php echo sanitize($r['file_type']); ?></td>
                        <td><?php echo formatSize($r['file_size']); ?></td>
                        <td><?php echo date('d M Y, h:i A', strtotime($r['uploaded_at'])); ?></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No uploads found in this date range.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
