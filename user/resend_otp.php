<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is in OTP step
if (!isset($_SESSION['temp_voter_id']) || !isset($_SESSION['temp_voter_email'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid session. Please start login process again.'
    ]);
    exit();
}

$voter_id = $_SESSION['temp_voter_id'];
$email = $_SESSION['temp_voter_email'];

try {
    // Generate and send new OTP
    if (generateAndSendOTP($voter_id, $email)) {
        echo json_encode([
            'success' => true,
            'message' => 'OTP has been resent to your email address.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send OTP. Please try again.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}
