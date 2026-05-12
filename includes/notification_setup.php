<?php
// Common notification setup for admin pages
require_once __DIR__ . '/../config/database.php';
$conn = getDbConnection();

$sql = "SELECT COUNT(*) as count FROM hand_requests WHERE status = 'pending'";
$pending_hands = mysqli_fetch_assoc(mysqli_query($conn, $sql))['count'];

$sql = "SELECT COUNT(*) as count FROM group_messages WHERE is_admin_message = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$new_chats = mysqli_fetch_assoc(mysqli_query($conn, $sql))['count'];

$total_notifications = $pending_hands + $new_chats;

closeDbConnection($conn);
?>
