<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
require_once __DIR__ . '/../includes/db_connect.php';

if (!empty($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = db_prepare('DELETE FROM elections WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    flash_set('success', 'Election deleted (if it existed)');
    header('Location: manage_elections.php');
    exit;
}

$res = $mysqli->query('SELECT * FROM elections ORDER BY created_at DESC');

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Elections</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Manage Elections</h3>
            <div>
                <a class="btn btn-success" href="add_election.php">Add Election</a>
                <a class="btn btn-secondary" href="dashboard.php">Dashboard</a>
            </div>
        </div>

        <?php if ($m = flash_get('success')): ?><div class="alert alert-success"><?= sanitize($m) ?></div><?php endif; ?>
        <?php if ($m = flash_get('error')): ?><div class="alert alert-danger"><?= sanitize($m) ?></div><?php endif; ?>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $res->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int)$row['id'] ?></td>
                        <td><?= sanitize($row['name']) ?></td>
                        <td><?= sanitize($row['start_date']) ?></td>
                        <td><?= sanitize($row['end_date']) ?></td>
                        <td><?= sanitize($row['created_at']) ?></td>
                        <td>
                            <a class="btn btn-sm btn-primary" href="add_candidate.php?election_id=<?= (int)$row['id'] ?>">Add Candidate</a>
                            <a class="btn btn-sm btn-danger" href="?delete=<?= (int)$row['id'] ?>" onclick="return confirm('Delete election?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>

</html>