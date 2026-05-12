<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';
require_once '../includes/notification_setup.php';

// Generate member type number
$member_type_number = 'MT' . date('Ymd') . rand(100, 999);

$conn = getDbConnection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $member_type_number = mysqli_real_escape_string($conn, $_POST['member_type_number']);
    $member_type_name = mysqli_real_escape_string($conn, $_POST['member_type_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    if (empty($member_type_name)) {
        $error = 'Member type name is required!';
    } else {
        // Check if type number exists
        $check_sql = "SELECT id FROM member_types WHERE member_type_number = '$member_type_number'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Member type number already exists!';
        } else {
            $sql = "INSERT INTO member_types (member_type_number, member_type_name, description) 
                    VALUES ('$member_type_number', '$member_type_name', '$description')";
            
            if (mysqli_query($conn, $sql)) {
                $success = 'Member type added successfully!';
                $member_type_number = 'MT' . date('Ymd') . rand(100, 999);
                $member_type_name = '';
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
    <title>Add Member Type - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-plus-circle"></i> Add New Member Type</h1>
                <div class="page-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    <a href="#" onclick="window.print()" class="btn btn-info">
                        <i class="fas fa-print"></i> Print
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
                            <label for="member_type_number">Member Type Number *</label>
                            <input type="text" id="member_type_number" name="member_type_number" 
                                   value="<?php echo htmlspecialchars($member_type_number); ?>" 
                                   required readonly class="form-control">
                            <small class="text-muted">Auto-generated number</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="member_type_name">Member Type Name *</label>
                            <input type="text" id="member_type_name" name="member_type_name" 
                                   value="<?php echo htmlspecialchars($member_type_name ?? ''); ?>" 
                                   required placeholder="Enter member type name"
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
                                <i class="fas fa-save"></i> Add Member Type
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