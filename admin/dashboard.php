<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
require_once __DIR__ . '/../includes/db_connect.php';

$admin_user = $_SESSION['admin_user'] ?? 'Admin';

$elections_count = $mysqli->query('SELECT COUNT(*) AS c FROM elections')->fetch_object()->c ?? 0;
$candidates_count = $mysqli->query('SELECT COUNT(*) AS c FROM candidates')->fetch_object()->c ?? 0;

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Online_Voting_System/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <span class="logo-badge">OV</span>
                <span style="font-weight:700">Admin Panel</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item nav-cta"><a class="btn btn-brand btn-sm" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
        <div class="site-header-divider"></div>
    </nav>

    <main class="container-fluid admin-container py-4">
        <div class="admin-wrap">
            <aside class="admin-sidebar">
                <div style="font-weight:600;margin-bottom:12px">Welcome, <?= sanitize($admin_user) ?></div>
                <a href="dashboard.php" class="active"><i class="fa-solid fa-gauge-high" style="width:18px"></i> Dashboard</a>
                <a href="manage_elections.php"><i class="fa-solid fa-calendar-days" style="width:18px"></i> Elections</a>
                <a href="manage_candidates.php"><i class="fa-solid fa-user-pen" style="width:18px"></i> Candidates</a>
                <a href="view_results.php"><i class="fa-solid fa-chart-column" style="width:18px"></i> Results</a>
                <a href="logs.php"><i class="fa-solid fa-file-lines" style="width:18px"></i> Logs</a>
            </aside>

            <section class="admin-main">
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <h3 style="margin:0">Dashboard</h3>
                    <div class="muted">Quick overview of your system</div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <h6>Elections</h6>
                            <div class="num"><?= (int)$elections_count ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <h6>Candidates</h6>
                            <div class="num"><?= (int)$candidates_count ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <h6>Actions</h6>
                            <div class="mt-2"><a class="btn btn-brand btn-sm me-2" href="add_election.php"><i class="fa-solid fa-plus"></i> New Election</a><a class="btn btn-outline-brand btn-sm" href="add_candidate.php"><i class="fa-solid fa-user-plus"></i> Add Candidate</a></div>
                        </div>
                    </div>
                </div>

                <div class="card-soft">
                    <h5 style="margin-top:0">Recent activity</h5>
                    <p class="muted">No recent events to show. Use the actions to create elections or add candidates quickly.</p>
                </div>
            </section>
        </div>
    </main>

</body>

</html>