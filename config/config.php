<?php
/**
 * Global Configuration File
 * Starts the session and defines site-wide constants
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----- Site Constants -----
define('SITE_NAME', 'Digital Document Repository');
define('BASE_URL', '/Digital-Document-Repository/'); // change if folder name differs

// ----- Upload Constants -----
define('UPLOAD_DIR', __DIR__ . '/../uploads/documents/');
define('UPLOAD_DIR_REL', 'uploads/documents/');
define('PROFILE_DIR', __DIR__ . '/../uploads/profile_pics/');
define('PROFILE_DIR_REL', 'uploads/profile_pics/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_EXTENSIONS', ['pdf', 'docx', 'doc', 'ppt', 'pptx', 'jpg', 'jpeg', 'png']);

// ----- Error Reporting (disable display_errors in production) -----
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
