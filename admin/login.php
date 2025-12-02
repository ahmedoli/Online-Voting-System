<?php
require_once __DIR__ . '/../includes/security_headers.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db_connect.php';

if (isAdminLoggedIn()) {
    header('Location: ' . getBaseUrl() . '/admin/dashboard.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = db_prepare('SELECT id, username, password FROM admins WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $password_valid = false;

            if (password_verify($password, $row['password'])) {
                $password_valid = true;
            } elseif ($password === $row['password']) {
                $password_valid = true;
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = db_prepare('UPDATE admins SET password = ? WHERE id = ?');
                $update_stmt->bind_param('si', $hashed_password, $row['id']);
                $update_stmt->execute();
                error_log("Updated plain text password to hashed for admin: " . $row['username']);
            }

            if ($password_valid) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $row['id'];
                $_SESSION['admin_user'] = $row['username'];
                header('Location: ' . getBaseUrl() . '/admin/dashboard.php');
                exit;
            }
        }
        $error = 'Invalid username or password';
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Online_Voting_System/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px 35px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh;">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="login-card">
                    <div class="login-brand">
                        <h4 style="margin:0"><i class="fa-solid fa-check-to-slot" style="margin-right:8px"></i> Online Voting - Admin</h4>
                    </div>
                    <div class="login-body">
                        <h5 class="mb-3">Sign in to your admin account</h5>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= sanitize($error) ?></div>
                        <?php endif; ?>
                        <form method="post">
                            <?= getCSRFField() ?>
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input name="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input name="password" type="password" class="form-control" required>
                            </div>
                            <div class="d-grid">
                                <button class="btn btn-brand">Login</button>
                            </div>
                        </form>
                        <div class="text-center mt-3 muted">Need help? Contact the system administrator.</div>
                        <div class="text-center mt-4">
                            <a href="<?= getBaseUrl() ?>/index.php" class="btn btn-outline-primary btn-lg px-4">
                                <i class="fas fa-arrow-left me-2"></i>Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>