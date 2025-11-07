<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_destroy();
header('Location: ../index.php');
exit();
