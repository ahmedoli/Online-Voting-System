<?php
// Start session only if not already started  
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $id_number = trim($_POST['id_number']);
    $id_type = trim($_POST['id_type']);

    // Validate input
    if (empty($name) || empty($phone) || empty($email) || empty($password) || empty($id_number) || empty($id_type)) {
        $message = 'All fields are required.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } elseif (strlen($phone) < 11) {
        $message = 'Please enter a valid phone number.';
        $message_type = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long.';
        $message_type = 'error';
    } elseif (($id_type == 'NID' && strlen($id_number) < 10) || ($id_type == 'Student' && strlen($id_number) < 6)) {
        $message = $id_type == 'NID' ? 'Please enter a valid NID number (at least 10 digits).' : 'Please enter a valid Student ID (at least 6 characters).';
        $message_type = 'error';
    } else {
        // Check database structure
        $has_id_number = false;
        $has_nid_number = false;
        $has_name_column = false;

        try {
            // Check for modern structure with id_number and id_type columns
            $columns_check = $conn->query("SHOW COLUMNS FROM voters LIKE 'id_number'");
            $has_id_number = $columns_check && $columns_check->num_rows > 0;

            // Fallback to old nid_number structure if needed
            if (!$has_id_number) {
                $nid_check = $conn->query("SHOW COLUMNS FROM voters LIKE 'nid_number'");
                $has_nid_number = $nid_check && $nid_check->num_rows > 0;
            }

            // Check if name column exists
            $name_check = $conn->query("SHOW COLUMNS FROM voters LIKE 'name'");
            $has_name_column = $name_check && $name_check->num_rows > 0;
        } catch (Exception $e) {
            $message = 'Database connection error. Please try again.';
            $message_type = 'error';
        }
        if ($has_id_number) {
            // New structure with id_number and id_type
            $stmt = $conn->prepare("SELECT id FROM voters WHERE email = ? OR phone = ? OR id_number = ?");
        } elseif ($has_nid_number) {
            // Old structure with nid_number
            $stmt = $conn->prepare("SELECT id FROM voters WHERE email = ? OR phone = ? OR nid_number = ?");
        } else {
            // Fallback - only check email and phone
            $stmt = $conn->prepare("SELECT id FROM voters WHERE email = ? OR phone = ?");
        }

        if (!$stmt) {
            $message = 'Database error: ' . $conn->error;
            $message_type = 'error';
        } else {
            // Bind parameters based on available columns
            if ($has_id_number || $has_nid_number) {
                $stmt->bind_param("sss", $email, $phone, $id_number);
            } else {
                $stmt->bind_param("ss", $email, $phone);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $message = 'A voter with this email, phone number, or ID already exists.';
                $message_type = 'error';
            } else {
                // Hash password for security
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Register new voter with appropriate database structure
                if ($has_id_number) {
                    // Modern structure with id_number and id_type
                    if ($has_name_column) {
                        $insert_stmt = $conn->prepare("INSERT INTO voters (name, phone, email, password, id_number, id_type) VALUES (?, ?, ?, ?, ?, ?)");
                        if ($insert_stmt) {
                            $insert_stmt->bind_param("ssssss", $name, $phone, $email, $hashed_password, $id_number, $id_type);
                        }
                    } else {
                        $insert_stmt = $conn->prepare("INSERT INTO voters (phone, email, password, id_number, id_type) VALUES (?, ?, ?, ?, ?)");
                        if ($insert_stmt) {
                            $insert_stmt->bind_param("sssss", $phone, $email, $hashed_password, $id_number, $id_type);
                        }
                    }
                } elseif ($has_nid_number) {
                    // Legacy structure with nid_number only
                    if ($has_name_column) {
                        $insert_stmt = $conn->prepare("INSERT INTO voters (name, phone, email, password, nid_number) VALUES (?, ?, ?, ?, ?)");
                        if ($insert_stmt) {
                            $insert_stmt->bind_param("sssss", $name, $phone, $email, $hashed_password, $id_number);
                        }
                    } else {
                        $insert_stmt = $conn->prepare("INSERT INTO voters (phone, email, password, nid_number) VALUES (?, ?, ?, ?)");
                        if ($insert_stmt) {
                            $insert_stmt->bind_param("ssss", $phone, $email, $hashed_password, $id_number);
                        }
                    }
                } else {
                    // Basic structure - no ID support
                    if ($has_name_column) {
                        $insert_stmt = $conn->prepare("INSERT INTO voters (name, phone, email, password) VALUES (?, ?, ?, ?)");
                        if ($insert_stmt) {
                            $insert_stmt->bind_param("ssss", $name, $phone, $email, $hashed_password);
                        }
                    } else {
                        $insert_stmt = $conn->prepare("INSERT INTO voters (phone, email, password) VALUES (?, ?, ?)");
                        if ($insert_stmt) {
                            $insert_stmt->bind_param("sss", $phone, $email, $hashed_password);
                        }
                    }
                }

                if (!$insert_stmt) {
                    $message = 'Database error occurred. Please try again.';
                    $message_type = 'error';
                } else {
                    if ($insert_stmt->execute()) {
                        if ($has_id_number || $has_nid_number) {
                            $message = 'Registration successful! You can now login with your ' . $id_type . ' (' . $id_number . ') and password.';
                        } else {
                            $message = 'Registration successful! You can now login with your phone or email and password.';
                        }
                        $message_type = 'success';
                        // Clear form data on success
                        $name = $phone = $email = $id_number = '';
                    } else {
                        $message = 'Registration failed. Please check your information and try again.';
                        $message_type = 'error';
                    }
                    $insert_stmt->close();
                }
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Registration - Online Voting System</title>
    <meta name="description" content="Register with NID or Student ID to participate in secure online elections">
    <meta name="keywords" content="voter registration, online voting, democracy, elections, NID, student ID">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --card-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            --input-focus-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        * {
            font-family: 'Inter', sans-serif;
        }

        .bg-gradient {
            background: var(--primary-gradient);
            min-height: 100vh;
        }

        .registration-container {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .form-control {
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: var(--input-focus-shadow);
            background: #fff;
            transform: translateY(-1px);
        }

        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            padding: 14px 28px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        }

        .btn-outline-primary {
            border: 2px solid #667eea;
            color: #667eea;
            border-radius: 12px;
            padding: 10px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: var(--primary-gradient);
            border-color: transparent;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 16px 20px;
            font-weight: 500;
            backdrop-filter: blur(10px);
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(79, 172, 254, 0.1), rgba(0, 242, 254, 0.1));
            color: #0369a1;
            border-left: 4px solid #0ea5e9;
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(250, 112, 154, 0.1), rgba(254, 225, 64, 0.1));
            color: #dc2626;
            border-left: 4px solid #ef4444;
        }

        .header-section {
            background: var(--primary-gradient);
            padding: 40px 30px;
            border-radius: 20px 20px 0 0;
            text-align: center;
            color: white;
        }

        .header-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            backdrop-filter: blur(10px);
        }

        .form-section {
            padding: 40px 30px;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 24px;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            z-index: 10;
        }

        .input-with-icon {
            padding-left: 48px;
        }

        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e5e7eb, transparent);
            margin: 30px 0;
        }

        .footer-links {
            padding: 20px 30px 30px;
            border-top: 1px solid #f3f4f6;
            background: rgba(248, 250, 252, 0.8);
            border-radius: 0 0 20px 20px;
        }

        @media (max-width: 768px) {
            .registration-container {
                margin: 20px;
                border-radius: 16px;
            }

            .header-section {
                padding: 30px 20px;
                border-radius: 16px 16px 0 0;
            }

            .form-section {
                padding: 30px 20px;
            }

            .footer-links {
                padding: 15px 20px 20px;
                border-radius: 0 0 16px 16px;
            }
        }

        .validation-info {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }

        .strength-indicator {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            transition: all 0.3s ease;
            width: 0%;
        }

        .form-check-custom {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .form-check-custom:hover {
            border-color: #667eea;
            background: #fff;
            transform: translateY(-1px);
        }

        .form-check-custom .form-check-input:checked~.form-check-label {
            color: #667eea;
            font-weight: 600;
        }

        .form-check-custom .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }

        .form-check-custom .form-check-input {
            margin-top: 0;
        }

        .form-check-custom:has(.form-check-input:checked) {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
            box-shadow: var(--input-focus-shadow);
        }
    </style>
</head>

<body class="bg-gradient">
    <div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center py-4">
        <div class="row w-100 justify-content-center">
            <div class="col-11 col-sm-10 col-md-8 col-lg-6 col-xl-5 col-xxl-4">
                <div class="registration-container">
                    <!-- Header Section -->
                    <div class="header-section">
                        <div class="header-icon">
                            <i class="fas fa-user-plus fa-2x"></i>
                        </div>
                        <h1 class="mb-2 fw-bold">Join Our Democracy</h1>
                        <p class="mb-0 opacity-90">Register to participate in secure online elections</p>
                    </div>

                    <!-- Form Section -->
                    <div class="form-section">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?> d-flex align-items-center mb-4">
                                <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-3 fs-5"></i>
                                <div><?= htmlspecialchars($message) ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <!-- Full Name -->
                            <div class="input-group-custom">
                                <label for="name" class="form-label">
                                    <i class="fas fa-user me-2 text-primary"></i>Full Name
                                </label>
                                <div class="position-relative">
                                    <i class="fas fa-user input-icon"></i>
                                    <input type="text" class="form-control input-with-icon" id="name" name="name"
                                        value="<?= htmlspecialchars($name ?? '') ?>"
                                        placeholder="Enter your full name" required minlength="2">
                                    <div class="invalid-feedback">Please enter your full name (minimum 2 characters)</div>
                                </div>
                            </div>

                            <!-- Phone Number -->
                            <div class="input-group-custom">
                                <label for="phone" class="form-label">
                                    <i class="fas fa-phone me-2 text-primary"></i>Phone Number
                                </label>
                                <div class="position-relative">
                                    <i class="fas fa-phone input-icon"></i>
                                    <input type="tel" class="form-control input-with-icon" id="phone" name="phone"
                                        value="<?= htmlspecialchars($phone ?? '') ?>"
                                        placeholder="01XXXXXXXXX" pattern="[0-9]{11}" required>
                                    <div class="invalid-feedback">Please enter a valid 11-digit phone number</div>
                                </div>
                                <div class="validation-info">
                                    <i class="fas fa-info-circle me-1"></i>Enter your 11-digit phone number starting with 01
                                </div>
                            </div>

                            <!-- Email Address -->
                            <div class="input-group-custom">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-2 text-primary"></i>Email Address
                                </label>
                                <div class="position-relative">
                                    <i class="fas fa-envelope input-icon"></i>
                                    <input type="email" class="form-control input-with-icon" id="email" name="email"
                                        value="<?= htmlspecialchars($email ?? '') ?>"
                                        placeholder="your.email@example.com" required>
                                    <div class="invalid-feedback">Please enter a valid email address</div>
                                </div>
                                <div class="validation-info">
                                    <i class="fas fa-info-circle me-1"></i>OTP will be sent to this email for login verification
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="input-group-custom">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2 text-primary"></i>Password
                                </label>
                                <div class="position-relative">
                                    <i class="fas fa-lock input-icon"></i>
                                    <input type="password" class="form-control input-with-icon" id="password" name="password"
                                        placeholder="Create a secure password" required minlength="6">
                                    <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-2 p-0"
                                        onclick="togglePassword()" id="passwordToggle">
                                        <i class="fas fa-eye text-muted"></i>
                                    </button>
                                    <div class="invalid-feedback">Password must be at least 6 characters long</div>
                                </div>
                                <div class="strength-indicator">
                                    <div class="strength-bar" id="strengthBar"></div>
                                </div>
                                <div class="validation-info" id="passwordHelp">
                                    <i class="fas fa-info-circle me-1"></i>Use a mix of letters, numbers, and symbols for better security
                                </div>
                            </div>

                            <!-- ID Type Selection -->
                            <div class="input-group-custom">
                                <label class="form-label">
                                    <i class="fas fa-id-badge me-2 text-primary"></i>ID Type
                                </label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="form-check form-check-custom">
                                            <input class="form-check-input" type="radio" name="id_type" id="nid_type" value="NID"
                                                <?= (!isset($id_type) || $id_type === 'NID') ? 'checked' : '' ?> required>
                                            <label class="form-check-label" for="nid_type">
                                                <i class="fas fa-id-card me-1"></i>National ID
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check form-check-custom">
                                            <input class="form-check-input" type="radio" name="id_type" id="student_type" value="Student"
                                                <?= (isset($id_type) && $id_type === 'Student') ? 'checked' : '' ?> required>
                                            <label class="form-check-label" for="student_type">
                                                <i class="fas fa-graduation-cap me-1"></i>Student ID
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ID Number -->
                            <div class="input-group-custom">
                                <label for="id_number" class="form-label">
                                    <i class="fas fa-id-card me-2 text-primary"></i>
                                    <span id="id_label">National ID Number</span>
                                </label>
                                <div class="position-relative">
                                    <i class="fas fa-id-card input-icon" id="id_icon"></i>
                                    <input type="text" class="form-control input-with-icon" id="id_number" name="id_number"
                                        value="<?= htmlspecialchars($id_number ?? '') ?>"
                                        placeholder="Enter your ID number" required>
                                    <div class="invalid-feedback" id="id_feedback">Please enter a valid ID number</div>
                                </div>
                                <div class="validation-info" id="id_help">
                                    <i class="fas fa-info-circle me-1"></i>Enter your National ID number for verification
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary w-100 py-3 mb-4">
                                <i class="fas fa-user-check me-2"></i>
                                <span>Complete Registration</span>
                            </button>
                        </form>

                        <div class="divider"></div>

                        <div class="text-center">
                            <p class="text-muted mb-3">
                                <i class="fas fa-users me-2"></i>Already have an account?
                            </p>
                            <a href="login.php" class="btn btn-outline-primary px-4 py-2">
                                <i class="fas fa-sign-in-alt me-2"></i>Login to Your Account
                            </a>
                        </div>
                    </div>

                    <!-- Footer Links -->
                    <div class="footer-links text-center">
                        <div class="row g-2">
                            <div class="col-6">
                                <a href="../index.php" class="btn btn-link text-muted text-decoration-none">
                                    <i class="fas fa-home me-1"></i>Home
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="../guest/view_results.php" class="btn btn-link text-muted text-decoration-none">
                                    <i class="fas fa-chart-bar me-1"></i>Results
                                </a>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Your data is secure and encrypted
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();

        // Password visibility toggle
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('#passwordToggle i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash text-muted';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fas fa-eye text-muted';
            }
        }

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthBar = document.getElementById('strengthBar');
            const helpText = document.getElementById('passwordHelp');

            let strength = 0;
            let feedback = '';

            if (password.length >= 6) strength += 25;
            if (password.match(/[a-z]/)) strength += 25;
            if (password.match(/[A-Z]/)) strength += 25;
            if (password.match(/[0-9]/)) strength += 15;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 10;

            if (strength < 25) {
                strengthBar.style.background = '#ef4444';
                feedback = 'Weak password - add more characters';
            } else if (strength < 50) {
                strengthBar.style.background = '#f97316';
                feedback = 'Fair password - add uppercase letters and numbers';
            } else if (strength < 75) {
                strengthBar.style.background = '#eab308';
                feedback = 'Good password - add special characters for better security';
            } else {
                strengthBar.style.background = '#22c55e';
                feedback = 'Strong password - excellent security!';
            }

            strengthBar.style.width = strength + '%';
            helpText.innerHTML = `<i class="fas fa-info-circle me-1"></i>${feedback}`;
        });

        // Phone number formatting
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) {
                value = value.slice(0, 11);
            }
            e.target.value = value;
        });

        // ID type handling
        function updateIdField() {
            const idType = document.querySelector('input[name="id_type"]:checked').value;
            const idLabel = document.getElementById('id_label');
            const idIcon = document.getElementById('id_icon');
            const idInput = document.getElementById('id_number');
            const idFeedback = document.getElementById('id_feedback');
            const idHelp = document.getElementById('id_help');

            if (idType === 'NID') {
                idLabel.textContent = 'National ID Number';
                idIcon.className = 'fas fa-id-card input-icon';
                idInput.placeholder = 'Enter your NID number';
                idInput.pattern = '[0-9]{10,17}';
                idFeedback.textContent = 'Please enter a valid NID number (10-17 digits)';
                idHelp.innerHTML = '<i class="fas fa-info-circle me-1"></i>Enter your National ID number for verification';
            } else {
                idLabel.textContent = 'Student ID Number';
                idIcon.className = 'fas fa-graduation-cap input-icon';
                idInput.placeholder = 'Enter your Student ID';
                idInput.pattern = '[A-Za-z0-9]{6,20}';
                idFeedback.textContent = 'Please enter a valid Student ID (6-20 characters)';
                idHelp.innerHTML = '<i class="fas fa-info-circle me-1"></i>Enter your Student ID for verification';
            }
        }

        // ID type change handlers
        document.querySelectorAll('input[name="id_type"]').forEach(radio => {
            radio.addEventListener('change', updateIdField);
        });

        // Initialize ID field based on default selection
        updateIdField();

        // ID number formatting
        document.getElementById('id_number').addEventListener('input', function(e) {
            const idType = document.querySelector('input[name="id_type"]:checked').value;

            if (idType === 'NID') {
                // NID: numbers only
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 17) {
                    value = value.slice(0, 17);
                }
                e.target.value = value;
            } else {
                // Student ID: alphanumeric
                let value = e.target.value.replace(/[^A-Za-z0-9]/g, '');
                if (value.length > 20) {
                    value = value.slice(0, 20);
                }
                e.target.value = value;
            }
        });

        // Smooth form animations
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });

            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>

</html>