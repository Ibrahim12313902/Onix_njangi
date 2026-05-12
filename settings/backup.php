<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();
$success = '';
$error = '';

// Handle backup creation
if (isset($_GET['action']) && $_GET['action'] == 'create') {
    $backup_name = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $backup_path = '../backups/' . $backup_name;
    
    // Create backups directory if it doesn't exist
    if (!file_exists('../backups')) {
        mkdir('../backups', 0777, true);
    }
    
    // Get all tables
    $tables = [];
    $result = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }
    
    $output = "-- ONIX Njangi System Backup\n";
    $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Generate backup for each table
    foreach ($tables as $table) {
        // Get create table statement
        $result = mysqli_query($conn, "SHOW CREATE TABLE $table");
        $row = mysqli_fetch_row($result);
        $output .= "\n\n" . $row[1] . ";\n\n";
        
        // Get data
        $result = mysqli_query($conn, "SELECT * FROM $table");
        while ($row = mysqli_fetch_assoc($result)) {
            $output .= "INSERT INTO $table VALUES (";
            $values = [];
            foreach ($row as $value) {
                $values[] = is_null($value) ? 'NULL' : "'" . mysqli_real_escape_string($conn, $value) . "'";
            }
            $output .= implode(', ', $values) . ");\n";
        }
    }
    
    // Save backup file
    if (file_put_contents($backup_path, $output)) {
        $size = filesize($backup_path);
        
        // Record in backup history
        $admin_id = $_SESSION['admin_id'];
        $sql = "INSERT INTO backup_history (backup_name, backup_file, backup_size, backup_type, created_by) 
                VALUES ('$backup_name', '$backup_name', '$size', 'manual', '$admin_id')";
        mysqli_query($conn, $sql);
        
        $success = "Backup created successfully!";
    } else {
        $error = "Failed to create backup!";
    }
}

// Handle backup download
if (isset($_GET['action']) && $_GET['action'] == 'download' && isset($_GET['file'])) {
    $file = '../backups/' . basename($_GET['file']);
    if (file_exists($file)) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit();
    }
}

// Handle backup restore
if (isset($_GET['action']) && $_GET['action'] == 'restore' && isset($_GET['file'])) {
    $file = '../backups/' . basename($_GET['file']);
    if (file_exists($file)) {
        // Read SQL file
        $sql = file_get_contents($file);
        
        // Split into individual queries
        $queries = explode(';', $sql);
        
        mysqli_begin_transaction($conn);
        $error_count = 0;
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                if (!mysqli_query($conn, $query)) {
                    $error_count++;
                }
            }
        }
        
        if ($error_count == 0) {
            mysqli_commit($conn);
            $success = "Database restored successfully!";
        } else {
            mysqli_rollback($conn);
            $error = "Error restoring database!";
        }
    }
}

// Handle backup deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Get file info
    $result = mysqli_query($conn, "SELECT backup_file FROM backup_history WHERE id = $id");
    $backup = mysqli_fetch_assoc($result);
    
    if ($backup) {
        $file = '../backups/' . $backup['backup_file'];
        if (file_exists($file)) {
            unlink($file);
        }
        
        mysqli_query($conn, "DELETE FROM backup_history WHERE id = $id");
        $success = "Backup deleted successfully!";
    }
}

// Get backup history
$history_sql = "SELECT bh.*, a.username 
                FROM backup_history bh
                LEFT JOIN admin_users a ON bh.created_by = a.id
                ORDER BY bh.created_at DESC";
$history_result = mysqli_query($conn, $history_sql);

// Get backup settings
$settings_sql = "SELECT * FROM system_settings WHERE setting_group = 'backup'";
$settings_result = mysqli_query($conn, $settings_sql);
$settings = [];
while ($row = mysqli_fetch_assoc($settings_result)) {
    $settings[$row['setting_key']] = $row;
}

closeDbConnection($conn);

// Get list of backup files
$backup_files = glob('../backups/*.sql');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .backup-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .backup-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .backup-card h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .backup-stats {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .stat-row:last-child {
            border-bottom: none;
        }
        
        .create-backup {
            text-align: center;
            padding: 30px;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .create-backup:hover {
            border-color: #667eea;
            background: #f8f9fa;
        }
        
        .create-backup i {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .backup-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.3s ease;
        }
        
        .backup-item:hover {
            background: #f8f9fa;
        }
        
        .backup-info h4 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .backup-info p {
            color: #6c757d;
            font-size: 12px;
        }
        
        .backup-size {
            font-weight: bold;
            color: #28a745;
        }
        
        .backup-actions {
            display: flex;
            gap: 10px;
        }
        
        .schedule-settings {
            margin-top: 30px;
        }
        
        @media (max-width: 1024px) {
            .backup-container {
                grid-template-columns: 1fr;
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
                <h1><i class="fas fa-database"></i> Backup & Restore</h1>
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
            
            <div class="backup-container">
                <!-- Create Backup -->
                <div class="backup-card">
                    <h2><i class="fas fa-plus-circle"></i> Create Backup</h2>
                    
                    <div class="backup-stats">
                        <div class="stat-row">
                            <span>Total Backups:</span>
                            <span class="badge badge-info"><?php echo count($backup_files); ?></span>
                        </div>
                        <div class="stat-row">
                            <span>Latest Backup:</span>
                            <span>
                                <?php 
                                if (!empty($backup_files)) {
                                    $latest = max($backup_files);
                                    echo date('d/m/Y H:i', filemtime($latest));
                                } else {
                                    echo 'Never';
                                }
                                ?>
                            </span>
                        </div>
                        <div class="stat-row">
                            <span>Total Size:</span>
                            <span>
                                <?php 
                                $total_size = 0;
                                foreach ($backup_files as $file) {
                                    $total_size += filesize($file);
                                }
                                echo round($total_size / 1024 / 1024, 2) . ' MB';
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="create-backup">
                        <i class="fas fa-database"></i>
                        <h3>Create New Backup</h3>
                        <p>Generate a complete backup of your database</p>
                        <a href="?action=create" class="btn btn-primary btn-lg" 
                           onclick="return confirm('Create a new backup? This may take a few moments.')">
                            <i class="fas fa-play"></i> Start Backup
                        </a>
                    </div>
                    
                    <!-- Backup Schedule Settings -->
                    <div class="schedule-settings">
                        <h3><i class="fas fa-clock"></i> Automatic Backup Settings</h3>
                        <form method="POST" action="system.php?group=backup">
                            <div class="form-group">
                                <label>Enable Auto Backup</label>
                                <select name="settings[auto_backup]" class="form-control">
                                    <option value="1" <?php echo ($settings['auto_backup']['setting_value'] ?? '1') == '1' ? 'selected' : ''; ?>>Yes</option>
                                    <option value="0" <?php echo ($settings['auto_backup']['setting_value'] ?? '1') == '0' ? 'selected' : ''; ?>>No</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Backup Frequency</label>
                                <select name="settings[backup_frequency]" class="form-control">
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Backup Time</label>
                                <input type="time" name="settings[backup_time]" 
                                       value="<?php echo htmlspecialchars($settings['backup_time']['setting_value'] ?? '02:00'); ?>" 
                                       class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label>Keep backups for (days)</label>
                                <input type="number" name="settings[backup_retention]" 
                                       value="<?php echo htmlspecialchars($settings['backup_retention']['setting_value'] ?? '30'); ?>" 
                                       class="form-control" min="1" max="365">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </form>
                    </div>
                </div>
                
                <!-- Backup History -->
                <div class="backup-card">
                    <h2><i class="fas fa-history"></i> Backup History</h2>
                    
                    <div class="backup-list">
                        <?php if (mysqli_num_rows($history_result) > 0): ?>
                            <?php while ($backup = mysqli_fetch_assoc($history_result)): ?>
                            <div class="backup-item">
                                <div class="backup-info">
                                    <h4><?php echo htmlspecialchars($backup['backup_name']); ?></h4>
                                    <p>
                                        <i class="fas fa-calendar"></i> <?php echo formatDate($backup['created_at']); ?>
                                        | <i class="fas fa-user"></i> <?php echo htmlspecialchars($backup['username'] ?? 'System'); ?>
                                        | <span class="backup-size"><?php echo round($backup['backup_size'] / 1024, 2); ?> KB</span>
                                    </p>
                                </div>
                                <div class="backup-actions">
                                    <a href="?action=download&file=<?php echo urlencode($backup['backup_file']); ?>" 
                                       class="btn btn-sm btn-info" title="Download">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <a href="?action=restore&file=<?php echo urlencode($backup['backup_file']); ?>" 
                                       class="btn btn-sm btn-warning" 
                                       onclick="return confirm('Restore this backup? Current data will be overwritten!')"
                                       title="Restore">
                                        <i class="fas fa-undo"></i>
                                    </a>
                                    <a href="?action=delete&id=<?php echo $backup['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Delete this backup?')"
                                       title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-database fa-4x"></i>
                                <p>No backups found</p>
                                <a href="?action=create" class="btn btn-primary">Create First Backup</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Manual Backup Files -->
                    <?php if (!empty($backup_files) && mysqli_num_rows($history_result) == 0): ?>
                    <h3 style="margin-top: 30px;">Manual Backup Files</h3>
                    <div class="backup-list">
                        <?php foreach ($backup_files as $file): ?>
                        <?php $filename = basename($file); ?>
                        <div class="backup-item">
                            <div class="backup-info">
                                <h4><?php echo htmlspecialchars($filename); ?></h4>
                                <p>
                                    <i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', filemtime($file)); ?>
                                    | <span class="backup-size"><?php echo round(filesize($file) / 1024, 2); ?> KB</span>
                                </p>
                            </div>
                            <div class="backup-actions">
                                <a href="?action=download&file=<?php echo urlencode($filename); ?>" 
                                   class="btn btn-sm btn-info" title="Download">
                                    <i class="fas fa-download"></i>
                                </a>
                                <a href="?action=restore&file=<?php echo urlencode($filename); ?>" 
                                   class="btn btn-sm btn-warning" 
                                   onclick="return confirm('Restore this backup? Current data will be overwritten!')"
                                   title="Restore">
                                    <i class="fas fa-undo"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Restore Instructions -->
            <div class="card" style="margin-top: 30px;">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle"></i> Backup & Restore Instructions</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                        <div>
                            <h4><i class="fas fa-download" style="color: #28a745;"></i> Create Backup</h4>
                            <p>Click "Start Backup" to create a complete database backup. The backup file will be saved in the backups folder.</p>
                        </div>
                        <div>
                            <h4><i class="fas fa-undo" style="color: #ffc107;"></i> Restore Backup</h4>
                            <p>Click the restore button to restore your database from a backup file. This will overwrite current data.</p>
                        </div>
                        <div>
                            <h4><i class="fas fa-download" style="color: #17a2b8;"></i> Download Backup</h4>
                            <p>Download backup files to your computer for safekeeping or to transfer to another server.</p>
                        </div>
                    </div>
                    <div class="alert alert-warning" style="margin-top: 20px;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> Restoring a backup will replace all current data. Make sure you have a recent backup before restoring.
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>