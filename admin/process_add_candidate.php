<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
require_once __DIR__ . '/../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add_candidate.php');
    exit;
}

$election_id = (int)($_POST['election_id'] ?? 0);
$candidate_name = trim($_POST['candidate_name'] ?? '');
$party = trim($_POST['party'] ?? '');

if ($election_id <= 0 || $candidate_name === '') {
    flash_set('error', 'Election and candidate name are required');
    header('Location: add_candidate.php');
    exit;
}

$stmt = db_prepare('INSERT INTO candidates (election_id, candidate_name, party, votes) VALUES (?, ?, ?, 0)');
$stmt->bind_param('iss', $election_id, $candidate_name, $party);
if ($stmt->execute()) {
    flash_set('success', 'Candidate added');
    header('Location: manage_candidates.php');
    exit;
}
 else {
    flash_set('error', 'Failed to add candidate');
    header('Location: add_candidate.php');
    exit;
}
