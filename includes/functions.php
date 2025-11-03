<?php
session_start();
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
