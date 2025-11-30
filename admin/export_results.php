<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
require_once __DIR__ . '/../includes/db_connect.php';

// Check if election_id is provided
$election_id = isset($_GET['election_id']) ? (int)$_GET['election_id'] : null;

if ($election_id) {
    // Get election name for filename
    $election_stmt = $mysqli->prepare("SELECT name FROM elections WHERE id = ?");
    $election_stmt->bind_param('i', $election_id);
    $election_stmt->execute();
    $election_result = $election_stmt->get_result();
    $election_name = $election_result->fetch_assoc()['name'] ?? 'Unknown Election';
    $filename = preg_replace('/[^A-Za-z0-9_-]/', '_', $election_name) . '_results.csv';
} else {
    $filename = 'all_election_results.csv';
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Add column headers
fputcsv($output, ['Candidate Name', 'Party', 'Total Votes', 'Percentage']);

// Fetch candidate results
if ($election_id) {
    // Export specific election
    $query = "
    SELECT 
        c.candidate_name,
        c.party,
        COUNT(v.id) AS total_votes
    FROM candidates c
    LEFT JOIN vote_logs v ON v.candidate_id = c.id
    WHERE c.election_id = ?
    GROUP BY c.id
    ORDER BY total_votes DESC";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $election_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Calculate total votes for this election
    $total_votes_query = "
        SELECT COUNT(v.id) AS total 
        FROM vote_logs v 
        JOIN candidates c ON c.id = v.candidate_id 
        WHERE c.election_id = ?";
    $total_votes_stmt = $mysqli->prepare($total_votes_query);
    $total_votes_stmt->bind_param('i', $election_id);
    $total_votes_stmt->execute();
    $total_votes_result = $total_votes_stmt->get_result();
    $total_votes = $total_votes_result->fetch_assoc()['total'];
} else {
    // Export all elections (fallback)
    $query = "
    SELECT 
        c.candidate_name,
        c.party,
        COUNT(v.id) AS total_votes
    FROM candidates c
    LEFT JOIN vote_logs v ON v.candidate_id = c.id
    GROUP BY c.id
    ORDER BY total_votes DESC";

    $result = $mysqli->query($query);

    // Calculate total votes for percentage
    $total_votes_result = $mysqli->query("SELECT COUNT(id) AS total FROM vote_logs");
    $total_votes = ($total_votes_result && $row = $total_votes_result->fetch_assoc()) ? $row['total'] : 0;
}

if (!$result) {
    // If query fails, redirect to dashboard with error
    header("Location: dashboard.php?error=export_failed");
    exit();
}

// Write rows
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $percentage = ($total_votes > 0) ? round(($row['total_votes'] / $total_votes) * 100, 2) : 0;
        fputcsv($output, [$row['candidate_name'], $row['party'], $row['total_votes'], $percentage . '%']);
    }
} else {
    // If no results, add a message row
    fputcsv($output, ['No voting results available yet', '', '', '']);
}

fclose($output);
exit();
