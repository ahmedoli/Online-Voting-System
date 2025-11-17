<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

$elections = $conn->query("
    SELECT id, name, end_date 
    FROM elections 
    WHERE end_date IS NOT NULL 
    AND end_date < NOW()
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
            background: rgba(255, 255, 255, 0.95);
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
    </style>
</head>

<body>

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
                <div class="results-container mb-4">

                    <!-- Election Header -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0"><?= sanitize($e['name']) ?></h4>
                        <span class="badge badge-completed">Completed</span>
                    </div>

                    <?php
                    // Fetch candidates
                    $stmt = $conn->prepare("
                    SELECT candidate_name, party, votes 
                    FROM candidates 
                    WHERE election_id = ? 
                    ORDER BY votes DESC
                ");
                    $stmt->bind_param("i", $e['id']);
                    $stmt->execute();
                    $res = $stmt->get_result();

                    // Determine winner
                    $winner = $res->fetch_assoc();
                    $winner_name = $winner['candidate_name'];
                    $winner_votes = $winner['votes'];

                    // Re-run result for table display
                    $stmt->execute();
                    $res = $stmt->get_result();
                    ?>

                    <!-- Winner Highlight -->
                    <div class="alert alert-success py-2 mb-3">
                        <strong><i class="fas fa-trophy me-1"></i>Winner:</strong>
                        <?= sanitize($winner_name) ?> (<?= $winner_votes ?> votes)
                    </div>

                    <!-- Results Table -->
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Candidate</th>
                                <th>Party</th>
                                <th class="text-center">Votes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($c = $res->fetch_assoc()): ?>
                                <tr class="<?= $c['candidate_name'] === $winner_name ? 'winner-row' : '' ?>">
                                    <td><?= sanitize($c['candidate_name']) ?></td>
                                    <td><?= sanitize($c['party']) ?></td>
                                    <td class="text-center"><?= (int)$c['votes'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <small class="text-muted">
                        <i class="far fa-clock me-1"></i>
                        Completed on: <?= date("F j, Y", strtotime($e['end_date'])) ?>
                    </small>

                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

</body>

</html>