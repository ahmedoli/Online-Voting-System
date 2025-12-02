<?php
session_start();
require_once "../includes/db_connect.php";

if (!function_exists('getCandidates')) {
    function getCandidates($conn, $election_id, $position)
    {
        $stmt = $conn->prepare("SELECT * FROM candidates WHERE election_id=? AND position=?");
        $stmt->bind_param("is", $election_id, $position);
        $stmt->execute();
        return $stmt->get_result();
    }
}

if (!function_exists('getPositionOrder')) {
    function getPositionOrder($position)
    {
        $order = [
            'president' => 1,
            'vice-president' => 2,
            'vice president' => 2,
            'general secretary' => 3,
            'secretary' => 4,
            'treasurer' => 5,
            'joint secretary' => 6,
            'organizing secretary' => 7,
            'publicity secretary' => 8,
            'sports secretary' => 9,
            'cultural secretary' => 10,
            'member' => 11,
            'executive member' => 11,
            'general' => 99
        ];

        $pos_lower = strtolower(trim($position));
        return isset($order[$pos_lower]) ? $order[$pos_lower] : 50;
    }
}


if (!isset($_SESSION['voter_id'])) {
    header('Location: login.php');
    exit();
}

$voter_id = $_SESSION['voter_id'];
$voter_name = isset($_SESSION['voter_name']) ? $_SESSION['voter_name'] : 'Unknown Voter';
$voter_email = isset($_SESSION['voter_email']) ? $_SESSION['voter_email'] : 'No email';

if ($voter_name === 'Unknown Voter') {
    $name_stmt = $conn->prepare("SELECT name FROM voters WHERE id = ?");
    $name_stmt->bind_param("i", $voter_id);
    $name_stmt->execute();
    $name_result = $name_stmt->get_result();
    if ($name_result->num_rows > 0) {
        $voter_data = $name_result->fetch_assoc();
        $voter_name = $voter_data['name'];
        $_SESSION['voter_name'] = $voter_name;
    }
}

$message = '';
$message_type = '';

if (isset($_GET['success']) && $_GET['success'] == '1') {
    $message = 'Your votes have been successfully recorded for all positions!';
    $message_type = 'success';
    echo '<script>if (window.history.replaceState) { window.history.replaceState(null, null, window.location.pathname); }</script>';
} elseif (isset($_GET['already']) && $_GET['already'] == '1') {
    $message = 'You have already voted in this election.';
    $message_type = 'error';
    echo '<script>if (window.history.replaceState) { window.history.replaceState(null, null, window.location.pathname); }</script>';
} elseif (isset($_GET['missing']) && $_GET['missing'] == '1') {
    $message = 'Please select a candidate for all positions before voting.';
    $message_type = 'error';
    echo '<script>if (window.history.replaceState) { window.history.replaceState(null, null, window.location.pathname); }</script>';
} elseif (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'vote_fail':
            $message = 'Failed to record some votes. Please try again.';
            break;
        case 'transaction_fail':
            $message = 'A system error occurred while processing your vote. Please try again.';
            break;
        case 'missing_election':
            $message = 'Invalid election selected.';
            break;
        default:
            $message = 'An error occurred while processing your vote.';
    }
    $message_type = 'error';
    echo '<script>if (window.history.replaceState) { window.history.replaceState(null, null, window.location.pathname); }</script>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote_candidate'])) {
    $candidate_id = (int)$_POST['candidate_id'];
    $election_id = (int)$_POST['election_id'];

    $check_stmt = $conn->prepare("SELECT id FROM vote_logs WHERE voter_id = ? AND election_id = ?");
    $check_stmt->bind_param("ii", $voter_id, $election_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $message = 'You have already voted in this election.';
        $message_type = 'error';
    } else {
        $vote_stmt = $conn->prepare("INSERT INTO vote_logs (voter_id, election_id, candidate_id) VALUES (?, ?, ?)");
        $vote_stmt->bind_param("iii", $voter_id, $election_id, $candidate_id);

        if ($vote_stmt->execute()) {
            $update_stmt = $conn->prepare("UPDATE candidates SET votes = votes + 1 WHERE id = ?");
            $update_stmt->bind_param("i", $candidate_id);
            $update_stmt->execute();

            $message = 'Your vote has been successfully recorded!';
            $message_type = 'success';
        } else {
            $message = 'Failed to record your vote. Please try again.';
            $message_type = 'error';
        }
    }
}

try {
    $elections_query = "SELECT * FROM elections WHERE start_date <= CURDATE() AND end_date >= CURDATE() ORDER BY start_date DESC";
    $elections_result = $conn->query($elections_query);
    if (!$elections_result) {
        throw new Exception('Failed to fetch elections');
    }
} catch (Exception $e) {
    error_log('Elections fetch error: ' . $e->getMessage());
    $elections_result = false;
    $hasActiveElection = false;
    $active_elections = [];
}

$hasActiveElection = false;
$active_elections = [];
$user_id = $voter_id;
$alert_message = $message;
$alert_type = ($message_type === 'success') ? 'success' : 'danger';

if ($elections_result && $elections_result->num_rows > 0) {
    $hasActiveElection = true;
    while ($row = $elections_result->fetch_assoc()) {
        $active_elections[] = $row;
    }
}
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

$alreadyVoted = false;
if ($hasActiveElection) {
    $current_election_id = $active_elections[0]['id'];
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

        <div class="row g-0 h-100">
            <!-- Left Side - Active Elections -->
            <div class="col-xl-8 col-lg-9 px-3">
                <div class="card shadow-sm h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-vote-yea me-2"></i>Active Elections
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($hasActiveElection && !empty($active_elections)): ?>
                            <div class="row">
                                <?php foreach ($active_elections as $election): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="border rounded p-3 h-100 live-results-container" data-election-id="<?= $election['id'] ?>">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="fw-bold mb-0"><?= htmlspecialchars($election['name']) ?></h6>
                                                <div class="results-controls">
                                                    <small class="text-muted">
                                                        <i class="fas fa-circle text-success me-1" style="font-size: 0.6em;"></i>Live Results
                                                    </small>
                                                </div>
                                            </div>
                                            <p class="text-muted small mb-2"><?= htmlspecialchars($election['description']) ?></p>

                                            <!-- Election End Time -->
                                            <div class="d-flex align-items-center mb-3">
                                                <i class="fas fa-clock text-warning me-2"></i>
                                                <small class="text-muted">
                                                    <strong>Ends:</strong> <?= date('M j, Y - g:i A', strtotime($election['end_date'])) ?>
                                                </small>
                                            </div>

                                            <?php
                                            $voted_check = $conn->prepare("SELECT id FROM vote_logs WHERE voter_id = ? AND election_id = ?");
                                            $voted_check->bind_param("ii", $voter_id, $election['id']);
                                            $voted_check->execute();
                                            $has_voted = $voted_check->get_result()->num_rows > 0;
                                            ?>

                                            <?php if ($has_voted): ?>
                                                <div class="alert alert-success py-2">
                                                    <i class="fas fa-check-circle me-2"></i>You have already voted in this election
                                                </div>

                                                <!-- Live Results Container -->
                                                <div class="live-results mt-3">
                                                    <!-- Real-time results will be loaded here -->
                                                </div>
                                            <?php else: ?>
                                                <?php
                                                $candidates_stmt = $conn->prepare("SELECT * FROM candidates WHERE election_id = ? ORDER BY candidate_name");
                                                $candidates_stmt->bind_param("i", $election['id']);
                                                $candidates_stmt->execute();
                                                $candidates_result = $candidates_stmt->get_result();
                                                $positions = [];
                                                while ($candidate = $candidates_result->fetch_assoc()) {
                                                    $position = $candidate['position'] ?: 'General';
                                                    $position = ucwords(strtolower(trim($position)));
                                                    if (!isset($positions[$position])) {
                                                        $positions[$position] = [];
                                                    }
                                                    $positions[$position][] = $candidate;
                                                }

                                                uksort($positions, function ($a, $b) {
                                                    return getPositionOrder($a) - getPositionOrder($b);
                                                });
                                                ?>

                                                <form method="POST" action="process_vote.php" class="vote-form">
                                                    <input type="hidden" name="election_id" value="<?= $election['id'] ?>">

                                                    <?php if (!empty($positions)): ?>
                                                        <?php foreach ($positions as $position => $candidates): ?>
                                                            <div class="mb-4">
                                                                <h6 class="text-primary border-bottom pb-1 mb-3">
                                                                    <i class="fas fa-award me-2"></i><?= htmlspecialchars($position) ?>
                                                                </h6>
                                                                <?php
                                                                $position_slug = strtolower(trim($position));
                                                                $position_slug = preg_replace('/[^a-z0-9]+/', '_', $position_slug);
                                                                $position_slug = trim($position_slug, '_');
                                                                ?>
                                                                <?php foreach ($candidates as $candidate): ?>
                                                                    <div class="form-check border rounded p-3 mb-2 candidate-option">
                                                                        <input class="form-check-input" type="radio"
                                                                            name="<?= $position_slug ?>"
                                                                            value="<?= $candidate['id'] ?>"
                                                                            id="candidate_<?= $candidate['id'] ?>" required>
                                                                        <label class="form-check-label w-100" for="candidate_<?= $candidate['id'] ?>">
                                                                            <div class="d-flex justify-content-between align-items-center">
                                                                                <div>
                                                                                    <strong><?= htmlspecialchars($candidate['candidate_name']) ?></strong>
                                                                                    <?php if (!empty($candidate['party'])): ?>
                                                                                        <br><small class="text-muted"><?= htmlspecialchars($candidate['party']) ?></small>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                                <div class="text-muted">
                                                                                    <i class="fas fa-vote-yea"></i>
                                                                                </div>
                                                                            </div>
                                                                        </label>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endforeach; ?>

                                                        <button type="submit" class="btn btn-success btn-lg w-100 mt-3"
                                                            onclick="return confirm('Are you sure you want to cast your vote? This action cannot be undone.')">
                                                            <i class="fas fa-check me-2"></i>Cast Vote
                                                        </button>
                                                    <?php else: ?>
                                                        <div class="alert alert-warning">
                                                            <i class="fas fa-exclamation-triangle me-2"></i>No candidates available for this election.
                                                        </div>
                                                    <?php endif; ?>
                                                </form>

                                                <!-- Live Results Container (for non-voted elections) -->
                                                <div class="live-results mt-3">
                                                    <!-- Real-time results will be loaded here -->
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">No elections available at this time.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="col-xl-4 col-lg-5 px-3">
                <!-- Voting Status -->
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-user-check me-2"></i>Voting Status
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $total_active_elections = count($active_elections);
                        $voted_elections_count = 0;
                        if ($total_active_elections > 0) {
                            foreach ($active_elections as $election) {
                                $voted_check = $conn->prepare("SELECT id FROM vote_logs WHERE voter_id = ? AND election_id = ?");
                                $voted_check->bind_param("ii", $voter_id, $election['id']);
                                $voted_check->execute();
                                if ($voted_check->get_result()->num_rows > 0) {
                                    $voted_elections_count++;
                                }
                            }
                        }
                        ?>
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="text-primary">
                                    <i class="fas fa-vote-yea fs-4"></i>
                                    <div class="fw-bold"><?= $voted_elections_count ?></div>
                                    <small>Voted</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-warning">
                                    <i class="fas fa-clock fs-4"></i>
                                    <div class="fw-bold"><?= $total_active_elections - $voted_elections_count ?></div>
                                    <small>Pending</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Voting History -->
                <div class="card shadow-sm mb-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Voting History
                        </h6>
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
                                            <th>Candidate</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($h = $history->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <small><?= htmlspecialchars($h['election_name']) ?></small>
                                                    <br><span class="badge bg-secondary"><?= htmlspecialchars($h['position']) ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($h['candidate_name']) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-bolt me-2"></i>Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <a href="../guest/view_results.php" class="btn btn-outline-primary btn-sm w-100">
                            <i class="fas fa-chart-bar me-1"></i>View Results
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('error', function(e) {
            console.log('JavaScript error caught:', e.error);
        });

        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn && !submitBtn.disabled) {
                        submitBtn.disabled = true;
                        setTimeout(() => {
                            if (submitBtn) submitBtn.disabled = false;
                        }, 3000);
                    }
                });
            });
        });
    </script>
</body>

</html>