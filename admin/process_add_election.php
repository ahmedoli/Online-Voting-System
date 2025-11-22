<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
require_once __DIR__ . '/../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add_election.php');
    exit;
}

$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$start_date = $_POST['start_date'] ?: null;
$end_date = $_POST['end_date'] ?: null;

if ($name === '') {
    flash_set('error', 'Election name is required');
    header('Location: add_election.php');
    exit;
}

$stmt = db_prepare('SELECT id FROM elections WHERE name = ? LIMIT 1');
$stmt->bind_param('s', $name);
$stmt->execute();
$res = $stmt->get_result();
if ($res->fetch_assoc()) {
    flash_set('error', 'Election with this name already exists');
    header('Location: add_election.php');
    exit;
}

$stmt = db_prepare('INSERT INTO elections (name, description, start_date, end_date) VALUES (?, ?, ?, ?)');
$stmt->bind_param('ssss', $name, $description, $start_date, $end_date);
if ($stmt->execute()) {
    flash_set('success', 'Election created successfully');
    header('Location: manage_elections.php');
    exit;
} else {
    flash_set('error', 'Failed to create election');
    header('Location: add_election.php');
    exit;
}
