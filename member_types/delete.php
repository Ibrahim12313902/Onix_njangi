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

// Check if member type is being used
$check_sql = "SELECT COUNT(*) as count FROM members WHERE member_type_id = '$id'";
$check_result = mysqli_query($conn, $check_sql);
$check = mysqli_fetch_assoc($check_result);

if ($check['count'] > 0) {
    // Member type is in use, cannot delete
    header('Location: index.php?error=Cannot delete member type because it is assigned to ' . $check['count'] . ' members');
    exit();
}

// Delete member type
$sql = "DELETE FROM member_types WHERE id = '$id'";

if (mysqli_query($conn, $sql)) {
    header('Location: index.php?success=Member type deleted successfully');
} else {
    header('Location: index.php?error=' . urlencode(mysqli_error($conn)));
}

closeDbConnection($conn);
exit();