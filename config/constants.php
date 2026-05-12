<?php
// Session start
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Site constants
define('SITE_NAME', 'ONIX - Njangi Management System');
define('SITE_URL', 'http://localhost/onix_njangi/');
define('CURRENCY', 'FCFA');
define('CURRENCY_SYMBOL', 'FCFA');

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