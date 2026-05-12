<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();

// Check if hand ID is provided
if (!isset($_GET['hand_id']) || empty($_GET['hand_id'])) {
    header('Location: index.php?error=No hand ID provided');
    exit();
}

$hand_id = mysqli_real_escape_string($conn, $_GET['hand_id']);

// Get hand info
$hand_sql = "SELECT h.*, 
                    m.first_name, m.middle_name, m.surname, m.member_number
             FROM hands h
             LEFT JOIN members m ON h.member_id = m.id
             WHERE h.id = '$hand_id'";
$hand_result = mysqli_query($conn, $hand_sql);

if (mysqli_num_rows($hand_result) == 0) {
    header('Location: index.php?error=Hand not found');
    exit();
}

$hand = mysqli_fetch_assoc($hand_result);

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $amount = mysqli_real_escape_string($conn, $_POST['amount']);
            $contribution_date = mysqli_real_escape_string($conn, $_POST['contribution_date']);
            $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
            $reference_number = mysqli_real_escape_string($conn, $_POST['reference_number']);
            $notes = mysqli_real_escape_string($conn, $_POST['notes']);
            
            if (empty($amount) || empty($contribution_date)) {
                $error = 'Amount and date are required!';
            } else {
                $sql = "INSERT INTO contributions (hand_id, amount, contribution_date, payment_method, reference_number, notes) 
                        VALUES ('$hand_id', '$amount', '$contribution_date', '$payment_method', '$reference_number', '$notes')";
                
                if (mysqli_query($conn, $sql)) {
                    $success = 'Contribution added successfully!';
                } else {
                    $error = 'Error: ' . mysqli_error($conn);
                }
            }
        } elseif ($_POST['action'] == 'delete' && isset($_POST['contrib_id'])) {
            $contrib_id = mysqli_real_escape_string($conn, $_POST['contrib_id']);
            $sql = "DELETE FROM contributions WHERE id = '$contrib_id' AND hand_id = '$hand_id'";
            
            if (mysqli_query($conn, $sql)) {
                $success = 'Contribution deleted successfully!';
            } else {
                $error = 'Error: ' . mysqli_error($conn);
            }
        }
    }
}

// Get contributions
$contrib_sql = "SELECT * FROM contributions WHERE hand_id = '$hand_id' ORDER BY contribution_date DESC";
$contrib_result = mysqli_query($conn, $contrib_sql);

// Calculate total
$total_sql = "SELECT SUM(amount) as total FROM contributions WHERE hand_id = '$hand_id'";
$total_result = mysqli_query($conn, $total_sql);
$total = mysqli_fetch_assoc($total_result)['total'] ?? 0;

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hand Contributions - <?php echo SITE_NAME; ?></title>
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
                <h1><i class="fas fa-coins"></i> Contributions for Hand: <?php echo htmlspecialchars($hand['hand_number']); ?></h1>
                <div class="page-actions">
                    <a href="view.php?id=<?php echo $hand_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Hand
                    </a>
                </div>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3>Hand Summary</h3>
                </div>
                <div class="card-body">
                    <div class="summary-boxes">
                        <div class="summary-box">
                            <label>Member:</label>
                            <span><?php echo htmlspecialchars($hand['first_name'] . ' ' . $hand['surname']); ?></span>
                        </div>
                        <div class="summary-box">
                            <label>Member Number:</label>
                            <span><?php echo htmlspecialchars($hand['member_number']); ?></span>
                        </div>
                        <div class="summary-box highlight">
                            <label>Total Contributions:</label>
                            <span class="amount"><?php echo formatCurrency($total); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Add New Contribution</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="amount">Amount (FCFA) *</label>
                                <input type="number" id="amount" name="amount" required 
                                       class="form-control" min="1" step="100">
                            </div>
                            
                            <div class="form-group">
                                <label for="contribution_date">Date *</label>
                                <input type="date" id="contribution_date" name="contribution_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required 
                                       class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="payment_method">Payment Method</label>
                                <select id="payment_method" name="payment_method" class="form-control">
                                    <option value="Cash">Cash</option>
                                    <option value="Mobile Money">Mobile Money</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="reference_number">Reference Number</label>
                                <input type="text" id="reference_number" name="reference_number" 
                                       placeholder="Transaction reference" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea id="notes" name="notes" rows="2" 
                                      placeholder="Additional notes" class="form-control"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Add Contribution
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Contribution History</h3>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($contrib_result) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount (FCFA)</th>
                                    <th>Payment Method</th>
                                    <th>Reference</th>
                                    <th>Notes</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($contrib = mysqli_fetch_assoc($contrib_result)): ?>
                                <tr>
                                    <td><?php echo formatDate($contrib['contribution_date']); ?></td>
                                    <td><strong><?php echo formatCurrency($contrib['amount']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($contrib['payment_method']); ?></td>
                                    <td><?php echo htmlspecialchars($contrib['reference_number'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars(substr($contrib['notes'] ?? '', 0, 30)); ?></td>
                                    <td>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="contrib_id" value="<?php echo $contrib['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('Delete this contribution?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>