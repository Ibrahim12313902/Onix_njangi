<?php
require_once 'config.php';
requireMemberLogin();

require_once '../config/database.php';
$conn = getDbConnection();

$member_id = getMemberId();
$error = '';

$sql = "SELECT COUNT(*) as count FROM member_notifications WHERE member_id = '$member_id' AND is_read = 0";
$result = mysqli_query($conn, $sql);
$unread_notifications = mysqli_fetch_assoc($result)['count'] ?? 0;

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $message = mysqli_real_escape_string($conn, trim($_POST['message']));
    
    if (!empty($message)) {
        $sql = "INSERT INTO group_messages (member_id, message) VALUES ('$member_id', '$message')";
        if (mysqli_query($conn, $sql)) {
            // Refresh page to show new message
            header('Location: chat.php');
            exit();
        }
    }
}

// Get messages
$sql = "SELECT gm.*, 
        CASE 
            WHEN gm.is_admin_message = 1 THEN 'Admin'
            ELSE m.first_name
        END as first_name,
        CASE 
            WHEN gm.is_admin_message = 1 THEN ''
            ELSE m.surname
        END as surname,
        CASE 
            WHEN gm.is_admin_message = 1 THEN 'admin'
            ELSE m.member_number
        END as member_number
        FROM group_messages gm
        LEFT JOIN members m ON gm.member_id = m.id AND gm.is_admin_message = 0
        ORDER BY gm.created_at DESC
        LIMIT 100";
$messages_result = mysqli_query($conn, $sql);

// Get announcements
$sql = "SELECT * FROM announcements WHERE is_active = 1 ORDER BY created_at DESC LIMIT 5";
$announcements_result = mysqli_query($conn, $sql);

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Chat - Member Portal</title>
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
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 25px;
        }
        .chat-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            height: calc(100vh - 200px);
        }
        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
        }
        .chat-header h3 { margin: 0; display: flex; align-items: center; gap: 10px; }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }
        .message {
            margin-bottom: 15px;
            animation: fadeIn 0.3s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .message-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }
        .message-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        .message-avatar.me {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        .message-name {
            font-weight: 600;
            font-size: 13px;
        }
        .message-name.me { color: #28a745; }
        .message-time {
            font-size: 11px;
            color: #999;
        }
        .message-content {
            background: #f8f9fa;
            padding: 12px 15px;
            border-radius: 0 15px 15px 15px;
            margin-left: 45px;
            font-size: 14px;
            line-height: 1.5;
        }
        .message.mine .message-content {
            background: #e8f4fd;
            border-radius: 15px 0 15px 15px;
        }
        .chat-input {
            padding: 15px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }
        .chat-input input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 14px;
        }
        .chat-input input:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn-send {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 25px;
            cursor: pointer;
        }
        .btn-send:hover { opacity: 0.9; }
        .announcements-panel {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .announcements-header {
            background: #ffc107;
            color: #333;
            padding: 15px 20px;
            font-weight: 600;
        }
        .announcement-item {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }
        .announcement-item:last-child { border-bottom: none; }
        .announcement-item h4 {
            margin: 0 0 8px 0;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .announcement-item p {
            margin: 0;
            font-size: 13px;
            color: #666;
            line-height: 1.5;
        }
        .announcement-item .date {
            font-size: 11px;
            color: #999;
            margin-top: 8px;
        }
        .empty-chat {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .empty-chat i { font-size: 48px; margin-bottom: 15px; }
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
            <a href="payout_schedule.php"><i class="fas fa-calendar-alt"></i> Payout Schedule</a>
            <a href="my_hands.php"><i class="fas fa-hand-holding"></i> My Hands</a>
            <a href="chat.php" class="active"><i class="fas fa-comments"></i> Group Chat</a>
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
        <div class="chat-container">
            <div class="chat-header">
                <h3><i class="fas fa-comments"></i> Group Chat</h3>
            </div>
            <div class="chat-messages" id="chatMessages">
                <?php if (mysqli_num_rows($messages_result) > 0): ?>
                    <?php while ($msg = mysqli_fetch_assoc($messages_result)): ?>
                    <?php $is_me = ($msg['member_id'] == $member_id); ?>
                    <div class="message <?php echo $is_me ? 'mine' : ''; ?>">
                        <div class="message-header">
                            <div class="message-avatar <?php echo $is_me ? 'me' : ''; ?>">
                                <?php echo strtoupper(substr($msg['first_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <span class="message-name <?php echo $is_me ? 'me' : ''; ?>">
                                    <?php echo $is_me ? 'You' : htmlspecialchars($msg['first_name'] . ' ' . $msg['surname']); ?>
                                </span>
                                <span class="message-time"><?php echo timeAgo($msg['created_at']); ?></span>
                            </div>
                        </div>
                        <div class="message-content">
                            <?php echo htmlspecialchars($msg['message']); ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                <div class="empty-chat">
                    <i class="fas fa-comments"></i>
                    <p>No messages yet. Start the conversation!</p>
                </div>
                <?php endif; ?>
            </div>
            <form class="chat-input" method="POST">
                <input type="text" name="message" placeholder="Type your message..." required autocomplete="off">
                <button type="submit" name="send_message" class="btn-send">
                    <i class="fas fa-paper-plane"></i> Send
                </button>
            </form>
        </div>
        
        <div class="announcements-panel">
            <div class="announcements-header">
                <i class="fas fa-bullhorn"></i> Announcements
            </div>
            <?php if (mysqli_num_rows($announcements_result) > 0): ?>
                <?php while ($ann = mysqli_fetch_assoc($announcements_result)): ?>
                <div class="announcement-item">
                    <h4><i class="fas fa-star" style="color: #ffc107;"></i> <?php echo htmlspecialchars($ann['title']); ?></h4>
                    <p><?php echo htmlspecialchars($ann['content']); ?></p>
                    <div class="date"><?php echo timeAgo($ann['created_at']); ?></div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="announcement-item">
                    <p>No announcements yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Auto-scroll to bottom
        var chatMessages = document.getElementById('chatMessages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
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