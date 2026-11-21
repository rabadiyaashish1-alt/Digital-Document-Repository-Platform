<?php
// Admin panel shared header + sidebar
if (!isset($conn)) { require_once __DIR__ . '/../../config/database.php'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? sanitize($pageTitle) . ' - Admin Panel' : 'Admin Panel'; ?> | <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link href="<?php echo BASE_URL; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <nav class="admin-sidebar bg-dark text-white p-3 vh-100 position-sticky top-0">
        <a href="<?php echo BASE_URL; ?>admin/index.php" class="d-flex align-items-center mb-4 text-white text-decoration-none">
            <i class="bi bi-shield-lock-fill fs-3 me-2"></i>
            <span class="fs-5 fw-bold">Admin Panel</span>
        </a>
        <ul class="nav nav-pills flex-column gap-1">
            <li class="nav-item"><a href="<?php echo BASE_URL; ?>admin/index.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF'])=='index.php'?'active':''; ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li class="nav-item"><a href="<?php echo BASE_URL; ?>admin/manage-users.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF'])=='manage-users.php'?'active':''; ?>"><i class="bi bi-people-fill"></i> Manage Users</a></li>
            <li class="nav-item"><a href="<?php echo BASE_URL; ?>admin/manage-documents.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF'])=='manage-documents.php'?'active':''; ?>"><i class="bi bi-files"></i> Manage Documents</a></li>
            <li class="nav-item"><a href="<?php echo BASE_URL; ?>admin/manage-categories.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF'])=='manage-categories.php'?'active':''; ?>"><i class="bi bi-tags-fill"></i> Manage Categories</a></li>
            <li class="nav-item"><a href="<?php echo BASE_URL; ?>admin/reports.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF'])=='reports.php'?'active':''; ?>"><i class="bi bi-bar-chart-fill"></i> Upload Reports</a></li>
            <li class="nav-item"><hr class="text-secondary"></li>
            <li class="nav-item"><a href="<?php echo BASE_URL; ?>dashboard.php" class="nav-link text-white"><i class="bi bi-arrow-left-circle"></i> User Side</a></li>
            <li class="nav-item"><a href="<?php echo BASE_URL; ?>profile.php" class="nav-link text-white"><i class="bi bi-person-circle"></i> My Profile</a></li>
            <li class="nav-item"><a href="<?php echo BASE_URL; ?>logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow-1 p-4 bg-light" style="min-height:100vh;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div></div>
            <div class="d-flex align-items-center">
                <img src="<?php echo BASE_URL . ($_SESSION['profile_picture'] ?? 'assets/images/default-avatar.png'); ?>" class="rounded-circle me-2" width="35" height="35" style="object-fit:cover;">
                <span class="fw-semibold"><?php echo sanitize($_SESSION['full_name'] ?? 'Admin'); ?></span>
            </div>
        </div>
        <?php showMessage(); ?>
