<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();
$error = '';
$success = '';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?error=No ID provided');
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

$sql = "SELECT * FROM payout_cycles WHERE id = '$id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header('Location: index.php?error=Cycle not found');
    exit();
}

$cycle = mysqli_fetch_assoc($result);

if ($cycle['status'] != 'draft') {
    header('Location: view.php?id=' . $id . '&error=Only draft cycles can be edited');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cycle_name = mysqli_real_escape_string($conn, $_POST['cycle_name']);
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    
    if (empty($cycle_name) || empty($start_date)) {
        $error = 'Cycle name and start date are required!';
    } else {
        $sql = "SELECT COUNT(*) as count FROM hands";
        $result = mysqli_query($conn, $sql);
        $hand_count = mysqli_fetch_assoc($result)['count'];
        
        $sql = "SELECT MAX(payment_period_days) as max_days FROM hand_types";
        $result = mysqli_query($conn, $sql);
        $max_days = mysqli_fetch_assoc($result)['max_days'] ?? 30;
        
        $end_date = date('Y-m-d', strtotime($start_date . ' + ' . ($hand_count * $max_days) . ' days'));
        
        $sql = "UPDATE payout_cycles SET cycle_name = '$cycle_name', start_date = '$start_date', end_date = '$end_date' WHERE id = '$id'";
        
        if (mysqli_query($conn, $sql)) {
            header('Location: index.php?success=Cycle updated successfully!');
            exit();
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
    <title>Edit Payout Cycle - <?php echo SITE_NAME; ?></title>
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
                <h1><i class="fas fa-edit"></i> Edit Payout Cycle</h1>
                <div class="page-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3>Edit: <?php echo htmlspecialchars($cycle['cycle_name']); ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="cycle_number">Cycle Number</label>
                            <input type="text" id="cycle_number" 
                                   value="<?php echo htmlspecialchars($cycle['cycle_number']); ?>" 
                                   readonly class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="cycle_name">Cycle Name *</label>
                            <input type="text" id="cycle_name" name="cycle_name" 
                                   value="<?php echo htmlspecialchars($cycle['cycle_name']); ?>" 
                                   required class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="start_date">Start Date *</label>
                            <input type="date" id="start_date" name="start_date" 
                                   value="<?php echo $cycle['start_date']; ?>" 
                                   required class="form-control">
                            <small>Changing start date will recalculate the end date</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date">Calculated End Date</label>
                            <input type="text" id="end_date" 
                                   value="<?php echo formatDate($cycle['end_date']); ?>" 
                                   readonly class="form-control">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Cycle
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