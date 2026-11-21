<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

/*
 * NOTE FOR STUDENTS / SETUP:
 * A live SMTP server is not available in the local XAMPP environment,
 * so instead of emailing the reset link, this demo project directly
 * displays the reset link on screen after a valid email is submitted.
 * In a production project you would use PHPMailer to actually send the email.
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setMessage('danger', 'Please enter a valid email address.');
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $token  = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $update = $conn->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE user_id = ?");
            $update->bind_param("ssi", $token, $expiry, $user['user_id']);
            $update->execute();
            $update->close();

            $resetLink = BASE_URL . "reset-password.php?token=" . $token;
            setMessage('success', "Reset link generated. Since this is a local demo (no SMTP configured), click the link below to reset your password:<br><a href='$resetLink'>$resetLink</a>");
        } else {
            // For security, do not reveal whether the email exists or not
            setMessage('info', 'If that email exists in our system, a reset link has been generated.');
        }
        $stmt->close();
    }
}

$pageTitle = "Forgot Password";
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
                        <i class="bi bi-key display-4 text-primary"></i>
                        <h3 class="mt-2 fw-bold">Forgot Password</h3>
                        <p class="text-muted">Enter your registered email to reset your password</p>
                    </div>

                    <?php showMessage(); ?>

                    <form method="POST" action="forgot-password.php" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2">Send Reset Link</button>
                    </form>

                    <p class="text-center mt-4 mb-0"><a href="login.php"><i class="bi bi-arrow-left"></i> Back to Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
