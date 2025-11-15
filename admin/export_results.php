<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
require_once __DIR__ . '/../includes/db_connect.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="election_results.csv"');

$output = fopen('php://output', 'w');

// Add column headers
fputcsv($output, ['Candidate Name', 'Election', 'Total Votes', 'Percentage']);

// Fetch candidate results
$query = "
SELECT 
    c.candidate_name,
    e.name AS election_title,
    COUNT(v.id) AS total_votes
FROM candidates c
LEFT JOIN vote_logs v ON v.candidate_id = c.id
LEFT JOIN elections e ON e.id = c.election_id
GROUP BY c.id, e.name
ORDER BY e.name, total_votes DESC";

$result = $mysqli->query($query);

if (!$result) {
    // If query fails, redirect to dashboard with error
    header("Location: dashboard.php?error=export_failed");
    exit();
}

// Calculate total votes for percentage
$total_votes_result = $mysqli->query("SELECT COUNT(id) AS total FROM vote_logs");
$total_votes = ($total_votes_result && $row = $total_votes_result->fetch_assoc()) ? $row['total'] : 0;

// Write rows
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $percentage = ($total_votes > 0) ? round(($row['total_votes'] / $total_votes) * 100, 2) : 0;
        fputcsv($output, [$row['candidate_name'], $row['election_title'], $row['total_votes'], $percentage . '%']);
    }
} else {
    // If no results, add a message row
    fputcsv($output, ['No voting results available yet', '', '', '']);
}

fclose($output);
exit();
