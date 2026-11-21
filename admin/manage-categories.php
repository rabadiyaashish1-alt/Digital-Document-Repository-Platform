<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
requireAdmin();
 
$adminId = $_SESSION['user_id'];
 
// ----- Create -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'create') {
    $name = sanitize($_POST['category_name']);
    $desc = sanitize($_POST['description']);
 
    if (empty($name)) {
        setMessage('danger', 'Category name is required.');
    } else {
        $check = $conn->prepare("SELECT category_id FROM categories WHERE category_name = ?");
        $check->bind_param("s", $name);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            setMessage('danger', 'Category already exists.');
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (category_name, description, created_by) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $name, $desc, $adminId);
            $stmt->execute();
            $stmt->close();
            logActivity($conn, $adminId, "Created category: $name", "Category Management");
            setMessage('success', 'Category created.');
        }
        $check->close();
    }
    redirect(BASE_URL . 'admin/manage-categories.php');
}
 
// ----- Edit -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'edit') {
    $catId = (int) $_POST['category_id'];
    $name  = sanitize($_POST['category_name']);
    $desc  = sanitize($_POST['description']);
 
    $stmt = $conn->prepare("UPDATE categories SET category_name=?, description=? WHERE category_id=?");
    $stmt->bind_param("ssi", $name, $desc, $catId);
    $stmt->execute();
    $stmt->close();
 
    logActivity($conn, $adminId, "Updated category: $name", "Category Management");
    setMessage('success', 'Category updated.');
    redirect(BASE_URL . 'admin/manage-categories.php');
}
 
// ----- Delete -----
if (isset($_GET['delete'])) {
    $catId = (int) $_GET['delete'];
    $check = $conn->prepare("SELECT COUNT(*) AS c FROM documents WHERE category_id = ? AND status='active'");
    $check->bind_param("i", $catId);
    $check->execute();
    $inUse = $check->get_result()->fetch_assoc()['c'];
    $check->close();
 
    if ($inUse > 0) {
        setMessage('warning', "Cannot delete: $inUse document(s) use this category.");
    } else {
        $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->bind_param("i", $catId);
        $stmt->execute();
        $stmt->close();
        logActivity($conn, $adminId, "Deleted category ID: $catId", "Category Management");
        setMessage('success', 'Category deleted.');
    }
    redirect(BASE_URL . 'admin/manage-categories.php');
}
 
$categories = $conn->query("SELECT c.category_id, c.category_name, c.description, c.created_by, c.created_at,
                                    u.full_name AS creator, COUNT(d.document_id) AS doc_count
                             FROM categories c
                             LEFT JOIN users u ON c.created_by = u.user_id
                             LEFT JOIN documents d ON c.category_id = d.category_id AND d.status='active'
                             GROUP BY c.category_id, c.category_name, c.description, c.created_by, c.created_at, u.full_name
                             ORDER BY c.category_name ASC");
 
if ($categories === false) {
    die('Database query failed: ' . $conn->error);
}
 
$pageTitle = "Manage Categories";
require_once 'includes/admin-header.php';
?>
 
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold"><i class="bi bi-tags-fill"></i> Manage Categories</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCatModal"><i class="bi bi-plus-circle"></i> New Category</button>
</div>
 
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Name</th><th>Description</th><th>Created By</th><th>Documents</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php if ($categories->num_rows > 0): while ($cat = $categories->fetch_assoc()): ?>
                    <tr>
                        <td><i class="bi bi-folder-fill text-warning"></i> <?php echo sanitize($cat['category_name']); ?></td>
                        <td><?php echo sanitize($cat['description'] ?: '-'); ?></td>
                        <td><?php echo sanitize($cat['creator'] ?? 'System'); ?></td>
                        <td><span class="badge bg-info"><?php echo $cat['doc_count']; ?></span></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCat<?php echo $cat['category_id']; ?>"><i class="bi bi-pencil"></i></button>
                            <a href="manage-categories.php?delete=<?php echo $cat['category_id']; ?>" class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Delete this category?');"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
 
                    <div class="modal fade" id="editCat<?php echo $cat['category_id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="manage-categories.php">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="category_id" value="<?php echo $cat['category_id']; ?>">
                                    <div class="modal-header"><h5 class="modal-title">Edit Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body">
                                        <div class="mb-3"><label class="form-label">Name</label><input type="text" name="category_name" class="form-control" value="<?php echo sanitize($cat['category_name']); ?>" required></div>
                                        <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control"><?php echo sanitize($cat['description']); ?></textarea></div>
                                    </div>
                                    <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; else: ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No categories yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
 
<div class="modal fade" id="addCatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="manage-categories.php">
                <input type="hidden" name="action" value="create">
                <div class="modal-header"><h5 class="modal-title">New Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Name *</label><input type="text" name="category_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control"></textarea></div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Create</button></div>
            </form>
        </div>
    </div>
</div>
 
<?php require_once 'includes/admin-footer.php'; ?>