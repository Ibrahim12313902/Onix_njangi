<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();

// Calculate current quarter
$current_month = date('m');
$current_year = date('Y');
$quarter = ceil($current_month / 3);

// Calculate quarter dates
if ($quarter == 1) {
    $start_month = 1;
    $end_month = 3;
    $quarter_name = 'Q1';
} elseif ($quarter == 2) {
    $start_month = 4;
    $end_month = 6;
    $quarter_name = 'Q2';
} elseif ($quarter == 3) {
    $start_month = 7;
    $end_month = 9;
    $quarter_name = 'Q3';
} else {
    $start_month = 10;
    $end_month = 12;
    $quarter_name = 'Q4';
}

// Check if record already exists for this quarter
$check_sql = "SELECT id FROM saved_records 
              WHERE record_type = 'last_quarter' 
              AND title LIKE '%$quarter_name $current_year%'";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) > 0) {
    header('Location: last_quarter.php?error=Record for ' . $quarter_name . ' ' . $current_year . ' already exists');
    exit();
}

// Calculate total contributions for the quarter
$contributions_sql = "SELECT SUM(amount) as total FROM contributions 
                      WHERE MONTH(contribution_date) BETWEEN $start_month AND $end_month 
                      AND YEAR(contribution_date) = $current_year";
$contributions_result = mysqli_query($conn, $contributions_sql);
$total = mysqli_fetch_assoc($contributions_result)['total'] ?? 0;

// Create the record
$title = $quarter_name . ' ' . $current_year . ' Summary';
$description = "Total contributions for " . $quarter_name . " " . $current_year . " (Months " . $start_month . "-" . $end_month . ")";
$record_date = $current_year . '-' . $end_month . '-30';

$insert_sql = "INSERT INTO saved_records 
               (record_type, title, amount, description, record_date, icon, color, is_active) 
               VALUES ('last_quarter', '$title', '$total', '$description', '$record_date', 
                       'fas fa-chart-line', '#17a2b8', 1)";

if (mysqli_query($conn, $insert_sql)) {
    header('Location: last_quarter.php?success=Report generated for ' . $quarter_name . ' ' . $current_year);
} else {
    header('Location: last_quarter.php?error=' . urlencode(mysqli_error($conn)));
}

closeDbConnection($conn);
?>