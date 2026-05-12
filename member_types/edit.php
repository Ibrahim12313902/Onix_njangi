<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';
require_once '../includes/notification_setup.php';

$conn = getDbConnection();
$error = '';
$success = '';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?error=No ID provided');
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Get member type data
$sql = "SELECT * FROM member_types WHERE id = '$id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header('Location: index.php?error=Member type not found');
    exit();
}

$type = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $member_type_name = mysqli_real_escape_string($conn, $_POST['member_type_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    if (empty($member_type_name)) {
        $error = 'Member type name is required!';
    } else {
        $sql = "UPDATE member_types SET 
                member_type_name = '$member_type_name',
                description = '$description'
                WHERE id = '$id'";
        
        if (mysqli_query($conn, $sql)) {
            $success = 'Member type updated successfully!';
            // Refresh data
            $sql = "SELECT * FROM member_types WHERE id = '$id'";
            $result = mysqli_query($conn, $sql);
            $type = mysqli_fetch_assoc($result);
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
    <title>Edit Member Type - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-edit"></i> Edit Member Type</h1>
                <div class="page-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New
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
                    <h3>Edit Member Type: <?php echo htmlspecialchars($type['member_type_name']); ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="member_type_number">Member Type Number</label>
                            <input type="text" id="member_type_number" 
                                   value="<?php echo htmlspecialchars($type['member_type_number']); ?>" 
                                   readonly disabled class="form-control">
                            <small class="text-muted">Number cannot be changed</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="member_type_name">Member Type Name *</label>
                            <input type="text" id="member_type_name" name="member_type_name" 
                                   value="<?php echo htmlspecialchars($type['member_type_name']); ?>" 
                                   required placeholder="Enter member type name"
                                   class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3" 
                                      placeholder="Enter description (optional)"
                                      class="form-control"><?php echo htmlspecialchars($type['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Registration Date</label>
                            <input type="text" value="<?php echo formatDate($type['registration_date']); ?>" 
                                   readonly disabled class="form-control">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Member Type
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
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