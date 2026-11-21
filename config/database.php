<?php
/**
 * Database Connection File
 * Uses MySQLi (Object Oriented) with prepared statements throughout the project
 * to prevent SQL Injection.
 */

// ----- Database Configuration (change these as per your XAMPP setup) -----
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');            // default XAMPP MySQL password is empty
define('DB_NAME', 'digital_document_repository');

// ----- Create Connection -----
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// ----- Check Connection -----
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Set charset to avoid encoding issues
$conn->set_charset("utf8mb4");
?>
