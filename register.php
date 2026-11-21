<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect(BASE_URL . 'dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = sanitize($_POST['full_name']);
    $email     = sanitize($_POST['email']);
    $username  = sanitize($_POST['username']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];

    $errors = [];

    // ----- Server Side Validation -----
    if (empty($full_name) || empty($email) || empty($username) || empty($password)) {
        $errors[] = "All fields are required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }
    if (strlen($username) < 4) {
        $errors[] = "Username must be at least 4 characters.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    // ----- Check duplicate email / username using Prepared Statement -----
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Email or Username already exists.";
        }
        $stmt->close();
    }

    // ----- Insert new user -----
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (full_name, email, username, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $full_name, $email, $username, $hashedPassword);

        if ($stmt->execute()) {
            $newUserId = $stmt->insert_id;
            logActivity($conn, $newUserId, "Registered a new account", "Auth");
            setMessage('success', 'Registration successful! Please login to continue.');
            $stmt->close();
            redirect(BASE_URL . 'login.php');
        } else {
            $errors[] = "Something went wrong. Please try again.";
        }
        $stmt->close();
    }

    if (!empty($errors)) {
        setMessage('danger', implode('<br>', $errors));
    }
}

$pageTitle = "Register";
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
                        <p class="text-muted">Create a new account</p>
                    </div>

                    <?php showMessage(); ?>

                    <form method="POST" action="register.php" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required
                                   value="<?php echo isset($_POST['full_name']) ? sanitize($_POST['full_name']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required minlength="4"
                                   value="<?php echo isset($_POST['username']) ? sanitize($_POST['username']) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2">Register</button>
                    </form>

                    <p class="text-center mt-4 mb-0">Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
