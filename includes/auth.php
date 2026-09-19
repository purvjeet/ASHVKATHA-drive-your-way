<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Authentication & Session Management Helper
 */
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged in user details array or null
 */
function get_logged_user() {
    if (!is_logged_in()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'] ?? 0,
        'full_name' => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'customer',
        'phone' => $_SESSION['user_phone'] ?? '',
    ];
}

/**
 * Check if the logged in user is an Admin or Fleet Manager
 */
function is_admin() {
    if (!is_logged_in()) return false;
    $role = $_SESSION['user_role'] ?? '';
    return in_array($role, ['admin', 'manager', 'staff']);
}

/**
 * Protect customer pages - redirect to login if not authenticated
 */
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = "Please log in to access this page.";
        redirect('/login.php');
    }
}

/**
 * Protect admin pages - redirect to login or dashboard if unauthorized
 */
function require_admin() {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = "Admin authentication required.";
        redirect('/login.php');
    }
    if (!is_admin()) {
        $_SESSION['flash_error'] = "Access denied. Admin privileges required.";
        redirect('/customer/dashboard.php');
    }
}
