<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();

// Predefined achievements
$achievements = [
    [
        'title' => 'First Member Registered',
        'description' => 'The first member joined the Njangi group',
        'icon' => 'fas fa-user-plus',
        'color' => '#28a745'
    ],
    [
        'title' => '10 Members Milestone',
        'description' => 'Reached 10 active members in the group',
        'icon' => 'fas fa-users',
        'color' => '#17a2b8'
    ],
    [
        'title' => '25 Members Milestone',
        'description' => 'Reached 25 active members',
        'icon' => 'fas fa-users',
        'color' => '#17a2b8'
    ],
    [
        'title' => '50 Members Milestone',
        'description' => 'Celebrating 50 active members!',
        'icon' => 'fas fa-trophy',
        'color' => '#ffc107'
    ],
    [
        'title' => 'First Hand Opened',
        'description' => 'First Njangi hand was opened',
        'icon' => 'fas fa-hand-holding-heart',
        'color' => '#28a745'
    ],
    [
        'title' => '10 Hands Active',
        'description' => '10 hands are now active in the system',
        'icon' => 'fas fa-hands-helping',
        'color' => '#17a2b8'
    ],
    [
        'title' => '100,000 FCFA Savings',
        'amount' => 100000,
        'description' => 'Total savings reached 100,000 FCFA',
        'icon' => 'fas fa-piggy-bank',
        'color' => '#fd7e14'
    ],
    [
        'title' => '500,000 FCFA Savings',
        'amount' => 500000,
        'description' => 'Total savings reached 500,000 FCFA',
        'icon' => 'fas fa-star',
        'color' => '#ffc107'
    ],
    [
        'title' => '1,000,000 FCFA Savings',
        'amount' => 1000000,
        'description' => 'MAJOR MILESTONE: 1 Million FCFA in savings!',
        'icon' => 'fas fa-crown',
        'color' => '#ffc107'
    ]
];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $amount = !empty($_POST['amount']) ? mysqli_real_escape_string($conn, $_POST['amount']) : 'NULL';
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $achievement_date = mysqli_real_escape_string($conn, $_POST['achievement_date']);
    $icon = mysqli_real_escape_string($conn, $_POST['icon']);
    $color = mysqli_real_escape_string($conn, $_POST['color']);
    
    if (empty($title) || empty($achievement_date)) {
        $error = 'Title and date are required!';
    } else {
        $amount_sql = ($amount == 'NULL') ? "NULL" : "'$amount'";
        $sql = "INSERT INTO saved_records 
                (record_type, title, amount, description, record_date, icon, color, is_active) 
                VALUES ('achievement', '$title', $amount_sql, '$description', '$achievement_date', '$icon', '$color', 1)";
        
        if (mysqli_query($conn, $sql)) {
            $success = 'Achievement added successfully!';
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
    <title>Add Achievement - <?php echo SITE_NAME; ?></title>
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
                <h1><i class="fas fa-plus-circle"></i> Add Achievement</h1>
                <div class="page-actions">
                    <a href="achievements.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
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
                    <h3>Quick Add from Templates</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                        <?php foreach ($achievements as $ach): ?>
                        <button class="btn btn-outline" style="text-align: left;" 
                                onclick="fillForm('<?php echo addslashes($ach['title']); ?>', 
                                                '<?php echo $ach['amount'] ?? ''; ?>', 
                                                '<?php echo addslashes($ach['description']); ?>',
                                                '<?php echo $ach['icon']; ?>',
                                                '<?php echo $ach['color']; ?>')">
                            <i class="<?php echo $ach['icon']; ?>" style="color: <?php echo $ach['color']; ?>;"></i>
                            <?php echo $ach['title']; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Or Create Custom Achievement</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="title">Achievement Title *</label>
                            <input type="text" id="title" name="title" required class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="amount">Amount (FCFA) - Leave empty if no monetary value</label>
                            <input type="number" id="amount" name="amount" class="form-control" min="0" step="100">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3" class="form-control"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="achievement_date">Achievement Date *</label>
                            <input type="date" id="achievement_date" name="achievement_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required class="form-control">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="icon">Icon</label>
                                <input type="text" id="icon" name="icon" value="fas fa-trophy" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label for="color">Color</label>
                                <input type="color" id="color" name="color" value="#ffc107" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Achievement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function fillForm(title, amount, description, icon, color) {
            document.getElementById('title').value = title;
            if (amount) document.getElementById('amount').value = amount;
            document.getElementById('description').value = description;
            document.getElementById('icon').value = icon;
            document.getElementById('color').value = color;
        }
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
