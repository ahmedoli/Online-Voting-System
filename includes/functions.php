<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db_connect.php';

function sanitize($s)
{
    return htmlspecialchars(trim($s), ENT_QUOTES, 'UTF-8');
}

function isAdminLoggedIn()
{
    return !empty($_SESSION['admin_id']);
}

function requireAdmin()
{
    if (!isAdminLoggedIn()) {
        header('Location: /Online_Voting_System/admin/login.php');
        exit;
    }
}

function flash_set($key, $msg)
{
    $_SESSION['flash'][$key] = $msg;
}

function flash_get($key)
{
    if (!empty($_SESSION['flash'][$key])) {
        $m = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $m;
    }
    return null;
}

// OTP Management Functions
function generateOTP()
{
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

function storeOTP($voter_id, $otp)
{
    global $conn;
    $expires_at = date('Y-m-d H:i:s', time() + 120); // 2 minutes expiration

    // Delete existing OTPs for this voter
    $stmt = $conn->prepare("DELETE FROM voter_otps WHERE voter_id = ?");
    $stmt->bind_param("i", $voter_id);
    $stmt->execute();

    // Insert new OTP
    $stmt = $conn->prepare("INSERT INTO voter_otps (voter_id, otp_code, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $voter_id, $otp, $expires_at);
    return $stmt->execute();
}

function verifyOTP($voter_id, $otp)
{
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM voter_otps WHERE voter_id = ? AND otp_code = ? AND expires_at > NOW()");
    $stmt->bind_param("is", $voter_id, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        // Delete used OTP
        $stmt = $conn->prepare("DELETE FROM voter_otps WHERE voter_id = ?");
        $stmt->bind_param("i", $voter_id);
        $stmt->execute();
        return true;
    }
    return false;
}

function generateAndSendOTP($voter_id, $email)
{
    require_once __DIR__ . '/email_sender.php';

    $otp = generateOTP();
    if (storeOTP($voter_id, $otp)) {
        return sendOTPEmail($email, $otp);
    }
    return false;
}
