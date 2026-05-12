<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = floatval($_POST['amount']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $deposit_date = mysqli_real_escape_string($conn, $_POST['deposit_date']);
    
    // Get current balance
    $total_deposits_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM piggie_box_transactions 
                            WHERE transaction_type IN ('DEPOSIT', 'INTEREST')";
    $total_deposits_result = mysqli_query($conn, $total_deposits_sql);
    $total_deposits = mysqli_fetch_assoc($total_deposits_result)['total'];
    
    $total_withdrawals_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM piggie_box_transactions 
                               WHERE transaction_type = 'WITHDRAWAL'";
    $total_withdrawals_result = mysqli_query($conn, $total_withdrawals_sql);
    $total_withdrawals = mysqli_fetch_assoc($total_withdrawals_result)['total'];
    
    $current_balance = $total_deposits - $total_withdrawals;
    $new_balance = $current_balance + $amount;
    
    // Insert transaction
    $sql = "INSERT INTO piggie_box_transactions 
            (transaction_type, amount, description, transaction_date, balance_after, recorded_by) 
            VALUES ('DEPOSIT', '$amount', '$description', '$deposit_date', '$new_balance', '" . $_SESSION['admin_id'] . "')";
    
    if (mysqli_query($conn, $sql)) {
        // Update or create saved record
        $check_sql = "SELECT id FROM saved_records WHERE record_type = 'piggie_box' AND is_active = 1 ORDER BY id DESC LIMIT 1";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $record = mysqli_fetch_assoc($check_result);
            $update_sql = "UPDATE saved_records SET amount = amount + $amount WHERE id = " . $record['id'];
            mysqli_query($conn, $update_sql);
        }
        
        header('Location: piggie_box.php?success=Deposit of ' . formatCurrency($amount) . ' processed successfully');
    } else {
        header('Location: piggie_box.php?error=' . urlencode(mysqli_error($conn)));
    }
} else {
    header('Location: piggie_box.php');
}

closeDbConnection($conn);
?>