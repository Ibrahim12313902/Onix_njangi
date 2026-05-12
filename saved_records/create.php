<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();
$error = '';
$success = '';

$record_type = isset($_GET['type']) ? $_GET['type'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $record_type = mysqli_real_escape_string($conn, $_POST['record_type']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $amount = mysqli_real_escape_string($conn, $_POST['amount'] ?: 0);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $record_date = mysqli_real_escape_string($conn, $_POST['record_date']);
    $icon = mysqli_real_escape_string($conn, $_POST['icon'] ?? 'fas fa-save');
    $color = mysqli_real_escape_string($conn, $_POST['color'] ?? '#667eea');
    
    if (empty($title) || empty($record_date)) {
        $error = 'Title and date are required!';
    } else {
        $sql = "INSERT INTO saved_records (record_type, title, amount, description, record_date, icon, color, is_active) 
                VALUES ('$record_type', '$title', '$amount', '$description', '$record_date', '$icon', '$color', 1)";
        
        if (mysqli_query($conn, $sql)) {
            $success = 'Record created successfully!';
        } else {
            $error = 'Error: ' . mysqli_error($conn);
        }
    }
}

closeDbConnection($conn);

// Set default values based on type
$default_title = '';
$default_icon = 'fas fa-save';
$default_color = '#667eea';

switch ($record_type) {
    case 'current_month':
        $default_title = date('F Y') . ' Contributions';
        $default_icon = 'fas fa-calendar-alt';
        $default_color = '#28a745';
        break;
    case 'last_quarter':
        $quarter = ceil(date('n') / 3) - 1;
        if ($quarter < 1) $quarter = 4;
        $year = date('Y');
        if ($quarter == 4) $year = $year - 1;
        $default_title = 'Q' . $quarter . ' ' . $year . ' Summary';
        $default_icon = 'fas fa-chart-line';
        $default_color = '#17a2b8';
        break;
    case 'achievement':
        $default_title = 'New Achievement';
        $default_icon = 'fas fa-trophy';
        $default_color = '#ffc107';
        break;
    case 'piggie_box':
        $default_title = 'Piggie Box - ' . date('F Y');
        $default_icon = 'fas fa-piggy-bank';
        $default_color = '#dc3545';
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Saved Record - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.0/spectrum.min.css">
</head>
<body>
    <?php include '../includes/notification_setup.php'; ?>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-plus-circle"></i> Create New Saved Record</h1>
                <div class="page-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Saved Records
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
                    <h3>Record Details</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="record_type">Record Type *</label>
                            <select id="record_type" name="record_type" required class="form-control">
                                <option value="">Select Type</option>
                                <option value="current_month" <?php echo $record_type == 'current_month' ? 'selected' : ''; ?>>Current Month</option>
                                <option value="last_quarter" <?php echo $record_type == 'last_quarter' ? 'selected' : ''; ?>>Last Quarter</option>
                                <option value="achievement" <?php echo $record_type == 'achievement' ? 'selected' : ''; ?>>Achievement</option>
                                <option value="piggie_box" <?php echo $record_type == 'piggie_box' ? 'selected' : ''; ?>>Piggie Box</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="title">Title *</label>
                            <input type="text" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($_POST['title'] ?? $default_title); ?>" 
                                   required class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="amount">Amount (FCFA)</label>
                            <input type="number" id="amount" name="amount" 
                                   value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>" 
                                   class="form-control" min="0" step="100">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4" 
                                      class="form-control"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="record_date">Record Date *</label>
                            <input type="date" id="record_date" name="record_date" 
                                   value="<?php echo htmlspecialchars($_POST['record_date'] ?? date('Y-m-d')); ?>" 
                                   required class="form-control">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="icon">Icon</label>
                                <input type="text" id="icon" name="icon" 
                                       value="<?php echo htmlspecialchars($_POST['icon'] ?? $default_icon); ?>" 
                                       class="form-control" placeholder="fas fa-icon-name">
                                <small>Font Awesome icon class</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="color">Color</label>
                                <input type="color" id="color" name="color" 
                                       value="<?php echo htmlspecialchars($_POST['color'] ?? $default_color); ?>" 
                                       class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Record
                            </button>
                            <button type="reset" class="btn btn-secondary">Reset</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.0/spectrum.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#color').spectrum({
                preferredFormat: "hex",
                showInput: true
            });
        });
    </script>
</body>
</html>