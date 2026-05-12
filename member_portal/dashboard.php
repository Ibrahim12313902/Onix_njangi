<?php
require_once 'config.php';
requireMemberLogin();

require_once '../config/database.php';
$conn = getDbConnection();

// Auto-complete cycles that have passed their end date
checkAndCompleteCycles($conn);

$member_id = getMemberId();

// Get member details
$sql = "SELECT * FROM members WHERE id = '$member_id'";
$result = mysqli_query($conn, $sql);
$member = mysqli_fetch_assoc($result);

// Get total hands
$sql = "SELECT COUNT(*) as count FROM hands WHERE member_id = '$member_id'";
$result = mysqli_query($conn, $sql);
$total_hands = mysqli_fetch_assoc($result)['count'];

// Get active cycle
$sql = "SELECT pc.*, 
        (SELECT SUM(COALESCE(ht.default_amount, h.amount, 0)) FROM hands h 
         JOIN hand_types ht ON h.hand_type_id = ht.id 
         WHERE h.payout_cycle_id = pc.id) as total_expected,
        (SELECT SUM(c.amount) FROM contributions c 
         JOIN hands h ON c.hand_id = h.id 
         WHERE h.payout_cycle_id = pc.id) as total_collected
        FROM payout_cycles pc 
        WHERE pc.status IN ('active', 'draft') 
        ORDER BY pc.created_at DESC LIMIT 1";
$result = mysqli_query($conn, $sql);
$active_cycle = mysqli_fetch_assoc($result) ?? null;

// Get member's hands in active cycle with positions
$my_hands = [];
$my_position = null;
$next_payout = null;
$total_to_contribute = 0;

if ($active_cycle) {
    $sql = "SELECT h.*, ht.hand_type_name, ht.payment_period_days, ht.default_amount,
            ch.position_order, pd.deadline_date, h.received_at, h.payout_status
            FROM hands h
            JOIN hand_types ht ON h.hand_type_id = ht.id
            JOIN cycle_hands ch ON ch.hand_id = h.id AND ch.cycle_id = '" . $active_cycle['id'] . "'
            LEFT JOIN payment_deadlines pd ON pd.hand_id = h.id
            WHERE h.member_id = '$member_id'
            ORDER BY ch.position_order ASC";
    $result = mysqli_query($conn, $sql);
    while ($hand = mysqli_fetch_assoc($result)) {
        // Get contributions for this hand
        $contrib_sql = "SELECT SUM(amount) as total FROM contributions WHERE hand_id = '" . $hand['id'] . "'";
        $contrib_result = mysqli_query($conn, $contrib_sql);
        $hand['total_paid'] = mysqli_fetch_assoc($contrib_result)['total'] ?? 0;
        $hand['balance'] = ($hand['default_amount'] ?? 0) - $hand['total_paid'];
        $total_to_contribute += $hand['default_amount'] ?? 0;
        
        // Check if payout received
        $hand['has_received'] = ($hand['received_at'] || $hand['payout_status'] == 'paid');
        
        if ($hand['has_received']) {
            $hand['status_text'] = 'Received';
        } elseif ($hand['balance'] > 0) {
            $hand['status_text'] = 'Pending';
            if ($next_payout === null) {
                $next_payout = $hand;
            }
        } else {
            $hand['status_text'] = 'Ready';
        }
        
        $my_hands[] = $hand;
    }
    
    // Calculate member's position in cycle
    $sql = "SELECT h.member_id, ch.position_order
            FROM hands h
            JOIN cycle_hands ch ON ch.hand_id = h.id AND ch.cycle_id = '" . $active_cycle['id'] . "'
            WHERE h.member_id = '$member_id'
            ORDER BY ch.position_order ASC LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($pos = mysqli_fetch_assoc($result)) {
        $my_position = $pos['position_order'];
    }
}

// Get cycle stats
$cycle_stats = ['total_members' => 0, 'paid_count' => 0, 'total_collected' => 0];
if ($active_cycle) {
    $sql = "SELECT COUNT(*) as total_members FROM cycle_hands WHERE cycle_id = '" . $active_cycle['id'] . "'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $cycle_stats['total_members'] = $row['total_members'] ?? 0;
    
    $sql = "SELECT SUM(c.amount) as total 
            FROM contributions c 
            JOIN hands h ON c.hand_id = h.id
            JOIN cycle_hands ch ON ch.hand_id = h.id
            WHERE ch.cycle_id = '" . $active_cycle['id'] . "'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $cycle_stats['total_collected'] = $row['total'] ?? 0;
}

// Get notifications
$sql = "SELECT * FROM member_notifications WHERE member_id = '$member_id' ORDER BY created_at DESC LIMIT 10";
$notifications = mysqli_query($conn, $sql);
$sql = "SELECT COUNT(*) as count FROM member_notifications WHERE member_id = '$member_id' AND is_read = 0";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$unread_notifications = $row['count'] ?? 0;

// Get recent announcements
$sql = "SELECT * FROM announcements WHERE is_active = 1 ORDER BY created_at DESC LIMIT 3";
$announcements = mysqli_query($conn, $sql);

// Get cycle members with payment status for transparency
$cycle_members = [];
if ($active_cycle) {
    $sql = "SELECT m.id, m.first_name, m.surname, m.phone,
            COALESCE(ht.default_amount, h.amount, 0) as expected,
            COALESCE((SELECT SUM(amount) FROM contributions WHERE hand_id = h.id), 0) as paid,
            ch.position_order as position,
            h.received_at, h.payout_status
            FROM cycle_hands ch
            JOIN hands h ON ch.hand_id = h.id
            JOIN members m ON h.member_id = m.id
            JOIN hand_types ht ON h.hand_type_id = ht.id
            WHERE ch.cycle_id = '" . $active_cycle['id'] . "'
            ORDER BY ch.position_order ASC";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            if ($row['received_at'] || $row['payout_status'] == 'paid') {
                $row['status'] = 'received';
            } elseif ($row['paid'] >= $row['expected']) {
                $row['status'] = 'eligible';
            } elseif ($row['paid'] > 0) {
                $row['status'] = 'partial';
            } else {
                $row['status'] = 'pending';
            }
            $cycle_members[] = $row;
        }
    }
}

// Current payout recipient
$current_recipient = null;
if ($active_cycle && !empty($cycle_members)) {
    foreach ($cycle_members as $m) {
        if ($m['status'] != 'paid') {
            $current_recipient = $m;
            break;
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
    <title>Dashboard - Member Portal</title>
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
            max-width: 1400px;
            margin: 0 auto;
        }
        .welcome-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        .welcome-banner h2 { margin: 0 0 10px 0; }
        .welcome-banner p { margin: 0; opacity: 0.9; }
        .position-highlight {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 8px;
            margin-top: 15px;
            font-size: 18px;
        }
        .position-highlight strong { font-size: 24px; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        .stat-card .label { font-size: 12px; color: #666; text-transform: uppercase; }
        .stat-card .value { font-size: 24px; font-weight: 600; color: #333; margin-top: 5px; }
        .info-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 25px;
        }
        .info-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .info-card h3 {
            margin: 0 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .question-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        .question-box h3 { margin: 0 0 20px 0; }
        .questions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .question-item {
            background: rgba(255,255,255,0.15);
            padding: 15px;
            border-radius: 8px;
        }
        .question-item h4 { margin: 0 0 10px 0; font-size: 14px; }
        .question-item p { margin: 0; font-size: 16px; font-weight: 600; }
        .progress-section { margin-top: 15px; }
        .progress-bar-custom {
            height: 10px;
            background: rgba(255,255,255,0.3);
            border-radius: 5px;
            overflow: hidden;
        }
        .progress-fill-custom {
            height: 100%;
            background: white;
            border-radius: 5px;
        }
        .announcement-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .announcement-item:last-child { border-bottom: none; }
        .announcement-item h4 { margin: 0 0 5px 0; font-size: 14px; }
        .announcement-item p { margin: 0; color: #666; font-size: 13px; }
        .announcement-item .date { font-size: 11px; color: #999; }
        .member-list-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .member-list-item:last-child { border-bottom: none; }
        .member-list-item .position { 
            width: 35px; height: 35px; 
            border-radius: 50%; 
            background: #667eea; 
            color: white;
            display: flex; align-items: center; justify-content: center;
            font-weight: 600; margin-right: 15px;
        }
        .member-list-item .info { flex: 1; }
        .member-list-item .name { font-weight: 600; }
        .member-list-item .amount { font-size: 13px; color: #666; }
        .member-list-item .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-paid { background: #d4edda; color: #155724; }
        .status-received { background: #28a745; color: white; }
        .status-eligible { background: #17a2b8; color: white; }
        .status-partial { background: #fff3cd; color: #856404; }
        .status-pending { background: #f8d7da; color: #721c24; }
        .status-pending { background: #f8d7da; color: #721c24; }
        .notification-badge {
            position: relative;
        }
        .notification-badge .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 50%;
        }
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
        .notification-btn:hover {
            background: rgba(255,255,255,0.2);
        }
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
        .notification-panel.show {
            display: block;
        }
        .notification-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .notification-header h3 {
            margin: 0;
            font-size: 15px;
            color: #333;
        }
        .notification-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            gap: 12px;
            transition: background 0.3s;
            cursor: pointer;
        }
        .notification-item:hover {
            background: #f8f9fa;
        }
        .notification-item.unread {
            background: #e8f4fd;
        }
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
        .notification-icon.warning { background: #fff3cd; color: #ffc107; }
        .notification-icon.info { background: #e8f4fd; color: #667eea; }
        .notification-icon.chat { background: #f3e5f5; color: #9c27b0; }
        .notification-content {
            flex: 1;
            min-width: 0;
        }
        .notification-title {
            font-weight: 600;
            font-size: 13px;
            color: #333;
            margin-bottom: 3px;
        }
        .notification-desc {
            font-size: 12px;
            color: #666;
            margin-bottom: 3px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .notification-time {
            font-size: 11px;
            color: #999;
        }
        .notification-empty {
            padding: 30px 15px;
            text-align: center;
            color: #999;
        }
        .nav-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
    </style>
</head>
<body>
    <header class="member-header">
        <div>
            <h2><i class="fas fa-hand-holding-heart"></i> ONIX Njangi</h2>
        </div>
        <nav class="member-nav">
            <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
            <a href="contributions.php"><i class="fas fa-coins"></i> Contributions</a>
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
                        <div class="notification-empty">
                            <i class="fas fa-bell-slash"></i>
                            <p>Loading...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <div class="member-content">
        <div class="welcome-banner">
            <h2>Welcome back, <?php echo htmlspecialchars($member['first_name']); ?>!</h2>
            <p>Member Number: <?php echo htmlspecialchars($member['member_number']); ?></p>
            <?php if ($my_position && $active_cycle): ?>
            <div class="position-highlight">
                <i class="fas fa-star"></i> You are Position <strong>#<?php echo $my_position; ?></strong> out of <?php echo $cycle_stats['total_members']; ?> members
            </div>
            <?php endif; ?>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon" style="background: #e8f4fd; color: #667eea;">
                    <i class="fas fa-hand-holding"></i>
                </div>
                <div class="label">My Hands</div>
                <div class="value"><?php echo $total_hands; ?></div>
            </div>
            <div class="stat-card">
                <div class="icon" style="background: #fff3cd; color: #ffc107;">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="label">To Contribute</div>
                <div class="value"><?php echo formatCurrency($total_to_contribute); ?></div>
            </div>
            <div class="stat-card">
                <div class="icon" style="background: #d4edda; color: #28a745;">
                    <i class="fas fa-gift"></i>
                </div>
                <div class="label">My Position</div>
                <div class="value"><?php echo $my_position ? '#' . $my_position : 'N/A'; ?></div>
            </div>
            <div class="stat-card notification-badge">
                <div class="icon" style="background: #f8d7da; color: #dc3545;">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="label">Notifications</div>
                <div class="value"><?php echo $unread_notifications; ?></div>
                <?php if ($unread_notifications > 0): ?>
                <span class="badge"><?php echo $unread_notifications; ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($active_cycle): ?>
        <div class="question-box">
            <h3><i class="fas fa-question-circle"></i> Quick Answers</h3>
            <div class="questions-grid">
                <div class="question-item">
                    <h4>How much should I pay?</h4>
                    <p><?php echo formatCurrency($total_to_contribute); ?></p>
                    <small>This cycle</small>
                </div>
                <div class="question-item">
                    <h4>When will I receive money?</h4>
                    <p>Position #<?php echo $my_position ?? 'TBD'; ?></p>
                    <small><?php echo $active_cycle['cycle_name']; ?></small>
                </div>
                <div class="question-item">
                    <h4>Current Cycle Status</h4>
                    <p><?php echo ucfirst($active_cycle['status']); ?></p>
                    <div class="progress-section">
                        <div class="progress-bar-custom">
                            <div class="progress-fill-custom" style="width: <?php echo ($active_cycle['total_expected'] ?? 0) > 0 ? round(($active_cycle['total_collected'] / $active_cycle['total_expected']) * 100) : 0; ?>%"></div>
                        </div>
                        <small><?php echo ($active_cycle['total_expected'] ?? 0) > 0 ? round(($active_cycle['total_collected'] / $active_cycle['total_expected']) * 100) : 0; ?>% collected</small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="info-cards">
            <div class="info-card">
                <h3><i class="fas fa-trophy" style="color: #667eea;"></i> Current Payout Recipient</h3>
                <?php if ($current_recipient): ?>
                <div class="member-list-item" style="background: #e8f5e9; border-radius: 8px;">
                    <div class="position" style="background: #28a745;">
                        #<?php echo $current_recipient['position']; ?>
                    </div>
                    <div class="info">
                        <div class="name"><?php echo htmlspecialchars($current_recipient['first_name'] . ' ' . $current_recipient['surname']); ?></div>
                        <div class="amount">Expected: <?php echo formatCurrency($current_recipient['expected']); ?> | Paid: <?php echo formatCurrency($current_recipient['paid']); ?></div>
                    </div>
                </div>
                <?php else: ?>
                <p style="color: #666;">All members have been paid!</p>
                <?php endif; ?>
            </div>
            
            <div class="info-card">
                <h3><i class="fas fa-bullhorn" style="color: #ffc107;"></i> Announcements</h3>
                <?php if (mysqli_num_rows($announcements) > 0): ?>
                <?php while ($ann = mysqli_fetch_assoc($announcements)): ?>
                <div class="announcement-item">
                    <h4><?php echo htmlspecialchars($ann['title']); ?></h4>
                    <p><?php echo htmlspecialchars(substr($ann['content'], 0, 100)); ?>...</p>
                    <span class="date"><?php echo timeAgo($ann['created_at']); ?></span>
                </div>
                <?php endwhile; ?>
                <?php else: ?>
                <p style="color: #666;">No announcements yet.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="info-card">
            <h3><i class="fas fa-chart-bar" style="color: #667eea;"></i> Cycle Progress - Transparency View</h3>
            <?php if (!empty($cycle_members)): ?>
            <?php 
            $total_expected = 0;
            foreach ($cycle_members as $m) { $total_expected += $m['expected']; }
            ?>
            <div style="margin-bottom: 15px; display: flex; gap: 20px;">
                <span><strong>Total Collected:</strong> <?php echo formatCurrency($cycle_stats['total_collected']); ?></span>
                <span><strong>Expected:</strong> <?php echo formatCurrency($total_expected); ?></span>
                <span><strong>Members:</strong> <?php echo count($cycle_members); ?></span>
            </div>
            <div style="max-height: 300px; overflow-y: auto;">
                <?php foreach ($cycle_members as $m): ?>
                <div class="member-list-item">
                    <div class="position">#<?php echo $m['position']; ?></div>
                    <div class="info">
                        <div class="name"><?php echo htmlspecialchars($m['first_name'] . ' ' . $m['surname']); ?></div>
                        <div class="amount">
                            Paid: <?php echo formatCurrency($m['paid']); ?> / <?php echo formatCurrency($m['expected']); ?>
                            <span style="color: <?php echo $m['expected'] > 0 ? round(($m['paid']/$m['expected'])*100) : 0; ?> >= 100 ? '#28a745' : '#ffc107'; ?>">
                                (<?php echo $m['expected'] > 0 ? round(($m['paid']/$m['expected'])*100) : 0; ?>%)
                            </span>
                        </div>
                    </div>
                    <span class="status-badge status-<?php echo $m['status']; ?>">
                        <?php 
                        if ($m['status'] == 'received') echo '<i class="fas fa-gift"></i> Received';
                        elseif ($m['status'] == 'eligible') echo '<i class="fas fa-check"></i> Eligible';
                        elseif ($m['status'] == 'paid') echo 'Paid';
                        elseif ($m['status'] == 'partial') echo 'Partial';
                        else echo 'Pending';
                        ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p style="color: #666;">No active cycle.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
function toggleNotifications() {
    var panel = document.getElementById('notificationPanel');
    panel.classList.toggle('show');
    if (panel.classList.contains('show')) {
        loadMemberNotifications();
    }
}

document.addEventListener('click', function(e) {
    var panel = document.getElementById('notificationPanel');
    var btn = document.querySelector('.notification-btn');
    if (panel && btn && !panel.contains(e.target) && !btn.contains(e.target)) {
        panel.classList.remove('show');
    }
});

function loadMemberNotifications() {
    var memberId = <?php echo $member_id; ?>;
    fetch('../api/notifications.php?action=get_member&member_id=' + memberId)
        .then(response => response.json())
        .then(data => {
            var list = document.getElementById('notificationList');
            if (data.success && data.data.length > 0) {
                list.innerHTML = data.data.map(item => {
                    var iconClass = 'info';
                    var icon = 'fa-bell';
                    if (item.type === 'group_chat') { iconClass = 'chat'; icon = 'fa-comment'; }
                    else if (item.type === 'success') { iconClass = 'success'; icon = 'fa-check-circle'; }
                    else if (item.type === 'danger') { iconClass = 'danger'; icon = 'fa-times-circle'; }
                    else if (item.type === 'warning') { iconClass = 'warning'; icon = 'fa-exclamation-circle'; }
                    
                    var unread = item.is_read == 0 ? 'unread' : '';
                    var time = timeAgo(item.created_at);
                    var title = item.title || 'Notification';
                    var desc = item.message || item.message || '';
                    
                    return '<div class="notification-item ' + unread + '" onclick="window.location.href=\'chat.php\'">' +
                        '<div class="notification-icon ' + iconClass + '"><i class="fas ' + icon + '"></i></div>' +
                        '<div class="notification-content">' +
                        '<div class="notification-title">' + title + '</div>' +
                        '<div class="notification-desc">' + desc + '</div>' +
                        '<div class="notification-time">' + time + '</div>' +
                        '</div></div>';
                }).join('');
            } else {
                list.innerHTML = '<div class="notification-empty"><i class="fas fa-check-circle"></i><p>No notifications</p></div>';
            }
        })
        .catch(error => {
            document.getElementById('notificationList').innerHTML = '<div class="notification-empty"><i class="fas fa-exclamation-triangle"></i><p>Error loading</p></div>';
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