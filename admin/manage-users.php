<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
requireAdmin();

$adminId = $_SESSION['user_id'];

// ----- Block / Unblock -----
if (isset($_GET['toggle_status'])) {
    $uid = (int) $_GET['toggle_status'];
    $stmt = $conn->prepare("SELECT status, full_name FROM users WHERE user_id = ? AND role='user'");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($u) {
        $newStatus = $u['status'] === 'active' ? 'blocked' : 'active';
        $update = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
        $update->bind_param("si", $newStatus, $uid);
        $update->execute();
        $update->close();

        logActivity($conn, $adminId, ucfirst($newStatus) . " user: " . $u['full_name'], "User Management");
        setMessage('success', "User has been $newStatus.");
    }
    redirect(BASE_URL . 'admin/manage-users.php');
}

// ----- Delete User (also removes their documents via ON DELETE CASCADE) -----
if (isset($_GET['delete'])) {
    $uid = (int) $_GET['delete'];
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ? AND role='user'");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($u) {
        $del = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $del->bind_param("i", $uid);
        $del->execute();
        $del->close();

        logActivity($conn, $adminId, "Deleted user: " . $u['full_name'], "User Management");
        setMessage('success', 'User deleted successfully.');
    } else {
        setMessage('danger', 'User not found.');
    }
    redirect(BASE_URL . 'admin/manage-users.php');
}

// ----- Search -----
$search = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$sql = "SELECT u.*, COUNT(d.document_id) AS doc_count
        FROM users u LEFT JOIN documents d ON u.user_id = d.user_id AND d.status='active'
        WHERE u.role = 'user'";
$types = "";
$params = [];
if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)";
    $types = "sss";
    $like = "%$search%";
    $params = [$like, $like, $like];
}
$sql .= " GROUP BY u.user_id ORDER BY u.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($types)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result();
$stmt->close();

$pageTitle = "Manage Users";
require_once 'includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h3 class="fw-bold"><i class="bi bi-people-fill"></i> Manage Users</h3>
    <form method="GET" class="d-flex" style="max-width:300px;">
        <input type="text" name="q" class="form-control me-2" placeholder="Search users..." value="<?php echo sanitize($search); ?>">
        <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
    </form>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>#</th><th>Name</th><th>Email</th><th>Username</th><th>Documents</th><th>Status</th><th>Joined</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                <?php if ($users->num_rows > 0): $i=1; while ($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><img src="<?php echo BASE_URL . $u['profile_picture']; ?>" width="30" height="30" class="rounded-circle me-2" style="object-fit:cover;"><?php echo sanitize($u['full_name']); ?></td>
                        <td><?php echo sanitize($u['email']); ?></td>
                        <td><?php echo sanitize($u['username']); ?></td>
                        <td><span class="badge bg-info"><?php echo $u['doc_count']; ?></span></td>
                        <td><span class="badge bg-<?php echo $u['status']=='active'?'success':'danger'; ?>"><?php echo ucfirst($u['status']); ?></span></td>
                        <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                        <td class="text-end">
                            <a href="manage-users.php?toggle_status=<?php echo $u['user_id']; ?>" class="btn btn-sm btn-outline-warning" title="<?php echo $u['status']=='active'?'Block':'Unblock'; ?>">
                                <i class="bi bi-<?php echo $u['status']=='active'?'lock-fill':'unlock-fill'; ?>"></i>
                            </a>
                            <a href="manage-users.php?delete=<?php echo $u['user_id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete"
                               onclick="return confirm('Delete this user and all their documents permanently?');"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No users found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
