<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();
$error = '';
$success = '';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM announcements WHERE id = '$id'");
    header('Location: index.php?success=Announcement deleted');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($title) || empty($content)) {
        $error = 'Title and content are required!';
    } else {
        $sql = "INSERT INTO announcements (title, content, is_active, created_by) 
                VALUES ('$title', '$content', '$is_active', '" . $_SESSION['admin_id'] . "')";
        
        if (mysqli_query($conn, $sql)) {
            $success = 'Announcement created successfully!';
        } else {
            $error = 'Error: ' . mysqli_error($conn);
        }
    }
}

$sql = "SELECT a.*, ad.full_name as admin_name 
        FROM announcements a 
        LEFT JOIN admin_users ad ON a.created_by = ad.id 
        ORDER BY a.created_at DESC";
$announcements = mysqli_query($conn, $sql);

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/notification_setup.php'; ?>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-bullhorn"></i> Announcements</h1>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                <div class="card">
                    <div class="card-header">
                        <h3>Create New Announcement</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label for="title">Title *</label>
                                <input type="text" id="title" name="title" required class="form-control" 
                                       placeholder="e.g., Important Meeting Tomorrow">
                            </div>
                            
                            <div class="form-group">
                                <label for="content">Content *</label>
                                <textarea id="content" name="content" rows="5" required class="form-control"
                                          placeholder="Enter announcement details..."></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="is_active" value="1" checked>
                                    Publish immediately (visible to members)
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-bullhorn"></i> Post Announcement
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>All Announcements</h3>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($announcements) > 0): ?>
                            <?php while ($ann = mysqli_fetch_assoc($announcements)): ?>
                            <div style="padding: 15px; border-bottom: 1px solid #eee; <?php echo $ann['is_active'] ? '' : 'opacity: 0.5;'; ?>">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <div>
                                        <h4 style="margin: 0 0 5px 0;">
                                            <?php echo htmlspecialchars($ann['title']); ?>
                                            <?php if (!$ann['is_active']): ?>
                                            <span class="badge badge-secondary">Draft</span>
                                            <?php endif; ?>
                                        </h4>
                                        <p style="margin: 0 0 5px 0; color: #666; font-size: 13px;">
                                            <?php echo htmlspecialchars(substr($ann['content'], 0, 100)); ?>...
                                        </p>
                                        <small style="color: #999;">
                                            By <?php echo htmlspecialchars($ann['admin_name'] ?? 'Admin'); ?> | 
                                            <?php echo formatDate($ann['created_at']); ?>
                                        </small>
                                    </div>
                                    <a href="index.php?delete=<?php echo $ann['id']; ?>" class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Delete this announcement?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="color: #666;">No announcements yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>