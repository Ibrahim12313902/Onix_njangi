<?php
// Member portal session and auth functions

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function isMemberLoggedIn() {
    return isset($_SESSION['member_id']) && !empty($_SESSION['member_id']);
}

function requireMemberLogin() {
    if (!isMemberLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function getMemberId() {
    return $_SESSION['member_id'] ?? 0;
}

function getMemberName() {
    return $_SESSION['member_name'] ?? 'Member';
}

if (!function_exists('formatCurrency')) {
    function formatCurrency($amount) {
        return number_format($amount, 0, '.', ',') . ' FCFA';
    }
}

if (!function_exists('formatDate')) {
    function formatDate($date) {
        if (empty($date)) return '-';
        return date('d/m/Y', strtotime($date));
    }
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('d/m/Y', $time);
}
?>