<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];

// ---------------------------------------------------------------------
// Handle Permanent Delete
// ---------------------------------------------------------------------
if (isset($_GET['delete'])) {
    $docId = (int) $_GET['delete'];

    // Check that the document belongs to the logged-in user
    $stmt = $conn->prepare("
        SELECT document_id, document_name, file_path
        FROM documents
        WHERE document_id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $docId, $userId);
    $stmt->execute();
    $doc = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($doc) {

        // -------------------------------------------------------------
        // 1. Get ALL uploaded file paths for this document
        //    This includes old versions.
        // -------------------------------------------------------------
        $files = [];

        $fileStmt = $conn->prepare("
            SELECT file_path
            FROM uploads
            WHERE document_id = ?
        ");
        $fileStmt->bind_param("i", $docId);
        $fileStmt->execute();
        $fileResult = $fileStmt->get_result();

        while ($file = $fileResult->fetch_assoc()) {
            $files[] = $file['file_path'];
        }

        $fileStmt->close();

        // Also include the current document file
        $files[] = $doc['file_path'];

        // -------------------------------------------------------------
        // 2. Delete all physical files from uploads/documents/
        // -------------------------------------------------------------
        foreach ($files as $relativePath) {

            $fullPath = __DIR__ . '/' . $relativePath;

            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        // -------------------------------------------------------------
        // 3. Permanently delete document from documents table
        // -------------------------------------------------------------
        $del = $conn->prepare("
            DELETE FROM documents
            WHERE document_id = ? AND user_id = ?
        ");
        $del->bind_param("ii", $docId, $userId);

        if ($del->execute()) {

            // uploads table records are automatically deleted
            // because database.sql uses ON DELETE CASCADE.

            logActivity(
                $conn,
                $userId,
                "Permanently deleted document: " . $doc['document_name'],
                "Document"
            );

            setMessage('success', 'Document and all its versions deleted successfully.');

        } else {

            setMessage('danger', 'Unable to delete document from database.');

        }

        $del->close();

    } else {

        setMessage('danger', 'Document not found or access denied.');

    }

    redirect(BASE_URL . 'documents.php');
}

// ---------------------------------------------------------------------
// Search / Filter (Module 5: Search System)
// ---------------------------------------------------------------------
$searchName = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$searchCategory = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$searchDate = isset($_GET['date']) ? sanitize($_GET['date']) : '';

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
if (!empty($searchDate)) {
    $sql .= " AND DATE(d.uploaded_at) = ?";
    $types .= "s";
    $params[] = $searchDate;
}

$sql .= " ORDER BY d.uploaded_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$documents = $stmt->get_result();
$stmt->close();

$categories = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");

// ---------------------------------------------------------------------
// Single Document View (Preview + Version Info)
// ---------------------------------------------------------------------
$viewDoc = null;
$versions = null;
if (isset($_GET['view'])) {
    $viewId = (int) $_GET['view'];
    $stmt = $conn->prepare("SELECT d.*, c.category_name FROM documents d
                             LEFT JOIN categories c ON d.category_id = c.category_id
                             WHERE d.document_id = ? AND d.user_id = ? AND d.status='active'");
    $stmt->bind_param("ii", $viewId, $userId);
    $stmt->execute();
    $viewDoc = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($viewDoc) {
        $vstmt = $conn->prepare("SELECT * FROM uploads WHERE document_id = ? ORDER BY version_no DESC");
        $vstmt->bind_param("i", $viewId);
        $vstmt->execute();
        $versions = $vstmt->get_result();
        $vstmt->close();
    }
}

$pageTitle = "My Documents";
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h3 class="fw-bold"><i class="bi bi-files"></i> My Documents</h3>
    <a href="upload.php" class="btn btn-primary"><i class="bi bi-cloud-upload"></i> Upload New Document</a>
</div>

<!-- Search Form -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="documents.php" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search by Name</label>
                <input type="text" name="q" class="form-control" placeholder="Document name..." value="<?php echo sanitize($searchName); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="0">All Categories</option>
                    <?php $categories->data_seek(0); while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['category_id']; ?>" <?php echo $searchCategory == $cat['category_id'] ? 'selected' : ''; ?>>
                            <?php echo sanitize($cat['category_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Upload Date</label>
                <input type="date" name="date" class="form-control" value="<?php echo sanitize($searchDate); ?>">
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i> Search</button>
            </div>
        </form>
    </div>
</div>

<?php if ($viewDoc): ?>
<!-- Document Detail / Preview Panel -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-bold d-flex justify-content-between">
        <span><i class="bi bi-eye"></i> Document Preview</span>
        <a href="documents.php" class="btn-close" aria-label="Close"></a>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <?php
                $ext = strtolower($viewDoc['file_type']);
                $fileUrl = BASE_URL . $viewDoc['file_path'];
                if (in_array($ext, ['jpg', 'jpeg', 'png'])): ?>
                    <img src="<?php echo $fileUrl; ?>" class="img-fluid rounded border" alt="Preview">
                <?php elseif ($ext === 'pdf'): ?>
                    <embed src="<?php echo $fileUrl; ?>" type="application/pdf" width="100%" height="400px" class="border rounded">
                <?php else: ?>
                    <div class="text-center py-5 border rounded bg-light">
                        <i class="bi <?php echo getFileIcon($ext); ?>" style="font-size:4rem;"></i>
                        <p class="mt-2 text-muted">Preview not available for this file type.<br>Please download to view.</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <h5><?php echo sanitize($viewDoc['document_name']); ?></h5>
                <p class="text-muted"><?php echo sanitize($viewDoc['description'] ?: 'No description provided.'); ?></p>
                <table class="table table-sm">
                    <tr><th>Category</th><td><?php echo sanitize($viewDoc['category_name'] ?? 'Uncategorized'); ?></td></tr>
                    <tr><th>File Type</th><td class="text-uppercase"><?php echo sanitize($viewDoc['file_type']); ?></td></tr>
                    <tr><th>File Size</th><td><?php echo formatSize($viewDoc['file_size']); ?></td></tr>
                    <tr><th>Current Version</th><td>v<?php echo $viewDoc['version_no']; ?></td></tr>
                    <tr><th>Uploaded On</th><td><?php echo date('d M Y, h:i A', strtotime($viewDoc['uploaded_at'])); ?></td></tr>
                    <tr><th>Last Updated</th><td><?php echo date('d M Y, h:i A', strtotime($viewDoc['updated_at'])); ?></td></tr>
                </table>
                <div class="d-flex gap-2">
                    <a href="download.php?id=<?php echo $viewDoc['document_id']; ?>" class="btn btn-success"><i class="bi bi-download"></i> Download</a>
                    <a href="edit-document.php?id=<?php echo $viewDoc['document_id']; ?>" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> Edit Details</a>
                    <a href="documents.php?delete=<?php echo $viewDoc['document_id']; ?>" class="btn btn-outline-danger"
                       onclick="return confirm('Are you sure you want to delete this document?');"><i class="bi bi-trash"></i> Delete</a>
                </div>

                <h6 class="mt-4"><i class="bi bi-clock-history"></i> Version History</h6>
                <ul class="list-group list-group-flush small">
                    <?php while ($v = $versions->fetch_assoc()): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Version <?php echo $v['version_no']; ?> - <?php echo formatSize($v['file_size']); ?></span>
                        <span class="text-muted"><?php echo date('d M Y', strtotime($v['uploaded_at'])); ?></span>
                    </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Documents Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Document Name</th><th>Category</th><th>Type</th><th>Size</th><th>Version</th><th>Uploaded On</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($documents->num_rows > 0): ?>
                    <?php while ($doc = $documents->fetch_assoc()): ?>
                    <tr>
                        <td><i class="bi <?php echo getFileIcon($doc['file_type']); ?>"></i> <?php echo sanitize($doc['document_name']); ?></td>
                        <td><span class="badge bg-secondary"><?php echo sanitize($doc['category_name'] ?? 'Uncategorized'); ?></span></td>
                        <td class="text-uppercase"><?php echo sanitize($doc['file_type']); ?></td>
                        <td><?php echo formatSize($doc['file_size']); ?></td>
                        <td>v<?php echo $doc['version_no']; ?></td>
                        <td><?php echo date('d M Y', strtotime($doc['uploaded_at'])); ?></td>
                        <td class="text-end">
                            <a href="documents.php?view=<?php echo $doc['document_id']; ?>" class="btn btn-sm btn-outline-secondary" title="Preview"><i class="bi bi-eye"></i></a>
                            <a href="download.php?id=<?php echo $doc['document_id']; ?>" class="btn btn-sm btn-outline-success" title="Download"><i class="bi bi-download"></i></a>
                            <a href="edit-document.php?id=<?php echo $doc['document_id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                            <a href="documents.php?delete=<?php echo $doc['document_id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete"
                               onclick="return confirm('Are you sure you want to delete this document?');"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center text-muted py-5">No documents found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
