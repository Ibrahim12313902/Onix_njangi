<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();

$current_month = date('m');
$current_year = date('Y');
$month_name = date('F Y');

// Check if record already exists for this month
$check_sql = "SELECT id FROM saved_records 
              WHERE record_type = 'current_month' 
              AND MONTH(record_date) = $current_month 
              AND YEAR(record_date) = $current_year";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) > 0) {
    header('Location: current_month.php?error=Record for ' . $month_name . ' already exists');
    exit();
}

// Calculate total contributions for the month from hands
$contributions_sql = "SELECT SUM(amount) as total FROM contributions 
                      WHERE MONTH(contribution_date) = $current_month 
                      AND YEAR(contribution_date) = $current_year";
$contributions_result = mysqli_query($conn, $contributions_sql);
$total = mysqli_fetch_assoc($contributions_result)['total'] ?? 0;

// Create the record
$title = $month_name . ' Contributions';
$description = "Total contributions for " . $month_name;
$record_date = $current_year . '-' . $current_month . '-01';

$insert_sql = "INSERT INTO saved_records 
               (record_type, title, amount, description, record_date, icon, color, is_active) 
               VALUES ('current_month', '$title', '$total', '$description', '$record_date', 
                       'fas fa-calendar-alt', '#28a745', 1)";

if (mysqli_query($conn, $insert_sql)) {
    header('Location: current_month.php?success=Report generated for ' . $month_name);
} else {
    header('Location: current_month.php?error=' . urlencode(mysqli_error($conn)));
}

closeDbConnection($conn);
?>