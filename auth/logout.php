<?php
/**
 * APEXPLANET SOFTWARE PVT. LTD. — FULL STACK WEB DEVELOPMENT INTERNSHIP
 * Task 5: Capstone Project — E-Learning Portal (LMS)
 * File: auth/logout.php
 * 
 * Secure Session Termination & Logout Handling
 * Author: G. Praveen
 */

require_once __DIR__ . '/../includes/helpers.php';

// Unset all session array data
$_SESSION = [];

// Clear session cookie from client
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Terminate active session
session_destroy();

// Start fresh session to pass the logout notification flash
session_start();
set_flash('info', 'You have been signed out successfully. See you again soon!');

redirect('auth/login.php');
