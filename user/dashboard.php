<?php
session_start();
require_once "../includes/db_connect.php";
// require_once "../includes/auth_user.php"; // Removed: file does not exist and session check is already present

if (!function_exists('getCandidates')) {
    function getCandidates($conn, $election_id, $position)
    {
        $stmt = $conn->prepare("SELECT * FROM candidates WHERE election_id=? AND position=?");
        $stmt->bind_param("is", $election_id, $position);
        $stmt->execute();
        return $stmt->get_result();
    }
}


if (!isset($_SESSION['voter_id'])) {
    header('Location: login.php');
    exit();
}

// Ensure voter_name is set in session for display
if (!isset($_SESSION['voter_name'])) {
    $stmt = $conn->prepare("SELECT name FROM voters WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['voter_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $_SESSION['voter_name'] = $row['name'];
    }
    $stmt->close();
}


$user_id = $_SESSION['voter_id'];

// Handle voting feedback alerts
$alert_message = '';
$alert_type = '';
if (isset($_GET['success'])) {
    $alert_message = 'Your vote has been recorded successfully.';
    $alert_type = 'success';
} elseif (isset($_GET['already'])) {
    $alert_message = 'You have already voted in this election.';
    $alert_type = 'warning';
} elseif (isset($_GET['missing'])) {
    $alert_message = 'Please select a candidate for each position.';
    $alert_type = 'danger';
} elseif (isset($_GET['error'])) {
    $alert_message = 'An error occurred while casting your vote. Please try again.';
    $alert_type = 'danger';
}

// FETCH ALL ACTIVE ELECTIONS
$active_elections = [];
$active_elections_result = $conn->query("SELECT * FROM elections WHERE start_date <= CURDATE() AND end_date >= CURDATE() ORDER BY start_date ASC");
if ($active_elections_result && $active_elections_result instanceof mysqli_result) {
    while ($row = $active_elections_result->fetch_assoc()) {
        $active_elections[] = $row;
    }
}
$hasActiveElection = count($active_elections) > 0;

// FETCH VOTING STATS
$stats_stmt = $conn->prepare("SELECT COUNT(DISTINCT election_id) AS total_voted FROM vote_logs WHERE voter_id = ?");
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
if ($stats_result && $stats_result instanceof mysqli_result) {
    $stats = $stats_result->fetch_assoc();
} else {
    $stats = ['total_voted' => 0];
}
$stats['active_elections'] = count($active_elections);
$stats_stmt->close();

// FETCH VOTING HISTORY
$history_stmt = $conn->prepare("
    SELECT v.election_id, v.position, v.candidate_id, c.candidate_name, e.name AS election_name
    FROM vote_logs v
    INNER JOIN candidates c ON v.candidate_id = c.id
    INNER JOIN elections e ON v.election_id = e.id
    WHERE v.voter_id = ?
    ORDER BY v.id DESC
");
$history_stmt->bind_param("i", $user_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();
if ($history_result && $history_result instanceof mysqli_result) {
    $history = $history_result;
} else {
    $history = false;
}
$history_stmt->close();

// --- VOTE PROCESSING ---
$alreadyVoted = false;
if ($hasActiveElection) {
    $current_election_id = $active_elections[0]['id'];
    // Check if already voted
    $check_stmt = $conn->prepare("SELECT 1 FROM vote_logs WHERE voter_id = ? AND election_id = ?");
    $check_stmt->bind_param("ii", $user_id, $current_election_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    $alreadyVoted = $check_stmt->num_rows > 0;
    $check_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Online Voting System - Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8fafc;
        }

        .card {
            border-radius: 1rem;
            border: 1px solid #e3e6ea;
            background: #fff;
        }

        .card-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important;
            color: #fff;
            border-bottom: 1px solid #e3e6ea;
            border-radius: 1rem 1rem 0 0;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .dashboard-section {
            margin-bottom: 2rem;
        }

        .card:not(:last-child) {
            margin-bottom: 1.5rem;
        }

        .form-check-label {
            font-weight: 500;
        }

        .badge-status {
            font-size: 0.95em;
            padding: 0.4em 0.8em;
            border-radius: 0.5em;
        }

        .position-header {
            background: #e3f0fc;
            color: #1976d2;
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            font-size: 1.08rem;
        }

        .position-header i {
            margin-right: 0.5rem;
        }

        .gap-lg-4 {
            gap: 2rem;
        }

        .sidebar-gap>.card:not(:last-child) {
            margin-bottom: 1.5rem;
        }

        .header-status {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important;
            color: #fff;
        }

        .header-history {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%) !important;
            color: #fff;
        }

        .dashboard-sidebar-flush {
            margin-right: 0 !important;
            padding-right: 0 !important;
        }

        .header-actions {
            background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%) !important;
            color: #333;
        }

        @media (max-width: 991.98px) {
            .dashboard-section {
                margin-bottom: 1.5rem;
            }

            .gap-lg-4 {
                gap: 1rem;
            }
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-0">
        <div class="container-fluid px-0">
            <a class="navbar-brand fw-bold ms-3" href="#">Online Voting System</a>
            <div class="ms-auto d-flex align-items-center me-3">
                <a href="/Online_Voting_System/index.php" class="btn btn-primary btn-sm me-3" style="color:#fff;min-width:120px;">
                    <i class="fas fa-home me-1"></i> Back to Home
                </a>
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-2 fs-3"></i>
                        <span class="fw-bold fs-5" style="color: #fff; letter-spacing: 0.5px;">
                            <?php echo isset($_SESSION['voter_name']) ? htmlspecialchars($_SESSION['voter_name']) : 'Voter'; ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    <div class="container-fluid px-0 mt-4" style="padding-right:0 !important; margin-right:0 !important;">
        <?php if ($alert_message): ?>
            <div class="alert alert-<?= $alert_type ?> alert-dismissible fade show mx-3" role="alert">
                <?= htmlspecialchars($alert_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <div class="row gap-lg-4 gx-0">
            <!-- LEFT COLUMN: Active Elections -->
            <div class="col-lg-7 mb-4 mb-lg-0">
                <section class="dashboard-section">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex align-items-center">
                            <i class="fas fa-vote-yea me-2 text-primary"></i> Active Elections
                        </div>
                        <div class="card-body">
                            <?php if (!$hasActiveElection): ?>
                                <div class="alert alert-info mb-0">No active elections available.</div>
                            <?php else: ?>
                                <div class="row g-3">
                                    <?php foreach ($active_elections as $election): ?>
                                        <div class="col-12 col-md-6">
                                            <div class="border rounded p-3 mb-2 bg-light">
                                                <h5 class="fw-bold mb-1"><?= htmlspecialchars($election['name']) ?></h5>
                                                <div class="mb-1 small text-muted"><?= htmlspecialchars($election['description'] ?? '') ?></div>
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="badge bg-success badge-status">Active</span>
                                                    <span class="text-muted small"><i class="fas fa-calendar-alt me-1"></i>Ends: <?= htmlspecialchars($election['end_date'] ?? '') ?></span>
                                                </div>
                                                <?php
                                                // Only show voting form for the first active election and if not already voted
                                                if ($active_elections[0]['id'] == $election['id'] && !$alreadyVoted):
                                                    // Dynamically fetch all unique positions for this election
                                                    $positions = [];
                                                    $pos_stmt = $conn->prepare("SELECT DISTINCT position FROM candidates WHERE election_id = ? ORDER BY FIELD(position, 'President', 'Vice-President', 'General Secretary'), position");
                                                    $pos_stmt->bind_param("i", $election['id']);
                                                    $pos_stmt->execute();
                                                    $pos_result = $pos_stmt->get_result();
                                                    while ($row = $pos_result->fetch_assoc()) {
                                                        $positions[] = $row['position'];
                                                    }
                                                    $pos_stmt->close();
                                                    $position_icons = [
                                                        'President' => 'fa-user-tie',
                                                        'Vice-President' => 'fa-user-friends',
                                                        'General Secretary' => 'fa-user-pen',
                                                    ];
                                                ?>
                                                    <form method="POST" action="process_vote.php" onsubmit="return validateVoteForm(this);">
                                                        <input type="hidden" name="election_id" value="<?= (int)$election['id'] ?>">
                                                        <?php
                                                        function slugify($text)
                                                        {
                                                            $text = strtolower(trim($text));
                                                            $text = preg_replace('/[^a-z0-9]+/', '_', $text);
                                                            return trim($text, '_');
                                                        }
                                                        $position_slug_map = [];
                                                        foreach ($positions as $label):
                                                            $slug = slugify($label);
                                                            $position_slug_map[$slug] = $label;
                                                            $candidates = getCandidates($conn, $election['id'], $label);
                                                            if ($candidates && $candidates->num_rows > 0): ?>
                                                                <div class="mb-3 position-block-js" data-position-label="<?= htmlspecialchars($label) ?>">
                                                                    <div class="position-header">
                                                                        <i class="fas <?= isset($position_icons[$label]) ? $position_icons[$label] : 'fa-user' ?>"></i>
                                                                        <?= htmlspecialchars($label) ?>
                                                                    </div>
                                                                    <?php foreach ($candidates as $candidate): ?>
                                                                        <div class="form-check mb-1">
                                                                            <input class="form-check-input" type="radio" name="<?= $slug ?>" value="<?= $candidate['id'] ?>" id="<?= $slug . '_' . $candidate['id'] ?>" required>
                                                                            <label class="form-check-label" for="<?= $slug . '_' . $candidate['id'] ?>">
                                                                                <?= htmlspecialchars($candidate['candidate_name']) ?>
                                                                            </label>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                        <?php endif;
                                                        endforeach;
                                                        ?>
                                                        <button type="submit" class="btn btn-primary w-100">Cast Vote</button>
                                                    </form>
                                                    <script>
                                                        function validateVoteForm(form) {
                                                            var valid = true;
                                                            var missingPositions = [];
                                                            var positionBlocks = form.querySelectorAll('.position-block-js');
                                                            positionBlocks.forEach(function(block) {
                                                                var label = block.getAttribute('data-position-label');
                                                                var radios = block.querySelectorAll('input[type=radio]');
                                                                var checked = false;
                                                                radios.forEach(function(radio) {
                                                                    if (radio.checked) checked = true;
                                                                });
                                                                if (!checked) {
                                                                    valid = false;
                                                                    missingPositions.push(label);
                                                                }
                                                            });
                                                            if (!valid) {
                                                                alert('Please select a candidate for each position before submitting your vote.');
                                                            }
                                                            return valid;
                                                        }
                                                    </script>
                                                <?php elseif ($active_elections[0]['id'] == $election['id'] && $alreadyVoted): ?>
                                                    <div class="alert alert-success text-center mb-0">Your vote has been recorded successfully.</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            </div>
            <!-- RIGHT COLUMN: Sidebar -->
            <div class="col-lg-4 sidebar-gap dashboard-sidebar-flush">
                <section class="dashboard-section">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header header-status d-flex align-items-center">
                            <i class="fas fa-chart-pie me-2 text-info"></i> Voting Status
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <div class="fw-bold fs-4"><?= $stats['total_voted'] ?? 0 ?></div>
                                    <div class="small text-muted">Elections Voted</div>
                                </div>
                                <div>
                                    <div class="fw-bold fs-4"><?= $stats['active_elections'] ?? 0 ?></div>
                                    <div class="small text-muted">Active Elections</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header header-history d-flex align-items-center">
                            <i class="fas fa-history me-2 text-secondary"></i> Voting History
                        </div>
                        <div class="card-body">
                            <?php if (!$history || $history->num_rows == 0): ?>
                                <div class="text-center text-muted">No voting history yet.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Election</th>
                                                <th>Position</th>
                                                <th>Candidate</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($h = $history->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($h['election_name']) ?></td>
                                                    <td><?= htmlspecialchars($h['position']) ?></td>
                                                    <td><?= htmlspecialchars($h['candidate_name']) ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-header header-actions d-flex align-items-center">
                            <i class="fas fa-bolt me-2 text-warning"></i> Quick Actions
                        </div>
                        <div class="card-body">
                            <a href="../guest/view_results.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-chart-bar me-2"></i>View Results
                            </a>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>


</html>