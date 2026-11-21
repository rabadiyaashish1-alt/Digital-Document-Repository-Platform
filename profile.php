<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];

// ----- Fetch current user data -----
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ----- Update Profile Info -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $fullName = sanitize($_POST['full_name']);
    $phone    = sanitize($_POST['phone']);

    if (empty($fullName)) {
        setMessage('danger', 'Full name is required.');
    } else {
        $update = $conn->prepare("UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?");
        $update->bind_param("ssi", $fullName, $phone, $userId);
        $update->execute();
        $update->close();

        $_SESSION['full_name'] = $fullName;
        logActivity($conn, $userId, "Updated profile information", "Profile");
        setMessage('success', 'Profile updated successfully.');
    }
    redirect(BASE_URL . 'profile.php');
}

// ----- Upload Profile Picture -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_picture') {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
            setMessage('danger', 'Only JPG and PNG images are allowed for profile pictures.');
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            setMessage('danger', 'Profile picture must be less than 2 MB.');
        } else {
            if (!is_dir(PROFILE_DIR)) mkdir(PROFILE_DIR, 0755, true);

            $newName = 'profile_' . $userId . '_' . time() . '.' . $ext;
            $destination = PROFILE_DIR . $newName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $relativePath = PROFILE_DIR_REL . $newName;
                $update = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
                $update->bind_param("si", $relativePath, $userId);
                $update->execute();
                $update->close();

                $_SESSION['profile_picture'] = $relativePath;
                logActivity($conn, $userId, "Updated profile picture", "Profile");
                setMessage('success', 'Profile picture updated successfully.');
            } else {
                setMessage('danger', 'Failed to upload picture.');
            }
        }
    } else {
        setMessage('danger', 'Please choose an image to upload.');
    }
    redirect(BASE_URL . 'profile.php');
}

// ----- Change Password -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPassword = $_POST['current_password'];
    $newPassword     = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if (!password_verify($currentPassword, $user['password'])) {
        setMessage('danger', 'Current password is incorrect.');
    } elseif (strlen($newPassword) < 6) {
        setMessage('danger', 'New password must be at least 6 characters.');
    } elseif ($newPassword !== $confirmPassword) {
        setMessage('danger', 'New passwords do not match.');
    } else {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $update->bind_param("si", $hashed, $userId);
        $update->execute();
        $update->close();

        logActivity($conn, $userId, "Changed password", "Profile");
        setMessage('success', 'Password changed successfully.');
    }
    redirect(BASE_URL . 'profile.php');
}

$pageTitle = "My Profile";
require_once 'includes/header.php';
?>

<h3 class="fw-bold mb-4"><i class="bi bi-person-circle"></i> My Profile</h3>

<div class="row g-4">
    <!-- Profile Picture -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <img src="<?php echo BASE_URL . $user['profile_picture']; ?>" class="rounded-circle mb-3 border" width="140" height="140" style="object-fit:cover;">
                <h5><?php echo sanitize($user['full_name']); ?></h5>
                <p class="text-muted mb-3"><?php echo sanitize($user['email']); ?> <br>
                    <span class="badge bg-<?php echo $user['role']=='admin'?'danger':'primary'; ?> text-uppercase"><?php echo $user['role']; ?></span>
                </p>
                <form method="POST" action="profile.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_picture">
                    <input type="file" name="profile_picture" class="form-control mb-2" accept=".jpg,.jpeg,.png" required>
                    <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-upload"></i> Update Picture</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <!-- Edit Profile -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold"><i class="bi bi-pencil-square"></i> Edit Profile Information</div>
            <div class="card-body">
                <form method="POST" action="profile.php">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required value="<?php echo sanitize($user['full_name']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?php echo sanitize($user['username']); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" value="<?php echo sanitize($user['email']); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo sanitize($user['phone']); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold"><i class="bi bi-shield-lock"></i> Change Password</div>
            <div class="card-body">
                <form method="POST" action="profile.php">
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-key"></i> Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
