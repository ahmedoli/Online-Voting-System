<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
require_once __DIR__ . '/../includes/db_connect.php';

$admin_user = $_SESSION['admin_user'] ?? 'Admin';

try {
    $elections_result = $mysqli->query('SELECT COUNT(*) AS c FROM elections');
    $elections_count = $elections_result ? $elections_result->fetch_object()->c : 0;

    $candidates_result = $mysqli->query('SELECT COUNT(*) AS c FROM candidates');
    $candidates_count = $candidates_result ? $candidates_result->fetch_object()->c : 0;
} catch (Exception $e) {
    $elections_count = 0;
    $candidates_count = 0;
    error_log("Dashboard query error: " . $e->getMessage());
}

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

    <style>
        .admin-sidebar {
            background: linear-gradient(135deg, #6e245e 0%, #854d8a 100%) !important;
            border-radius: 12px;
            padding: 20px;
            height: fit-content;
            color: white !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .admin-sidebar a {
            display: block;
            padding: 12px 15px;
            border-radius: 8px;
            color: white !important;
            text-decoration: none;
            margin-bottom: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .admin-sidebar a:hover,
        .admin-sidebar a.active {
            background: rgba(255, 255, 255, 0.15) !important;
            color: white !important;
            transform: translateX(5px);
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .stat-card h6 {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .num {
            font-size: 2rem;
            font-weight: bold;
            color: #495057;
            margin-bottom: 10px;
        }

        .card-soft {
            background: white;
            border-radius: 12px;
            padding: 25px;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            color: white !important;
        }

        .logo-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 10px;
            border-radius: 6px;
            margin-right: 10px;
        }

        .btn-brand {
            background: #0d6efd;
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
        }

        .btn-outline-brand {
            border: 1px solid #0d6efd;
            color: #0d6efd;
            background: transparent;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
        }

        .muted {
            color: #6c757d;
        }

        .admin-container {
            width: 100%;
            max-width: none;
            padding-left: 20px;
            padding-right: 20px;
        }

        .admin-wrap {
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }

        .admin-sidebar {
            width: 250px;
            min-width: 250px;
            flex-shrink: 0;
        }

        .admin-main {
            flex: 1;
            width: 100%;
        }

        @media (max-width: 768px) {
            .admin-wrap {
                flex-direction: column;
            }

            .admin-sidebar {
                width: 100%;
                min-width: 100%;
                margin-bottom: 20px;
            }
        }
    </style>
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
                    <li class="nav-item nav-cta me-2">
                        <a class="btn btn-primary btn-sm" style="color:#fff;min-width:120px;" href="/Online_Voting_System/index.php">
                            <i class="fas fa-home me-1"></i> Back to Home
                        </a>
                    </li>
                    </li>
                    <li class="nav-item nav-cta">
                        <a class="btn btn-brand btn-sm" href="logout.php">Logout</a>
                    </li>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>