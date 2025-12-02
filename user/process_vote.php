<?php
session_start();
require_once "../includes/db_connect.php";
require_once "../includes/functions.php";

if (!isset($_SESSION['voter_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['voter_id'];

if (!isset($_POST['election_id'])) {
    header("Location: dashboard.php?error=missing_election");
    exit;
}
$election_id = (int)$_POST['election_id'];

$conn->autocommit(false);

try {
    $check = $conn->prepare("SELECT 1 FROM vote_logs WHERE voter_id = ? AND election_id = ? FOR UPDATE");
    $check->bind_param("ii", $user_id, $election_id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $conn->rollback();
        header("Location: dashboard.php?already=1");
        exit;
    }
    $check->close();

<<<<<<< HEAD




    // Fetch only positions that actually have candidates
=======
    error_log('Vote POST: ' . json_encode($_POST));

    $pos_stmt = $conn->prepare("SELECT position FROM candidates WHERE election_id = ? GROUP BY position HAVING COUNT(*) > 0");
    $pos_stmt->bind_param("i", $election_id);
    $pos_stmt->execute();
    $pos_result = $pos_stmt->get_result();
    $positions = [];
    $slug_to_position = [];
    function slugify($text)
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '_', $text);
        return trim($text, '_');
    }
    while ($row = $pos_result->fetch_assoc()) {
        $pos = trim($row['position']);
        $slug = slugify($pos);
        $positions[] = $slug;
        $slug_to_position[$slug] = $pos;
    }
    $pos_stmt->close();

<<<<<<< HEAD
    // Check all positions with candidates are present in POST (using slugs)
    foreach ($positions as $slug) {
        if (!isset($_POST[$slug]) || $_POST[$slug] === '' || !is_numeric($_POST[$slug])) {
            $conn->rollback();

            header("Location: dashboard.php?missing=1");
            exit;
        }
        // Validate candidate exists and belongs to this election
=======
    foreach ($positions as $slug) {
        if (!isset($_POST[$slug]) || $_POST[$slug] === '' || !is_numeric($_POST[$slug])) {
            $conn->rollback();
            error_log("Missing or invalid vote for position: $slug (available POST keys: " . implode(', ', array_keys($_POST)) . ")");
            header("Location: dashboard.php?missing=1");
            exit;
        }
        $validate_stmt = $conn->prepare("SELECT 1 FROM candidates WHERE id = ? AND election_id = ? AND position = ?");
        $validate_stmt->bind_param("iis", $_POST[$slug], $election_id, $slug_to_position[$slug]);
        $validate_stmt->execute();
        if ($validate_stmt->get_result()->num_rows === 0) {
            $conn->rollback();
<<<<<<< HEAD

=======
            error_log("Invalid candidate selection: candidate_id={$_POST[$slug]}, election_id=$election_id, position={$slug_to_position[$slug]}");
            header("Location: dashboard.php?error=invalid_candidate");
            exit;
        }
        $validate_stmt->close();
    }

<<<<<<< HEAD
    // Insert votes securely, catch DB errors
=======
    $success = true;
    $error_detail = '';
    foreach ($positions as $slug) {
        $candidate_id = (int)$_POST[$slug];
        $pos = $slug_to_position[$slug];
        if (!logSecureVote($user_id, $election_id, $candidate_id, $pos)) {
            $success = false;
            $error_detail .= "Failed to log vote for position: $pos; ";
        }
    }

    if ($success) {
<<<<<<< HEAD
        // Commit the transaction
        $conn->commit();
        header("Location: dashboard.php?success=1");
    } else {
        // Rollback on error
        $conn->rollback();

        header("Location: dashboard.php?error=vote_fail");
    }
} catch (Exception $e) {
    // Rollback on any exception
    $conn->rollback();

    header("Location: dashboard.php?error=transaction_fail");
} finally {
    // Restore autocommit
=======
        $conn->commit();
        header("Location: dashboard.php?success=1");
    } else {
        $conn->rollback();
        error_log('Vote error: ' . $error_detail);
        header("Location: dashboard.php?error=vote_fail");
    }
} catch (Exception $e) {
    $conn->rollback();
    error_log('Vote transaction error: ' . $e->getMessage());
    header("Location: dashboard.php?error=transaction_fail");
} finally {
    $conn->autocommit(true);
}
exit;
