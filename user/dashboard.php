<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['voter_id'])) {
    header('Location: login.php');
    exit();
}

$voter_id = $_SESSION['voter_id'];
$voter_name = isset($_SESSION['voter_name']) ? $_SESSION['voter_name'] : 'Unknown Voter';
$voter_email = isset($_SESSION['voter_email']) ? $_SESSION['voter_email'] : 'No email';

// Fetch voter name from database if not in session (fallback for existing sessions)
if ($voter_name === 'Unknown Voter') {
    $name_stmt = $conn->prepare("SELECT name FROM voters WHERE id = ?");
    $name_stmt->bind_param("i", $voter_id);
    $name_stmt->execute();
    $name_result = $name_stmt->get_result();
    if ($name_result->num_rows > 0) {
        $voter_data = $name_result->fetch_assoc();
        $voter_name = $voter_data['name'];
        $_SESSION['voter_name'] = $voter_name; // Store it in session for future use
    }
}

$message = '';
$message_type = '';

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote_candidate'])) {
    $candidate_id = (int)$_POST['candidate_id'];
    $election_id = (int)$_POST['election_id'];

    // Check if voter has already voted in this election
    $check_stmt = $conn->prepare("SELECT id FROM vote_logs WHERE voter_id = ? AND election_id = ?");
    $check_stmt->bind_param("ii", $voter_id, $election_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $message = 'You have already voted in this election.';
        $message_type = 'error';
    } else {
        // Record the vote
        $vote_stmt = $conn->prepare("INSERT INTO vote_logs (voter_id, election_id, candidate_id) VALUES (?, ?, ?)");
        $vote_stmt->bind_param("iii", $voter_id, $election_id, $candidate_id);

        if ($vote_stmt->execute()) {
            // Update candidate vote count
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

// Get active elections
$elections_query = "SELECT * FROM elections WHERE start_date <= CURDATE() AND end_date >= CURDATE() ORDER BY start_date DESC";
$elections_result = $conn->query($elections_query);

// Get voter's voting history
$history_stmt = $conn->prepare("
    SELECT e.name as election_name, c.candidate_name, c.party, vl.voted_at 
    FROM vote_logs vl 
    JOIN elections e ON vl.election_id = e.id 
    JOIN candidates c ON vl.candidate_id = c.id 
    WHERE vl.voter_id = ? 
    ORDER BY vl.voted_at DESC
");
$history_stmt->bind_param("i", $voter_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Dashboard - Online Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>

<body class="bg-light dashboard-full">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-vote-yea me-2"></i>Online Voting System
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i>Welcome, <?= htmlspecialchars($voter_name) ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-0 dashboard-section">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mx-3">
                <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-0 h-100">
            <!-- Voter Info Card -->
            <div class="col-xl-3 col-lg-4 px-3 dashboard-card">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-circle me-2"></i>Your Profile
                        </h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Name:</strong> <?= htmlspecialchars($voter_name) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($voter_email) ?></p>
                        <p class="text-success">
                            <i class="fas fa-check-circle me-1"></i>Verified Voter
                        </p>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="card shadow-sm mt-3 flex-fill">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-external-link-alt me-2"></i>Quick Links
                        </h6>
                    </div>
                    <div class="card-body">
                        <a href="../guest/view_results.php" class="btn btn-outline-primary btn-sm w-100 mb-2">
                            <i class="fas fa-chart-bar me-1"></i>View Results
                        </a>
                        <a href="../index.php" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="fas fa-home me-1"></i>Back to Home
                        </a>
                    </div>
                </div>
            </div>

            <!-- Active Elections -->
            <div class="col-xl-9 col-lg-8 px-3 dashboard-card">
                <div class="card shadow-sm h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-vote-yea me-2"></i>Active Elections
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($elections_result->num_rows > 0): ?>
                            <?php while ($election = $elections_result->fetch_assoc()): ?>
                                <div class="border rounded p-3 mb-3 live-results-container" data-election-id="<?= $election['id'] ?>">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="fw-bold mb-0"><?= htmlspecialchars($election['name']) ?></h6>
                                        <div class="results-controls">
                                            <small class="text-muted">
                                                <i class="fas fa-circle text-success me-1" style="font-size: 0.6em;"></i>Live Results
                                            </small>
                                        </div>
                                    </div>
                                    <p class="text-muted small mb-3"><?= htmlspecialchars($election['description']) ?></p>

                                    <?php
                                    // Check if voter has already voted in this election
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
                                        // Get candidates for this election
                                        $candidates_stmt = $conn->prepare("SELECT * FROM candidates WHERE election_id = ? ORDER BY candidate_name");
                                        $candidates_stmt->bind_param("i", $election['id']);
                                        $candidates_stmt->execute();
                                        $candidates_result = $candidates_stmt->get_result();
                                        ?>

                                        <form method="POST" class="vote-form">
                                            <input type="hidden" name="election_id" value="<?= $election['id'] ?>">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Select your candidate:</label>
                                                <?php while ($candidate = $candidates_result->fetch_assoc()): ?>
                                                    <div class="form-check border rounded p-2 mb-2">
                                                        <input class="form-check-input" type="radio" name="candidate_id"
                                                            value="<?= $candidate['id'] ?>" id="candidate_<?= $candidate['id'] ?>" required>
                                                        <label class="form-check-label w-100" for="candidate_<?= $candidate['id'] ?>">
                                                            <strong><?= htmlspecialchars($candidate['candidate_name']) ?></strong>
                                                            <?php if (!empty($candidate['party'])): ?>
                                                                <span class="text-muted">- <?= htmlspecialchars($candidate['party']) ?></span>
                                                            <?php endif; ?>
                                                        </label>
                                                    </div>
                                                <?php endwhile; ?>
                                            </div>
                                            <button type="submit" name="vote_candidate" class="btn btn-success"
                                                onclick="return confirm('Are you sure you want to cast your vote? This action cannot be undone.')">
                                                <i class="fas fa-check me-2"></i>Cast Vote
                                            </button>
                                        </form>

                                        <!-- Live Results Container (for non-voted elections) -->
                                        <div class="live-results mt-3">
                                            <!-- Real-time results will be loaded here -->
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-vote-yea fa-3x text-muted mb-3"></i>
                                <h6>No Active Elections</h6>
                                <p class="text-muted">There are currently no active elections available for voting.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Voting History -->
                <div class="card shadow-sm mt-4 flex-fill">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Your Voting History
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($history_result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Election</th>
                                            <th>Candidate</th>
                                            <th>Party</th>
                                            <th>Voted At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($vote = $history_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($vote['election_name']) ?></td>
                                                <td><?= htmlspecialchars($vote['candidate_name']) ?></td>
                                                <td><?= htmlspecialchars($vote['party']) ?></td>
                                                <td><?= date('M j, Y g:i A', strtotime($vote['voted_at'])) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center">You haven't voted in any elections yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/realtime-results.js"></script>
</body>

</html>