<?php

require_once __DIR__ . '/error_handler.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', 3600);
    session_start();

    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }
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
        header('Location: ' . getBaseUrl() . '/admin/login.php');
        exit;
    }
}

function getBaseUrl()
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $path = dirname($script);

    $basePath = preg_replace('#/(admin|user|includes|guest)$#', '', $path);

    return $protocol . $host . $basePath;
}

function getAssetUrl($path)
{
    return getBaseUrl() . '/' . ltrim($path, '/');
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

function logSecureVote($voter_id, $election_id, $candidate_id, $position)
{
    global $mysqli, $conn;
    $db = $mysqli ? $mysqli : $conn;
    if (!$db) {
        return false;
    }

    $timestamp = date('Y-m-d H:i:s');
    $vote_hash = generateVoteHash($voter_id, $election_id, $candidate_id, $timestamp);
<<<<<<< HEAD
=======
    error_log("logSecureVote: Attempting to log vote - voter_id=$voter_id, election_id=$election_id, candidate_id=$candidate_id, position=$position, hash=$vote_hash");

    $check_stmt = $db->prepare("SELECT 1 FROM vote_logs WHERE voter_id = ? AND election_id = ? AND position = ?");
    if ($check_stmt) {
        $check_stmt->bind_param("iis", $voter_id, $election_id, $position);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) {
<<<<<<< HEAD
=======
            error_log("logSecureVote: Duplicate vote attempt detected - voter already voted for this position");
            $check_stmt->close();
            return false;
        }
        $check_stmt->close();
    }

    $stmt = $db->prepare("INSERT INTO vote_logs (voter_id, election_id, candidate_id, position, vote_hash, voted_at) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
<<<<<<< HEAD
=======
        error_log("logSecureVote: MySQL prepare failed - " . $db->error);
        $check_column = $db->query("SHOW COLUMNS FROM vote_logs LIKE 'position'");
        if (!$check_column || $check_column->num_rows === 0) {
            $add_column = $db->query("ALTER TABLE vote_logs ADD COLUMN position VARCHAR(100) NOT NULL DEFAULT ''");
            if ($add_column) {
<<<<<<< HEAD
=======
                error_log("logSecureVote: position column added successfully");
                $stmt = $db->prepare("INSERT INTO vote_logs (voter_id, election_id, candidate_id, position, vote_hash, voted_at) VALUES (?, ?, ?, ?, ?, ?)");
            }
        }
        if (!$stmt) {
            return false;
        }
    }
    $stmt->bind_param("iiisss", $voter_id, $election_id, $candidate_id, $position, $vote_hash, $timestamp);
    $result = $stmt->execute();
    if (!$result) {
<<<<<<< HEAD
        $stmt->close();
        return false;
    } else {
=======
        error_log("logSecureVote: MySQL execute failed - " . $stmt->error);
        error_log("logSecureVote: Query was: INSERT INTO vote_logs (voter_id, election_id, candidate_id, position, vote_hash, voted_at) VALUES ($voter_id, $election_id, $candidate_id, '$position', '$vote_hash', '$timestamp')");
        $stmt->close();
        return false;
    } else {
        error_log("logSecureVote: Vote logged successfully with ID " . $db->insert_id);

        $update_stmt = $db->prepare("UPDATE candidates SET votes = votes + 1 WHERE id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("i", $candidate_id);
            $update_result = $update_stmt->execute();
<<<<<<< HEAD
            // Vote count updated
            $update_stmt->close();
=======
            if (!$update_result) {
                error_log("logSecureVote: Failed to update candidate vote count - " . $update_stmt->error);
            } else {
                error_log("logSecureVote: Candidate vote count updated successfully for candidate_id=$candidate_id");
            }
            $update_stmt->close();
        } else {
            error_log("logSecureVote: Failed to prepare candidate update query - " . $db->error);
        }
    }
    $stmt->close();
    return $result;
}

function verifyVoteIntegrity($vote_log_id)
{
    global $mysqli;

    $stmt = $mysqli->prepare("SELECT voter_id, election_id, candidate_id, vote_hash, voted_at FROM vote_logs WHERE id = ?");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("i", $vote_log_id);

    if (!$stmt->execute()) {
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
}
function generateOTP()
{
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

function storeOTP($voter_id, $otp)
{
    global $conn;
    $expires_at = date('Y-m-d H:i:s', time() + 120);


    $otp = trim($otp);


    $stmt = $conn->prepare("DELETE FROM voter_otps WHERE voter_id = ?");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("i", $voter_id);
    $stmt->execute();
    $stmt->close();


    $stmt = $conn->prepare("INSERT INTO voter_otps (voter_id, otp_code, expires_at) VALUES (?, ?, ?)");
    if (!$stmt) {
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


<<<<<<< HEAD
    // Get all OTPs for this voter to prevent timing attacks
=======
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


    $stmt = $conn->prepare("SELECT id, otp_code, expires_at FROM voter_otps WHERE voter_id = ?");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("i", $voter_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $valid_otp_found = false;
    $current_timestamp = time();

    while ($row = $result->fetch_assoc()) {
        $expires_timestamp = strtotime($row['expires_at']);

        if (hash_equals($row['otp_code'], $otp) && $expires_timestamp > $current_timestamp) {
            $valid_otp_found = true;
<<<<<<< HEAD
=======
            error_log("OTP Verification SUCCESS - Valid and not expired");
            break;
        }
    }
    $stmt->close();

    if ($valid_otp_found) {
        $delete_stmt = $conn->prepare("DELETE FROM voter_otps WHERE voter_id = ?");
        if ($delete_stmt) {
            $delete_stmt->bind_param("i", $voter_id);
            $delete_stmt->execute();
            $delete_stmt->close();
        }
        return true;
    } else {
<<<<<<< HEAD
=======
        error_log("OTP Verification FAILED - no matching voter/OTP combination found or expired");
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

function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getCSRFField()
{
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone)
{
<<<<<<< HEAD
    // Remove all non-digits and check length
=======
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) >= 10 && strlen($phone) <= 15;
}

function validateName($name)
{
    $name = trim($name);
    return !empty($name) && strlen($name) >= 2 && strlen($name) <= 100 &&
        preg_match('/^[a-zA-Z\s\-\.\']+$/', $name);
}

function validatePassword($password)
{
    return !empty($password) && strlen($password) >= 6;
}

function validateIdNumber($id_number, $id_type)
{
    $id_number = preg_replace('/[^a-zA-Z0-9]/', '', $id_number);
    if ($id_type === 'NID') {
        return strlen($id_number) >= 10 && strlen($id_number) <= 20 &&
            preg_match('/^[0-9]+$/', $id_number);
    } else if ($id_type === 'Student') {
        return strlen($id_number) >= 6 && strlen($id_number) <= 20;
    }
    return false;
}
