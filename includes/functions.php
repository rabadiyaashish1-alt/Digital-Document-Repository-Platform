<?php
/**
 * Common Helper Functions used across the project
 */

/**
 * Sanitize user input to prevent XSS
 */
function sanitize($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect helper
 */
function redirect($url)
{
    header("Location: " . $url);
    exit();
}

/**
 * Check if a user is logged in
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/**
 * Check if the logged-in user is an admin
 */
function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Force login - redirects to login page if not authenticated
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        redirect(BASE_URL . 'login.php');
    }
}

/**
 * Force admin access only
 */
function requireAdmin()
{
    if (!isLoggedIn()) {
        redirect(BASE_URL . 'admin/login.php');
    }
    if (!isAdmin()) {
        redirect(BASE_URL . 'dashboard.php');
    }
}

/**
 * Set a flash message to session (shown once, then cleared)
 */
function setMessage($type, $message)
{
    $_SESSION['flash_type'] = $type;   // 'success' | 'danger' | 'warning' | 'info'
    $_SESSION['flash_message'] = $message;
}

/**
 * Display and clear the flash message (Bootstrap alert)
 */
function showMessage()
{
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_type'];
        $msg  = $_SESSION['flash_message'];
        echo "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
                $msg
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    }
}

/**
 * Log user/admin activity into activity_logs table
 */
function logActivity($conn, $user_id, $action, $module)
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, module, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $action, $module, $ip);
    $stmt->execute();
    $stmt->close();
}

/**
 * Format bytes into human readable size
 */
function formatSize($bytes)
{
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' bytes';
}

/**
 * Return a Bootstrap icon class based on file extension
 */
function getFileIcon($ext)
{
    $ext = strtolower($ext);
    switch ($ext) {
        case 'pdf':
            return 'bi-file-earmark-pdf-fill text-danger';
        case 'doc':
        case 'docx':
            return 'bi-file-earmark-word-fill text-primary';
        case 'ppt':
        case 'pptx':
            return 'bi-file-earmark-ppt-fill text-warning';
        case 'jpg':
        case 'jpeg':
        case 'png':
            return 'bi-file-earmark-image-fill text-success';
        default:
            return 'bi-file-earmark-fill text-secondary';
    }
}
?>
