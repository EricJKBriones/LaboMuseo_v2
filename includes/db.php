<?php
// ============================================================
// includes/db.php — Database connection
// ============================================================

// Mysqli-style connection variables
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'museo_db';

// Create connection
$conn = mysqli_connect($host, $user, $password, $database);

// Check connection
if (!$conn) {
	die('Connection failed: ' . mysqli_connect_error());
}

// Keep existing constants for the PDO-based app core
define('DB_HOST', $host);
define('DB_USER', $user);
define('DB_PASS', $password);
define('DB_NAME', $database);
define('DB_CHARSET', 'utf8mb4');

require_once __DIR__ . '/app_core.php';
