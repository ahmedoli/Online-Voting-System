<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';


// Fetch all elections with end_date
$elections = $conn->query("
    SELECT id, name, start_date, end_date 
    FROM elections 
    WHERE end_date IS NOT NULL 
    ORDER BY end_date DESC
");

if (!$elections) {
    die("<div class='container mt-5'>
            <div class='alert alert-danger'>
                SQL Error: " . $conn->error . "
            </div>
        </div>");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Public Election Results</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', sans-serif;
        }

        .results-container {
            background: rgba(255, 255, 255, 0.97);
            padding: 30px;
            border-radius: 18px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .winner-row {
            background: #e0f7e9 !important;
            font-weight: 600;
        }

        .badge-completed {
            background: #4f46e5;
        }

        .position-block {
            margin-bottom: 2.5rem;
        }

        .position-block:not(:first-child) {
            margin-top: 2.5rem;
        }

        .position-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: #1976d2;
            border-bottom: 2px solid #e3f0fc;
            padding-bottom: 0.5rem;
            margin-bottom: 1.2rem;
            letter-spacing: 0.5px;
        }

        .table-results th,
        .table-results td {
            vertical-align: middle;
        }
    </style>
</head>

<body>

    <?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>




    <div class="container py-5">
        <div class="text-center mb-4">
            <h2 class="text-white fw-bold"><i class="fas fa-chart-bar me-2"></i>Election Results</h2>
            <p class="text-light">Completed elections & final vote counts</p>
        </div>

        <?php if (!$elections || $elections->num_rows === 0): ?>
            <div class="results-container text-center">
                <h5 class="text-muted">No elections available</h5>
            </div>
        <?php endif; ?>

        <?php if ($elections): ?>
            <?php while ($e = $elections->fetch_assoc()): ?>
                <?php
                $now = date('Y-m-d');
                $is_completed = ($e['end_date'] < $now);
                $is_in_progress = ($e['start_date'] <= $now && $e['end_date'] >= $now);
                ?>
                <div class="results-container mb-4">
                    <!-- Election Header -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0"><?= sanitize($e['name']) ?></h4>
                        <?php if ($is_completed): ?>
                            <span class="badge badge-completed">Completed</span>
                        <?php elseif ($is_in_progress): ?>
                            <span class="badge bg-warning text-dark">Voting In Progress</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Upcoming</span>
                        <?php endif; ?>
                    </div>

                    <?php
                    // Fetch candidates grouped by position
                    $stmt = $conn->prepare("
                        SELECT c.candidate_name, c.party, c.position, COUNT(vl.id) as votes
                        FROM candidates c
                        LEFT JOIN vote_logs vl ON vl.candidate_id = c.id AND vl.election_id = ?
                        WHERE c.election_id = ?
                        GROUP BY c.id
                        ORDER BY c.position, votes DESC
                    ");
                    $stmt->bind_param("ii", $e['id'], $e['id']);
                    $stmt->execute();
                    $res = $stmt->get_result();

                    // Group candidates by position and find overall winner (per position)
                    $positions = [];
                    $overall_winner = null;
                    $max_votes = 0;
                    while ($candidate = $res->fetch_assoc()) {
                        $position = $candidate['position'] ?: 'General';
                        if (!isset($positions[$position])) {
                            $positions[$position] = [];
                        }
                        $positions[$position][] = $candidate;
                    }
                    // Find overall winner (highest votes among all positions)
                    foreach ($positions as $cands) {
                        foreach ($cands as $cand) {
                            if ($cand['votes'] > $max_votes) {
                                $max_votes = $cand['votes'];
                                $overall_winner = $cand;
                            }
                        }
                    }
                    $winner_name = $overall_winner ? $overall_winner['candidate_name'] : null;
                    $winner_votes = $overall_winner ? $overall_winner['votes'] : 0;
                    ?>

                    <?php if ($is_completed && $overall_winner && $winner_name): ?>
                        <!-- Winner Highlight -->
                        <div class="alert alert-success py-2 mb-3">
                            <strong><i class="fas fa-trophy me-1"></i>Overall Winner:</strong>
                            <?= sanitize($winner_name) ?> (<?= $winner_votes ?> votes)
                        </div>
                    <?php elseif ($is_in_progress): ?>
                        <div class="alert alert-info py-2 mb-3">
                            <strong><i class="fas fa-hourglass-half me-1"></i>Voting is in progress.</strong> Results will be available after the voting period ends.
                        </div>
                    <?php endif; ?>

                    <!-- Results by Position -->
                    <?php if ($is_completed && !empty($positions)): ?>
                        <?php foreach ($positions as $position => $candidates): ?>
                            <div class="position-block">
                                <div class="position-title"><?= sanitize($position) ?></div>
                                <table class="table table-bordered table-results align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Candidate Name</th>
                                            <th>Party</th>
                                            <th class="text-center">Total Votes</th>
                                            <th class="text-center">Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $total_votes = 0;
                                        foreach ($candidates as $c) {
                                            $total_votes += (int)$c['votes'];
                                        }
                                        foreach ($candidates as $c):
                                            $percent = $total_votes > 0 ? round(((int)$c['votes'] / $total_votes) * 100) : 0;
                                        ?>
                                            <tr class="<?= $winner_name && $c['candidate_name'] === $winner_name ? 'winner-row' : '' ?>">
                                                <td><?= sanitize($c['candidate_name']) ?></td>
                                                <td><?= sanitize($c['party']) ?></td>
                                                <td class="text-center"><?= (int)$c['votes'] ?></td>
                                                <td class="text-center"><?= $percent ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    <?php elseif ($is_in_progress): ?>
                        <div class="text-center text-muted py-4">Live results are hidden until voting ends.</div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">No candidates found for this election</div>
                    <?php endif; ?>

                    <small class="text-muted">
                        <i class="far fa-clock me-1"></i>
                        <?php if ($is_completed): ?>
                            Completed on: <?= date("F j, Y", strtotime($e['end_date'])) ?>
                        <?php elseif ($is_in_progress): ?>
                            Voting ends: <?= date("F j, Y", strtotime($e['end_date'])) ?>
                        <?php else: ?>
                            Voting starts: <?= date("F j, Y", strtotime($e['start_date'])) ?>
                        <?php endif; ?>
                    </small>

                </div>
            <?php endwhile; ?>
        <?php endif; ?>

        <!-- Back button below results section -->
        <div class="container pb-5">
            <div class="d-flex justify-content-center mt-5">
                <button class="btn btn-primary btn-lg px-5" onclick="window.history.back();">
                    <i class="fas fa-arrow-left me-2"></i>Back to Home
                </button>
            </div>
        </div>
    </div>

</body>

</html>