<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/constants.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

$conn = getDbConnection();

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $amount = floatval($_POST['amount']);
    $transaction_date = mysqli_real_escape_string($conn, $_POST['transaction_date']);
    $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
    $reference_number = mysqli_real_escape_string($conn, $_POST['reference_number'] ?? '');
    
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
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        if ($action == 'deposit') {
            $new_balance = $current_balance + $amount;
            
            $sql = "INSERT INTO piggie_box_transactions 
                    (transaction_type, amount, description, transaction_date, balance_after, reference_number, recorded_by) 
                    VALUES ('DEPOSIT', '$amount', '$description', '$transaction_date', '$new_balance', '$reference_number', '" . $_SESSION['admin_id'] . "')";
            
            if (!mysqli_query($conn, $sql)) {
                throw new Exception('Error processing deposit: ' . mysqli_error($conn));
            }
            
            $success = 'Deposit processed successfully';
            
        } elseif ($action == 'withdrawal') {
            if ($amount > $current_balance) {
                throw new Exception('Insufficient balance! Available: ' . formatCurrency($current_balance));
            }
            
            $new_balance = $current_balance - $amount;
            
            $sql = "INSERT INTO piggie_box_transactions 
                    (transaction_type, amount, description, transaction_date, balance_after, reference_number, recorded_by) 
                    VALUES ('WITHDRAWAL', '$amount', '$description', '$transaction_date', '$new_balance', '$reference_number', '" . $_SESSION['admin_id'] . "')";
            
            if (!mysqli_query($conn, $sql)) {
                throw new Exception('Error processing withdrawal: ' . mysqli_error($conn));
            }
            
            $success = 'Withdrawal processed successfully';
            
        } elseif ($action == 'interest') {
            $new_balance = $current_balance + $amount;
            
            $sql = "INSERT INTO piggie_box_transactions 
                    (transaction_type, amount, description, transaction_date, balance_after, reference_number, recorded_by) 
                    VALUES ('INTEREST', '$amount', '$description', '$transaction_date', '$new_balance', '$reference_number', '" . $_SESSION['admin_id'] . "')";
            
            if (!mysqli_query($conn, $sql)) {
                throw new Exception('Error adding interest: ' . mysqli_error($conn));
            }
            
            $success = 'Interest added successfully';
        }
        
        mysqli_commit($conn);
        header('Location: piggie_box.php?success=' . urlencode($success));
        exit();
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = $e->getMessage();
        header('Location: piggie_box.php?error=' . urlencode($error));
        exit();
    }
}

closeDbConnection($conn);
header('Location: piggie_box.php');
exit();
?>