<?php
require_once 'config.php';
requireMemberLogin();

require_once '../config/database.php';
$conn = getDbConnection();

// Auto-complete cycles that have passed their end date
checkAndCompleteCycles($conn);

$member_id = getMemberId();

$sql = "SELECT COUNT(*) as count FROM member_notifications WHERE member_id = '$member_id' AND is_read = 0";
$result = mysqli_query($conn, $sql);
$unread_notifications = mysqli_fetch_assoc($result)['count'] ?? 0;

// Get active cycle
$sql = "SELECT * FROM payout_cycles WHERE status IN ('active', 'draft') ORDER BY created_at DESC LIMIT 1";
$result = mysqli_query($conn, $sql);
$active_cycle = mysqli_fetch_assoc($result) ?? null;

// Get all hands in cycle with member info
$cycle_members = [];
$my_position = null;
$my_info = null;

if ($active_cycle) {
    $sql = "SELECT m.id, m.first_name, m.surname, m.phone, m.member_number,
            COALESCE(ht.default_amount, h.amount, 0) as expected,
            COALESCE((SELECT SUM(amount) FROM contributions WHERE hand_id = h.id), 0) as paid,
            ch.position_order as position,
            pd.deadline_date,
            h.received_at, h.payout_status
            FROM cycle_hands ch
            JOIN hands h ON ch.hand_id = h.id
            JOIN members m ON h.member_id = m.id
            JOIN hand_types ht ON h.hand_type_id = ht.id
            LEFT JOIN payment_deadlines pd ON pd.hand_id = h.id
            WHERE ch.cycle_id = '" . $active_cycle['id'] . "'
            ORDER BY ch.position_order ASC";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        // Status priority: received > paid > partial > pending
        if ($row['received_at'] || $row['payout_status'] == 'paid') {
            $row['status'] = 'received';
        } elseif ($row['paid'] >= $row['expected']) {
            $row['status'] = 'paid';
        } elseif ($row['paid'] > 0) {
            $row['status'] = 'partial';
        } else {
            $row['status'] = 'pending';
        }
        $row['balance'] = $row['expected'] - $row['paid'];
        $cycle_members[] = $row;
        
        if ($row['id'] == $member_id) {
            $my_position = $row['position'];
            $my_info = $row;
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
    <title>Payout Schedule - Member Portal</title>
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
        .member-content {
            padding: 25px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .position-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            text-align: center;
        }
        .position-card h2 { margin: 0 0 10px 0; font-size: 18px; opacity: 0.9; }
        .position-number {
            font-size: 80px;
            font-weight: 700;
            margin: 20px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        .position-card .info {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 20px;
        }
        .position-card .info-item {
            text-align: center;
        }
        .position-card .info-item .value {
            font-size: 24px;
            font-weight: 600;
        }
        .position-card .info-item .label {
            font-size: 12px;
            opacity: 0.8;
        }
        .cycle-info {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .cycle-info h3 { margin: 0 0 15px 0; }
        .cycle-dates {
            display: flex;
            gap: 30px;
        }
        .cycle-dates .date-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .cycle-dates .label { font-size: 12px; color: #666; }
        .cycle-dates .value { font-weight: 600; }
        .schedule-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .schedule-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 2px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .schedule-header h3 { margin: 0; }
        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f9fa;
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
            position: sticky;
            top: 0;
        }
        tr:hover { background: #f8f9fa; }
        .position-cell {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
        }
        .position-current {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        .position-mine {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: 3px solid #ffc107;
        }
        .position-paid {
            background: #e9ecef;
            color: #6c757d;
        }
        .position-default {
            background: #f8f9fa;
            color: #333;
        }
        .member-name { font-weight: 600; }
        .member-id { font-size: 11px; color: #999; }
        .amount-cell { text-align: right; }
        .amount-expected { color: #333; }
        .amount-paid { color: #28a745; }
        .amount-balance { color: #dc3545; }
        .status-badge {
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-paid { background: #d4edda; color: #155724; }
        .status-partial { background: #fff3cd; color: #856404; }
        .status-pending { background: #f8d7da; color: #721c24; }
        .status-current { background: #d1ecf1; color: #0c5460; }
        .highlight-row { background: #e8f4fd !important; }
        .highlight-row td { font-weight: 600; }
    </style>
</head>
<body>
    <header class="member-header">
        <div>
            <h2><i class="fas fa-hand-holding-heart"></i> ONIX Njangi</h2>
        </div>
        <nav class="member-nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="contributions.php"><i class="fas fa-coins"></i> Contributions</a>
            <a href="payout_schedule.php" class="active"><i class="fas fa-calendar-alt"></i> Payout Schedule</a>
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
        <?php if ($active_cycle): ?>
        
        <div class="position-card">
            <h2>Your Position in This Cycle</h2>
            <div class="position-number">#<?php echo $my_position ?? 'TBD'; ?></div>
            <div class="info">
                <div class="info-item">
                    <div class="value"><?php echo count($cycle_members); ?></div>
                    <div class="label">Total Members</div>
                </div>
                <div class="info-item">
                    <div class="value"><?php echo formatCurrency($my_info['expected'] ?? 0); ?></div>
                    <div class="label">Your Expected</div>
                </div>
                <div class="info-item">
                    <div class="value"><?php echo formatCurrency($my_info['paid'] ?? 0); ?></div>
                    <div class="label">You Paid</div>
                </div>
                <div class="info-item">
                    <div class="value"><?php echo formatCurrency($my_info['balance'] ?? 0); ?></div>
                    <div class="label">Balance</div>
                </div>
            </div>
        </div>
        
        <div class="cycle-info">
            <h3><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($active_cycle['cycle_name']); ?></h3>
            <div class="cycle-dates">
                <div class="date-item">
                    <i class="fas fa-play" style="color: #28a745;"></i>
                    <div>
                        <div class="label">Start Date</div>
                        <div class="value"><?php echo formatDate($active_cycle['start_date']); ?></div>
                    </div>
                </div>
                <div class="date-item">
                    <i class="fas fa-stop" style="color: #dc3545;"></i>
                    <div>
                        <div class="label">End Date</div>
                        <div class="value"><?php echo formatDate($active_cycle['end_date']); ?></div>
                    </div>
                </div>
                <div class="date-item">
                    <i class="fas fa-users" style="color: #667eea;"></i>
                    <div>
                        <div class="label">Status</div>
                        <div class="value"><?php echo ucfirst($active_cycle['status']); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="schedule-table">
            <div class="schedule-header">
                <h3><i class="fas fa-list-ol"></i> Full Payout Schedule</h3>
                <div>
                    <span class="status-badge status-paid"><i class="fas fa-check"></i> Paid</span>
                    <span class="status-badge status-current"><i class="fas fa-star"></i> Current</span>
                    <span class="status-badge status-pending"><i class="fas fa-clock"></i> Pending</span>
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Position</th>
                            <th>Member</th>
                            <th>Phone</th>
                            <th style="text-align: right;">Expected</th>
                            <th style="text-align: right;">Paid</th>
                            <th style="text-align: right;">Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $row_num = 1;
                        $found_me = false;
                        $current_position = null;
                        foreach ($cycle_members as $m): 
                            // Determine if this is the current recipient
                            $is_current = false;
                            if ($current_position === null && $m['status'] != 'paid') {
                                $is_current = true;
                                $current_position = $m['position'];
                            }
                            
                            $is_me = ($m['id'] == $member_id);
                        ?>
                        <tr class="<?php echo $is_me ? 'highlight-row' : ''; ?>">
                            <td><?php echo $row_num++; ?></td>
                            <td>
                                <div class="position-cell <?php echo $is_current ? 'position-current' : ($is_me ? 'position-mine' : ($m['status'] == 'paid' ? 'position-paid' : 'position-default')); ?>">
                                    <?php echo $m['position']; ?>
                                </div>
                            </td>
                            <td>
                                <div class="member-name">
                                    <?php echo htmlspecialchars($m['first_name'] . ' ' . $m['surname']); ?>
                                    <?php if ($is_me): ?>
                                    <span style="background: #667eea; color: white; padding: 2px 8px; border-radius: 10px; font-size: 10px; margin-left: 5px;">YOU</span>
                                    <?php endif; ?>
                                    <?php if ($is_current): ?>
                                    <span style="background: #28a745; color: white; padding: 2px 8px; border-radius: 10px; font-size: 10px; margin-left: 5px;"><i class="fas fa-star"></i></span>
                                    <?php endif; ?>
                                </div>
                                <div class="member-id"><?php echo htmlspecialchars($m['member_number']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($m['phone'] ?? '-'); ?></td>
                            <td class="amount-cell amount-expected"><?php echo formatCurrency($m['expected']); ?></td>
                            <td class="amount-cell amount-paid"><?php echo formatCurrency($m['paid']); ?></td>
                            <td class="amount-cell amount-balance"><?php echo formatCurrency(max(0, $m['balance'])); ?></td>
                            <td>
                                <?php if ($m['status'] == 'received'): ?>
                                <span class="status-badge" style="background: #d4edda; color: #155724;"><i class="fas fa-gift"></i> Received</span>
                                <?php elseif ($is_current): ?>
                                <span class="status-badge status-current"><i class="fas fa-star"></i> Receiving Now</span>
                                <?php elseif ($m['status'] == 'paid'): ?>
                                <span class="status-badge status-paid"><i class="fas fa-check"></i> Paid</span>
                                <?php elseif ($m['status'] == 'partial'): ?>
                                <span class="status-badge status-partial"><i class="fas fa-hourglass-half"></i> Partial</span>
                                <?php else: ?>
                                <span class="status-badge status-pending"><i class="fas fa-clock"></i> Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php else: ?>
        
        <div style="background: white; padding: 60px; border-radius: 12px; text-align: center; color: #666;">
            <i class="fas fa-calendar-times fa-5x" style="color: #ddd; margin-bottom: 20px;"></i>
            <h2>No Active Cycle</h2>
            <p>There is no active payout cycle at the moment. Check back later.</p>
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