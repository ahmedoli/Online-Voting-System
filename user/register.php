<?php
if (session_status() === PHP_SESSION_NONE) session_start();
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
        $stmt = $conn->prepare("SELECT id FROM voters WHERE email = ? OR phone = ? OR id_number = ?");
        $stmt->bind_param("sss", $email, $phone, $id_number);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            $message = 'A voter with this email, phone number, or ID already exists.';
            $message_type = 'error';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = $conn->prepare("INSERT INTO voters (name, phone, email, password, id_number, id_type) VALUES (?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("ssssss", $name, $phone, $email, $hashed_password, $id_number, $id_type);

            if ($insert_stmt->execute()) {
                $_SESSION['success_message'] = 'Registration successful! You can now login with your credentials.';
                header('Location: login.php');
                exit();
            } else {
                $message = 'Registration failed. Please try again.';
                $message_type = 'error';
            }
            $insert_stmt->close();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Registration - Online Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        .registration-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px 35px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
        }

        .form-control {
            border-radius: 12px;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
        }

        .alert {
            border-radius: 12px;
        }

        .password-strength {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .id-option {
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .id-option:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .id-option.active {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }

        .id-option input[type="radio"] {
            display: none;
        }
    </style>
</head>

<body>
    <div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center py-4">
        <div class="row w-100 justify-content-center">
            <div class="col-11 col-sm-10 col-md-8 col-lg-6 col-xl-5 col-xxl-4">
                <div class="registration-container">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                        <h1 class="mb-2 fw-bold">Join Our Democracy</h1>
                        <p class="mb-0 text-muted">Register to participate in secure online elections</p>
                    </div>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?> d-flex align-items-center mb-4">
                            <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-3"></i>
                            <div><?= htmlspecialchars($message) ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Full Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Enter your full name" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" placeholder="01XXXXXXXXX" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email Address</label>
                                <input type="email" name="email" class="form-control" placeholder="your.email@example.com" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Create a strong password" required>
                            <div class="password-strength">
                                <div class="password-strength-bar" id="strength-bar"></div>
                            </div>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Identity Verification</label>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="id-option" for="nid">
                                        <input type="radio" name="id_type" value="NID" id="nid" checked>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-id-card text-primary me-3"></i>
                                            <div>
                                                <div class="fw-semibold">National ID</div>
                                                <small class="text-muted">NID Card Number</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <label class="id-option" for="student">
                                        <input type="radio" name="id_type" value="Student" id="student">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-graduation-cap text-primary me-3"></i>
                                            <div>
                                                <div class="fw-semibold">Student ID</div>
                                                <small class="text-muted">University/College ID</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="mt-3">
                                <input type="text" name="id_number" id="id_number" class="form-control" placeholder="Enter your NID number" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-user-plus me-2"></i>Register Now
                        </button>

                        <div class="text-center">
                            <p class="text-muted mb-2">Already have an account?</p>
                            <a href="login.php" class="btn btn-outline-primary px-4">
                                <i class="fas fa-sign-in-alt me-2"></i>Login Here
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strength-bar');
            let strength = 0;

            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;

            const colors = ['#ef4444', '#f97316', '#eab308', '#22c55e'];
            const widths = ['25%', '50%', '75%', '100%'];

            strengthBar.style.width = strength > 0 ? widths[strength - 1] : '0%';
            strengthBar.style.background = strength > 0 ? colors[strength - 1] : '#e5e7eb';
        });

        // ID type selection
        document.querySelectorAll('input[name="id_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.id-option').forEach(opt => opt.classList.remove('active'));
                this.parentElement.classList.add('active');

                const placeholder = this.value === 'NID' ? 'Enter your NID number' : 'Enter your Student ID';
                document.getElementById('id_number').placeholder = placeholder;
            });
        });

        // Initialize first option as active
        document.querySelector('.id-option').classList.add('active');
    </script>
</body>

</html>