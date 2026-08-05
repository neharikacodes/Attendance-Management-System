<?php
/**
 * logout.php — Secure Logout
 * Attendance Management System
 */

require_once 'includes/auth.php';

// Destroy session completely
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

// Redirect to login with message
session_start();
$_SESSION['flash'] = ['type' => 'info', 'message' => 'You have been logged out successfully.'];
session_write_close();

header('Location: index.php');
exit;