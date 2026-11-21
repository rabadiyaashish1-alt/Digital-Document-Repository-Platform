<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$token = isset($_GET['token']) ? sanitize($_GET['token']) : (isset($_POST['token']) ? sanitize($_POST['token']) : '');
$validToken = false;
$userId = null;

if (!empty($token)) {
    $stmt = $conn->prepare("SELECT user_id, reset_expiry FROM users WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (strtotime($row['reset_expiry']) > time()) {
            $validToken = true;
            $userId = $row['user_id'];
        } else {
            setMessage('danger', 'This reset link has expired. Please request a new one.');
        }
    } else {
        setMessage('danger', 'Invalid or already used reset link.');
    }
    $stmt->close();
} else {
    setMessage('danger', 'No reset token provided.');
}

if ($validToken && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if (strlen($password) < 6) {
        setMessage('danger', 'Password must be at least 6 characters.');
    } elseif ($password !== $confirm) {
        setMessage('danger', 'Passwords do not match.');
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE user_id = ?");
        $update->bind_param("si", $hashed, $userId);
        $update->execute();
        $update->close();

        logActivity($conn, $userId, "Reset password", "Auth");
        setMessage('success', 'Password reset successful! Please login with your new password.');
        redirect(BASE_URL . 'login.php');
    }
}

$pageTitle = "Reset Password";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link href="<?php echo BASE_URL; ?>assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-page d-flex align-items-center">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-shield-lock display-4 text-primary"></i>
                        <h3 class="mt-2 fw-bold">Reset Password</h3>
                    </div>

                    <?php showMessage(); ?>

                    <?php if ($validToken): ?>
                    <form method="POST" action="reset-password.php" novalidate>
                        <input type="hidden" name="token" value="<?php echo sanitize($token); ?>">
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2">Reset Password</button>
                    </form>
                    <?php else: ?>
                        <p class="text-center"><a href="forgot-password.php">Request a new reset link</a></p>
                    <?php endif; ?>

                    <p class="text-center mt-4 mb-0"><a href="login.php"><i class="bi bi-arrow-left"></i> Back to Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
