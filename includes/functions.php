<?php

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
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}


function generateVoteHash($voter_id, $election_id, $candidate_id, $timestamp)
{

    $salt = 'SECURE_VOTE_SALT_' . date('Y-m-d');
    $data = $voter_id . '|' . $election_id . '|' . $candidate_id . '|' . $timestamp . '|' . $salt;
    return hash('sha256', $data);
}

/**
 * Log secure vote entry with hash
 * Backlog 6: Tamper-proof vote logging
 */
function logSecureVote($voter_id, $election_id, $candidate_id)
{
    global $mysqli, $conn;


    $db = $mysqli ? $mysqli : $conn;

    if (!$db) {
        error_log("logSecureVote: No database connection available");
        return false;
    }

    $timestamp = date('Y-m-d H:i:s');
    $vote_hash = generateVoteHash($voter_id, $election_id, $candidate_id, $timestamp);

    error_log("logSecureVote: Attempting to log vote - voter_id=$voter_id, election_id=$election_id, candidate_id=$candidate_id, hash=$vote_hash");


    $stmt = $db->prepare("INSERT INTO vote_logs (voter_id, election_id, candidate_id, vote_hash, voted_at) VALUES (?, ?, ?, ?, ?)");

    if (!$stmt) {
        error_log("logSecureVote: MySQL prepare failed - " . $db->error);

        $check_column = $db->query("SHOW COLUMNS FROM vote_logs LIKE 'vote_hash'");
        if (!$check_column || $check_column->num_rows === 0) {
            error_log("logSecureVote: vote_hash column missing, attempting to add it");
            $add_column = $db->query("ALTER TABLE vote_logs ADD COLUMN vote_hash VARCHAR(64) NOT NULL DEFAULT ''");
            if ($add_column) {
                error_log("logSecureVote: vote_hash column added successfully");

                $stmt = $db->prepare("INSERT INTO vote_logs (voter_id, election_id, candidate_id, vote_hash, voted_at) VALUES (?, ?, ?, ?, ?)");
            }
        }

        if (!$stmt) {
            return false;
        }
    }

    $stmt->bind_param("iiiss", $voter_id, $election_id, $candidate_id, $vote_hash, $timestamp);
    $result = $stmt->execute();

    if (!$result) {
        error_log("logSecureVote: MySQL execute failed - " . $stmt->error);
        error_log("logSecureVote: Query was: INSERT INTO vote_logs (voter_id, election_id, candidate_id, vote_hash, voted_at) VALUES ($voter_id, $election_id, $candidate_id, '$vote_hash', '$timestamp')");
    } else {
        error_log("logSecureVote: Vote logged successfully with ID " . $db->insert_id);
    }

    $stmt->close();
    return $result;
}

/**
 * Verify vote integrity using hash
 * Backlog 6: Tamper detection
 */
function verifyVoteIntegrity($vote_log_id)
{
    global $mysqli;

    $stmt = $mysqli->prepare("SELECT voter_id, election_id, candidate_id, vote_hash, voted_at FROM vote_logs WHERE id = ?");

    if (!$stmt) {
        error_log("MySQL prepare failed: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param("i", $vote_log_id);

    if (!$stmt->execute()) {
        error_log("MySQL execute failed: " . $stmt->error);
        $stmt->close();
        return false;
    }

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        return false;
    }

    $vote = $result->fetch_assoc();
    $expected_hash = generateVoteHash(
        $vote['voter_id'],
        $vote['election_id'],
        $vote['candidate_id'],
        $vote['voted_at']
    );

    $stmt->close();
    return $vote['vote_hash'] === $expected_hash;
}

/**
 * Get secure vote statistics
 * Backlog 6: Security reporting
 */
function getSecureVoteStats()
{
    global $mysqli;

    $query = "
        SELECT 
            COUNT(*) as total_votes,
            COUNT(DISTINCT voter_id) as unique_voters,
            COUNT(CASE WHEN vote_hash != '' THEN 1 END) as secured_votes,
            COUNT(CASE WHEN vote_hash = '' THEN 1 END) as unsecured_votes
        FROM vote_logs
    ";

    $result = $mysqli->query($query);
    return $result ? $result->fetch_assoc() : [];
} // OTP Management Functions
function generateOTP()
{
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

function storeOTP($voter_id, $otp)
{
    global $conn;
    $expires_at = date('Y-m-d H:i:s', time() + 120); // 2 minutes expiration


    $otp = trim($otp);


    $stmt = $conn->prepare("DELETE FROM voter_otps WHERE voter_id = ?");
    if (!$stmt) {
        error_log("MySQL prepare failed: " . $conn->error);
        return false;
    }
    $stmt->bind_param("i", $voter_id);
    $stmt->execute();
    $stmt->close();


    $stmt = $conn->prepare("INSERT INTO voter_otps (voter_id, otp_code, expires_at) VALUES (?, ?, ?)");
    if (!$stmt) {
        error_log("MySQL prepare failed: " . $conn->error);
        return false;
    }
    $stmt->bind_param("iss", $voter_id, $otp, $expires_at);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}
function verifyOTP($voter_id, $otp)
{
    global $conn;


    $voter_id = (int)$voter_id;
    $otp = trim($otp);


    error_log("OTP Verification: voter_id=$voter_id, otp='$otp'");


    $debug_stmt = $conn->prepare("SELECT otp_code, expires_at FROM voter_otps WHERE voter_id = ?");
    if (!$debug_stmt) {
        error_log("MySQL prepare failed: " . $conn->error);
        return false;
    }
    $debug_stmt->bind_param("i", $voter_id);
    $debug_stmt->execute();
    $debug_result = $debug_stmt->get_result();

    while ($row = $debug_result->fetch_assoc()) {
        $expired = (strtotime($row['expires_at']) <= time()) ? "EXPIRED" : "VALID";
        error_log("Found OTP: '{$row['otp_code']}', expires: {$row['expires_at']} ($expired)");
    }
    $debug_stmt->close();


    $stmt = $conn->prepare("SELECT id, expires_at FROM voter_otps WHERE voter_id = ? AND otp_code = ?");
    if (!$stmt) {
        error_log("MySQL prepare failed: " . $conn->error);
        return false;
    }
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

            $delete_stmt = $conn->prepare("DELETE FROM voter_otps WHERE voter_id = ?");
            if ($delete_stmt) {
                $delete_stmt->bind_param("i", $voter_id);
                $delete_stmt->execute();
                $delete_stmt->close();
            }
            $stmt->close();
            return true;
        } else {
            error_log("OTP Verification FAILED - OTP expired");
            $stmt->close();
            return false;
        }
    } else {
        error_log("OTP Verification FAILED - no matching voter/OTP combination found");
        $stmt->close();
        return false;
    }
}
function generateAndSendOTP($voter_id, $email)
{
    $otp = generateOTP();
    if (storeOTP($voter_id, $otp)) {

        require_once __DIR__ . '/email_sender_fixed.php';
        return sendOTPEmail($email, $otp);
    }
    return false;
}
