<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
require_once __DIR__ . '/../includes/db_connect.php'; // ✅ Get overall statistics
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

      <!-- Results Table -->
      <div class="table-responsive table-container">
        <table class="table table-hover align-middle text-center">
          <thead class="table-primary">
            <tr>
              <th>Candidate Name</th>
              <th>Election</th>
              <th>Total Votes</th>
              <th>Percentage</th>
            </tr>
          </thead>
          <tbody id="results-table">
            <!-- Results loaded via AJAX -->
          </tbody>
        </table>
      </div>

      <div class="text-center mt-4">
        <a href="dashboard.php" class="btn btn-secondary px-4 me-2">
          <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
        <a href="export_results.php" class="btn btn-success px-4">
          <i class="fas fa-file-export me-1"></i> Export Results (CSV)
        </a>
      </div>
    </div>
  </div>

  <script>
    // 🔁 Auto-refresh every 5 seconds
    function fetchResults() {
      fetch('fetch_results.php')
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.text();
        })
        .then(html => {
          document.getElementById('results-table').innerHTML = html;
        })
        .catch(err => {
          console.error('Error loading results:', err);
          document.getElementById('results-table').innerHTML =
            '<tr><td colspan="4" class="text-danger">Error loading results. Please refresh the page.</td></tr>';
        });
    }

    fetchResults(); // Initial load
    setInterval(fetchResults, 5000);
  </script>

</body>

</html>