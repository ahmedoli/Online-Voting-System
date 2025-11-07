<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['voter_id'])) {
    header('Location: dashboard.php');
    exit();
}

$step = isset($_SESSION['otp_step']) ? $_SESSION['otp_step'] : 1;
$message = '';
$message_type = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        // Step 1: Credential verification
        $id_number = trim($_POST['id_number']);
        $password = trim($_POST['password']);

        if (empty($id_number) || empty($password)) {
            $message = 'Please enter both ID number and password.';
            $message_type = 'error';
        } else {
            // Check database structure safely like in registration
            $has_id_number = false;
            $has_nid_number = false;

            try {
                $columns_check = $conn->query("SHOW COLUMNS FROM voters LIKE 'id_number'");
                $has_id_number = $columns_check && $columns_check->num_rows > 0;

                if (!$has_id_number) {
                    $nid_check = $conn->query("SHOW COLUMNS FROM voters LIKE 'nid_number'");
                    $has_nid_number = $nid_check && $nid_check->num_rows > 0;
                }
            } catch (Exception $e) {
                $message = 'Database structure error: ' . $e->getMessage();
                $message_type = 'error';
            }

            // Build query based on available columns
            if ($has_id_number) {
                // Modern structure with id_number
                $stmt = $conn->prepare("SELECT id, phone, email, password FROM voters WHERE id_number = ? LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param("s", $id_number);
                }
            } elseif ($has_nid_number) {
                // Legacy structure with nid_number
                $stmt = $conn->prepare("SELECT id, phone, email, password FROM voters WHERE nid_number = ? LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param("s", $id_number);
                }
            } else {
                // Fallback: try using phone or email as identifier
                $stmt = $conn->prepare("SELECT id, phone, email, password FROM voters WHERE (phone = ? OR email = ?) LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param("ss", $id_number, $id_number);
                }
            }

            if (!$stmt) {
                $message = 'Database connection error. Please try again.';
                $message_type = 'error';
            }
            if ($stmt && $stmt->execute()) {
                $result = $stmt->get_result();

                if ($result && $result->num_rows == 1) {
                    $voter = $result->fetch_assoc();

                    // Verify password
                    if (password_verify($password, $voter['password'])) {
                        $_SESSION['temp_voter_id'] = $voter['id'];
                        $_SESSION['temp_voter_name'] = 'Voter'; // Default name
                        $_SESSION['temp_voter_email'] = $voter['email'];
                        $_SESSION['temp_voter_phone'] = $voter['phone'];
                        $_SESSION['temp_voter_id_type'] = 'ID'; // Default type

                        // Generate and send OTP automatically to email
                        if (generateAndSendOTP($voter['id'], $voter['email'])) {
                            $_SESSION['otp_step'] = 2;
                            $step = 2;
                            $message = 'OTP has been sent to your email address (' . $voter['email'] . '). Please check your email.';
                            $message_type = 'success';
                        } else {
                            $message = 'Failed to send OTP. Please try again.';
                            $message_type = 'error';
                        }
                    } else {
                        $message = 'Invalid ID number or password.';
                        $message_type = 'error';
                    }
                } else {
                    $message = 'Invalid ID number or password.';
                    $message_type = 'error';
                }

                if ($stmt) {
                    $stmt->close();
                }
            } else {
                $message = 'Database query error. Please try again.';
                $message_type = 'error';
            }
        }
    } elseif ($step == 2) {
        // Step 2: OTP verification
        $otp = trim($_POST['otp']);

        if (empty($otp)) {
            $message = 'Please enter the OTP.';
            $message_type = 'error';
        } elseif (!isset($_SESSION['temp_voter_id'])) {
            // Session expired, restart process
            session_unset();
            header('Location: login.php');
            exit();
        } else {
            // Verify OTP
            if (verifyOTP($_SESSION['temp_voter_id'], $otp)) {
                // Login successful
                $_SESSION['voter_id'] = $_SESSION['temp_voter_id'];
                $_SESSION['voter_name'] = $_SESSION['temp_voter_name'];
                $_SESSION['voter_email'] = $_SESSION['temp_voter_email'];
                $_SESSION['voter_phone'] = $_SESSION['temp_voter_phone'];

                // Clear temporary session data
                unset($_SESSION['temp_voter_id']);
                unset($_SESSION['temp_voter_name']);
                unset($_SESSION['temp_voter_email']);
                unset($_SESSION['temp_voter_phone']);
                unset($_SESSION['otp_step']);

                header('Location: dashboard.php');
                exit();
            } else {
                $message = 'Invalid or expired OTP.';
                $message_type = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Login - Online Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: #ffffff;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }

        .login-card {
            max-width: 450px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            border: 1px solid #e5e7eb;
            overflow: hidden;
            margin: 0 auto;
        }

        .card-header {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: white;
            padding: 2rem 2rem 1.5rem 2rem;
            text-align: center;
            border: none;
            border-radius: 0;
        }

        .card-header h3 {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .card-header p {
            opacity: 0.9;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }

        .login-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
            font-size: 1.5rem;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 0;
        }

        .step {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .step.active {
            background-color: rgba(255, 255, 255, 0.3);
            color: white;
        }

        .step.inactive {
            background-color: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
        }

        .step-line {
            width: 40px;
            height: 2px;
            background-color: rgba(255, 255, 255, 0.2);
            margin: 0 10px;
        }

        .card-body {
            padding: 2.5rem 2rem 2rem 2rem;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f9fafb;
        }

        .form-control:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            background-color: white;
        }

        .input-group-text {
            background-color: #f3f4f6;
            border: 2px solid #e5e7eb;
            border-right: none;
            border-radius: 12px 0 0 12px;
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }

        .input-group:focus-within .input-group-text {
            border-color: #4f46e5;
            background-color: #eef2ff;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            border: none;
            border-radius: 12px;
            padding: 0.875rem 1.5rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3);
        }

        .btn-outline-primary {
            border: 2px solid #4f46e5;
            color: #4f46e5;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .btn-outline-primary:hover {
            background-color: #4f46e5;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.2);
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background-color: #f0fdf4;
            color: #166534;
            border-left: 4px solid #22c55e;
        }

        .alert-danger {
            background-color: #fef2f2;
            color: #dc2626;
            border-left: 4px solid #ef4444;
        }

        .form-text {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.5rem;
        }

        .otp-input {
            text-align: center;
            font-size: 2rem;
            font-weight: bold;
            letter-spacing: 0.5em;
            border: 3px solid #e5e7eb !important;
            border-radius: 15px !important;
            transition: all 0.3s ease;
        }

        .otp-input:focus {
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1) !important;
            transform: scale(1.02);
        }

        .otp-icon {
            margin-bottom: 1rem;
        }



        /* Additional centering styles */
        .container-fluid {
            padding: 20px;
        }

        .row {
            margin: 0;
        }

        @media (max-width: 576px) {
            .login-card {
                margin: 1rem auto;
                border-radius: 16px;
                max-width: 95%;
            }

            .card-header,
            .card-body {
                padding: 1.5rem;
            }

            .container-fluid {
                padding: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid d-flex justify-content-center align-items-center min-vh-100">
        <div class="row w-100 justify-content-center">
            <div class="col-11 col-sm-8 col-md-6 col-lg-5 col-xl-4">
                <div class="card login-card mx-auto">
                    <!-- Blue Header -->
                    <div class="card-header">
                        <div class="login-icon">
                            <i class="fas fa-vote-yea"></i>
                        </div>
                        <h3>Voter Login</h3>
                        <p>Secure access to your voting dashboard</p>
                        <!-- Step Indicator -->
                        <div class="step-indicator">
                            <div class="step <?= $step == 1 ? 'active' : ($step > 1 ? 'active' : 'inactive') ?>">1</div>
                            <div class="step-line"></div>
                            <div class="step <?= $step == 2 ? 'active' : 'inactive' ?>">2</div>
                        </div>
                    </div>
                    <!-- White Background Body -->
                    <div class="card-body">

                        <!-- Alert Messages -->
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?>">
                                <?= htmlspecialchars($message) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($step == 1): ?>
                            <!-- Step 1: Login Form -->
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="id_number" class="form-label fw-semibold">
                                        <i class="fas fa-id-card me-2 text-primary"></i>ID Number
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">
                                            <i class="fas fa-user text-muted"></i>
                                        </span>
                                        <input type="text" class="form-control" id="id_number" name="id_number"
                                            value="<?= htmlspecialchars($_POST['id_number'] ?? '') ?>"
                                            placeholder="Enter your NID or Student ID" required>
                                    </div>
                                    <div class="form-text">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            National ID or Student ID accepted
                                        </small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label fw-semibold">
                                        <i class="fas fa-lock me-2 text-primary"></i>Password
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">
                                            <i class="fas fa-key text-muted"></i>
                                        </span>
                                        <input type="password" class="form-control" id="password" name="password"
                                            placeholder="Enter your password" required>
                                    </div>
                                </div>

                                <div class="mb-3 text-center">
                                    <small class="text-muted">OTP will be sent to your registered email address</small>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">Continue to Verification</button>
                            </form>

                            <!-- Registration Link Section -->
                            <div class="text-center mt-4">
                                <hr class="my-3" style="opacity: 0.3;">
                                <p class="mb-2 text-muted">Don't have an account?</p>
                                <a href="register.php" class="btn btn-outline-primary">
                                    <i class="fas fa-user-plus me-2"></i>Register Now
                                </a>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        New voters can register with NID or Student ID
                                    </small>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Step 2: OTP Verification -->
                            <div class="text-center mb-4">
                                <div class="otp-icon mb-3">
                                    <i class="fas fa-envelope-open-text text-primary" style="font-size: 3rem;"></i>
                                </div>
                                <h4 class="fw-bold mb-3">Enter Verification Code</h4>
                                <div class="alert alert-info border-0" style="background-color: #e8f4fd;">
                                    <p class="mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        We've sent a 6-digit code to
                                    </p>
                                    <p class="fw-bold text-primary mb-0"><?= htmlspecialchars($_SESSION['temp_voter_email'] ?? '') ?></p>
                                </div>
                            </div>

                            <form method="POST">
                                <div class="mb-4">
                                    <input type="text" class="form-control text-center otp-input" id="otp" name="otp"
                                        maxlength="6" pattern="[0-9]{6}" required autocomplete="off"
                                        placeholder="000000" style="font-size: 2rem; letter-spacing: 0.5em; height: 70px; border: 3px solid #e5e7eb;">
                                    <div class="text-center mt-3">
                                        <div class="d-flex justify-content-center align-items-center">
                                            <i class="fas fa-clock text-muted me-2"></i>
                                            <span class="text-muted">Code expires in </span>
                                            <span id="timer" class="fw-bold text-danger ms-1">2:00</span>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 mb-4" style="height: 50px; font-size: 1.1rem;">
                                    <i class="fas fa-shield-check me-2"></i>Verify & Login
                                </button>

                                <!-- Didn't receive the code section -->
                                <div class="text-center">
                                    <div class="card border-0" style="background-color: #f8f9fa;">
                                        <div class="card-body py-3">
                                            <h6 class="mb-2">Didn't receive the code?</h6>
                                            <p class="text-muted small mb-3">
                                                Check your spam folder or wait for <span id="resendTimer" class="fw-bold">60</span> seconds to resend
                                            </p>
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="resendBtn" disabled>
                                                <i class="fas fa-redo me-1"></i>Resend Code
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <?php if ($step == 2): ?>
        <script>
            // OTP Timer (2 minutes)
            let timeLeft = 120;
            const timer = document.getElementById('timer');

            const otpInterval = setInterval(function() {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                timer.textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;

                if (timeLeft <= 0) {
                    clearInterval(otpInterval);
                    timer.textContent = 'expired';
                }
                timeLeft--;
            }, 1000);

            // Resend Timer (60 seconds)
            let resendTime = 60;
            const resendBtn = document.getElementById('resendBtn');
            const resendTimer = document.getElementById('resendTimer');

            const resendInterval = setInterval(function() {
                resendTimer.textContent = resendTime;
                resendTime--;

                if (resendTime < 0) {
                    clearInterval(resendInterval);
                    resendBtn.disabled = false;
                    resendBtn.innerHTML = '<i class="fas fa-redo me-1"></i>Resend Code';
                    resendBtn.classList.remove('btn-outline-primary');
                    resendBtn.classList.add('btn-primary');
                }
            }, 1000);

            // Format OTP input to only accept numbers
            document.getElementById('otp').addEventListener('input', function(e) {
                this.value = this.value.replace(/\D/g, '');
            });

            // Auto-focus on OTP input
            document.getElementById('otp').focus();
        </script>
    <?php endif; ?>

    <script>
        // Modern login enhancements
        document.addEventListener('DOMContentLoaded', function() {
            // Format ID number input (uppercase, alphanumeric only)
            const idInput = document.getElementById('id_number');
            if (idInput) {
                idInput.addEventListener('input', function(e) {
                    this.value = this.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
                });

                // Auto-focus on page load
                idInput.focus();
            }

            // Add smooth animations to form elements
            document.querySelectorAll('.form-control').forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });

                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });

            // Form submission with loading state
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');

                    if (form.checkValidity()) {
                        // Show loading state
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                        submitBtn.disabled = true;
                    }
                });
            });

            // Keyboard navigation
            const idField = document.getElementById('id_number');
            const passwordField = document.getElementById('password');

            if (idField && passwordField) {
                idField.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        passwordField.focus();
                    }
                });

                passwordField.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.closest('form').submit();
                    }
                });
            }
        });
    </script>
</body>

</html>