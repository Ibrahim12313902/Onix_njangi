<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();
$error = '';
$success = '';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?error=No ID provided');
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Get hand status data
$sql = "SELECT * FROM hand_status WHERE id = '$id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header('Location: index.php?error=Hand status not found');
    exit();
}

$status = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $hand_status_name = mysqli_real_escape_string($conn, $_POST['hand_status_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    if (empty($hand_status_name)) {
        $error = 'Hand status name is required!';
    } else {
        $sql = "UPDATE hand_status SET 
                hand_status_name = '$hand_status_name',
                description = '$description'
                WHERE id = '$id'";
        
        if (mysqli_query($conn, $sql)) {
            $success = 'Hand status updated successfully!';
            // Refresh data
            $sql = "SELECT * FROM hand_status WHERE id = '$id'";
            $result = mysqli_query($conn, $sql);
            $status = mysqli_fetch_assoc($result);
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
    <title>Edit Hand Status - <?php echo SITE_NAME; ?></title>
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
                <h1><i class="fas fa-edit"></i> Edit Hand Status</h1>
                <div class="page-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
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
                    <h3>Edit: <?php echo htmlspecialchars($status['hand_status_name']); ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="hand_status_number">Hand Status Number</label>
                            <input type="text" id="hand_status_number" 
                                   value="<?php echo htmlspecialchars($status['hand_status_number']); ?>" 
                                   readonly disabled class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="hand_status_name">Hand Status Name *</label>
                            <input type="text" id="hand_status_name" name="hand_status_name" 
                                   value="<?php echo htmlspecialchars($status['hand_status_name']); ?>" 
                                   required placeholder="Enter status name"
                                   class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3" 
                                      class="form-control"><?php echo htmlspecialchars($status['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Hand Status
                            </button>
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
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