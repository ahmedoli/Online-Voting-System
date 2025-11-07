<?php
// Check if MySQLi extension is loaded
if (!extension_loaded('mysqli')) {
    http_response_code(500);
    echo "MySQLi extension is not loaded. Please enable MySQLi in your PHP configuration.";
    exit;
}

$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = '';
$db_name = 'online_voting_system';

// Set MySQLi error reporting (only if function exists)
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

$mysqli = new mysqli($db_host, $db_user, $db_pass);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    exit;
}

if (!$mysqli->select_db($db_name)) {
    $createSql = "CREATE DATABASE IF NOT EXISTS `" . $mysqli->real_escape_string($db_name) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if (!$mysqli->query($createSql)) {
        http_response_code(500);
        echo "Failed to create database: " . $mysqli->error;
        exit;
    }
    $mysqli->select_db($db_name);
}

$mysqli->set_charset('utf8mb4');

// Create alias for backward compatibility
$conn = $mysqli;

$check = $mysqli->query("SHOW TABLES LIKE 'admins'");
if ($check && $check->num_rows === 0) {
    $sqlFile = __DIR__ . '/../database/database.sql';
    if (is_readable($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        if ($sql !== false && trim($sql) !== '') {
            // Run multiple statements
            if ($mysqli->multi_query($sql)) {
                // Clear results
                do {
                    if ($res = $mysqli->store_result()) {
                        $res->free();
                    }
                } while ($mysqli->more_results() && $mysqli->next_result());
            }
        }
    }
}

function db_prepare($query)
{
    global $mysqli;
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
    }
    return $stmt;
}
