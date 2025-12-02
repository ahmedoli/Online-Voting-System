<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
require_once __DIR__ . '/../includes/db_connect.php';

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

$total_voters = 0;
$total_votes = 0;

$voter_result = $mysqli->query("SELECT COUNT(*) AS total_voters FROM voters");
if ($voter_result && $row = $voter_result->fetch_assoc()) {
  $total_voters = $row['total_voters'];
}

$vote_result = $mysqli->query("SELECT COUNT(DISTINCT voter_id) AS total_votes FROM vote_logs");
if ($vote_result && $row = $vote_result->fetch_assoc()) {
  $total_votes = $row['total_votes'];
}

$turnout = ($total_voters > 0) ? round(($total_votes / $total_voters) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Live Election Results - Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
    }

    .dashboard-card {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 20px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
      padding: 25px;
    }

    .stat-card {
      background: #f9fafb;
      border-radius: 15px;
      padding: 20px;
      text-align: center;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    }

    .table-container {
      max-height: 500px;
      overflow-y: auto;
    }
  </style>
</head>

<body>
  <div class="container py-5">
    <div class="dashboard-card">
      <div class="text-center mb-4">
        <h2 class="fw-bold text-primary"><i class="fas fa-chart-bar me-2"></i>Live Election Results</h2>
        <p class="text-muted">Candidate totals update every few seconds automatically</p>
      </div>

      <!-- Stats Summary -->
      <div class="row mb-4 g-3">
        <div class="col-md-4">
          <div class="stat-card">
            <h5 class="text-muted">Total Voters</h5>
            <h3><?= $total_voters ?></h3>
          </div>
        </div>
        <div class="col-md-4">
          <div class="stat-card">
            <h5 class="text-muted">Votes Cast</h5>
            <h3><?= $total_votes ?></h3>
          </div>
        </div>
        <div class="col-md-4">
          <div class="stat-card">
            <h5 class="text-muted">Voter Turnout</h5>
            <h3><?= $turnout ?>%</h3>
          </div>
        </div>
      </div>

      <!-- Elections Results -->
      <?php
      // Get all elections with dates
      $elections_query = "SELECT id, name, description, start_date, end_date FROM elections ORDER BY created_at DESC";
      $elections_result = $mysqli->query($elections_query);

      if ($elections_result && $elections_result->num_rows > 0):
      ?>
        <?php while ($election = $elections_result->fetch_assoc()): ?>
          <div class="card mb-4">
            <div class="card-header bg-primary text-white">
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                  <i class="fas fa-vote-yea me-2"></i><?= htmlspecialchars($election['name']) ?>
                </h5>
                <a href="export_results.php?election_id=<?= $election['id'] ?>" class="btn btn-light btn-sm">
                  <i class="fas fa-file-export me-1"></i> Export CSV
                </a>
              </div>
              <?php if ($election['description']): ?>
                <small class="text-light"><?= htmlspecialchars($election['description']) ?></small>
              <?php endif; ?>
            </div>
            <div class="card-body">
              <?php
              // Check election status
              $now = date('Y-m-d');
              $is_completed = ($election['end_date'] < $now);
              ?>

              <?php if ($is_completed): ?>
                <!-- Winners by Position for this election -->
                <div class="alert alert-success mb-4">
                  <h6 class="mb-3"><i class="fas fa-trophy me-2"></i>Winners by Position</h6>
                  <?php
                  // Get winners for this specific election
                  $election_winners_query = "
                    SELECT 
                      c.candidate_name,
                      c.party,
                      c.position,
                      COUNT(v.id) AS total_votes
                    FROM candidates c
                    LEFT JOIN vote_logs v ON v.candidate_id = c.id
                    WHERE c.election_id = ?
                    GROUP BY c.id, c.candidate_name, c.party, c.position
                    HAVING total_votes > 0
                    ORDER BY c.position, total_votes DESC";

                  $stmt = $mysqli->prepare($election_winners_query);
                  $stmt->bind_param('i', $election['id']);
                  $stmt->execute();
                  $election_winners_result = $stmt->get_result();

                  // Find winner for each position
                  $election_position_winners = [];
                  while ($candidate = $election_winners_result->fetch_assoc()) {
                    $position = ucwords(strtolower(trim($candidate['position'])));
                    if (
                      !isset($election_position_winners[$position]) ||
                      $candidate['total_votes'] > $election_position_winners[$position]['total_votes']
                    ) {
                      $election_position_winners[$position] = $candidate;
                    }
                  }

                  // Sort positions by hierarchy
                  uksort($election_position_winners, function ($a, $b) {
                    return getPositionOrder($a) - getPositionOrder($b);
                  });
                  ?>

                  <?php if (!empty($election_position_winners)): ?>
                    <div class="row">
                      <?php foreach ($election_position_winners as $position => $winner): ?>
                        <div class="col-md-4 mb-2">
                          <div class="d-flex align-items-center">
                            <i class="fas fa-crown text-warning me-2"></i>
                            <div>
                              <strong class="text-success"><?= htmlspecialchars($position) ?>:</strong>
                              <span><?= htmlspecialchars($winner['candidate_name']) ?></span>
                              <small class="text-muted">(<?= $winner['total_votes'] ?> votes)</small>
                            </div>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php else: ?>
                    <small class="text-muted">No winners found for this election.</small>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <?php
                  $candidates_query = "
                    SELECT 
                      c.candidate_name,
                      c.party,
                      c.position,
                      COUNT(v.id) AS total_votes
                    FROM candidates c
                    LEFT JOIN vote_logs v ON v.candidate_id = c.id
                    WHERE c.election_id = ?
                    GROUP BY c.id
                    ORDER BY c.position, total_votes DESC";

                  $candidates_stmt = $mysqli->prepare($candidates_query);
                  $candidates_stmt->bind_param('i', $election['id']);
                  $candidates_stmt->execute();
                  $candidates_result = $candidates_stmt->get_result();

                  $election_total_query = "
                    SELECT COUNT(v.id) AS total 
                    FROM vote_logs v 
                    JOIN candidates c ON c.id = v.candidate_id 
                    WHERE c.election_id = ?";
                  $election_total_stmt = $mysqli->prepare($election_total_query);
                  $election_total_stmt->bind_param('i', $election['id']);
                  $election_total_stmt->execute();
                  $election_total_result = $election_total_stmt->get_result();
                  $election_total_votes = $election_total_result->fetch_assoc()['total'];

                  // Group candidates by position with proper case formatting
                  $positions = [];
                  while ($candidate = $candidates_result->fetch_assoc()) {
                    $position = $candidate['position'] ?: 'General';
                    // Normalize position display (Title Case)
                  $positions = [];
                  while ($candidate = $candidates_result->fetch_assoc()) {
                    $position = $candidate['position'] ?: 'General';
                    $position = ucwords(strtolower(trim($position)));
                    if (!isset($positions[$position])) {
                      $positions[$position] = [];
                    }
                    $positions[$position][] = $candidate;
                  }

                  // Sort positions by hierarchy
                  uksort($positions, function ($a, $b) {
                    return getPositionOrder($a) - getPositionOrder($b);
                  });

                  if (!empty($positions)):
                    foreach ($positions as $position => $candidates):
                      $position_total_votes = 0;
                      foreach ($candidates as $c) {
                        $position_total_votes += (int)$c['total_votes'];
                      }
                  ?>
                      <h4 class="mb-3"><?= htmlspecialchars($position) ?></h4>
                      <table class="table table-hover">
                        <thead class="table-light">
                          <tr>
                            <th>Candidate Name</th>
                            <th>Party</th>
                            <th class="text-center">Total Votes</th>
                            <th class="text-center">Percentage</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($candidates as $candidate):
                            $percentage = ($position_total_votes > 0) ? round(($candidate['total_votes'] / $position_total_votes) * 100, 2) : 0;
                          ?>
                            <tr>
                              <td><?= htmlspecialchars($candidate['candidate_name']) ?></td>
                              <td><?= htmlspecialchars($candidate['party']) ?></td>
                              <td class="text-center"><?= $candidate['total_votes'] ?></td>
                              <td class="text-center"><?= $percentage ?>%</td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div class="text-muted text-center py-4">No candidates found for this election</div>
                  <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="card">
          <div class="card-body text-center">
            <h5 class="text-muted">No elections found</h5>
            <p class="text-muted">Create an election to see results here.</p>
          </div>
        </div>
      <?php endif; ?>

      <div class="text-center mt-4">
        <a href="dashboard.php" class="btn btn-secondary px-4">
          <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
      </div>
    </div>
  </div>

  <script>
    // Auto-refresh the page every 30 seconds to show updated vote counts
    setTimeout(function() {
      window.location.reload();
    }, 30000);
  </script>

</body>

</html>
