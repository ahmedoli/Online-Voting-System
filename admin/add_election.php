<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Election</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container py-4">
        <h3>Add Election</h3>
        <form action="process_add_election.php" method="post">
            <div class="mb-3">
                <label class="form-label">Election Name</label>
                <input name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control"></textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Start Date</label>
                    <input name="start_date" type="date" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">End Date</label>
                    <input name="end_date" type="date" class="form-control">
                </div>
            </div>
            <div>
                <button class="btn btn-primary">Create Election</button>
                <a class="btn btn-secondary" href="manage_elections.php">Back</a>
            </div>
        </form>
    </div>
</body>

</html>