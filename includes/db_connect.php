<?php
if (!extension_loaded('mysqli')) {
    http_response_code(500);
    echo "MySQLi extension is not loaded. Please enable MySQLi in your PHP configuration.";
    exit;
}

$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = '';
$db_name = 'online_voting_system';

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

$mysqli = new mysqli($db_host, $db_user, $db_pass);
if ($mysqli->connect_errno) {
    error_log("Database connection failed: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
    http_response_code(500);
    die("Database connection error. Please try again later.");
}

if (!$mysqli->select_db($db_name)) {
    $createSql = "CREATE DATABASE IF NOT EXISTS `" . $mysqli->real_escape_string($db_name) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if (!$mysqli->query($createSql)) {
        error_log("Failed to create database: " . $mysqli->error);
        http_response_code(500);
        die("Database setup error. Please contact administrator.");
    }
    $mysqli->select_db($db_name);
}

$mysqli->set_charset('utf8mb4');

$conn = $mysqli;

$check = $mysqli->query("SHOW TABLES LIKE 'admins'");
if ($check && $check->num_rows === 0) {
    $sqlFile = __DIR__ . '/../database/database.sql';
    if (is_readable($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        if ($sql !== false && trim($sql) !== '') {
            if ($mysqli->multi_query($sql)) {
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
