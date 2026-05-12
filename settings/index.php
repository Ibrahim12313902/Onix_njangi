<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();

// Get settings by group
$groups = ['general', 'notifications', 'display', 'security', 'backup', 'njangi', 'registration'];
$settings_by_group = [];

foreach ($groups as $group) {
    $sql = "SELECT * FROM system_settings WHERE setting_group = '$group' AND is_editable = 1 ORDER BY id";
    $result = mysqli_query($conn, $sql);
    $settings_by_group[$group] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $settings_by_group[$group][] = $row;
    }
}

// Get quick stats
$stats = [];

// Admin users count
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM admin_users");
$stats['admin_count'] = mysqli_fetch_assoc($result)['count'];

// Total members
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM members");
$stats['member_count'] = mysqli_fetch_assoc($result)['count'];

// Total hands
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM hands");
$stats['hand_count'] = mysqli_fetch_assoc($result)['count'];

// Recent activity
$activity_sql = "SELECT a.*, u.username 
                 FROM activity_logs a
                 LEFT JOIN admin_users u ON a.user_id = u.id
                 ORDER BY a.created_at DESC 
                 LIMIT 10";
$activity_result = mysqli_query($conn, $activity_sql);

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .settings-container {
            display: flex;
            gap: 30px;
        }
        
        .settings-sidebar {
            width: 280px;
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        
        .settings-sidebar .user-info {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .settings-sidebar .user-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 40px;
        }
        
        .settings-sidebar .user-name {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .settings-sidebar .user-email {
            font-size: 14px;
            color: #6c757d;
        }
        
        .settings-menu {
            margin-top: 20px;
        }
        
        .settings-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: #6c757d;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            margin-bottom: 5px;
        }
        
        .settings-menu-item:hover {
            background: #f8f9fa;
            color: #667eea;
        }
        
        .settings-menu-item.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .settings-menu-item i {
            width: 20px;
            text-align: center;
        }
        
        .settings-main {
            flex: 1;
        }
        
        .settings-header {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        
        .stat-content h3 {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .stat-content .number {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .settings-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        
        .settings-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .card-header {
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-header i {
            font-size: 24px;
        }
        
        .card-header h2 {
            font-size: 18px;
            margin: 0;
            flex: 1;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .setting-item:last-child {
            border-bottom: none;
        }
        
        .setting-label {
            font-size: 14px;
            color: #6c757d;
        }
        
        .setting-value {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .card-footer {
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }
        
        .activity-list {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-action {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .activity-time {
            font-size: 12px;
            color: #6c757d;
        }
        
        @media (max-width: 1024px) {
            .settings-container {
                flex-direction: column;
            }
            
            .settings-sidebar {
                width: 100%;
                position: static;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/notification_setup.php'; ?>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="settings-container">
                <!-- Sidebar -->
                <div class="settings-sidebar">
                    <div class="user-info">
                        <div class="user-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator'); ?></div>
                        <div class="user-email"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'admin@onix.com'); ?></div>
                    </div>
                    
                    <nav class="settings-menu">
                        <a href="index.php" class="settings-menu-item active">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a href="profile.php" class="settings-menu-item">
                            <i class="fas fa-user"></i> Profile
                        </a>
                        <a href="system.php" class="settings-menu-item">
                            <i class="fas fa-cog"></i> System Settings
                        </a>
                        <a href="preferences.php" class="settings-menu-item">
                            <i class="fas fa-sliders-h"></i> Preferences
                        </a>
                        <a href="backup.php" class="settings-menu-item">
                            <i class="fas fa-database"></i> Backup & Restore
                        </a>
                        <a href="password.php" class="settings-menu-item">
                            <i class="fas fa-lock"></i> Change Password
                        </a>
                        <hr style="margin: 15px 0; border-color: #dee2e6;">
                        <a href="../dashboard.php" class="settings-menu-item">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </nav>
                </div>
                
                <!-- Main Content -->
                <div class="settings-main">
                    <div class="settings-header">
                        <h1><i class="fas fa-cog"></i> Settings Dashboard</h1>
                        <p>Manage your system configuration and preferences</p>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <h3>Total Members</h3>
                                <div class="number"><?php echo $stats['member_count']; ?></div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-hands-helping"></i>
                            </div>
                            <div class="stat-content">
                                <h3>Active Hands</h3>
                                <div class="number"><?php echo $stats['hand_count']; ?></div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div class="stat-content">
                                <h3>Administrators</h3>
                                <div class="number"><?php echo $stats['admin_count']; ?></div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="stat-content">
                                <h3>Settings</h3>
                                <div class="number"><?php echo array_sum(array_map('count', $settings_by_group)); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Settings Overview -->
                    <div class="settings-grid">
                        <?php foreach ($settings_by_group as $group => $settings): ?>
                        <div class="settings-card">
                            <div class="card-header">
                                <?php
                                $icons = [
                                    'general' => '<i class="fas fa-globe"></i>',
                                    'notifications' => '<i class="fas fa-bell"></i>',
                                    'display' => '<i class="fas fa-paint-brush"></i>',
                                    'security' => '<i class="fas fa-shield-alt"></i>',
                                    'backup' => '<i class="fas fa-database"></i>',
                                    'njangi' => '<i class="fas fa-hand-holding-heart"></i>',
                                    'registration' => '<i class="fas fa-user-plus"></i>'
                                ];
                                echo $icons[$group] ?? '<i class="fas fa-cog"></i>';
                                ?>
                                <h2><?php echo ucfirst($group); ?> Settings</h2>
                            </div>
                            <div class="card-body">
                                <?php foreach (array_slice($settings, 0, 4) as $setting): ?>
                                <div class="setting-item">
                                    <span class="setting-label"><?php echo htmlspecialchars($setting['description'] ?? $setting['setting_key']); ?>:</span>
                                    <span class="setting-value">
                                        <?php 
                                        if ($setting['setting_type'] == 'boolean') {
                                            echo $setting['setting_value'] == '1' ? 'Yes' : 'No';
                                        } elseif ($setting['setting_type'] == 'color') {
                                            echo '<span style="display:inline-block; width:20px; height:20px; background:'.$setting['setting_value'].'; border-radius:4px;"></span>';
                                        } elseif ($setting['setting_key'] == 'currency') {
                                            echo $setting['setting_value'] . ' (FCFA)';
                                        } else {
                                            echo htmlspecialchars($setting['setting_value']);
                                        }
                                        ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="card-footer">
                                <a href="system.php?group=<?php echo $group; ?>" class="btn btn-sm btn-primary">
                                    Manage All <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="activity-list">
                        <h3 style="margin-bottom: 20px;"><i class="fas fa-history"></i> Recent Activity</h3>
                        
                        <?php if (mysqli_num_rows($activity_result) > 0): ?>
                            <?php while ($activity = mysqli_fetch_assoc($activity_result)): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-circle"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-action"><?php echo htmlspecialchars($activity['action']); ?></div>
                                    <div class="activity-description"><?php echo htmlspecialchars($activity['description'] ?? ''); ?></div>
                                    <div class="activity-time">
                                        <?php echo formatDate($activity['created_at']); ?> 
                                        by <?php echo htmlspecialchars($activity['username'] ?? 'System'); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-history fa-3x"></i>
                                <p>No recent activity</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../js/script.js"></script>
</body>
</html>