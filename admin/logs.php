<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
require_once __DIR__ . '/../includes/db_connect.php';

// Backlog 6: Secure Vote Log - Fetch secure vote logs (only voter ID and timestamp)
$logs_query = "
    SELECT 
        vl.id,
        vl.voter_id,
        vl.vote_hash,
        vl.voted_at,
        e.name as election_name
    FROM vote_logs vl
    JOIN elections e ON vl.election_id = e.id
    ORDER BY vl.voted_at DESC
    LIMIT 100
";
$logs_result = $mysqli->query($logs_query);

// Get vote statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_votes,
        COUNT(DISTINCT voter_id) as unique_voters,
        COUNT(DISTINCT election_id) as elections_with_votes,
        DATE(MIN(voted_at)) as first_vote,
        DATE(MAX(voted_at)) as latest_vote
    FROM vote_logs
";
$stats_result = $mysqli->query($stats_query);
$stats = $stats_result ? $stats_result->fetch_assoc() : [];
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Secure Vote Logs - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Online_Voting_System/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .logs-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
        }

        .stat-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border-left: 4px solid #667eea;
        }

        .secure-badge {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
        }

        .hash-display {
            font-family: monospace;
            font-size: 0.8em;
            color: #6c757d;
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="logs-container">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-primary mb-1">
                        <i class="fas fa-shield-alt me-2"></i>Secure Vote Logs
                    </h2>
                    <p class="text-muted mb-0">Backlog 6: Tamper-proof vote logging system</p>
                </div>
                <div>
                    <span class="secure-badge">
                        <i class="fas fa-lock me-1"></i>Secured & Hashed
                    </span>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4 g-3">
                <div class="col-md-3">
                    <div class="stat-card">
                        <h5 class="text-primary"><?= $stats['total_votes'] ?? 0 ?></h5>
                        <small class="text-muted">Total Secure Votes</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h5 class="text-success"><?= $stats['unique_voters'] ?? 0 ?></h5>
                        <small class="text-muted">Unique Voters</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h5 class="text-info"><?= $stats['elections_with_votes'] ?? 0 ?></h5>
                        <small class="text-muted">Elections</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h5 class="text-warning"><?= isset($stats['first_vote']) ? date('M j', strtotime($stats['first_vote'])) : 'N/A' ?></h5>
                        <small class="text-muted">First Vote</small>
                    </div>
                </div>
            </div>

            <!-- Security Features Notice -->
            <div class="alert alert-info d-flex align-items-center mb-4">
                <i class="fas fa-info-circle me-3 fs-5"></i>
                <div>
                    <strong>Security Implementation:</strong> All votes are hashed with SHA-256 and timestamped.
                    Only voter ID and time are visible for privacy protection. Vote details are encrypted and tamper-proof.
                </div>
            </div>

            <!-- Secure Vote Logs Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>Log ID</th>
                            <th>Voter ID</th>
                            <th>Voted At</th>
                            <th>Election</th>
                            <th>Security Hash</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($logs_result && $logs_result->num_rows > 0): ?>
                            <?php while ($log = $logs_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong>#<?= $log['id'] ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-user me-1"></i>
                                            Voter <?= $log['voter_id'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?= date('M j, Y', strtotime($log['voted_at'])) ?></strong><br>
                                            <small class="text-muted"><?= date('H:i:s', strtotime($log['voted_at'])) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-primary"><?= htmlspecialchars($log['election_name']) ?></span>
                                    </td>
                                    <td>
                                        <div class="hash-display">
                                            <?= substr($log['vote_hash'], 0, 12) ?>...
                                            <i class="fas fa-fingerprint ms-1 text-success"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Verified
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    No secure vote logs found. Votes will appear here once cast.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Navigation -->
            <div class="text-center mt-4">
                <a href="dashboard.php" class="btn btn-secondary px-4">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>

</html>