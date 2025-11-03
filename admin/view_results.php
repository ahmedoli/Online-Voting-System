<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
require_once __DIR__ . '/../includes/db_connect.php';

$elections = $mysqli->query('SELECT id, name FROM elections ORDER BY created_at DESC');

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container py-4">
        <h3>Election Results</h3>
        <?php while ($e = $elections->fetch_assoc()): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5><?= sanitize($e['name']) ?></h5>
                    <?php
                    $stmt = db_prepare('SELECT candidate_name, party, votes FROM candidates WHERE election_id = ? ORDER BY votes DESC');
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
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</body>

</html>