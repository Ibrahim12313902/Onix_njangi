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

$sql = "SELECT * FROM members WHERE id = '$id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header('Location: index.php?error=Member not found');
    exit();
}

$member = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirm_password)) {
        $error = 'All fields are required!';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters!';
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE members SET password_hash = '$password_hash' WHERE id = '$id'";
        
        if (mysqli_query($conn, $sql)) {
            $success = 'Password set successfully for ' . $member['first_name'] . ' ' . $member['surname'];
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
    <title>Set Password - <?php echo SITE_NAME; ?></title>
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
                <h1><i class="fas fa-key"></i> Set Member Password</h1>
                <div class="page-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Members
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
                    <h3>Member: <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['surname']); ?></h3>
                    <p>Member Number: <?php echo htmlspecialchars($member['member_number']); ?></p>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="password">New Password *</label>
                            <input type="password" id="password" name="password" required 
                                   class="form-control" minlength="6" placeholder="Enter new password">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   class="form-control" minlength="6" placeholder="Confirm new password">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Set Password
                            </button>
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                    
                    <div class="info-box" style="margin-top: 20px;">
                        <h4><i class="fas fa-info-circle"></i> Login Information</h4>
                        <p>Member can login using:</p>
                        <ul style="margin: 10px 0; padding-left: 20px;">
                            <li>Phone Number: <strong><?php echo htmlspecialchars($member['phone'] ?? 'Not set'); ?></strong></li>
                            <li>Email: <strong><?php echo htmlspecialchars($member['email'] ?? 'Not set'); ?></strong></li>
                            <li>Username: <strong><?php echo htmlspecialchars($member['username'] ?? 'Not set'); ?></strong></li>
                        </ul>
                        <p><small>Tell the member which login method they should use along with this password.</small></p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../js/script.js"></script>
</body>
</html>