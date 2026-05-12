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

// Get hand type data
$sql = "SELECT * FROM hand_types WHERE id = '$id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header('Location: index.php?error=Hand type not found');
    exit();
}

$type = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $hand_type_name = mysqli_real_escape_string($conn, $_POST['hand_type_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $default_amount = mysqli_real_escape_string($conn, $_POST['default_amount']);
    $payment_period_type = mysqli_real_escape_string($conn, $_POST['payment_period_type']);
    $payment_period_days = mysqli_real_escape_string($conn, $_POST['payment_period_days']);
    
    if (empty($hand_type_name)) {
        $error = 'Hand type name is required!';
    } else {
        $sql = "UPDATE hand_types SET 
                hand_type_name = '$hand_type_name',
                description = '$description',
                default_amount = '$default_amount',
                payment_period_type = '$payment_period_type',
                payment_period_days = '$payment_period_days'
                WHERE id = '$id'";
        
        if (mysqli_query($conn, $sql)) {
            $success = 'Hand type updated successfully!';
            // Refresh data
            $sql = "SELECT * FROM hand_types WHERE id = '$id'";
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
    <title>Edit Hand Type - <?php echo SITE_NAME; ?></title>
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
                <h1><i class="fas fa-edit"></i> Edit Hand Type</h1>
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
                    <h3>Edit: <?php echo htmlspecialchars($type['hand_type_name']); ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="hand_type_number">Hand Type Number</label>
                            <input type="text" id="hand_type_number" 
                                   value="<?php echo htmlspecialchars($type['hand_type_number']); ?>" 
                                   readonly disabled class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="hand_type_name">Hand Type Name *</label>
                            <input type="text" id="hand_type_name" name="hand_type_name" 
                                   value="<?php echo htmlspecialchars($type['hand_type_name']); ?>" 
                                   required placeholder="Enter hand type name"
                                   class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="default_amount">Default Amount (FCFA)</label>
                            <input type="number" id="default_amount" name="default_amount" 
                                   value="<?php echo htmlspecialchars($type['default_amount']); ?>" 
                                   class="form-control" min="0" step="100">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3" 
                                      class="form-control"><?php echo htmlspecialchars($type['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="payment_period_type">Payment Period Type</label>
                            <select id="payment_period_type" name="payment_period_type" class="form-control">
                                <option value="daily" <?php echo ($type['payment_period_type'] ?? 'monthly') == 'daily' ? 'selected' : ''; ?>>Daily</option>
                                <option value="weekly" <?php echo ($type['payment_period_type'] ?? 'monthly') == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                <option value="biweekly" <?php echo ($type['payment_period_type'] ?? 'monthly') == 'biweekly' ? 'selected' : ''; ?>>Bi-weekly</option>
                                <option value="monthly" <?php echo ($type['payment_period_type'] ?? 'monthly') == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="payment_period_days">Payment Deadline (days)</label>
                            <input type="number" id="payment_period_days" name="payment_period_days" 
                                   value="<?php echo htmlspecialchars($type['payment_period_days'] ?? '30'); ?>" 
                                   class="form-control" min="1" max="365">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Hand Type
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