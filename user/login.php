<?php

// Start session
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

// Check database connection
if (!$conn) {
    die("Database connection failed. Please contact administrator.");
}

// Clear any previous login session data when accessing login page fresh (not during form submission)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['resend'])) {
    unset(
        $_SESSION['login_step'],
        $_SESSION['temp_voter_id'],
        $_SESSION['temp_voter_name'],
        $_SESSION['temp_voter_email'],
        $_SESSION['temp_voter_phone']
    );
}

// Initialize variables
$step = isset($_SESSION['login_step']) ? $_SESSION['login_step'] : 1;
$message = '';
$message_type = '';
$show_resend = false;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($step == 1) {
        // Step 1: ID and Password verification
        $id_number = trim($_POST['id_number'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($id_number) || empty($password)) {
            $message = 'Please enter both ID number and password.';
            $message_type = 'error';
        } else {
            // Check database structure first
            $has_id_number = false;
            $has_nid_number = false;

            // Check for id_number column
            $check_id = $conn->query("SHOW COLUMNS FROM voters LIKE 'id_number'");
            if ($check_id && $check_id->num_rows > 0) {
                $has_id_number = true;
            }

            // Check for nid_number column
            $check_nid = $conn->query("SHOW COLUMNS FROM voters LIKE 'nid_number'");
            if ($check_nid && $check_nid->num_rows > 0) {
                $has_nid_number = true;
            }

            // Build appropriate query based on available columns
            $sql = "SELECT id, email, phone, password FROM voters WHERE ";
            $params = [];
            $types = "";

            if ($has_id_number && $has_nid_number) {
                $sql .= "(id_number = ? OR nid_number = ?)";
                $params = [$id_number, $id_number];
                $types = "ss";
            } elseif ($has_id_number) {
                $sql .= "id_number = ?";
                $params = [$id_number];
                $types = "s";
            } elseif ($has_nid_number) {
                $sql .= "nid_number = ?";
                $params = [$id_number];
                $types = "s";
            } else {
                // Fallback - try email or phone
                $sql .= "(email = ? OR phone = ?)";
                $params = [$id_number, $id_number];
                $types = "ss";
            }

            $sql .= " LIMIT 1";

            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows == 1) {
                    $voter = $result->fetch_assoc();

                    // Verify password
                    if (password_verify($password, $voter['password'])) {
                        // Store temporary session data
                        $_SESSION['temp_voter_id'] = $voter['id'];
                        $_SESSION['temp_voter_name'] = $voter['name'];
                        $_SESSION['temp_voter_email'] = $voter['email'];
                        $_SESSION['temp_voter_phone'] = $voter['phone'];

                        // Generate and send OTP
                        if (generateAndSendOTP($voter['id'], $voter['email'])) {
                            $_SESSION['login_step'] = 2;
                            $step = 2;
                            $message = 'OTP has been sent to your email: ' . $voter['email'] . '. Please check your inbox and spam folder.';
                            $message_type = 'success';
                            $show_resend = true;
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
                $stmt->close();
            } else {
                $message = 'Database connection error. Please try again.';
                $message_type = 'error';
            }
        }
    } elseif ($step == 2) {
        // Step 2: OTP Verification
        $otp = trim($_POST['otp'] ?? '');

        if (empty($otp)) {
            $message = 'Please enter the 6-digit OTP code.';
            $message_type = 'error';
            $show_resend = true;
        } elseif (strlen($otp) !== 6 || !ctype_digit($otp)) {
            $message = 'Please enter a valid 6-digit numeric code.';
            $message_type = 'error';
            $show_resend = true;
        } elseif (!isset($_SESSION['temp_voter_id'])) {
            $message = 'Session expired. Please login again.';
            $message_type = 'error';
            $step = 1;
            $_SESSION['login_step'] = 1;
        } else {
            // Verify OTP with better error handling
            try {
                $verification_result = verifyOTP($_SESSION['temp_voter_id'], $otp);

                if ($verification_result) {
                    // Login successful - set permanent session
                    $_SESSION['voter_id'] = $_SESSION['temp_voter_id'];
                    $_SESSION['voter_name'] = $_SESSION['temp_voter_name'];
                    $_SESSION['voter_email'] = $_SESSION['temp_voter_email'];
                    $_SESSION['voter_phone'] = $_SESSION['temp_voter_phone'];

                    // Clear temporary data
                    unset(
                        $_SESSION['temp_voter_id'],
                        $_SESSION['temp_voter_name'],
                        $_SESSION['temp_voter_email'],
                        $_SESSION['temp_voter_phone'],
                        $_SESSION['login_step']
                    );

                    // Redirect to dashboard
                    header('Location: dashboard.php');
                    exit();
                } else {
                    // Check if OTP exists but is expired
                    $otp_check = $conn->prepare("SELECT expires_at FROM voter_otps WHERE voter_id = ? ORDER BY created_at DESC LIMIT 1");
                    $otp_check->bind_param("i", $_SESSION['temp_voter_id']);
                    $otp_check->execute();
                    $otp_result = $otp_check->get_result();

                    if ($otp_result && $otp_result->num_rows > 0) {
                        $otp_data = $otp_result->fetch_assoc();
                        if ($otp_data['expires_at'] < date('Y-m-d H:i:s')) {
                            $message = 'OTP has expired. Please request a new code.';
                        } else {
                            $message = 'Invalid OTP code. Please check and try again.';
                        }
                    } else {
                        $message = 'No OTP found. Please request a new code.';
                    }

                    $message_type = 'error';
                    $show_resend = true;
                    $otp_check->close();
                }
            } catch (Exception $e) {
                error_log("OTP Verification Error: " . $e->getMessage());
                $message = 'Verification failed. Please try again.';
                $message_type = 'error';
                $show_resend = true;
            }
        }
    }
}

// Handle resend OTP request
if (isset($_GET['resend']) && $_GET['resend'] == '1' && isset($_SESSION['temp_voter_id'])) {
    if (generateAndSendOTP($_SESSION['temp_voter_id'], $_SESSION['temp_voter_email'])) {
        $message = 'New OTP has been sent to your email.';
        $message_type = 'success';
        $show_resend = true;
    } else {
        $message = 'Failed to resend OTP. Please try again.';
        $message_type = 'error';
        $show_resend = true;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
        }

        .card-header {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .card-body {
            padding: 2rem;
        }

        .form-control {
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            padding: 0.875rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            border: none;
            border-radius: 12px;
            padding: 0.875rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3);
        }

        .otp-input {
            font-size: 2rem;
            text-align: center;
            letter-spacing: 0.5em;
            font-weight: bold;
        }

        .alert {
            border-radius: 12px;
            border: none;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .step.active {
            background: #4f46e5;
            color: white;
        }

        .step.completed {
            background: #10b981;
            color: white;
        }

        .step.inactive {
            background: #e5e7eb;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="card-header">
                <h2 class="mb-0"><i class="fas fa-vote-yea me-2"></i>Voter Login</h2>
                <p class="mb-0 mt-2">Secure access to your voting dashboard</p>
            </div>

            <div class="card-body">
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step <?= $step == 1 ? 'active' : ($step > 1 ? 'completed' : 'inactive') ?>">1</div>
                    <div class="step <?= $step == 2 ? 'active' : ($step > 2 ? 'completed' : 'inactive') ?>">2</div>
                </div>

                <!-- Alert Messages -->
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $message_type === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
                        <i class="fas <?= $message_type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle' ?> me-2"></i>
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($step == 1): ?>
                    <!-- Step 1: ID and Password -->
                    <h4 class="text-center mb-4">Enter Your Credentials</h4>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="id_number" class="form-label fw-bold">ID Number</label>
                            <input type="text" class="form-control" id="id_number" name="id_number"
                                placeholder="Enter your National ID or Student ID"
                                value="<?= htmlspecialchars($_POST['id_number'] ?? '') ?>" required>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>National ID or Student ID accepted
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label fw-bold">Password</label>
                            <input type="password" class="form-control" id="password" name="password"
                                placeholder="Enter your password" required>
                            <div class="form-text">
                                <i class="fas fa-shield-alt me-1"></i>OTP will be sent to your registered email address
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-sign-in-alt me-2"></i>Continue to OTP
                        </button>
                    </form>

                <?php else: ?>
                    <!-- Step 2: OTP Verification -->
                    <div class="text-center mb-4">
                        <i class="fas fa-envelope-open-text text-primary" style="font-size: 3rem;"></i>
                        <h4 class="mt-3 mb-2">Enter Verification Code</h4>
                        <p class="text-muted">We've sent a 6-digit code to:</p>
                        <p class="fw-bold text-primary"><?= htmlspecialchars($_SESSION['temp_voter_email'] ?? '') ?></p>
                    </div>

                    <form method="POST">
                        <div class="mb-4">
                            <input type="text" class="form-control otp-input" id="otp" name="otp"
                                maxlength="6" placeholder="000000" required autocomplete="off"
                                pattern="[0-9]{6}">
                            <div class="text-center mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>Code expires in <span id="timer">2:00</span>
                                </small>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-check-circle me-2"></i>Verify & Login
                        </button>
                    </form>

                    <div class="text-center">
                        <?php if ($show_resend): ?>
                            <p class="text-muted mb-2">Didn't receive the code?</p>
                            <div class="d-flex gap-2 justify-content-center">
                                <a href="?resend=1" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-redo me-1"></i>Resend Code
                                </a>
                                <a href="login.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-arrow-left me-1"></i>Start Over
                                </a>
                            </div>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>Back to Login
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Registration Link -->
                <div class="text-center mt-4">
                    <hr>
                    <p class="mb-2">Don't have an account?</p>
                    <a href="register.php" class="btn btn-outline-secondary">
                        <i class="fas fa-user-plus me-2"></i>Register Here
                    </a>
                    <p class="text-muted small mt-3">
                        <i class="fas fa-lock me-1"></i>New voters can register with NID or Student ID
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // OTP Timer functionality
        let timeLeft = 120; // 2 minutes
        const timerElement = document.getElementById('timer');

        if (timerElement) {
            const countdown = setInterval(() => {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

                if (timeLeft <= 0) {
                    clearInterval(countdown);
                    timerElement.textContent = 'Expired';
                    timerElement.className = 'text-danger fw-bold';
                }
                timeLeft--;
            }, 1000);
        }

        // Auto-focus inputs and formatting
        document.addEventListener('DOMContentLoaded', function() {
            const idInput = document.getElementById('id_number');
            const otpInput = document.getElementById('otp');

            if (idInput) idInput.focus();
            if (otpInput) {
                otpInput.focus();

                // Format OTP input - only numbers, max 6 digits
                otpInput.addEventListener('input', function(e) {
                    // Remove non-numeric characters
                    let value = this.value.replace(/[^0-9]/g, '');

                    // Limit to 6 digits
                    if (value.length > 6) {
                        value = value.substring(0, 6);
                    }

                    this.value = value;

                    // Auto-submit when 6 digits are entered
                    if (value.length === 6) {
                        console.log('OTP entered: ' + value);
                        // Optional: auto-submit after a short delay
                        // setTimeout(() => this.form.submit(), 500);
                    }
                });

                // Handle paste events
                otpInput.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const paste = (e.clipboardData || window.clipboardData).getData('text');
                    const numericPaste = paste.replace(/[^0-9]/g, '').substring(0, 6);
                    this.value = numericPaste;

                    if (numericPaste.length === 6) {
                        console.log('OTP pasted: ' + numericPaste);
                    }
                });

                // Handle keyboard shortcuts
                otpInput.addEventListener('keydown', function(e) {
                    // Allow: backspace, delete, tab, escape, enter
                    if ([8, 9, 27, 13, 46].indexOf(e.keyCode) !== -1 ||
                        // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                        (e.keyCode === 65 && e.ctrlKey === true) ||
                        (e.keyCode === 67 && e.ctrlKey === true) ||
                        (e.keyCode === 86 && e.ctrlKey === true) ||
                        (e.keyCode === 88 && e.ctrlKey === true)) {
                        return;
                    }
                    // Ensure that it is a number and stop the keypress
                    if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>

</html>