<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?error=No ID provided');
    exit();
}

$id = (int)$_GET['id'];

// Get record info for redirect
$sql = "SELECT * FROM saved_records WHERE id = '$id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header('Location: index.php?error=Record not found');
    exit();
}

$record = mysqli_fetch_assoc($result);
$record_type = $record['record_type'];

// Delete the record
$sql = "DELETE FROM saved_records WHERE id = '$id'";
if (mysqli_query($conn, $sql)) {
    // Redirect to appropriate page based on record type
    switch ($record_type) {
        case 'current_month':
            header('Location: current_month.php?success=Record deleted successfully');
            break;
        case 'last_quarter':
            header('Location: last_quarter.php?success=Record deleted successfully');
            break;
        case 'achievement':
            header('Location: achievements.php?success=Record deleted successfully');
            break;
        case 'piggie_box':
            header('Location: piggie_box.php?success=Record deleted successfully');
            break;
        default:
            header('Location: index.php?success=Record deleted successfully');
    }
} else {
    header('Location: index.php?error=Error deleting record: ' . mysqli_error($conn));
}

closeDbConnection($conn);
exit();
