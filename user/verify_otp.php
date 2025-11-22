<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['temp_voter_id'], $_SESSION['temp_voter_email'])) {
    header("Location: login.php");
    exit();
}

$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = implode("", $_POST['otp']);

    if (verifyOTP($_SESSION['temp_voter_id'], $otp)) {
        $_SESSION['voter_id'] = $_SESSION['temp_voter_id'];
        $_SESSION['voter_email'] = $_SESSION['temp_voter_email'];
        unset($_SESSION['temp_voter_id'], $_SESSION['temp_voter_email']);
        header("Location: dashboard.php");
        exit();
    } else {
        $message = "Invalid or expired OTP.";
        $message_type = "error";
    }
}

if (isset($_GET['resend'])) {
    generateAndSendOTP($_SESSION['temp_voter_id'], $_SESSION['temp_voter_email']);
    $message = "A new OTP has been sent to your email.";
    $message_type = "success";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Verify OTP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: Arial, sans-serif;
        }

        .verify-box {
            background: #fff;
            border-radius: 20px;
            padding: 40px;
            width: 450px;
        }

        .otp-input {
            width: 55px;
            height: 55px;
            font-size: 28px;
            text-align: center;
            border: 2px solid #ccc;
            border-radius: 10px;
        }

        .otp-input:focus {
            border-color: #6A5AE0;
            box-shadow: 0 0 3px rgba(106, 90, 224, .7);
            outline: none;
        }

        .btn-main {
            background: #5568FE;
            color: #fff;
            border-radius: 12px;
            padding: 14px;
            width: 100%;
            border: none;
        }

        .resend-btn,
        .start-btn {
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 14px;
        }

        .resend-btn {
            background: #EEF2FF;
            border: 1px solid #CBD5FF;
            color: #5568FE;
        }

        .start-btn {
            background: #F3F4F6;
            border: 1px solid #D1D5DB;
        }
    </style>
</head>

<body class="d-flex justify-content-center align-items-center min-vh-100">

    <div class="verify-box shadow">

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                <?= $message ?>
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="text-center mb-3">
            <img src="https://cdn-icons-png.flaticon.com/512/561/561127.png" width="70">
        </div>

        <h3 class="text-center">Enter Verification Code</h3>
        <p class="text-center text-muted">
            A 6-digit code was sent to<br>
            <strong><?= $_SESSION['temp_voter_email'] ?></strong>
        </p>

        <div id="timer" class="text-center text-secondary mb-3 fs-6">
            ⏳ Code expires in <span id="countdown">2:00</span>
        </div>

        <form method="POST" class="text-center">
            <div class="d-flex justify-content-center gap-2 mb-4">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <input type="text" maxlength="1" name="otp[]" class="otp-input" oninput="moveNext(this)">
                <?php endfor; ?>
            </div>

            <button class="btn-main">✔ Verify & Login</button>
        </form>

        <div class="text-center mt-4">
            <p class="text-muted">Didn't receive the code?</p>
            <a href="?resend=1" class="resend-btn me-2">🔄 Resend</a>
            <a href="login.php" class="start-btn">↩ Start Over</a>
        </div>

    </div>

    <script>
        // Auto next input
        function moveNext(e) {
            if (e.value.length === 1 && e.nextElementSibling) {
                e.nextElementSibling.focus();
            }
        }

        let time = 120;
        const el = document.getElementById("countdown");
        setInterval(() => {
            if (time <= 0) return;
            time--;
            el.textContent = `${Math.floor(time/60)}:${String(time%60).padStart(2, '0')}`;
        }, 1000);
    </script>

</body>

</html>