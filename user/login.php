<?php
require_once '../includes/security_headers.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$message = '';
$message_type = '';

if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    $message_type = 'success';
    unset($_SESSION['success_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $message = "Invalid request. Please try again.";
        $message_type = "error";
    } else {
        $login_input = sanitize($_POST['login_input'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($login_input) || empty($password)) {
            $message = "Please enter your email/phone and password.";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("SELECT * FROM voters WHERE email=? OR phone=? LIMIT 1");
            $stmt->bind_param("ss", $login_input, $login_input);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $voter = $result->fetch_assoc();
                if (password_verify($password, $voter['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['temp_voter_id'] = $voter['id'];
                    $_SESSION['temp_voter_email'] = $voter['email'];

                    if (generateAndSendOTP($voter['id'], $voter['email'])) {
                        header("Location: verify_otp.php");
                        exit();
                    } else {
                        $message = "Failed to send OTP. Please try again.";
                        $message_type = "error";
                    }
                } else {
                    $message = "Incorrect password. Please try again.";
                    $message_type = "error";
                }
            } else {
                $message = "No account found with that email or phone.";
                $message_type = "error";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Voter Login - Online Voting System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px 35px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .form-control {
            border-radius: 12px;
            padding: 12px 16px;
        }

        .alert {
            border-radius: 12px;
        }

        .forgot-link {
            font-size: 14px;
            text-decoration: none;
            color: #667eea;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 50px;
        }

        .password-wrapper button {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            z-index: 10;
            height: auto;
            width: auto;
            line-height: 1;
        }

        .password-wrapper button:focus {
            box-shadow: none;
            outline: none;
        }
    </style>
</head>

<body>
    <div class="container d-flex align-items-center justify-content-center min-vh-100">
        <div class="col-md-5">
            <div class="login-container">
                <div class="text-center mb-4">
                    <i class="fas fa-lock fa-3x text-primary mb-3"></i>
                    <h3 class="fw-bold mb-1">Welcome Back, Voter</h3>
                    <p class="text-muted">Login to continue and cast your vote</p>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type === 'error' ? 'danger' : 'success' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <?= getCSRFField() ?>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-envelope me-1"></i> Email or Phone</label>
                        <input type="text" name="login_input" class="form-control" placeholder="Enter your email or phone" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-lock me-1"></i> Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                            <button type="button" onclick="togglePassword()" class="btn btn-link p-0">
                                <i class="fas fa-eye text-muted"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-sign-in-alt me-1"></i> Login
                    </button>

                    <div class="text-center">
                        <a href="forgot_password.php" class="forgot-link"><i class="fas fa-key me-1"></i>Forgot Password?</a>
                    </div>

                    <a href="/Online_Voting_System/index.php" class="btn btn-outline-primary w-100 mb-3 mt-3" style="border-radius:12px; font-weight:600;">
                        <i class="fas fa-arrow-left me-2"></i>Back to Home
                    </a>

                    <hr class="my-4">

                    <div class="text-center">
                        <p class="text-muted mb-2">Don't have an account?</p>
                        <a href="register.php" class="btn btn-outline-primary px-4">
                            <i class="fas fa-user-plus me-1"></i> Register Now
                        </a>
                    </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = event.currentTarget.querySelector('i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'fas fa-eye-slash text-muted';
            } else {
                passwordInput.type = 'password';
                icon.className = 'fas fa-eye text-muted';
            }
        }
    </script>
</body>

</html>
