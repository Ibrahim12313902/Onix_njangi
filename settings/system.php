<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();

$selected_group = isset($_GET['group']) ? $_GET['group'] : 'general';
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST['settings'] as $key => $value) {
        $key = mysqli_real_escape_string($conn, $key);
        $value = mysqli_real_escape_string($conn, $value);
        
        $sql = "UPDATE system_settings SET setting_value = '$value', updated_at = NOW() WHERE setting_key = '$key'";
        mysqli_query($conn, $sql);
    }
    
    // Log activity
    $admin_id = $_SESSION['admin_id'];
    $action = "Updated system settings";
    $log_sql = "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                VALUES ('$admin_id', '$action', 'Updated $selected_group settings', '" . $_SERVER['REMOTE_ADDR'] . "')";
    mysqli_query($conn, $log_sql);
    
    $success = 'Settings updated successfully!';
}

// Get settings for selected group
$sql = "SELECT * FROM system_settings WHERE setting_group = '$selected_group' ORDER BY id";
$result = mysqli_query($conn, $sql);

// Get all groups for tabs
$groups_sql = "SELECT DISTINCT setting_group FROM system_settings ORDER BY setting_group";
$groups_result = mysqli_query($conn, $groups_sql);

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .settings-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .settings-tab {
            padding: 12px 24px;
            border-radius: 8px;
            background: #f8f9fa;
            color: #6c757d;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .settings-tab:hover {
            background: #e9ecef;
            color: #667eea;
        }
        
        .settings-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .settings-form {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .setting-row {
            display: grid;
            grid-template-columns: 250px 1fr 100px;
            gap: 20px;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .setting-row:hover {
            background: #f8f9fa;
        }
        
        .setting-label {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .setting-description {
            font-size: 12px;
            color: #6c757d;
            margin-top: 4px;
        }
        
        .setting-input {
            width: 100%;
        }
        
        .setting-help {
            color: #6c757d;
            font-size: 14px;
        }
        
        .form-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #dee2e6;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }
        
        @media (max-width: 768px) {
            .setting-row {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .settings-tabs {
                flex-direction: column;
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
            <div class="page-header">
                <h1><i class="fas fa-cog"></i> System Settings</h1>
                <div class="page-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Settings
                    </a>
                </div>
            </div>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Settings Tabs -->
            <div class="settings-tabs">
                <?php while ($group = mysqli_fetch_assoc($groups_result)): ?>
                <a href="system.php?group=<?php echo $group['setting_group']; ?>" 
                   class="settings-tab <?php echo ($group['setting_group'] == $selected_group) ? 'active' : ''; ?>">
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
                    echo $icons[$group['setting_group']] ?? '<i class="fas fa-cog"></i>';
                    ?>
                    <?php echo ucfirst($group['setting_group']); ?>
                </a>
                <?php endwhile; ?>
            </div>
            
            <!-- Settings Form -->
            <form method="POST" class="settings-form">
                <h2 style="margin-bottom: 20px;"><?php echo ucfirst($selected_group); ?> Settings</h2>
                
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($setting = mysqli_fetch_assoc($result)): ?>
                    <div class="setting-row">
                        <div>
                            <div class="setting-label"><?php echo htmlspecialchars($setting['description'] ?? $setting['setting_key']); ?></div>
                            <?php if (!empty($setting['description']) && $setting['description'] != $setting['setting_key']): ?>
                                <div class="setting-description"><?php echo htmlspecialchars($setting['description']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="setting-input">
                            <?php if ($setting['setting_type'] == 'boolean'): ?>
                                <select name="settings[<?php echo $setting['setting_key']; ?>]" class="form-control">
                                    <option value="1" <?php echo $setting['setting_value'] == '1' ? 'selected' : ''; ?>>Yes</option>
                                    <option value="0" <?php echo $setting['setting_value'] == '0' ? 'selected' : ''; ?>>No</option>
                                </select>
                                
                            <?php elseif ($setting['setting_type'] == 'color'): ?>
                                <input type="color" name="settings[<?php echo $setting['setting_key']; ?>]" 
                                       value="<?php echo htmlspecialchars($setting['setting_value']); ?>" class="form-control">
                                       
                            <?php elseif ($setting['setting_type'] == 'number'): ?>
                                <input type="number" name="settings[<?php echo $setting['setting_key']; ?>]" 
                                       value="<?php echo htmlspecialchars($setting['setting_value']); ?>" 
                                       class="form-control" min="0" step="1">
                                       
                            <?php elseif ($setting['setting_type'] == 'select' && !empty($setting['options'])): ?>
                                <select name="settings[<?php echo $setting['setting_key']; ?>]" class="form-control">
                                    <?php 
                                    $options = json_decode($setting['options'], true);
                                    if ($options) {
                                        foreach ($options as $value => $label) {
                                            $selected = ($setting['setting_value'] == $value) ? 'selected' : '';
                                            echo "<option value=\"$value\" $selected>$label</option>";
                                        }
                                    }
                                    ?>
                                </select>
                                
                            <?php else: ?>
                                <input type="text" name="settings[<?php echo $setting['setting_key']; ?>]" 
                                       value="<?php echo htmlspecialchars($setting['setting_value']); ?>" 
                                       class="form-control">
                            <?php endif; ?>
                        </div>
                        
                        <div class="setting-help">
                            <?php echo $setting['setting_key']; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-cog fa-4x"></i>
                        <p>No settings found for this group.</p>
                    </div>
                <?php endif; ?>
            </form>
        </main>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../js/script.js"></script>
</body>
</html>