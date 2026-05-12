<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';
require_once '../includes/notification_setup.php';

$conn = getDbConnection();
$success = '';
$error = '';

// Handle payout action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_received'])) {
    $hand_id = (int)$_POST['hand_id'];
    $cycle_id = (int)$_POST['cycle_id'];
    
    $data = markPayoutReceived($conn, $hand_id, $cycle_id);
    if ($data) {
        $success = $data['first_name'] . " " . $data['surname'] . " has received their payout of " . formatCurrency($data['amount']);
    } else {
        $error = "Error processing payout";
    }
}

// Get active cycles
$sql = "SELECT * FROM payout_cycles WHERE status IN ('active', 'draft') ORDER BY created_at DESC";
$cycles_result = mysqli_query($conn, $sql);

$selected_cycle_id = isset($_GET['cycle_id']) ? (int)$_GET['cycle_id'] : 0;
if (!$selected_cycle_id && mysqli_num_rows($cycles_result) > 0) {
    $first_cycle = mysqli_fetch_assoc($cycles_result);
    $selected_cycle_id = $first_cycle['id'];
    mysqli_data_seek($cycles_result, 0);
}

// Get cycle hands with status
$cycle_hands = [];
$current_recipient = null;
if ($selected_cycle_id) {
    $sql = "SELECT h.*, m.first_name, m.middle_name, m.surname, m.phone, m.member_number,
            ht.hand_type_name, ht.default_amount,
            ch.position_order,
            COALESCE((SELECT SUM(amount) FROM contributions WHERE hand_id = h.id), 0) as paid
            FROM cycle_hands ch
            JOIN hands h ON ch.hand_id = h.id
            JOIN members m ON h.member_id = m.id
            JOIN hand_types ht ON h.hand_type_id = ht.id
            WHERE ch.cycle_id = '$selected_cycle_id'
            ORDER BY ch.position_order ASC";
    $hands_result = mysqli_query($conn, $sql);
    
    while ($hand = mysqli_fetch_assoc($hands_result)) {
        if ($hand['received_at'] || $hand['payout_status'] == 'paid') {
            $hand['status'] = 'received';
        } elseif ($hand['paid'] >= ($hand['default_amount'] ?? $hand['amount'])) {
            $hand['status'] = 'eligible';
        } elseif ($hand['paid'] > 0) {
            $hand['status'] = 'partial';
        } else {
            $hand['status'] = 'pending';
        }
        $cycle_hands[] = $hand;
        
        if (!$current_recipient && $hand['status'] != 'received') {
            $current_recipient = $hand;
        }
    }
}

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payout Management - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .payout-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .recipient-highlight {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }
        .recipient-highlight h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .recipient-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .recipient-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: bold;
        }
        .recipient-details h2 {
            margin: 0 0 5px 0;
            font-size: 28px;
        }
        .recipient-details p {
            margin: 5px 0;
            opacity: 0.9;
        }
        .recipient-amount {
            margin-left: auto;
            text-align: right;
        }
        .recipient-amount .amount {
            font-size: 36px;
            font-weight: bold;
        }
        .payout-btn {
            background: white;
            color: #28a745;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
        }
        .payout-btn:hover {
            background: rgba(255,255,255,0.9);
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-received { background: #d4edda; color: #155724; }
        .status-eligible { background: #cce5ff; color: #004085; }
        .status-partial { background: #fff3cd; color: #856404; }
        .status-pending { background: #f8d7da; color: #721c24; }
        .hand-row {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
            gap: 15px;
        }
        .hand-row:last-child { border-bottom: none; }
        .hand-row:hover { background: #f8f9fa; }
        .position-badge {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }
        .position-badge.current {
            background: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.3);
        }
        .position-badge.received {
            background: #6c757d;
        }
        .hand-info { flex: 1; }
        .hand-info h4 { margin: 0 0 5px 0; }
        .hand-info p { margin: 0; color: #666; font-size: 13px; }
        .hand-amount { text-align: right; }
        .hand-amount .total { font-weight: 600; font-size: 16px; }
        .hand-amount .paid { color: #28a745; font-size: 13px; }
        .hand-action { min-width: 150px; text-align: right; }
        .btn-payout {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-payout:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .cycle-selector {
            margin-bottom: 20px;
        }
        .cycle-selector select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            min-width: 250px;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-gift"></i> Payout Management</h1>
            </div>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (mysqli_num_rows($cycles_result) > 0): ?>
            
            <div class="cycle-selector">
                <form method="GET">
                    <label><strong>Select Cycle:</strong></label>
                    <select name="cycle_id" onchange="this.form.submit()">
                        <?php while ($cycle = mysqli_fetch_assoc($cycles_result)): ?>
                        <option value="<?php echo $cycle['id']; ?>" <?php echo ($cycle['id'] == $selected_cycle_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cycle['cycle_name']); ?> 
                            (<?php echo ucfirst($cycle['status']); ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </form>
            </div>
            
            <?php if ($current_recipient): ?>
            <div class="recipient-highlight">
                <h3><i class="fas fa-star"></i> Current Payout Recipient</h3>
                <div class="recipient-info">
                    <div class="recipient-avatar">
                        <?php echo strtoupper(substr($current_recipient['first_name'], 0, 1)); ?>
                    </div>
                    <div class="recipient-details">
                        <h2><?php echo htmlspecialchars($current_recipient['first_name'] . ' ' . $current_recipient['surname']); ?></h2>
                        <p><i class="fas fa-id-badge"></i> <?php echo htmlspecialchars($current_recipient['member_number']); ?></p>
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($current_recipient['phone']); ?></p>
                        <p><i class="fas fa-hand-holding"></i> <?php echo htmlspecialchars($current_recipient['hand_type_name']); ?></p>
                    </div>
                    <div class="recipient-amount">
                        <div class="amount"><?php echo formatCurrency($current_recipient['amount']); ?></div>
                        <div>Amount to Receive</div>
                        <div style="margin-top: 10px;">
                            <span class="status-badge" style="background: rgba(255,255,255,0.2); color: white;">
                                <i class="fas fa-check-circle"></i> Eligible (Paid: <?php echo formatCurrency($current_recipient['paid']); ?>)
                            </span>
                        </div>
                        <form method="POST" style="margin-top: 15px;">
                            <input type="hidden" name="hand_id" value="<?php echo $current_recipient['id']; ?>">
                            <input type="hidden" name="cycle_id" value="<?php echo $selected_cycle_id; ?>">
                            <button type="submit" name="mark_received" class="payout-btn">
                                <i class="fas fa-check"></i> Confirm Payout Received
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="payout-card" style="text-align: center; padding: 40px;">
                <i class="fas fa-check-circle fa-4x" style="color: #28a745; margin-bottom: 20px;"></i>
                <h3>All Payouts Completed!</h3>
                <p>All members in this cycle have received their payouts.</p>
            </div>
            <?php endif; ?>
            
            <div class="payout-card">
                <h3><i class="fas fa-list"></i> All Hands in Cycle</h3>
                <div>
                    <?php foreach ($cycle_hands as $hand): ?>
                    <div class="hand-row">
                        <div class="position-badge <?php echo ($hand['status'] == 'eligible' && $hand['id'] == $current_recipient['id']) ? 'current' : ''; ?> <?php echo $hand['status'] == 'received' ? 'received' : ''; ?>">
                            #<?php echo $hand['position_order']; ?>
                        </div>
                        <div class="hand-info">
                            <h4>
                                <?php echo htmlspecialchars($hand['first_name'] . ' ' . $hand['surname']); ?>
                                <?php if ($hand['status'] == 'eligible' && $hand['id'] == $current_recipient['id']): ?>
                                <span style="background: #28a745; color: white; padding: 2px 8px; border-radius: 10px; font-size: 10px; margin-left: 5px;">NEXT</span>
                                <?php endif; ?>
                            </h4>
                            <p><?php echo htmlspecialchars($hand['member_number']); ?> • <?php echo htmlspecialchars($hand['hand_number']); ?></p>
                        </div>
                        <div class="hand-amount">
                            <div class="total"><?php echo formatCurrency($hand['amount']); ?></div>
                            <div class="paid">Paid: <?php echo formatCurrency($hand['paid']); ?></div>
                        </div>
                        <div class="hand-action">
                            <?php if ($hand['status'] == 'received'): ?>
                            <span class="status-badge status-received"><i class="fas fa-gift"></i> Received</span>
                            <?php elseif ($hand['status'] == 'eligible'): ?>
                            <span class="status-badge status-eligible"><i class="fas fa-check"></i> Eligible</span>
                            <?php elseif ($hand['status'] == 'partial'): ?>
                            <span class="status-badge status-partial"><i class="fas fa-clock"></i> Partial</span>
                            <?php else: ?>
                            <span class="status-badge status-pending"><i class="fas fa-hourglass"></i> Pending</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php else: ?>
            
            <div class="payout-card" style="text-align: center; padding: 60px;">
                <i class="fas fa-calendar-times fa-5x" style="color: #ddd; margin-bottom: 20px;"></i>
                <h2>No Active Cycles</h2>
                <p>There are no active payout cycles to manage.</p>
                <a href="../payout_cycles/create.php" class="btn btn-primary" style="margin-top: 20px;">
                    <i class="fas fa-plus"></i> Create New Cycle
                </a>
            </div>
            
            <?php endif; ?>
        </main>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
