<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
require_once __DIR__ . '/../includes/db_connect.php';

$election_id = isset($_GET['election_id']) ? (int)$_GET['election_id'] : null;
$elections = $mysqli->query('SELECT id, name FROM elections ORDER BY name');

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Candidate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container py-4">
        <h3>Add Candidate</h3>
        <form action="process_add_candidate.php" method="post">
            <div class="mb-3">
                <label class="form-label">Election</label>
                <select name="election_id" class="form-select" required>
                    <option value="">Select election</option>
                    <?php while ($e = $elections->fetch_assoc()): ?>
                        <option value="<?= (int)$e['id'] ?>" <?= ($election_id && $election_id == $e['id']) ? 'selected' : '' ?>><?= sanitize($e['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Candidate Name</label>
                <input name="candidate_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Party</label>
                <input name="party" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">Position</label>
                <input name="position" class="form-control" placeholder="e.g., President, Vice President, Treasurer" required>
            </div>
            <div>
                <button class="btn btn-primary">Add Candidate</button>
                <a class="btn btn-secondary" href="manage_candidates.php">Back</a>
            </div>
        </form>
    </div>
</body>

</html>