<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$message = '';
$step = isset($_SESSION['reset_step']) ? $_SESSION['reset_step'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    $check = $conn->prepare("SELECT id FROM voters WHERE email=?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $voter = $result->fetch_assoc();
        $_SESSION['reset_voter_id'] = $voter['id'];
        $_SESSION['reset_email'] = $email;

        if (generateAndSendOTP($voter['id'], $email)) {
            $_SESSION['reset_step'] = 2;
            $step = 2;
            $message = "OTP sent to your email.";
        } else {
            $message = "Failed to send OTP. Try again.";
        }
    } else {
        $message = "No voter found with that email.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $otp = trim($_POST['otp']);
    if (!isset($_SESSION['reset_voter_id'])) {
        $message = "Session expired. Please start the password reset process again.";
        $step = 1;
    } else {
        $voter_id = $_SESSION['reset_voter_id'];
        if (verifyOTP($voter_id, $otp)) {
            $_SESSION['reset_step'] = 3;
            $step = 3;
            $message = "OTP verified! You can now set a new password.";
        } else {
            $message = "Invalid or expired OTP.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    $new_password = trim($_POST['new_password']);
    if (!isset($_SESSION['reset_voter_id'])) {
        $message = "Session expired. Please start the password reset process again.";
        $step = 1;
    } else {
        $voter_id = $_SESSION['reset_voter_id'];
        if (strlen($new_password) >= 6) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE voters SET password=? WHERE id=?");
            $stmt->bind_param("si", $hashed, $voter_id);
            $stmt->execute();

            unset($_SESSION['reset_step'], $_SESSION['reset_voter_id'], $_SESSION['reset_email']);

            $_SESSION['success_message'] = "Password reset successful! You can now login.";
            header('Location: login.php');
            exit();
        } else {
            $message = "Password must be at least 6 characters.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Forgot Password - Online Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height:100vh;">
    <div class="card shadow-sm p-4" style="width:400px;">
        <h4 class="text-center mb-3">Forgot Password</h4>
        <?php if ($message): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>


        <?php if ($step == 1): ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Enter your registered email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary flex-fill">Send OTP</button>
                    <button type="button" class="btn btn-outline-secondary flex-fill" onclick="window.history.back();">Back</button>
                </div>
            </form>


        <?php elseif ($step == 2): ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Enter OTP (sent to your email)</label>
                    <input type="text" name="otp" class="form-control" required>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary flex-fill">Verify OTP</button>
                    <button type="button" class="btn btn-outline-secondary flex-fill" onclick="window.history.back();">Back</button>
                </div>
            </form>

        <?php elseif ($step == 3): ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Enter New Password</label>
                    <input type="password" name="new_password" class="form-control" required minlength="6">
                </div>
                <button class="btn btn-success w-100">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>
