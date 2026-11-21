<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect based on session state
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(BASE_URL . 'admin/index.php');
    }
    redirect(BASE_URL . 'dashboard.php');
} else {
    redirect(BASE_URL . 'login.php');
}
?>
