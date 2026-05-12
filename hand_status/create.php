<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

// Generate hand status number
$hand_status_number = 'HS' . date('Ymd') . rand(100, 999);

$conn = getDbConnection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $hand_status_number = mysqli_real_escape_string($conn, $_POST['hand_status_number']);
    $hand_status_name = mysqli_real_escape_string($conn, $_POST['hand_status_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    if (empty($hand_status_name)) {
        $error = 'Hand status name is required!';
    } else {
        // Check if status number exists
        $check_sql = "SELECT id FROM hand_status WHERE hand_status_number = '$hand_status_number'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Hand status number already exists!';
        } else {
            $sql = "INSERT INTO hand_status (hand_status_number, hand_status_name, description) 
                    VALUES ('$hand_status_number', '$hand_status_name', '$description')";
            
            if (mysqli_query($conn, $sql)) {
                $success = 'Hand status added successfully!';
                $hand_status_number = 'HS' . date('Ymd') . rand(100, 999);
                $hand_status_name = '';
                $description = '';
            } else {
                $error = 'Error: ' . mysqli_error($conn);
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
    <title>Add Hand Status - <?php echo SITE_NAME; ?></title>
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
                <h1><i class="fas fa-plus-circle"></i> Add New Hand Status</h1>
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
                    <h3>Fill all field as required</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="hand_status_number">Hand Status Number *</label>
                            <input type="text" id="hand_status_number" name="hand_status_number" 
                                   value="<?php echo htmlspecialchars($hand_status_number); ?>" 
                                   required readonly class="form-control">
                            <small>Auto-generated number</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="hand_status_name">Hand Status Name *</label>
                            <input type="text" id="hand_status_name" name="hand_status_name" 
                                   value="<?php echo htmlspecialchars($hand_status_name ?? ''); ?>" 
                                   required placeholder="Enter status name (e.g., Active, Closed)"
                                   class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3" 
                                      placeholder="Enter description (optional)"
                                      class="form-control"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Add Hand Status
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset
                            </button>
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