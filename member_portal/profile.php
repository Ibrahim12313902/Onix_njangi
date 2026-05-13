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

// Get member details
$sql = "SELECT * FROM members WHERE id = '$member_id'";
$result = mysqli_query($conn, $sql);
$member = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
        $surname = mysqli_real_escape_string($conn, $_POST['surname']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        
        $sql = "UPDATE members SET first_name = '$first_name', surname = '$surname', phone = '$phone', email = '$email' WHERE id = '$member_id'";
        
        if (mysqli_query($conn, $sql)) {
            $_SESSION['member_name'] = $first_name . ' ' . $surname;
            $success = 'Profile updated successfully!';
            $member['first_name'] = $first_name;
            $member['surname'] = $surname;
            $member['phone'] = $phone;
            $member['email'] = $email;
        } else {
            $error = 'Error updating profile: ' . mysqli_error($conn);
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All password fields are required!';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match!';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters!';
        } else {
            // Verify current password
            if (password_verify($current_password, $member['password_hash']) || md5($current_password) == $member['password_hash']) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE members SET password_hash = '$new_hash' WHERE id = '$member_id'";
                
                if (mysqli_query($conn, $sql)) {
                    $success = 'Password changed successfully!';
                } else {
                    $error = 'Error changing password!';
                }
            } else {
                $error = 'Current password is incorrect!';
            }
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
    <title>Profile - Member Portal</title>
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
            max-width: 800px;
            margin: 0 auto;
        }
        .profile-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 15px;
        }
        .profile-header h2 { margin: 0; }
        .profile-header p { margin: 5px 0 0 0; opacity: 0.8; font-size: 14px; }
        .profile-body {
            padding: 30px;
        }
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .form-section:last-child { border-bottom: none; margin-bottom: 0; }
        .form-section h3 {
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        .btn-save:hover { opacity: 0.9; }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .info-item:last-child { border-bottom: none; }
        .info-label { color: #666; }
        .info-value { font-weight: 600; color: #333; }
    </style>
</head>
<body>
    <header class="member-header">
        <div>
            <h2><i class="fas fa-hand-holding-heart"></i> ONIX Njangi</h2>
        </div>
        <button class="member-menu-btn" onclick="toggleMemberMenu()" aria-label="Toggle menu">☰</button>
        <nav class="member-nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="contributions.php"><i class="fas fa-coins"></i> Contributions</a>
            <a href="payout_schedule.php"><i class="fas fa-calendar-alt"></i> Payout Schedule</a>
            <a href="my_hands.php"><i class="fas fa-hand-holding"></i> My Hands</a>
            <a href="chat.php"><i class="fas fa-comments"></i> Group Chat</a>
            <a href="profile.php" class="active"><i class="fas fa-user-cog"></i> Profile</a>
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
        <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h2><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['surname']); ?></h2>
                <p><i class="fas fa-id-badge"></i> <?php echo htmlspecialchars($member['member_number']); ?></p>
            </div>
            <div class="profile-body">
                <div class="form-section">
                    <h3><i class="fas fa-user-edit" style="color: #667eea;"></i> Edit Profile</h3>
                    <form method="POST">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" name="first_name" value="<?php echo htmlspecialchars($member['first_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Surname</label>
                                <input type="text" name="surname" value="<?php echo htmlspecialchars($member['surname']); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($member['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($member['email'] ?? ''); ?>">
                        </div>
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </form>
                </div>
                
                <div class="form-section">
                    <h3><i class="fas fa-lock" style="color: #667eea;"></i> Change Password</h3>
                    <form method="POST">
                        <input type="hidden" name="change_password" value="1">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" required minlength="6">
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" required minlength="6">
                            </div>
                        </div>
                        <button type="submit" class="btn-save">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>
                </div>
                
                <div class="form-section">
                    <h3><i class="fas fa-info-circle" style="color: #667eea;"></i> Account Information</h3>
                    <div class="info-item">
                        <span class="info-label">Member Number</span>
                        <span class="info-value"><?php echo htmlspecialchars($member['member_number']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Member Type</span>
                        <span class="info-value"><?php echo htmlspecialchars($member['member_type_id'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Username</span>
                        <span class="info-value"><?php echo htmlspecialchars($member['username'] ?? 'Not set'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Date of Birth</span>
                        <span class="info-value"><?php echo formatDate($member['date_of_birth']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Gender</span>
                        <span class="info-value"><?php echo htmlspecialchars($member['gender']); ?></span>
                    </div>
                </div>
            </div>
        </div>
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
function toggleMemberMenu() {
    var nav = document.querySelector('.member-nav');
    nav.classList.toggle('menu-open');
}
</script>
</body>
</html>