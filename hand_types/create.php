<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

// Generate hand type number
$hand_type_number = 'HT' . date('Ymd') . rand(100, 999);

$conn = getDbConnection();
$error = '';
$success = '';
$payment_period_type = 'monthly';
$payment_period_days = 30;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $hand_type_number = mysqli_real_escape_string($conn, $_POST['hand_type_number']);
    $hand_type_name = mysqli_real_escape_string($conn, $_POST['hand_type_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $default_amount = mysqli_real_escape_string($conn, $_POST['default_amount']);
    $payment_period_type = mysqli_real_escape_string($conn, $_POST['payment_period_type']);
    $payment_period_days = mysqli_real_escape_string($conn, $_POST['payment_period_days']);
    
    if (empty($hand_type_name)) {
        $error = 'Hand type name is required!';
    } else {
        // Check if type number exists
        $check_sql = "SELECT id FROM hand_types WHERE hand_type_number = '$hand_type_number'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Hand type number already exists!';
        } else {
            $sql = "INSERT INTO hand_types (hand_type_number, hand_type_name, description, default_amount, payment_period_type, payment_period_days) 
                    VALUES ('$hand_type_number', '$hand_type_name', '$description', '$default_amount', '$payment_period_type', '$payment_period_days')";
            
            if (mysqli_query($conn, $sql)) {
                $success = 'Hand type added successfully!';
                $hand_type_number = 'HT' . date('Ymd') . rand(100, 999);
                $hand_type_name = '';
                $description = '';
                $default_amount = '';
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
    <title>Add Hand Type - <?php echo SITE_NAME; ?></title>
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
                <h1><i class="fas fa-plus-circle"></i> Add New Hand Type</h1>
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
                            <label for="hand_type_number">Hand Type Number *</label>
                            <input type="text" id="hand_type_number" name="hand_type_number" 
                                   value="<?php echo htmlspecialchars($hand_type_number); ?>" 
                                   required readonly class="form-control">
                            <small>Auto-generated number</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="hand_type_name">Hand Type Name *</label>
                            <input type="text" id="hand_type_name" name="hand_type_name" 
                                   value="<?php echo htmlspecialchars($hand_type_name ?? ''); ?>" 
                                   required placeholder="Enter hand type name (e.g., Monthly Contribution)"
                                   class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="default_amount">Default Amount (FCFA)</label>
                            <input type="number" id="default_amount" name="default_amount" 
                                   value="<?php echo htmlspecialchars($default_amount ?? ''); ?>" 
                                   placeholder="Enter default contribution amount"
                                   class="form-control" min="0" step="100">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3" 
                                      placeholder="Enter description (optional)"
                                      class="form-control"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="payment_period_type">Payment Period Type</label>
                            <select id="payment_period_type" name="payment_period_type" class="form-control">
                                <option value="daily" <?php echo ($_POST['payment_period_type'] ?? 'monthly') == 'daily' ? 'selected' : ''; ?>>Daily</option>
                                <option value="weekly" <?php echo ($_POST['payment_period_type'] ?? 'monthly') == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                <option value="biweekly" <?php echo ($_POST['payment_period_type'] ?? 'monthly') == 'biweekly' ? 'selected' : ''; ?>>Bi-weekly</option>
                                <option value="monthly" <?php echo ($_POST['payment_period_type'] ?? 'monthly') == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                            </select>
                            <small>How often payments are expected for this hand type</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="payment_period_days">Payment Deadline (days)</label>
                            <input type="number" id="payment_period_days" name="payment_period_days" 
                                   value="<?php echo htmlspecialchars($payment_period_days ?? '30'); ?>" 
                                   class="form-control" min="1" max="365">
                            <small>Number of days allowed to make payment</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Add Hand Type
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