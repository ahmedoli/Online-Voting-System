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

    // Ensure OTP is clean (remove any whitespace)
    $otp = trim($otp);

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

    // Clean input
    $voter_id = (int)$voter_id;
    $otp = trim($otp);

    // Verify the OTP with debugging
    error_log("OTP Verification: voter_id=$voter_id, otp='$otp'");

    // First check what OTPs exist for this voter
    $debug_stmt = $conn->prepare("SELECT otp_code, expires_at FROM voter_otps WHERE voter_id = ?");
    $debug_stmt->bind_param("i", $voter_id);
    $debug_stmt->execute();
    $debug_result = $debug_stmt->get_result();

    while ($row = $debug_result->fetch_assoc()) {
        $expired = (strtotime($row['expires_at']) <= time()) ? "EXPIRED" : "VALID";
        error_log("Found OTP: '{$row['otp_code']}', expires: {$row['expires_at']} ($expired)");
    }

    // Get OTP record for this voter and code
    $stmt = $conn->prepare("SELECT id, expires_at FROM voter_otps WHERE voter_id = ? AND otp_code = ?");
    $stmt->bind_param("is", $voter_id, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $expires_timestamp = strtotime($row['expires_at']);
        $current_timestamp = time();

        error_log("OTP found - Expires: {$row['expires_at']} (" . date('Y-m-d H:i:s', $expires_timestamp) . ")");
        error_log("Current time: " . date('Y-m-d H:i:s', $current_timestamp));

        if ($expires_timestamp > $current_timestamp) {
            error_log("OTP Verification SUCCESS - Valid and not expired");
            // Delete used OTP
            $delete_stmt = $conn->prepare("DELETE FROM voter_otps WHERE voter_id = ?");
            $delete_stmt->bind_param("i", $voter_id);
            $delete_stmt->execute();
            return true;
        } else {
            error_log("OTP Verification FAILED - OTP expired");
            return false;
        }
    } else {
        error_log("OTP Verification FAILED - no matching voter/OTP combination found");
        return false;
    }
}
function generateAndSendOTP($voter_id, $email)
{
    $otp = generateOTP();
    if (storeOTP($voter_id, $otp)) {
        // Use fixed email sender that handles both PHPMailer and simple mail
        require_once __DIR__ . '/email_sender_fixed.php';
        return sendOTPEmail($email, $otp);
    }
    return false;
}
