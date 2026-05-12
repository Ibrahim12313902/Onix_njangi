<style>
    .notification-dropdown {
        position: relative;
        display: inline-block;
    }
    .notification-btn {
        background: none;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
        padding: 8px 15px;
        border-radius: 8px;
        transition: background 0.3s;
        position: relative;
    }
    .notification-btn:hover {
        background: rgba(255,255,255,0.2);
    }
    .notification-badge {
        position: absolute;
        top: 0;
        right: 0;
        background: #dc3545;
        color: white;
        font-size: 11px;
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
        width: 380px;
        max-height: 500px;
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
        padding: 15px 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .notification-header h3 {
        margin: 0;
        font-size: 16px;
        color: #333;
    }
    .notification-header .mark-all {
        font-size: 12px;
        color: #667eea;
        cursor: pointer;
        text-decoration: none;
    }
    .notification-item {
        padding: 15px 20px;
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
    .notification-item.unread:hover {
        background: #d4edfa;
    }
    .notification-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .notification-icon.hand { background: #fff3cd; color: #ffc107; }
    .notification-icon.chat { background: #e8f4fd; color: #667eea; }
    .notification-icon.info { background: #d4edda; color: #28a745; }
    .notification-icon.danger { background: #f8d7da; color: #dc3545; }
    .notification-content {
        flex: 1;
        min-width: 0;
    }
    .notification-title {
        font-weight: 600;
        font-size: 14px;
        color: #333;
        margin-bottom: 4px;
    }
    .notification-desc {
        font-size: 13px;
        color: #666;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .notification-time {
        font-size: 11px;
        color: #999;
    }
    .notification-empty {
        padding: 40px 20px;
        text-align: center;
        color: #999;
    }
    .notification-empty i {
        font-size: 40px;
        color: #ddd;
        margin-bottom: 10px;
    }
</style>

<header class="main-header">
    <div class="header-left">
        <button class="mobile-menu-btn" id="mobileMenuBtn">
            <i class="fas fa-bars"></i>
        </button>
        <h1><i class="fas fa-hand-holding-heart"></i> <?php echo SITE_NAME; ?></h1>
    </div>
    
    <div class="header-right">
        <div class="notification-dropdown">
            <button class="notification-btn" onclick="toggleNotifications()">
                <i class="fas fa-bell"></i>
                <?php if (isset($total_notifications) && $total_notifications > 0): ?>
                <span class="notification-badge"><?php echo $total_notifications; ?></span>
                <?php endif; ?>
            </button>
            <div class="notification-panel" id="notificationPanel">
                <div class="notification-header">
                    <h3><i class="fas fa-bell"></i> Notifications</h3>
                    <span class="mark-all" onclick="location.reload()">Refresh</span>
                </div>
                <div id="notificationList">
                    <div class="notification-empty">
                        <i class="fas fa-bell-slash"></i>
                        <p>Loading notifications...</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
        </div>
        <a href="<?php echo SITE_URL; ?>logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</header>

<script>
function toggleNotifications() {
    var panel = document.getElementById('notificationPanel');
    panel.classList.toggle('show');
    if (panel.classList.contains('show')) {
        loadNotifications();
    }
}

// Close notification panel when clicking outside
document.addEventListener('click', function(e) {
    var panel = document.getElementById('notificationPanel');
    var btn = document.querySelector('.notification-btn');
    if (!panel.contains(e.target) && !btn.contains(e.target)) {
        panel.classList.remove('show');
    }
});

function loadNotifications() {
    fetch('../api/notifications.php?action=get_admin')
        .then(response => response.json())
        .then(data => {
            var list = document.getElementById('notificationList');
            if (data.success && data.data.length > 0) {
                list.innerHTML = data.data.map(item => {
                    var iconClass = item.type === 'hand_request' ? 'hand' : 'chat';
                    var icon = item.type === 'hand_request' ? 'fa-hand-holding' : 'fa-comment';
                    var time = timeAgo(item.created_at);
                    return '<div class="notification-item" onclick="location.href=\'' + (item.type === 'hand_request' ? 'hand_requests/' : 'group_chat/') + '\'">' +
                        '<div class="notification-icon ' + iconClass + '"><i class="fas ' + icon + '"></i></div>' +
                        '<div class="notification-content">' +
                        '<div class="notification-title">' + item.title + '</div>' +
                        '<div class="notification-desc">' + item.member_name + ' - ' + (item.hand_type_name || item.message) + '</div>' +
                        '<div class="notification-time">' + time + '</div>' +
                        '</div></div>';
                }).join('');
            } else {
                list.innerHTML = '<div class="notification-empty"><i class="fas fa-check-circle"></i><p>No new notifications</p></div>';
            }
        })
        .catch(error => {
            document.getElementById('notificationList').innerHTML = '<div class="notification-empty"><i class="fas fa-exclamation-triangle"></i><p>Error loading notifications</p></div>';
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
