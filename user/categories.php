<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];

// ----- Create Category -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = sanitize($_POST['category_name']);
    $desc = sanitize($_POST['description']);

    if (empty($name)) {
        setMessage('danger', 'Category name is required.');
    } else {
        $check = $conn->prepare("SELECT category_id FROM categories WHERE category_name = ?");
        $check->bind_param("s", $name);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            setMessage('danger', 'This category already exists.');
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (category_name, description, created_by) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $name, $desc, $userId);
            $stmt->execute();
            $stmt->close();
            logActivity($conn, $userId, "Created category: $name", "Category");
            setMessage('success', 'Category created successfully.');
        }
        $check->close();
    }
    redirect(BASE_URL . 'user/categories.php');
}

// ----- Edit Category -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $catId = (int) $_POST['category_id'];
    $name  = sanitize($_POST['category_name']);
    $desc  = sanitize($_POST['description']);

    $stmt = $conn->prepare("UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?");
    $stmt->bind_param("ssi", $name, $desc, $catId);
    $stmt->execute();
    $stmt->close();

    logActivity($conn, $userId, "Updated category: $name", "Category");
    setMessage('success', 'Category updated successfully.');
    redirect(BASE_URL . 'user/categories.php');
}

// ----- Delete Category -----
if (isset($_GET['delete'])) {
    $catId = (int) $_GET['delete'];

    // Check if any documents use this category
    $check = $conn->prepare("SELECT COUNT(*) AS total FROM documents WHERE category_id = ? AND status='active'");
    $check->bind_param("i", $catId);
    $check->execute();
    $inUse = $check->get_result()->fetch_assoc()['total'];
    $check->close();

    if ($inUse > 0) {
        setMessage('warning', "Cannot delete: $inUse document(s) are still assigned to this category.");
    } else {
        $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->bind_param("i", $catId);
        $stmt->execute();
        $stmt->close();
        logActivity($conn, $userId, "Deleted category ID: $catId", "Category");
        setMessage('success', 'Category deleted successfully.');
    }
    redirect(BASE_URL . 'user/categories.php');
}

// ----- Fetch all categories with document counts -----
$categories = $conn->query("SELECT c.*, COUNT(d.document_id) AS doc_count
                             FROM categories c
                             LEFT JOIN documents d ON c.category_id = d.category_id AND d.status='active'
                             GROUP BY c.category_id
                             ORDER BY c.category_name ASC");

$pageTitle = "Categories";
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold"><i class="bi bi-tags"></i> Categories</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="bi bi-plus-circle"></i> New Category
    </button>
</div>

<div class="row g-4">
    <?php if ($categories->num_rows > 0): ?>
        <?php while ($cat = $categories->fetch_assoc()): ?>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5><i class="bi bi-folder-fill text-warning"></i> <?php echo sanitize($cat['category_name']); ?></h5>
                        <span class="badge bg-primary rounded-pill"><?php echo $cat['doc_count']; ?> docs</span>
                    </div>
                    <p class="text-muted small"><?php echo sanitize($cat['description'] ?: 'No description'); ?></p>
                    <div class="d-flex gap-2 mt-2">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $cat['category_id']; ?>">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <a href="categories.php?delete=<?php echo $cat['category_id']; ?>" class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Delete this category?');"><i class="bi bi-trash"></i> Delete</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editModal<?php echo $cat['category_id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="categories.php">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="category_id" value="<?php echo $cat['category_id']; ?>">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Category</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Category Name</label>
                                <input type="text" name="category_name" class="form-control" required value="<?php echo sanitize($cat['category_name']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control"><?php echo sanitize($cat['description']); ?></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="text-muted">No categories created yet.</p>
    <?php endif; ?>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="categories.php">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title">New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="category_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
