<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?error=No ID provided');
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

$sql = "SELECT * FROM payout_cycles WHERE id = '$id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header('Location: index.php?error=Cycle not found');
    exit();
}

$cycle = mysqli_fetch_assoc($result);

// Check if cycle has approved payment proofs
$sql = "SELECT COUNT(*) as count FROM payment_proofs pp
        JOIN cycle_hands ch ON pp.hand_id = ch.hand_id
        WHERE ch.cycle_id = '$id' AND pp.status = 'approved'";
$result = mysqli_query($conn, $sql);
$approved_payments = mysqli_fetch_assoc($result)['count'];

// Check if cycle has ended
$today = date('Y-m-d');
$cycle_ended = ($cycle['end_date'] && $cycle['end_date'] < $today);

// Check if all payouts are complete
$sql = "SELECT COUNT(*) as total,
        SUM(CASE WHEN h.received_at IS NOT NULL OR h.payout_status = 'paid' THEN 1 ELSE 0 END) as completed
        FROM cycle_hands ch
        JOIN hands h ON ch.hand_id = h.id
        WHERE ch.cycle_id = '$id'";
$result = mysqli_query($conn, $sql);
$payout_data = mysqli_fetch_assoc($result);
$all_payouts_complete = ($payout_data['total'] > 0 && $payout_data['total'] == $payout_data['completed']);

$can_delete = ($approved_payments == 0) || ($cycle_ended && $all_payouts_complete);
$delete_message = '';

if ($approved_payments > 0) {
    if ($cycle_ended && $all_payouts_complete) {
        $delete_message = "This cycle had $approved_payments approved payment(s), but the cycle has ended and all payouts are complete. You may now delete it.";
    } else {
        $delete_message = "This cycle has $approved_payments approved payment(s). You cannot delete it until the cycle's end date has passed and all payouts are complete.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $can_delete) {
    mysqli_query($conn, "UPDATE hands SET payout_cycle_id = NULL, payout_position = NULL WHERE payout_cycle_id = '$id'");
    
    mysqli_query($conn, "DELETE FROM payment_deadlines WHERE hand_id IN (SELECT hand_id FROM cycle_hands WHERE cycle_id = '$id')");
    mysqli_query($conn, "DELETE FROM cycle_hands WHERE cycle_id = '$id'");
    mysqli_query($conn, "DELETE FROM payout_cycles WHERE id = '$id'");
    
    header('Location: index.php?success=Cycle deleted successfully!');
    exit();
}

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Payout Cycle - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/notification_setup.php'; ?>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-trash"></i> Delete Payout Cycle</h1>
                <div class="page-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Cycle Details</h3>
                </div>
                <div class="card-body">
                    <p><strong>Cycle:</strong> <?php echo htmlspecialchars($cycle['cycle_name']); ?></p>
                    <p><strong>Number:</strong> <?php echo htmlspecialchars($cycle['cycle_number']); ?></p>
                    <p><strong>Start Date:</strong> <?php echo formatDate($cycle['start_date']); ?></p>
                    <p><strong>End Date:</strong> <?php echo formatDate($cycle['end_date']); ?></p>
                    <p><strong>Total Hands:</strong> <?php echo $cycle['total_hands']; ?></p>
                    <p><strong>Status:</strong> <span class="badge <?php echo $cycle['status'] == 'active' ? 'badge-success' : 'badge-info'; ?>"><?php echo ucfirst($cycle['status']); ?></span></p>
                    
                    <?php if (!empty($delete_message)): ?>
                    <div class="alert <?php echo $can_delete ? 'alert-warning' : 'alert-error'; ?>">
                        <i class="fas fa-<?php echo $can_delete ? 'exclamation-triangle' : 'lock'; ?>"></i>
                        <?php echo $delete_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($can_delete): ?>
                    <div class="alert alert-warning">
                        <strong>Warning!</strong> You are about to delete this payout cycle. This action cannot be undone.
                    </div>
                    
                    <form method="POST" action="">
                        <div class="form-actions">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Delete Cycle
                            </button>
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" disabled>
                            <i class="fas fa-lock"></i> Delete Disabled
                        </button>
                        <a href="index.php" class="btn btn-secondary">Back to List</a>
                    </div>
                    <p style="margin-top: 15px; color: #666; font-size: 13px;">
                        <i class="fas fa-info-circle"></i> This cycle cannot be deleted because it has approved payment transactions. 
                        Wait until the cycle ends and all payouts are complete, then try again.
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../js/script.js"></script>
</body>
</html>