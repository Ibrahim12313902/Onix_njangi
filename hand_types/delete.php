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

// Check if hand type is being used
$check_sql = "SELECT COUNT(*) as count FROM hands WHERE hand_type_id = '$id'";
$check_result = mysqli_query($conn, $check_sql);
$check = mysqli_fetch_assoc($check_result);

if ($check['count'] > 0) {
    header('Location: index.php?error=Cannot delete hand type because it is used in ' . $check['count'] . ' hands');
    exit();
}

// Delete hand type
$sql = "DELETE FROM hand_types WHERE id = '$id'";

if (mysqli_query($conn, $sql)) {
    header('Location: index.php?success=Hand type deleted successfully');
} else {
    header('Location: index.php?error=' . urlencode(mysqli_error($conn)));
}

closeDbConnection($conn);
exit();