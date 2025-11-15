<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
require_once __DIR__ . '/../includes/db_connect.php';

// ✅ Query for candidate results
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
    echo "<tr><td colspan='4' class='text-danger'>Query error: " . htmlspecialchars($mysqli->error) . "</td></tr>";
    exit();
}

// ✅ Total votes to calculate percentage
$total_votes_result = $mysqli->query("SELECT COUNT(id) AS total FROM vote_logs");
$total_votes = ($total_votes_result && $row = $total_votes_result->fetch_assoc()) ? $row['total'] : 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $percentage = ($total_votes > 0) ? round(($row['total_votes'] / $total_votes) * 100, 2) : 0;
        echo "
        <tr>
            <td>" . htmlspecialchars($row['candidate_name']) . "</td>
            <td>" . htmlspecialchars($row['election_title']) . "</td>
            <td>{$row['total_votes']}</td>
            <td>{$percentage}%</td>
        </tr>";
    }
} else {
    // Check if there are any candidates at all
    $candidate_check = $mysqli->query("SELECT COUNT(*) as count FROM candidates");
    $candidate_count = $candidate_check ? $candidate_check->fetch_assoc()['count'] : 0;

    if ($candidate_count == 0) {
        echo "<tr><td colspan='4' class='text-muted'>No candidates have been added yet. <a href='add_candidate.php'>Add candidates</a> to see results.</td></tr>";
    } else {
        echo "<tr><td colspan='4' class='text-muted'>No votes recorded yet. Results will appear here once voting begins.</td></tr>";
    }
}
