<?php
// ============================================================
// includes/db.php — Database connection
// ============================================================

// Mysqli-style connection variables
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'museo_db';

// Try direct connection first
try {
	$conn = mysqli_connect($host, $user, $password, $database);
} catch (mysqli_sql_exception $e) {
	// If DB is missing, attempt local bootstrap by creating it.
	if ((int) $e->getCode() === 1049) {
		try {
			$conn = mysqli_connect($host, $user, $password);
			if ($conn) {
				$escapedDb = mysqli_real_escape_string($conn, $database);
				mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `{$escapedDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
				mysqli_select_db($conn, $database);
			}
		} catch (mysqli_sql_exception $inner) {
			die('Connection failed: ' . $inner->getMessage());
		}
	} else {
		die('Connection failed: ' . $e->getMessage());
	}
}

// Check connection
if (!$conn) {
	die('Connection failed: ' . mysqli_connect_error() . '. If this is a fresh setup, import uploads/museo_db.sql in phpMyAdmin.');
}

// First-run bootstrap: if core tables are missing, import bundled SQL dump.
$requiredTable = 'exhibits';
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE '{$requiredTable}'");
$hasSchema = $tableCheck && mysqli_num_rows($tableCheck) > 0;

if (!$hasSchema) {
	$sqlDumpPath = realpath(__DIR__ . '/../uploads/museo_db.sql');
	if (!$sqlDumpPath || !is_readable($sqlDumpPath)) {
		die('Database is empty and SQL dump was not found. Please import uploads/museo_db.sql.');
	}

	$sqlDump = file_get_contents($sqlDumpPath);
	if ($sqlDump === false || trim($sqlDump) === '') {
		die('Database SQL dump is empty or unreadable: uploads/museo_db.sql');
	}

	if (!mysqli_multi_query($conn, $sqlDump)) {
		die('Failed importing database schema: ' . mysqli_error($conn));
	}

	// Drain all result sets produced by multi_query.
	do {
		$result = mysqli_store_result($conn);
		if ($result instanceof mysqli_result) {
			mysqli_free_result($result);
		}
	} while (mysqli_more_results($conn) && mysqli_next_result($conn));

	$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE '{$requiredTable}'");
	$hasSchema = $tableCheck && mysqli_num_rows($tableCheck) > 0;
	if (!$hasSchema) {
		die('Database initialized but required tables are still missing. Please import uploads/museo_db.sql manually.');
	}
}

// Keep existing constants for the PDO-based app core
define('DB_HOST', $host);
define('DB_USER', $user);
define('DB_PASS', $password);
define('DB_NAME', $database);
define('DB_CHARSET', 'utf8mb4');
