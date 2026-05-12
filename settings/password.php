<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $admin_id = $_SESSION['admin_id'];
    
    // Get current password
    $sql = "SELECT password FROM admin_users WHERE id = '$admin_id'";
    $result = mysqli_query($conn, $sql);
    $admin = mysqli_fetch_assoc($result);
    
    // Verify current password
    if (!password_verify($current_password, $admin['password'])) {
        $error = 'Current password is incorrect!';
    } elseif ($new_password != $confirm_password) {
        $error = 'New passwords do not match!';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long!';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE admin_users SET password = '$hashed_password' WHERE id = '$admin_id'";
        
        if (mysqli_query($conn, $update_sql)) {
            $success = 'Password changed successfully!';
            
            // Log activity
            $log_sql = "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                        VALUES ('$admin_id', 'Password changed', 'User changed their password', '" . $_SERVER['REMOTE_ADDR'] . "')";
            mysqli_query($conn, $log_sql);
        } else {
            $error = 'Error updating password: ' . mysqli_error($conn);
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
    <title>Change Password - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .password-container {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .password-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .password-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .password-header i {
            font-size: 60px;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .password-header h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .password-header p {
            color: #6c757d;
        }
        
        .password-requirements {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .password-requirements h4 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .requirement {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .requirement i {
            color: #28a745;
            font-size: 12px;
        }
        
        .password-strength {
            margin: 20px 0;
        }
        
        .strength-meter {
            height: 5px;
            background: #dee2e6;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .strength-meter-fill {
            height: 100%;
            width: 0;
            transition: width 0.3s ease;
        }
        
        .strength-text {
            font-size: 12px;
            margin-top: 5px;
            text-align: right;
        }
    </style>
</head>
<body>
    <?php include '../includes/notification_setup.php'; ?>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-key"></i> Change Password</h1>
                <div class="page-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Settings
                    </a>
                </div>
            </div>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="password-container">
                <div class="password-card">
                    <div class="password-header">
                        <i class="fas fa-lock"></i>
                        <h2>Update Your Password</h2>
                        <p>Choose a strong password to keep your account secure</p>
                    </div>
                    
                    <form method="POST" onsubmit="return validatePassword()">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" 
                                   required class="form-control" placeholder="Enter current password">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" 
                                   required class="form-control" placeholder="Enter new password"
                                   onkeyup="checkPasswordStrength()">
                        </div>
                        
                        <!-- Password Strength Meter -->
                        <div class="password-strength">
                            <div class="strength-meter">
                                <div id="strengthMeter" class="strength-meter-fill"></div>
                            </div>
                            <div id="strengthText" class="strength-text"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   required class="form-control" placeholder="Confirm new password"
                                   onkeyup="checkMatch()">
                            <small id="passwordMatch" style="color: #dc3545;"></small>
                        </div>
                        
                        <!-- Password Requirements -->
                        <div class="password-requirements">
                            <h4>Password Requirements:</h4>
                            <div class="requirement">
                                <i class="fas fa-check-circle"></i> At least 6 characters long
                            </div>
                            <div class="requirement">
                                <i class="fas fa-check-circle"></i> Include at least one number
                            </div>
                            <div class="requirement">
                                <i class="fas fa-check-circle"></i> Include at least one uppercase letter
                            </div>
                            <div class="requirement">
                                <i class="fas fa-check-circle"></i> Include at least one special character
                            </div>
                        </div>
                        
                        <div class="form-actions" style="margin-top: 30px;">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function checkPasswordStrength() {
            const password = document.getElementById('new_password').value;
            const meter = document.getElementById('strengthMeter');
            const text = document.getElementById('strengthText');
            
            let strength = 0;
            
            if (password.length >= 6) strength += 25;
            if (password.match(/[a-z]+/)) strength += 25;
            if (password.match(/[A-Z]+/)) strength += 25;
            if (password.match(/[0-9]+/)) strength += 25;
            if (password.match(/[$@#&!]+/)) strength += 25;
            
            // Cap at 100
            strength = Math.min(strength, 100);
            
            // Update meter
            meter.style.width = strength + '%';
            
            if (strength < 50) {
                meter.style.background = '#dc3545';
                text.innerHTML = 'Weak password';
                text.style.color = '#dc3545';
            } else if (strength < 75) {
                meter.style.background = '#ffc107';
                text.innerHTML = 'Medium password';
                text.style.color = '#ffc107';
            } else {
                meter.style.background = '#28a745';
                text.innerHTML = 'Strong password';
                text.style.color = '#28a745';
            }
        }
        
        function checkMatch() {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            const matchText = document.getElementById('passwordMatch');
            
            if (confirmPass.length > 0) {
                if (newPass === confirmPass) {
                    matchText.innerHTML = '✓ Passwords match';
                    matchText.style.color = '#28a745';
                } else {
                    matchText.innerHTML = '✗ Passwords do not match';
                    matchText.style.color = '#dc3545';
                }
            } else {
                matchText.innerHTML = '';
            }
        }
        
        function validatePassword() {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            
            if (newPass !== confirmPass) {
                alert('Passwords do not match!');
                return false;
            }
            
            if (newPass.length < 6) {
                alert('Password must be at least 6 characters long!');
                return false;
            }
            
            return true;
        }
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>