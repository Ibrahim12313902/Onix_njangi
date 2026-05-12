<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: achievements.php?error=No ID provided');
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Get record data
$sql = "SELECT * FROM saved_records WHERE id = '$id' AND record_type = 'achievement'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header('Location: achievements.php?error=Achievement not found');
    exit();
}

$record = mysqli_fetch_assoc($result);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $amount = !empty($_POST['amount']) ? mysqli_real_escape_string($conn, $_POST['amount']) : 'NULL';
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $record_date = mysqli_real_escape_string($conn, $_POST['record_date']);
    $icon = mysqli_real_escape_string($conn, $_POST['icon']);
    $color = mysqli_real_escape_string($conn, $_POST['color']);
    
    if (empty($title) || empty($record_date)) {
        $error = 'Title and date are required!';
    } else {
        $amount_sql = ($amount == 'NULL') ? "NULL" : "'$amount'";
        $sql = "UPDATE saved_records SET 
                title = '$title',
                amount = $amount_sql,
                description = '$description',
                record_date = '$record_date',
                icon = '$icon',
                color = '$color'
                WHERE id = '$id'";
        
        if (mysqli_query($conn, $sql)) {
            $success = 'Achievement updated successfully!';
            // Refresh data
            $sql = "SELECT * FROM saved_records WHERE id = '$id'";
            $result = mysqli_query($conn, $sql);
            $record = mysqli_fetch_assoc($result);
        } else {
            $error = 'Error: ' . mysqli_error($conn);
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
    <title>Edit Achievement - <?php echo SITE_NAME; ?></title>
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
                <h1><i class="fas fa-edit"></i> Edit Achievement</h1>
                <div class="page-actions">
                    <a href="achievements.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Achievements
                    </a>
                </div>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3>Edit Achievement Details</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="title">Achievement Title *</label>
                            <input type="text" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($record['title']); ?>" 
                                   required class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="amount">Amount (FCFA) - Leave empty if no monetary value</label>
                            <input type="number" id="amount" name="amount" 
                                   value="<?php echo $record['amount'] > 0 ? htmlspecialchars($record['amount']) : ''; ?>" 
                                   class="form-control" min="0" step="100">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4" 
                                      class="form-control"><?php echo htmlspecialchars($record['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="record_date">Achievement Date *</label>
                            <input type="date" id="record_date" name="record_date" 
                                   value="<?php echo htmlspecialchars($record['record_date']); ?>" 
                                   required class="form-control">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="icon">Icon</label>
                                <input type="text" id="icon" name="icon" 
                                       value="<?php echo htmlspecialchars($record['icon'] ?? 'fas fa-trophy'); ?>" 
                                       class="form-control" placeholder="fas fa-trophy">
                                <small>Font Awesome icon class</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="color">Color</label>
                                <input type="color" id="color" name="color" 
                                       value="<?php echo htmlspecialchars($record['color'] ?? '#ffc107'); ?>" 
                                       class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Achievement
                            </button>
                            <a href="achievements.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../js/script.js"></script>
</body>
</html>