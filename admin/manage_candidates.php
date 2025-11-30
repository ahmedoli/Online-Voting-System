<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
require_once __DIR__ . '/../includes/db_connect.php';

if (!empty($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = db_prepare('DELETE FROM candidates WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    flash_set('success', 'Candidate deleted');
    header('Location: manage_candidates.php');
    exit;
}

$res = $mysqli->query('SELECT c.*, e.name as election_name FROM candidates c LEFT JOIN elections e ON e.id = c.election_id ORDER BY c.id DESC');

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Candidates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Manage Candidates</h3>
            <div>
                <a class="btn btn-success" href="add_candidate.php">Add Candidate</a>
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
                    <th>Party</th>
                    <th>Position</th>
                    <th>Election</th>
                    <th>Votes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $res->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int)$row['id'] ?></td>
                        <td><?= sanitize($row['candidate_name']) ?></td>
                        <td><?= sanitize($row['party']) ?></td>
                        <td><?= sanitize($row['position'] ?: 'General') ?></td>
                        <td><?= sanitize($row['election_name']) ?></td>
                        <td><?= (int)$row['votes'] ?></td>
                        <td>
                            <a class="btn btn-sm btn-danger" href="?delete=<?= (int)$row['id'] ?>" onclick="return confirm('Delete candidate?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>

</html>