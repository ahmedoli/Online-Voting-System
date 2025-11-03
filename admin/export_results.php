<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
require_once __DIR__ . '/../includes/db_connect.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=results.csv');
$output = fopen('php://output', 'w');
fputcsv($output, ['Election', 'Candidate', 'Party', 'Votes']);

$elections = $mysqli->query('SELECT id, name FROM elections');
while ($e = $elections->fetch_assoc()) {
    $stmt = db_prepare('SELECT candidate_name, party, votes FROM candidates WHERE election_id = ?');
    $stmt->bind_param('i', $e['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($c = $res->fetch_assoc()) {
        fputcsv($output, [$e['name'], $c['candidate_name'], $c['party'], $c['votes']]);
    }
}
fclose($output);
exit;
