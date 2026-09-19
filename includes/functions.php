<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Helper Utilities & Formatting Functions
 */

/**
 * Dynamic BASE_URL calculation for localhost XAMPP & production environment
 */
if (!defined('BASE_URL')) {
    $doc_root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $proj_root = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    
    $base = '';
    if (!empty($doc_root) && !empty($proj_root) && strpos(strtolower($proj_root), strtolower($doc_root)) === 0) {
        $base = substr($proj_root, strlen($doc_root));
    }
    define('BASE_URL', rtrim(str_replace('\\', '/', $base), '/'));
}

/**
 * Helper to generate relative URLs prefixed with BASE_URL
 */
function url($path = '') {
    $path = '/' . ltrim($path, '/');
    return BASE_URL . $path;
}

/**
 * Helper to perform HTTP redirects using BASE_URL
 */
function redirect($path) {
    header("Location: " . url($path));
    exit();
}

/**
 * Format currency in INR (₹)
 */
function format_currency($amount) {
    return '₹' . number_format((float)$amount, 2, '.', ',');
}

/**
 * Sanitize string input
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate HTML status badge for vehicle or booking status
 */
function get_status_badge($status) {
    $status = strtolower(trim($status));
    $badge_class = 'badge-secondary';
    $label = ucfirst($status);

    switch ($status) {
        case 'available':
        case 'completed':
        case 'active':
        case 'approved':
        case 'resolved':
            $badge_class = 'badge-success';
            break;
        case 'rented':
        case 'reserved':
        case 'confirmed':
        case 'in_progress':
            $badge_class = 'badge-info';
            break;
        case 'maintenance':
        case 'pending':
        case 'scheduled':
        case 'open':
            $badge_class = 'badge-warning';
            break;
        case 'cancelled':
        case 'rejected':
        case 'failed':
        case 'inactive':
        case 'expired':
            $badge_class = 'badge-danger';
            break;
    }

    return "<span class=\"badge {$badge_class}\">" . htmlspecialchars($label) . "</span>";
}

/**
 * Calculate rental days between pickup and return dates
 */
function calculate_days($pickup, $return) {
    $pickup_dt = new DateTime($pickup);
    $return_dt = new DateTime($return);
    $interval = $pickup_dt->diff($return_dt);
    $days = $interval->days;
    return ($days < 1) ? 1 : $days;
}

/**
 * Set flash alert notification
 */
function set_flash_message($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Display flash alerts if set
 */
function display_flash_messages() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $types = ['success', 'error', 'info', 'warning'];
    $html = '';

    foreach ($types as $type) {
        $key = 'flash_' . $type;
        if (isset($_SESSION[$key]) && !empty($_SESSION[$key])) {
            $msg = htmlspecialchars($_SESSION[$key]);
            $html .= "<div class=\"alert alert-{$type}\">{$msg}</div>";
            unset($_SESSION[$key]);
        }
    }

    return $html;
}
