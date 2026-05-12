<?php
require_once 'config.php';
requireMemberLogin();

require_once '../config/database.php';
$conn = getDbConnection();

$member_id = getMemberId();
$error = '';
$success = '';

$sql = "SELECT COUNT(*) as count FROM member_notifications WHERE member_id = '$member_id' AND is_read = 0";
$result = mysqli_query($conn, $sql);
$unread_notifications = mysqli_fetch_assoc($result)['count'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['submit_proof'])) {
        $hand_id = (int)$_POST['hand_id'];
        $amount = mysqli_real_escape_string($conn, $_POST['amount']);
        $payment_date = mysqli_real_escape_string($conn, $_POST['payment_date']);
        $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method'] ?? 'Cash');
        $reference_number = mysqli_real_escape_string($conn, $_POST['reference_number'] ?? '');
        $upload_path = NULL;
        
        // Check if file was uploaded
        if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
            $filename = $_FILES['proof_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_filename = 'proof_' . $member_id . '_' . time() . '.' . $ext;
                $upload_path = 'uploads/proofs/' . $new_filename;
                
                if (!is_dir('uploads/proofs')) {
                    mkdir('uploads/proofs', 0777, true);
                }
                
                if (!move_uploaded_file($_FILES['proof_image']['tmp_name'], $upload_path)) {
                    $error = 'Failed to upload image.';
                }
            } else {
                $error = 'Invalid file type. Allowed: jpg, jpeg, png, gif, pdf';
            }
        }
        
        if (empty($error)) {
            $upload_sql = $upload_path ? "'$upload_path'" : "NULL";
            $sql = "INSERT INTO payment_proofs (member_id, hand_id, amount, proof_image, payment_date, status, payment_method, reference_number)
                    VALUES ('$member_id', '$hand_id', '$amount', $upload_sql, '$payment_date', 'pending', '$payment_method', '$reference_number')";
            
            if (mysqli_query($conn, $sql)) {
                $success = 'Proof of payment uploaded successfully! Waiting for admin approval.';
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        }
    }
    
    // Handle online payment submission
    if (isset($_POST['submit_online_payment'])) {
        $hand_id = (int)$_POST['hand_id'];
        $amount = mysqli_real_escape_string($conn, $_POST['amount']);
        $payment_date = date('Y-m-d');
        $payment_method = mysqli_real_escape_string($conn, $_POST['online_payment_method']);
        $reference_number = mysqli_real_escape_string($conn, $_POST['transaction_id']);
        
        if (empty($reference_number)) {
            $error = 'Please enter the transaction/transfer ID.';
        } else {
            // Create payment proof without image for online payments
            $sql = "INSERT INTO payment_proofs (member_id, hand_id, amount, payment_date, status, payment_method, reference_number, proof_image)
                    VALUES ('$member_id', '$hand_id', '$amount', '$payment_date', 'pending', '$payment_method', '$reference_number', NULL)";
            
            if (mysqli_query($conn, $sql)) {
                $success = 'Online payment submitted successfully! Transaction ID: ' . $reference_number . '. Waiting for admin verification.';
            } else {
                $error = 'Error submitting payment: ' . mysqli_error($conn);
            }
        }
    }
}

// Get member's hands
$sql = "SELECT h.*, ht.hand_type_name, ht.default_amount, ht.payment_period_days,
        ch.position_order, pd.deadline_date
        FROM hands h
        JOIN hand_types ht ON h.hand_type_id = ht.id
        LEFT JOIN cycle_hands ch ON ch.hand_id = h.id
        LEFT JOIN payment_deadlines pd ON pd.hand_id = h.id
        WHERE h.member_id = '$member_id'
        ORDER BY h.created_at DESC";
$hands_result = mysqli_query($conn, $sql);

// Get contributions for each hand
$hands = [];
while ($hand = mysqli_fetch_assoc($hands_result)) {
    $sql = "SELECT SUM(amount) as total FROM contributions WHERE hand_id = '" . $hand['id'] . "'";
    $result = mysqli_query($conn, $sql);
    $hand['total_contributed'] = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    $sql = "SELECT * FROM contributions WHERE hand_id = '" . $hand['id'] . "' ORDER BY contribution_date DESC";
    $result = mysqli_query($conn, $sql);
    $hand['contributions'] = [];
    while ($c = mysqli_fetch_assoc($result)) {
        $hand['contributions'][] = $c;
    }
    
    $sql = "SELECT * FROM payment_proofs WHERE hand_id = '" . $hand['id'] . "' AND member_id = '$member_id' ORDER BY created_at DESC";
    $result = mysqli_query($conn, $sql);
    $hand['pending_proofs'] = [];
    while ($p = mysqli_fetch_assoc($result)) {
        if ($p['status'] == 'pending') {
            $hand['pending_proofs'][] = $p;
        }
    }
    
    $hands[] = $hand;
}

// Get active cycle info
$sql = "SELECT * FROM payout_cycles WHERE status IN ('active', 'draft') ORDER BY created_at DESC LIMIT 1";
$result = mysqli_query($conn, $sql);
$active_cycle = mysqli_fetch_assoc($result) ?? null;

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contributions - Member Portal</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f5f5; }
        .member-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .member-nav {
            display: flex;
            gap: 20px;
        }
        .member-nav a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .member-nav a:hover, .member-nav a.active {
            background: rgba(255,255,255,0.2);
        }
        .member-content {
            padding: 25px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .hand-section {
            background: white;
            border-radius: 12px;
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .hand-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .hand-header h3 { margin: 0; display: flex; align-items: center; gap: 10px; }
        .hand-body { padding: 20px; }
        .payment-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .payment-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .payment-item .label { font-size: 11px; color: #666; text-transform: uppercase; }
        .payment-item .value { font-size: 20px; font-weight: 600; color: #333; margin-top: 5px; }
        .payment-item .value.danger { color: #dc3545; }
        .payment-item .value.success { color: #28a745; }
        .deadline-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        .deadline-ok { background: #d4edda; color: #155724; }
        .deadline-soon { background: #fff3cd; color: #856404; }
        .deadline-overdue { background: #f8d7da; color: #721c24; }
        .contribution-history {
            margin-top: 20px;
        }
        .contribution-history h4 {
            margin: 0 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        .contribution-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        .contribution-item:last-child { border-bottom: none; }
        .contribution-item .date { color: #666; font-size: 13px; }
        .contribution-item .amount { font-weight: 600; color: #28a745; }
        .contribution-item .status { font-size: 12px; }
        .pending-badge {
            background: #fff3cd;
            color: #856404;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 11px;
        }
        .upload-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .upload-section h4 { margin: 0 0 15px 0; }
        .upload-section input, .upload-section select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .upload-section input[type="file"] {
            width: 100%;
        }
        .btn-upload {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-upload:hover { opacity: 0.9; }
        .period-indicator {
            display: flex;
            gap: 5px;
            margin-top: 10px;
        }
        .period-week {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 600;
        }
        .period-paid { background: #28a745; color: white; }
        .period-pending { background: #eee; color: #666; }
        .notification-dropdown {
            position: relative;
            display: inline-block;
        }
        .notification-btn {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: background 0.3s;
            position: relative;
        }
        .notification-btn:hover { background: rgba(255,255,255,0.2); }
        .notification-badge-count {
            position: absolute;
            top: 0;
            right: 0;
            background: #dc3545;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 50%;
            min-width: 18px;
            text-align: center;
        }
        .notification-panel {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            width: 350px;
            max-height: 450px;
            overflow-y: auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            z-index: 1000;
            margin-top: 10px;
        }
        .notification-panel.show { display: block; }
        .notification-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .notification-header h3 { margin: 0; font-size: 15px; color: #333; }
        .notification-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            gap: 12px;
            cursor: pointer;
        }
        .notification-item:hover { background: #f8f9fa; }
        .notification-item.unread { background: #e8f4fd; }
        .notification-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .notification-icon.success { background: #d4edda; color: #28a745; }
        .notification-icon.danger { background: #f8d7da; color: #dc3545; }
        .notification-icon.chat { background: #f3e5f5; color: #9c27b0; }
        .notification-icon.info { background: #e8f4fd; color: #667eea; }
        .notification-content { flex: 1; min-width: 0; }
        .notification-title { font-weight: 600; font-size: 13px; color: #333; margin-bottom: 3px; }
        .notification-desc { font-size: 12px; color: #666; margin-bottom: 3px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .notification-time { font-size: 11px; color: #999; }
        .notification-empty { padding: 30px 15px; text-align: center; color: #999; }
        .nav-right { display: flex; align-items: center; gap: 15px; }
    </style>
</head>
<body>
    <header class="member-header">
        <div>
            <h2><i class="fas fa-hand-holding-heart"></i> ONIX Njangi</h2>
        </div>
        <nav class="member-nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="contributions.php" class="active"><i class="fas fa-coins"></i> Contributions</a>
            <a href="payout_schedule.php"><i class="fas fa-calendar-alt"></i> Payout Schedule</a>
            <a href="my_hands.php"><i class="fas fa-hand-holding"></i> My Hands</a>
            <a href="chat.php"><i class="fas fa-comments"></i> Group Chat</a>
            <a href="profile.php"><i class="fas fa-user-cog"></i> Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
        <div class="nav-right">
            <div class="notification-dropdown">
                <button class="notification-btn" onclick="toggleNotifications()">
                    <i class="fas fa-bell"></i>
                    <?php if ($unread_notifications > 0): ?>
                    <span class="notification-badge-count"><?php echo $unread_notifications; ?></span>
                    <?php endif; ?>
                </button>
                <div class="notification-panel" id="notificationPanel">
                    <div class="notification-header">
                        <h3><i class="fas fa-bell"></i> My Notifications</h3>
                    </div>
                    <div id="notificationList">
                        <div class="notification-empty"><i class="fas fa-bell-slash"></i><p>Loading...</p></div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <div class="member-content">
        <h2 style="margin-bottom: 20px;"><i class="fas fa-coins"></i> My Contributions</h2>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php foreach ($hands as $hand): ?>
        <?php 
        $balance = ($hand['default_amount'] ?? 0) - $hand['total_contributed'];
        $is_overdue = $hand['deadline_date'] && strtotime($hand['deadline_date']) < time() && $balance > 0;
        $is_soon = $hand['deadline_date'] && !$is_overdue && (strtotime($hand['deadline_date']) - time()) < (3 * 86400);
        ?>
        <div class="hand-section">
            <div class="hand-header">
                <h3>
                    <i class="fas fa-hand-holding"></i>
                    <?php echo htmlspecialchars($hand['hand_number']); ?>
                </h3>
                <span style="background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 20px;">
                    <?php echo htmlspecialchars($hand['hand_type_name']); ?>
                </span>
            </div>
            <div class="hand-body">
                <div class="payment-summary">
                    <div class="payment-item">
                        <div class="label">Expected Amount</div>
                        <div class="value"><?php echo formatCurrency($hand['default_amount'] ?? 0); ?></div>
                    </div>
                    <div class="payment-item">
                        <div class="label">Total Paid</div>
                        <div class="value success"><?php echo formatCurrency($hand['total_contributed']); ?></div>
                    </div>
                    <div class="payment-item">
                        <div class="label">Balance</div>
                        <div class="value <?php echo $balance > 0 ? 'danger' : 'success'; ?>">
                            <?php echo formatCurrency(max(0, $balance)); ?>
                        </div>
                    </div>
                    <div class="payment-item">
                        <div class="label">Deadline</div>
                        <div class="value">
                            <?php if ($hand['deadline_date']): ?>
                                <?php echo formatDate($hand['deadline_date']); ?>
                                <span class="deadline-badge <?php echo $is_overdue ? 'deadline-overdue' : ($is_soon ? 'deadline-soon' : 'deadline-ok'); ?>">
                                    <?php echo $is_overdue ? 'Overdue!' : ($is_soon ? 'Due Soon!' : 'On Track'); ?>
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($hand['pending_proofs'])): ?>
                <div class="alert" style="background: #fff3cd; color: #856404; margin-bottom: 15px;">
                    <i class="fas fa-clock"></i> You have <?php echo count($hand['pending_proofs']); ?> pending payment proof(s) awaiting admin approval.
                </div>
                <?php endif; ?>
                
                <?php if ($balance > 0): ?>
                
                <!-- Payment Options Tabs -->
                <div class="payment-tabs" style="display: flex; gap: 10px; margin-bottom: 15px;">
                    <button type="button" class="payment-tab active" onclick="showPaymentTab('upload')" style="padding: 10px 20px; border: 2px solid #667eea; background: #667eea; color: white; border-radius: 8px; cursor: pointer; flex: 1;">
                        <i class="fas fa-upload"></i> Upload Proof
                    </button>
                    <button type="button" class="payment-tab" onclick="showPaymentTab('mobile')" style="padding: 10px 20px; border: 2px solid #28a745; background: white; color: #28a745; border-radius: 8px; cursor: pointer; flex: 1;">
                        <i class="fas fa-mobile-alt"></i> Mobile Money
                    </button>
                    <button type="button" class="payment-tab" onclick="showPaymentTab('bank')" style="padding: 10px 20px; border: 2px solid #dc3545; background: white; color: #dc3545; border-radius: 8px; cursor: pointer; flex: 1;">
                        <i class="fas fa-university"></i> Bank Transfer
                    </button>
                </div>
                
                <!-- Upload Proof Form -->
                <div id="uploadForm" class="upload-section">
                    <h4><i class="fas fa-upload"></i> Upload Proof of Payment</h4>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="hand_id" value="<?php echo $hand['id']; ?>">
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                            <input type="number" name="amount" placeholder="Amount (FCFA)" required min="1" value="<?php echo $hand['default_amount'] ?? ''; ?>">
                            <select name="payment_method" required style="padding: 12px; border: 2px solid #e9ecef; border-radius: 8px;">
                                <option value="Cash">Cash</option>
                                <option value="Mobile Money">Mobile Money</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Other">Other</option>
                            </select>
                            <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <input type="text" name="reference_number" placeholder="Transaction/Reference ID (optional)" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px;">
                        </div>
                        <div style="margin-bottom: 10px;">
                            <input type="file" name="proof_image" accept="image/*,.pdf" required style="width: 100%; padding: 10px; border: 2px dashed #ccc; border-radius: 8px;">
                        </div>
                        <button type="submit" name="submit_proof" class="btn-upload">
                            <i class="fas fa-paper-plane"></i> Submit for Approval
                        </button>
                    </form>
                </div>
                
                <!-- Mobile Money Form -->
                <div id="mobileForm" class="upload-section" style="display: none; border: 2px solid #28a745;">
                    <h4 style="color: #28a745;"><i class="fas fa-mobile-alt"></i> Pay via Mobile Money</h4>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <p style="margin: 0 0 10px 0;"><strong>Send money to:</strong></p>
                        <p style="margin: 0;"><i class="fas fa-phone"></i> <strong>+237 6XX XXX XXX</strong> (Orange Money)</p>
                        <p style="margin: 5px 0 0 0;"><i class="fas fa-phone"></i> <strong>+237 6XX XXX XXX</strong> (MTN MoMo)</p>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="hand_id" value="<?php echo $hand['id']; ?>">
                        <input type="hidden" name="amount" value="<?php echo $hand['default_amount'] ?? ''; ?>">
                        <div style="margin-bottom: 10px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Select Network</label>
                            <select name="online_payment_method" required style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px;">
                                <option value="Orange Money">Orange Money</option>
                                <option value="MTN Mobile Money">MTN Mobile Money</option>
                                <option value="Express Union">Express Union</option>
                            </select>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Transaction ID *</label>
                            <input type="text" name="transaction_id" placeholder="Enter the MTCN or Transaction ID" required style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px;">
                        </div>
                        <p style="font-size: 12px; color: #666; margin-bottom: 10px;">
                            <i class="fas fa-info-circle"></i> After sending money, enter the transaction ID you received.
                        </p>
                        <button type="submit" name="submit_online_payment" class="btn-upload" style="background: #28a745;">
                            <i class="fas fa-paper-plane"></i> Submit Payment
                        </button>
                    </form>
                </div>
                
                <!-- Bank Transfer Form -->
                <div id="bankForm" class="upload-section" style="display: none; border: 2px solid #dc3545;">
                    <h4 style="color: #dc3545;"><i class="fas fa-university"></i> Pay via Bank Transfer</h4>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <p style="margin: 0 0 10px 0;"><strong>Bank Details:</strong></p>
                        <p style="margin: 0;"><strong>Bank Name:</strong> Commercial Bank</p>
                        <p style="margin: 5px 0;"><strong>Account Name:</strong> ONIX Njangi Group</p>
                        <p style="margin: 5px 0;"><strong>Account Number:</strong> 1234567890</p>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="hand_id" value="<?php echo $hand['id']; ?>">
                        <input type="hidden" name="amount" value="<?php echo $hand['default_amount'] ?? ''; ?>">
                        <div style="margin-bottom: 10px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Transfer Reference/ID *</label>
                            <input type="text" name="transaction_id" placeholder="Enter the transfer reference from your bank" required style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px;">
                        </div>
                        <p style="font-size: 12px; color: #666; margin-bottom: 10px;">
                            <i class="fas fa-info-circle"></i> After making the transfer, enter the reference number you received from the bank.
                        </p>
                        <button type="submit" name="submit_online_payment" class="btn-upload" style="background: #dc3545;">
                            <i class="fas fa-paper-plane"></i> Submit Payment
                        </button>
                    </form>
                </div>
                
                <script>
                function showPaymentTab(tab) {
                    document.getElementById('uploadForm').style.display = 'none';
                    document.getElementById('mobileForm').style.display = 'none';
                    document.getElementById('bankForm').style.display = 'none';
                    
                    if (tab === 'upload') {
                        document.getElementById('uploadForm').style.display = 'block';
                    } else if (tab === 'mobile') {
                        document.getElementById('mobileForm').style.display = 'block';
                    } else if (tab === 'bank') {
                        document.getElementById('bankForm').style.display = 'block';
                    }
                    
                    // Update button styles
                    document.querySelectorAll('.payment-tab').forEach(function(btn) {
                        btn.style.background = 'white';
                        btn.style.color = btn.style.borderColor.includes('667eea') ? '#667eea' : (btn.style.borderColor.includes('28a745') ? '#28a745' : '#dc3545');
                    });
                    event.target.style.background = event.target.style.borderColor.includes('667eea') ? '#667eea' : (event.target.style.borderColor.includes('28a745') ? '#28a745' : '#dc3545');
                    event.target.style.color = 'white';
                }
                </script>
                
                <?php else: ?>
                <div style="background: #d4edda; padding: 15px; border-radius: 8px; text-align: center; color: #155724;">
                    <i class="fas fa-check-circle"></i> Payment Complete! Thank you.
                </div>
                <?php endif; ?>
                
                <div class="contribution-history">
                    <h4>Contribution History</h4>
                    <?php if (empty($hand['contributions'])): ?>
                    <p style="color: #666;">No contributions yet.</p>
                    <?php else: ?>
                    <?php foreach ($hand['contributions'] as $c): ?>
                    <div class="contribution-item">
                        <div>
                            <span class="amount"><?php echo formatCurrency($c['amount']); ?></span>
                            <span class="date"><?php echo formatDate($c['contribution_date']); ?></span>
                        </div>
                        <div>
                            <?php if ($c['payment_method']): ?>
                            <span class="status"><?php echo htmlspecialchars($c['payment_method']); ?></span>
                            <?php endif; ?>
                            <span class="pending-badge"><i class="fas fa-check"></i> Confirmed</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($hands)): ?>
        <div style="background: white; padding: 40px; border-radius: 12px; text-align: center; color: #666;">
            <i class="fas fa-hand-holding fa-4x" style="color: #ddd; margin-bottom: 20px;"></i>
            <h3>No Hands Found</h3>
            <p>You don't have any hands yet. Contact admin to open a hand.</p>
        </div>
        <?php endif; ?>
    </div>
    <script>
function toggleNotifications() {
    var panel = document.getElementById('notificationPanel');
    panel.classList.toggle('show');
    if (panel.classList.contains('show')) { loadMemberNotifications(); }
}
document.addEventListener('click', function(e) {
    var panel = document.getElementById('notificationPanel');
    var btn = document.querySelector('.notification-btn');
    if (panel && btn && !panel.contains(e.target) && !btn.contains(e.target)) {
        panel.classList.remove('show');
    }
});
function loadMemberNotifications() {
    fetch('../api/notifications.php?action=get_member&member_id=<?php echo $member_id; ?>')
        .then(response => response.json())
        .then(data => {
            var list = document.getElementById('notificationList');
            if (data.success && data.data.length > 0) {
                list.innerHTML = data.data.map(item => {
                    var iconClass = item.type === 'group_chat' ? 'chat' : (item.type === 'success' ? 'success' : (item.type === 'danger' ? 'danger' : 'info'));
                    var icon = item.type === 'group_chat' ? 'fa-comment' : (item.type === 'success' ? 'fa-check-circle' : (item.type === 'danger' ? 'fa-times-circle' : 'fa-bell'));
                    var unread = item.is_read == 0 ? 'unread' : '';
                    var title = item.title || 'Notification';
                    var desc = item.message || '';
                    return '<div class="notification-item ' + unread + '" onclick="window.location.href=\'chat.php\'"><div class="notification-icon ' + iconClass + '"><i class="fas ' + icon + '"></i></div><div class="notification-content"><div class="notification-title">' + title + '</div><div class="notification-desc">' + desc + '</div><div class="notification-time">' + timeAgo(item.created_at) + '</div></div></div>';
                }).join('');
            } else {
                list.innerHTML = '<div class="notification-empty"><i class="fas fa-check-circle"></i><p>No notifications</p></div>';
            }
        });
}
function timeAgo(dateString) {
    var date = new Date(dateString);
    var now = new Date();
    var diff = (now - date) / 1000;
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + ' min ago';
    if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
    if (diff < 604800) return Math.floor(diff / 86400) + ' days ago';
    return date.toLocaleDateString();
}
</script>
</body>
</html>