<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];

$searchName     = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$searchCategory = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$dateFrom       = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$dateTo         = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

$results = null;
$searched = isset($_GET['search']);

if ($searched) {
    $sql = "SELECT d.*, c.category_name FROM documents d
            LEFT JOIN categories c ON d.category_id = c.category_id
            WHERE d.user_id = ? AND d.status = 'active'";
    $types = "i";
    $params = [$userId];

    if (!empty($searchName)) {
        $sql .= " AND d.document_name LIKE ?";
        $types .= "s";
        $params[] = "%$searchName%";
    }
    if ($searchCategory > 0) {
        $sql .= " AND d.category_id = ?";
        $types .= "i";
        $params[] = $searchCategory;
    }
    if (!empty($dateFrom)) {
        $sql .= " AND DATE(d.uploaded_at) >= ?";
        $types .= "s";
        $params[] = $dateFrom;
    }
    if (!empty($dateTo)) {
        $sql .= " AND DATE(d.uploaded_at) <= ?";
        $types .= "s";
        $params[] = $dateTo;
    }
    $sql .= " ORDER BY d.uploaded_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $results = $stmt->get_result();
    $stmt->close();
}

$categories = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");

$pageTitle = "Advanced Search";
require_once '../includes/header.php';
?>

<h3 class="fw-bold mb-4"><i class="bi bi-search"></i> Advanced Document Search</h3>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="search.php" class="row g-3 align-items-end">
            <input type="hidden" name="search" value="1">
            <div class="col-md-3">
                <label class="form-label">Document Name</label>
                <input type="text" name="q" class="form-control" value="<?php echo sanitize($searchName); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="0">All Categories</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['category_id']; ?>" <?php echo $searchCategory == $cat['category_id'] ? 'selected' : ''; ?>>
                            <?php echo sanitize($cat['category_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">From Date</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo sanitize($dateFrom); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">To Date</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo sanitize($dateTo); ?>">
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
            </div>
        </form>
    </div>
</div>

<?php if ($searched): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold">Search Results (<?php echo $results->num_rows; ?> found)</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Document</th><th>Category</th><th>Size</th><th>Uploaded</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                <?php if ($results->num_rows > 0): ?>
                    <?php while ($doc = $results->fetch_assoc()): ?>
                    <tr>
                        <td><i class="bi <?php echo getFileIcon($doc['file_type']); ?>"></i> <?php echo sanitize($doc['document_name']); ?></td>
                        <td><span class="badge bg-secondary"><?php echo sanitize($doc['category_name'] ?? 'Uncategorized'); ?></span></td>
                        <td><?php echo formatSize($doc['file_size']); ?></td>
                        <td><?php echo date('d M Y', strtotime($doc['uploaded_at'])); ?></td>
                        <td class="text-end">
                            <a href="../documents.php?view=<?php echo $doc['document_id']; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                            <a href="../download.php?id=<?php echo $doc['document_id']; ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-download"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No documents matched your search.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
