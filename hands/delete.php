<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?error=No ID provided');
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Check if hand has contributions
$check_sql = "SELECT COUNT(*) as count FROM contributions WHERE hand_id = '$id'";
$check_result = mysqli_query($conn, $check_sql);
$check = mysqli_fetch_assoc($check_result);

if ($check['count'] > 0) {
    header('Location: index.php?error=Cannot delete hand because it has ' . $check['count'] . ' contributions');
    exit();
}

// Delete hand
$sql = "DELETE FROM hands WHERE id = '$id'";

if (mysqli_query($conn, $sql)) {
    header('Location: index.php?success=Hand deleted successfully');
} else {
    header('Location: index.php?error=' . urlencode(mysqli_error($conn)));
}

closeDbConnection($conn);
exit();