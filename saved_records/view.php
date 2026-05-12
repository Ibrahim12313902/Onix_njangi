<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: achievements.php?error=No ID provided');
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

$sql = "SELECT * FROM saved_records WHERE id = '$id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header('Location: achievements.php?error=Record not found');
    exit();
}

$record = mysqli_fetch_assoc($result);
closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Achievement - <?php echo SITE_NAME; ?></title>
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
                <h1><i class="fas fa-trophy"></i> Achievement Details</h1>
                <div class="page-actions">
                    <a href="achievements.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <a href="edit.php?id=<?php echo $record['id']; ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="#" onclick="window.print()" class="btn btn-info">
                        <i class="fas fa-print"></i> Print
                    </a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <i class="<?php echo htmlspecialchars($record['icon'] ?? 'fas fa-trophy'); ?>" 
                           style="font-size: 80px; color: <?php echo htmlspecialchars($record['color'] ?? '#ffc107'); ?>;"></i>
                    </div>
                    
                    <h2 style="text-align: center; margin-bottom: 30px;"><?php echo htmlspecialchars($record['title']); ?></h2>
                    
                    <div class="details-grid">
                        <div class="detail-row">
                            <label>Achievement Date:</label>
                            <span class="value"><?php echo formatDate($record['record_date']); ?></span>
                        </div>
                        
                        <?php if ($record['amount'] > 0): ?>
                        <div class="detail-row">
                            <label>Milestone Amount:</label>
                            <span class="value" style="color: #28a745; font-size: 24px;"><?php echo formatCurrency($record['amount']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="detail-row">
                            <label>Created On:</label>
                            <span class="value"><?php echo formatDate($record['created_at']); ?></span>
                        </div>
                        
                        <?php if (!empty($record['description'])): ?>
                        <div class="detail-row full-width">
                            <label>Description:</label>
                            <div class="value" style="white-space: pre-line;"><?php echo nl2br(htmlspecialchars($record['description'])); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>