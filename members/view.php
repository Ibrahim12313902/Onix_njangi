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

// Get member details
$sql = "SELECT m.*, mt.member_type_name 
        FROM members m 
        LEFT JOIN member_types mt ON m.member_type_id = mt.id 
        WHERE m.id = '$id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header('Location: index.php?error=Member not found');
    exit();
}

$member = mysqli_fetch_assoc($result);

// Get member's hands
$hands_sql = "SELECT h.*, ht.hand_type_name, hs.hand_status_name 
              FROM hands h
              LEFT JOIN hand_types ht ON h.hand_type_id = ht.id
              LEFT JOIN hand_status hs ON h.hand_status_id = hs.id
              WHERE h.member_id = '$id'
              ORDER BY h.created_at DESC";
$hands_result = mysqli_query($conn, $hands_sql);

// Calculate total contributions
$total_sql = "SELECT SUM(c.amount) as total 
              FROM contributions c
              JOIN hands h ON c.hand_id = h.id
              WHERE h.member_id = '$id'";
$total_result = mysqli_query($conn, $total_sql);
$total_contributions = mysqli_fetch_assoc($total_result)['total'] ?? 0;

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Details - <?php echo SITE_NAME; ?></title>
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
                <h1><i class="fas fa-user-circle"></i> Member Details</h1>
                <div class="page-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Members
                    </a>
                    <a href="edit.php?id=<?php echo $member['id']; ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="#" onclick="window.print()" class="btn btn-info">
                        <i class="fas fa-print"></i> Print
                    </a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Personal Information</h3>
                </div>
                <div class="card-body">
                    <div class="member-info-grid">
                        <div class="info-row">
                            <label>Member Number:</label>
                            <span class="value"><?php echo htmlspecialchars($member['member_number']); ?></span>
                        </div>
                        <div class="info-row">
                            <label>Full Name:</label>
                            <span class="value">
                                <?php echo htmlspecialchars(
                                    $member['first_name'] . ' ' . 
                                    ($member['middle_name'] ? $member['middle_name'] . ' ' : '') . 
                                    $member['surname']
                                ); ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <label>Member Type:</label>
                            <span class="value"><?php echo htmlspecialchars($member['member_type_name'] ?? 'Not Assigned'); ?></span>
                        </div>
                        <div class="info-row">
                            <label>Nationality:</label>
                            <span class="value"><?php echo htmlspecialchars($member['nationality']); ?></span>
                        </div>
                        <div class="info-row">
                            <label>Date of Birth:</label>
                            <span class="value"><?php echo formatDate($member['date_of_birth']); ?></span>
                        </div>
                        <div class="info-row">
                            <label>Gender:</label>
                            <span class="value"><?php echo htmlspecialchars($member['gender']); ?></span>
                        </div>
                        <div class="info-row">
                            <label>Phone:</label>
                            <span class="value"><?php echo htmlspecialchars($member['phone'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-row">
                            <label>Email:</label>
                            <span class="value"><?php echo htmlspecialchars($member['email'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-row">
                            <label>Address:</label>
                            <span class="value"><?php echo nl2br(htmlspecialchars($member['address'] ?? 'N/A')); ?></span>
                        </div>
                        <div class="info-row">
                            <label>Registered On:</label>
                            <span class="value"><?php echo formatDate($member['registration_date']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Summary</h3>
                </div>
                <div class="card-body">
                    <div class="summary-stats">
                        <div class="stat-box">
                            <div class="stat-icon">
                                <i class="fas fa-hand-holding-heart"></i>
                            </div>
                            <div class="stat-content">
                                <h4>Total Hands</h4>
                                <p class="stat-number"><?php echo mysqli_num_rows($hands_result); ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-box">
                            <div class="stat-icon">
                                <i class="fas fa-coins"></i>
                            </div>
                            <div class="stat-content">
                                <h4>Total Contributions</h4>
                                <p class="stat-number"><?php echo formatCurrency($total_contributions); ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-box">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-content">
                                <h4>Member Since</h4>
                                <p class="stat-number"><?php echo date('M Y', strtotime($member['registration_date'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Member's Hands</h3>
                    <a href="../hands/create.php?member_id=<?php echo $member['id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Open New Hand
                    </a>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($hands_result) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Hand Number</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Amount (FCFA)</th>
                                    <th>Opening Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($hand = mysqli_fetch_assoc($hands_result)): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($hand['hand_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($hand['hand_type_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        if ($hand['hand_status_name'] == 'Active') $status_class = 'badge-success';
                                        elseif ($hand['hand_status_name'] == 'Closed') $status_class = 'badge-danger';
                                        else $status_class = 'badge-info';
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars($hand['hand_status_name'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $hand['amount'] > 0 ? formatCurrency($hand['amount']) : '-'; ?></td>
                                    <td><?php echo formatDate($hand['opening_date']); ?></td>
                                    <td class="actions">
                                        <a href="../hands/view.php?id=<?php echo $hand['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-hand-holding-heart fa-3x"></i>
                            <p>This member has no hands yet.</p>
                            <a href="../hands/create.php?member_id=<?php echo $member['id']; ?>" class="btn btn-primary">
                                Open First Hand
                            </a>
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