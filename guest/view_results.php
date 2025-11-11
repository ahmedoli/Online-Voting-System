<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

$elections = $conn->query('SELECT id, name, end_date FROM elections WHERE end_date IS NOT NULL ORDER BY created_at DESC');

?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Public Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>


<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Election Results</h3>
            <div class="results-controls">
                <small class="text-muted me-2">
                    <i class="fas fa-sync-alt me-1"></i>Auto-refresh enabled
                </small>
            </div>
        </div>
        <?php while ($e = $elections->fetch_assoc()): ?>
            <div class="card mb-3 live-results-container" data-election-id="<?= $e['id'] ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><?= sanitize($e['name']) ?></h5>
                        <small class="text-muted">
                            <i class="fas fa-circle text-success me-1" style="font-size: 0.6em;"></i>Live
                        </small>
                    </div>
                    <?php
                    $stmt = $conn->prepare('SELECT candidate_name, party, votes FROM candidates WHERE election_id = ? ORDER BY votes DESC');
                    $stmt->bind_param('i', $e['id']);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Candidate</th>
                                <th>Party</th>
                                <th>Votes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($c = $res->fetch_assoc()): ?>
                                <tr>
                                    <td><?= sanitize($c['candidate_name']) ?></td>
                                    <td><?= sanitize($c['party']) ?></td>
                                    <td><?= (int)$c['votes'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <!-- Live Results Container -->
                    <div class="live-results mt-3">
                        <!-- Real-time results will be loaded here -->
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/realtime-results.js"></script>
</body>

</html>