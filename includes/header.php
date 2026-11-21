<?php
// Common page header + top navigation bar (user side)
if (!isset($conn)) { require_once __DIR__ . '/../config/database.php'; }
if (!isset($_SESSION)) { require_once __DIR__ . '/../config/config.php'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? sanitize($pageTitle) . ' - ' . SITE_NAME : SITE_NAME; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="<?php echo BASE_URL; ?>dashboard.php">
            <i class="bi bi-folder2-open"></i> <?php echo SITE_NAME; ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <?php if (isLoggedIn()): ?>
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>documents.php"><i class="bi bi-files"></i> Documents</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>upload.php"><i class="bi bi-cloud-upload"></i> Upload</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>user/categories.php"><i class="bi bi-tags"></i> Categories</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>user/search.php"><i class="bi bi-search"></i> Search</a></li>
                <?php if (isAdmin()): ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>admin/index.php"><i class="bi bi-shield-lock"></i> Admin Panel</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <img src="<?php echo BASE_URL . (isset($_SESSION['profile_picture']) ? $_SESSION['profile_picture'] : 'assets/images/default-avatar.png'); ?>"
                             class="rounded-circle me-2" width="30" height="30" style="object-fit:cover;">
                        <?php echo sanitize($_SESSION['full_name'] ?? 'User'); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>profile.php"><i class="bi bi-person-circle"></i> My Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container my-4">
    <?php showMessage(); ?>
