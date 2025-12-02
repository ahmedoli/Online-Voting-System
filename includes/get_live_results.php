<?php

/**
 * Live Results API Endpoint
 * Provides real-time voting results for AJAX requests
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/functions.php';

// Basic rate limiting
session_start();
if (!isset($_SESSION['api_requests'])) {
    $_SESSION['api_requests'] = [];
}
$now = time();
$_SESSION['api_requests'] = array_filter($_SESSION['api_requests'], function ($time) use ($now) {
    return ($now - $time) < 60; // Keep requests from last minute
});
if (count($_SESSION['api_requests']) > 30) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Rate limit exceeded']);
    exit;
}
$_SESSION['api_requests'][] = $now;

try {
    $election_id = isset($_GET['election_id']) ? (int)$_GET['election_id'] : null;
    if ($election_id < 0) $election_id = null; // Validate positive integers only
    $results = [];

    if ($election_id) {
        // Get specific election results
        $elections = [$election_id];
    } else {
        // Get all active elections
        $election_query = "SELECT id FROM elections WHERE start_date <= CURDATE() AND end_date >= CURDATE()";
        $election_result = $conn->query($election_query);
        $elections = [];
        while ($row = $election_result->fetch_assoc()) {
            $elections[] = $row['id'];
        }
    }

    foreach ($elections as $eid) {
        // Get election info
        $election_stmt = $conn->prepare("SELECT id, name, description FROM elections WHERE id = ?");
        $election_stmt->bind_param("i", $eid);
        $election_stmt->execute();
        $election_data = $election_stmt->get_result()->fetch_assoc();

        if (!$election_data) continue;

        // Get candidates and their vote counts from vote_logs
        $candidates_stmt = $conn->prepare("
            SELECT 
                c.id,
                c.candidate_name as name,
                c.party,
                COUNT(vl.id) as vote_count
            FROM candidates c
            LEFT JOIN vote_logs vl ON vl.candidate_id = c.id AND vl.election_id = ?
            WHERE c.election_id = ?
            GROUP BY c.id
            ORDER BY vote_count DESC, c.candidate_name ASC
        ");
        $candidates_stmt->bind_param("ii", $eid, $eid);
        $candidates_stmt->execute();
        $candidates_result = $candidates_stmt->get_result();

        $candidates = [];
        $total_votes = 0;

        while ($candidate = $candidates_result->fetch_assoc()) {
            $candidates[] = [
                'id' => (int)$candidate['id'],
                'name' => $candidate['name'],
                'party' => $candidate['party'],
                'votes' => (int)$candidate['vote_count']
            ];
            $total_votes += (int)$candidate['vote_count'];
        }

        $results[] = [
            'id' => (int)$election_data['id'],
            'name' => $election_data['name'],
            'description' => $election_data['description'],
            'candidates' => $candidates,
            'total_votes' => $total_votes,
            'last_updated' => date('Y-m-d H:i:s')
        ];
    }

    echo json_encode([
        'success' => true,
        'results' => $results,
        'timestamp' => time(),
        'formatted_time' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to fetch results. Please try again later.',
        'timestamp' => time()
    ]);
}
