<?php
// Session start
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Site constants - with defined() check to prevent double-include errors
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'ONIX - Njangi Management System');
}
if (!defined('SITE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    if (strpos($dir, '/config') !== false) {
        $dir = dirname($dir);
    }
    define('SITE_URL', $protocol . $host . $dir . '/');
}
if (!defined('CURRENCY')) {
    define('CURRENCY', 'FCFA');
}
if (!defined('CURRENCY_SYMBOL')) {
    define('CURRENCY_SYMBOL', 'FCFA');
}

// Admin session check
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}

// Format currency (FCFA)
function formatCurrency($amount) {
    return number_format($amount, 0, '.', ',') . ' ' . CURRENCY_SYMBOL;
}

// Format date
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}
?>
