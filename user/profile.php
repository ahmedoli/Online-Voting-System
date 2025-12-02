<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['voter_id'])) {
    header('Location: login.php');
    exit();
}

$voter_id = $_SESSION['voter_id'];

$stmt = $conn->prepare("SELECT name, email, phone, id_number, id_type, registered_at FROM voters WHERE id = ?");
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: logout.php');
    exit();
}

$voter = $result->fetch_assoc();
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Online Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        .profile-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 18px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            margin-top: 50px;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 2rem;
        }

        .info-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
        }

        .info-value {
            color: #6c757d;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-outline-primary {
            border: 2px solid #667eea;
            color: #667eea;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-outline-primary:hover {
            background: #667eea;
            border-color: #667eea;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-vote-yea me-2"></i>Online Voting System
            </a>
            <div class="navbar-nav ms-auto">
                <div class="dropdown">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user me-1"></i><?= htmlspecialchars($voter['name']) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user-circle me-2"></i>Profile
                            </a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="profile-container">
                    <!-- Profile Header -->
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <h3 class="mb-1"><?= htmlspecialchars($voter['name']) ?></h3>
                        <p class="text-muted mb-0">Registered Voter</p>
                    </div>



                    <!-- Profile Information -->
                    <div class="info-card">
                        <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Personal Information</h5>

                        <div class="info-row">
                            <span class="info-label">Full Name</span>
                            <span class="info-value"><?= htmlspecialchars($voter['name']) ?></span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Email Address</span>
                            <span class="info-value"><?= htmlspecialchars($voter['email']) ?></span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Phone Number</span>
                            <span class="info-value"><?= htmlspecialchars($voter['phone']) ?></span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Voter ID</span>
                            <span class="info-value"><?= htmlspecialchars($voter['id_number']) ?></span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">ID Type</span>
                            <span class="info-value"><?= htmlspecialchars($voter['id_type']) ?></span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Registered On</span>
                            <span class="info-value"><?= date('F j, Y', strtotime($voter['registered_at'])) ?></span>
                        </div>
                    </div>

                    <!-- Action Buttons Centered -->
                    <div class="d-flex gap-3 flex-wrap justify-content-center mt-4">
                        <a href="change_password.php" class="btn btn-primary">
                            <i class="fas fa-key me-2"></i>Change Password
                        </a>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
