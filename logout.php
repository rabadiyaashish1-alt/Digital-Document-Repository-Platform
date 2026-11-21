<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$wasAdmin = false;
if (isLoggedIn()) {
    logActivity($conn, $_SESSION['user_id'], "Logged out", "Auth");
    $wasAdmin = isAdmin();
}

// ----- Destroy session completely -----
$_SESSION = [];
session_unset();
session_destroy();

// Start a new session just to show the flash message on the correct login page
session_start();
setMessage('success', 'You have been logged out successfully.');
redirect(BASE_URL . ($wasAdmin ? 'admin/login.php' : 'login.php'));
?>
