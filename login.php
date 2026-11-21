<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// If already logged in, send to the correct side
if (isLoggedIn()) {
    redirect(BASE_URL . (isAdmin() ? 'admin/index.php' : 'dashboard.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usernameOrEmail = sanitize($_POST['username']);
    $password         = $_POST['password'];

    if (empty($usernameOrEmail) || empty($password)) {
        setMessage('danger', 'Please enter both username/email and password.');
    } else {
        // ----- Fetch user using Prepared Statement -----
        $stmt = $conn->prepare("SELECT user_id, full_name, username, email, password, role, status, profile_picture
                                 FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if ($user['role'] === 'admin') {
                // ----- This login form is for regular users only -----
                setMessage('danger', 'This account is an administrator account. Please use the <a href="admin/login.php">Admin Login</a> page.');
            } elseif ($user['status'] === 'blocked') {
                setMessage('danger', 'Your account has been blocked. Please contact the administrator.');
            } elseif (password_verify($password, $user['password'])) {
                // ----- Set Session Variables -----
                $_SESSION['user_id']         = $user['user_id'];
                $_SESSION['full_name']       = $user['full_name'];
                $_SESSION['username']        = $user['username'];
                $_SESSION['email']           = $user['email'];
                $_SESSION['role']            = $user['role'];
                $_SESSION['profile_picture'] = $user['profile_picture'];

                logActivity($conn, $user['user_id'], "Logged in", "Auth");
                redirect(BASE_URL . 'dashboard.php');
            } else {
                setMessage('danger', 'Invalid password. Please try again.');
            }
        } else {
            setMessage('danger', 'No account found with that username/email.');
        }
        $stmt->close();
    }
}

$pageTitle = "User Login";
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
                        <i class="bi bi-folder2-open display-4 text-primary"></i>
                        <h3 class="mt-2 fw-bold"><?php echo SITE_NAME; ?></h3>
                        <p class="text-muted">User Login</p>
                    </div>

                    <?php showMessage(); ?>

                    <form method="POST" action="login.php" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Username or Email</label>
                            <input type="text" name="username" class="form-control" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <a href="forgot-password.php" class="small">Forgot Password?</a>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2">Login</button>
                    </form>

                    <p class="text-center mt-4 mb-0">Don't have an account? <a href="register.php">Register here</a></p>
                    <p class="text-center mt-2 mb-0"><a href="admin/login.php" class="text-muted small"><i class="bi bi-shield-lock"></i> Administrator? Login here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
