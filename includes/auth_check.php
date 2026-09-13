<?php
/**
 * APEXPLANET SOFTWARE PVT. LTD. — FULL STACK WEB DEVELOPMENT INTERNSHIP
 * Task 5: Capstone Project — E-Learning Portal (LMS)
 * File: includes/auth_check.php
 * 
 * Session Authentication, Access Control & Role Guard Middleware
 * Author: G. Praveen
 */

require_once __DIR__ . '/helpers.php';

/**
 * Check whether a user is currently authenticated in the active session.
 *
 * @return bool
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Get current authenticated user's ID.
 *
 * @return int|null
 */
function current_user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Get current authenticated user's display name.
 *
 * @return string
 */
function current_user_name(): string {
    return $_SESSION['user_name'] ?? 'User';
}

/**
 * Get current authenticated user's email address.
 *
 * @return string
 */
function current_user_email(): string {
    return $_SESSION['user_email'] ?? '';
}

/**
 * Get current authenticated user's role ('student', 'instructor', 'admin').
 *
 * @return string|null
 */
function current_user_role(): ?string {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Check if the authenticated user has administrative or instructor privileges.
 *
 * @return bool
 */
function is_admin(): bool {
    $role = current_user_role();
    return is_logged_in() && ($role === 'admin' || $role === 'instructor');
}

/**
 * Check if the authenticated user is a student.
 *
 * @return bool
 */
function is_student(): bool {
    return is_logged_in() && (current_user_role() === 'student');
}

/**
 * Require an authenticated session; redirects guests to the login page.
 *
 * @param string $redirect Path to login page
 */
function require_login(string $redirect = 'auth/login.php'): void {
    if (!is_logged_in()) {
        if (!empty($_SERVER['REQUEST_URI'])) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
        }
        set_flash('warning', 'Please sign in to access that page.');
        redirect($redirect);
    }
}

/**
 * Enforce Admin/Instructor-only access; non-admins and guests are redirected.
 *
 * @param string $redirect Path to login page
 */
function require_admin(string $redirect = 'auth/login.php'): void {
    if (!is_logged_in()) {
        if (!empty($_SERVER['REQUEST_URI'])) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
        }
        set_flash('danger', 'Administrator or Instructor authentication required.');
        redirect($redirect);
    }

    if (!is_admin()) {
        set_flash('danger', 'Access denied. You do not have permission to view that administrative resource.');
        redirect('dashboard.php');
    }
}

/**
 * Restrict pages to guests only (e.g. login, register, OTP verification).
 *
 * @param string $redirect Default path for authenticated users
 */
function require_guest(string $redirect = 'dashboard.php'): void {
    if (is_logged_in()) {
        if (is_admin()) {
            redirect('admin/index.php');
        } else {
            redirect($redirect);
        }
    }
}
