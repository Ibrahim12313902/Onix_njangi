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

// Get hand details
$sql = "SELECT h.*, 
               m.member_number, m.first_name, m.middle_name, m.surname, m.nationality, m.phone, m.email,
               ht.hand_type_name, ht.hand_type_number,
               hs.hand_status_name, hs.hand_status_number
        FROM hands h
        LEFT JOIN members m ON h.member_id = m.id
        LEFT JOIN hand_types ht ON h.hand_type_id = ht.id
        LEFT JOIN hand_status hs ON h.hand_status_id = hs.id
        WHERE h.id = '$id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header('Location: index.php?error=Hand not found');
    exit();
}

$hand = mysqli_fetch_assoc($result);

// Get contributions for this hand
$contrib_sql = "SELECT * FROM contributions WHERE hand_id = '$id' ORDER BY contribution_date DESC";
$contrib_result = mysqli_query($conn, $contrib_sql);

// Calculate total contributions
$total_sql = "SELECT SUM(amount) as total FROM contributions WHERE hand_id = '$id'";
$total_result = mysqli_query($conn, $total_sql);
$total = mysqli_fetch_assoc($total_result)['total'] ?? 0;

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hand Details - <?php echo SITE_NAME; ?></title>
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
                <h1><i class="fas fa-info-circle"></i> Hand Details: <?php echo htmlspecialchars($hand['hand_number']); ?></h1>
                <div class="page-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Hands
                    </a>
                    <a href="edit.php?id=<?php echo $hand['id']; ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="#" onclick="window.print()" class="btn btn-info">
                        <i class="fas fa-print"></i> Print
                    </a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Hand Information</h3>
                </div>
                <div class="card-body">
                    <div class="details-grid">
                        <div class="detail-row">
                            <label>Hand Number:</label>
                            <span class="value"><?php echo htmlspecialchars($hand['hand_number']); ?></span>
                        </div>
                        <div class="detail-row">
                            <label>Hand Type:</label>
                            <span class="value"><?php echo htmlspecialchars($hand['hand_type_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-row">
                            <label>Status:</label>
                            <span class="value">
                                <?php 
                                $status_class = '';
                                if ($hand['hand_status_name'] == 'Active') $status_class = 'badge-success';
                                elseif ($hand['hand_status_name'] == 'Closed') $status_class = 'badge-danger';
                                elseif ($hand['hand_status_name'] == 'Suspended') $status_class = 'badge-warning';
                                else $status_class = 'badge-info';
                                ?>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($hand['hand_status_name'] ?? 'N/A'); ?>
                                </span>
                            </span>
                        </div>
                        <div class="detail-row">
                            <label>Amount:</label>
                            <span class="value"><?php echo formatCurrency($hand['amount'] ?? 0); ?></span>
                        </div>
                        <div class="detail-row">
                            <label>Opening Date:</label>
                            <span class="value"><?php echo formatDate($hand['opening_date']); ?></span>
                        </div>
                        <?php if ($hand['closing_date']): ?>
                        <div class="detail-row">
                            <label>Closing Date:</label>
                            <span class="value"><?php echo formatDate($hand['closing_date']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="detail-row">
                            <label>Created:</label>
                            <span class="value"><?php echo formatDate($hand['created_at']); ?></span>
                        </div>
                        <?php if ($hand['notes']): ?>
                        <div class="detail-row full-width">
                            <label>Notes:</label>
                            <span class="value"><?php echo nl2br(htmlspecialchars($hand['notes'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Member Information</h3>
                </div>
                <div class="card-body">
                    <div class="details-grid">
                        <div class="detail-row">
                            <label>Member Number:</label>
                            <span class="value"><?php echo htmlspecialchars($hand['member_number'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-row">
                            <label>Full Name:</label>
                            <span class="value">
                                <?php echo htmlspecialchars(
                                    $hand['first_name'] . ' ' . 
                                    ($hand['middle_name'] ? $hand['middle_name'] . ' ' : '') . 
                                    $hand['surname']
                                ); ?>
                            </span>
                        </div>
                        <div class="detail-row">
                            <label>Nationality:</label>
                            <span class="value"><?php echo htmlspecialchars($hand['nationality'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-row">
                            <label>Phone:</label>
                            <span class="value"><?php echo htmlspecialchars($hand['phone'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-row">
                            <label>Email:</label>
                            <span class="value"><?php echo htmlspecialchars($hand['email'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Contributions</h3>
                    <div class="total-amount">
                        Total: <strong><?php echo formatCurrency($total ?? 0); ?></strong>
                    </div>
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
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-coins fa-3x"></i>
                            <p>No contributions recorded for this hand yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../js/script.js"></script>
</body>
</html>