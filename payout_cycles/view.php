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

$sql = "SELECT ch.*, h.hand_number, h.amount as hand_amount, m.first_name, m.middle_name, m.surname, m.phone,
        ht.hand_type_name, ht.payment_period_days, ht.default_amount,
        pd.deadline_date, pd.amount_due as expected_amount, pd.amount_paid, pd.status as payment_status
        FROM cycle_hands ch
        JOIN hands h ON ch.hand_id = h.id
        JOIN members m ON h.member_id = m.id
        JOIN hand_types ht ON h.hand_type_id = ht.id
        LEFT JOIN payment_deadlines pd ON pd.hand_id = ch.hand_id
        WHERE ch.cycle_id = '$id'
        ORDER BY ch.position_order ASC";
$hands_result = mysqli_query($conn, $sql);
if (!$hands_result) {
    die('Query error: ' . mysqli_error($conn));
}

$total_expected = 0;
$total_collected = 0;
$total_paid_count = 0;
$total_not_paid = 0;

$hands = [];
while ($hand = mysqli_fetch_assoc($hands_result)) {
    $expected = $hand['expected_amount'] ?? $hand['default_amount'] ?? $hand['hand_amount'] ?? 0;
    $paid = $hand['paid_amount'] ?? 0;
    
    $contrib_sql = "SELECT SUM(amount) as total FROM contributions WHERE hand_id = '" . $hand['hand_id'] . "'";
    $contrib_result = mysqli_query($conn, $contrib_sql);
    $actual_paid = mysqli_fetch_assoc($contrib_result)['total'] ?? 0;
    $hand['actual_paid'] = $actual_paid;
    
    $total_expected += $expected;
    $total_collected += $actual_paid;
    
    if ($actual_paid >= $expected && $expected > 0) {
        $total_paid_count++;
        $hand['is_paid'] = true;
    } else {
        $total_not_paid++;
        $hand['is_paid'] = false;
    }
    
    $hands[] = $hand;
}

$progress_percentage = $total_expected > 0 ? round(($total_collected / $total_expected) * 100) : 0;
$hands_paid_percentage = count($hands) > 0 ? round(($total_paid_count / count($hands)) * 100) : 0;

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Cycle - <?php echo SITE_NAME; ?></title>
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
                <h1><i class="fas fa-calendar-check"></i> Payout Cycle Details</h1>
                <div class="page-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    <?php if ($cycle['status'] == 'draft'): ?>
                    <a href="edit.php?id=<?php echo $cycle['id']; ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="cycle-info">
                <div class="info-card">
                    <h3><?php echo htmlspecialchars($cycle['cycle_name']); ?></h3>
                    <p class="cycle-number"><?php echo htmlspecialchars($cycle['cycle_number']); ?></p>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <label>Start Date</label>
                        <span><?php echo formatDate($cycle['start_date']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>End Date</label>
                        <span><?php echo formatDate($cycle['end_date']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Total Hands</label>
                        <span><?php echo $cycle['total_hands']; ?></span>
                    </div>
                    <div class="info-item">
                        <label>Status</label>
                        <?php 
                        $status_class = '';
                        if ($cycle['status'] == 'active') $status_class = 'badge-success';
                        elseif ($cycle['status'] == 'completed') $status_class = 'badge-info';
                        elseif ($cycle['status'] == 'cancelled') $status_class = 'badge-danger';
                        else $status_class = 'badge-warning';
                        ?>
                        <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($cycle['status']); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="cycle-stats">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="stat-content">
                        <h4>Total Expected</h4>
                        <p class="stat-number"><?php echo formatCurrency($total_expected); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-coins"></i></div>
                    <div class="stat-content">
                        <h4>Total Collected</h4>
                        <p class="stat-number"><?php echo formatCurrency($total_collected); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-content">
                        <h4>Paid</h4>
                        <p class="stat-number"><?php echo $total_paid_count; ?> / <?php echo count($hands); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-content">
                        <h4>Not Paid</h4>
                        <p class="stat-number"><?php echo $total_not_paid; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="progress-section">
                <div class="progress-item">
                    <label>Collection Progress (Money)</label>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $progress_percentage; ?>%"></div>
                    </div>
                    <span class="progress-text"><?php echo $progress_percentage; ?>%</span>
                </div>
                <div class="progress-item">
                    <label>Hands Paid Progress</label>
                    <div class="progress-bar">
                        <div class="progress-fill progress-success" style="width: <?php echo $hands_paid_percentage; ?>%"></div>
                    </div>
                    <span class="progress-text"><?php echo $hands_paid_percentage; ?>%</span>
                </div>
            </div>
            
            <?php 
            $next_receiver = null;
            $last_paid_position = 0;
            foreach ($hands as $hand) {
                if ($hand['is_paid']) {
                    $last_paid_position = $hand['position_order'];
                } else {
                    if ($next_receiver === null) {
                        $next_receiver = $hand;
                    }
                }
            }
            ?>
            
            <?php if ($next_receiver !== null): ?>
            <div class="card" style="border-left: 4px solid #28a745;">
                <div class="card-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white;">
                    <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-gift"></i> Current Payout Recipient
                    </h3>
                </div>
                <div class="card-body">
                    <div class="receiver-info">
                        <div class="receiver-detail">
                            <label>Position</label>
                            <span class="position-badge">#<?php echo $next_receiver['position_order']; ?></span>
                        </div>
                        <div class="receiver-detail">
                            <label>Hand Number</label>
                            <span class="value"><?php echo htmlspecialchars($next_receiver['hand_number']); ?></span>
                        </div>
                        <div class="receiver-detail">
                            <label>Member Name</label>
                            <span class="value">
                                <?php echo htmlspecialchars(
                                    $next_receiver['first_name'] . ' ' . 
                                    ($next_receiver['middle_name'] ? $next_receiver['middle_name'] . ' ' : '') . 
                                    $next_receiver['surname']
                                ); ?>
                            </span>
                        </div>
                        <div class="receiver-detail">
                            <label>Phone</label>
                            <span class="value"><?php echo htmlspecialchars($next_receiver['phone'] ?? '-'); ?></span>
                        </div>
                        <div class="receiver-detail">
                            <label>Hand Type</label>
                            <span class="value"><?php echo htmlspecialchars($next_receiver['hand_type_name']); ?></span>
                        </div>
                        <div class="receiver-detail">
                            <label>Expected Amount</label>
                            <span class="value"><?php echo formatCurrency($next_receiver['expected_amount'] ?? $next_receiver['default_amount'] ?? 0); ?></span>
                        </div>
                        <div class="receiver-detail">
                            <label>Already Paid</label>
                            <span class="value"><?php echo formatCurrency($next_receiver['actual_paid']); ?></span>
                        </div>
                        <div class="receiver-detail">
                            <label>Balance</label>
                            <span class="value text-danger"><?php echo formatCurrency(max(0, ($next_receiver['expected_amount'] ?? $next_receiver['default_amount'] ?? 0) - $next_receiver['actual_paid'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h3>Payout Order - Full List (First to Last)</h3>
                        <a href="hands.php?id=<?php echo $id; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i> Manage Hands
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($hands) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Position</th>
                                    <th>Hand Number</th>
                                    <th>Member</th>
                                    <th>Phone</th>
                                    <th>Hand Type</th>
                                    <th>Expected</th>
                                    <th>Paid</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Deadline</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $position = 1;
                                foreach ($hands as $hand): 
                                    $expected = $hand['expected_amount'] ?? $hand['default_amount'] ?? 0;
                                    $balance = $expected - $hand['actual_paid'];
                                    $status_class = '';
                                    $status_text = '';
                                    if ($hand['is_paid']) {
                                        $status_class = 'badge-success';
                                        $status_text = 'Paid';
                                    } elseif ($hand['actual_paid'] > 0) {
                                        $status_class = 'badge-warning';
                                        $status_text = 'Partial';
                                    } else {
                                        $status_class = 'badge-danger';
                                        $status_text = 'Not Paid';
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $position++; ?></td>
                                    <td><span class="badge badge-info">#<?php echo $hand['position_order']; ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($hand['hand_number']); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars(
                                            $hand['first_name'] . ' ' . 
                                            ($hand['middle_name'] ? $hand['middle_name'] . ' ' : '') . 
                                            $hand['surname']
                                        ); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($hand['phone'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($hand['hand_type_name']); ?></td>
                                    <td><?php echo formatCurrency($expected); ?></td>
                                    <td><strong><?php echo formatCurrency($hand['actual_paid']); ?></strong></td>
                                    <td class="<?php echo $balance > 0 ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo formatCurrency(max(0, $balance)); ?>
                                    </td>
                                    <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                    <td><?php echo $hand['deadline_date'] ? formatDate($hand['deadline_date']) : '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No hands in this cycle.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../js/script.js"></script>
</body>
</html>