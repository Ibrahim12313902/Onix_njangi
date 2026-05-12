<?php
require_once 'config.php';

if (isMemberLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success = 'Registration successful! Please login with your credentials.';
}

if (isset($_GET['password_set']) && $_GET['password_set'] == '1') {
    $success = 'Password set successfully! You can now login.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once '../config/database.php';
    $conn = getDbConnection();
    
    $login_type = $_POST['login_type'] ?? 'phone';
    $password = $_POST['password'];
    
    if ($login_type == 'phone') {
        $identifier = mysqli_real_escape_string($conn, $_POST['phone']);
        $sql = "SELECT * FROM members WHERE phone = '$identifier' AND is_active = 1";
    } elseif ($login_type == 'member') {
        $identifier = mysqli_real_escape_string($conn, $_POST['member_number']);
        $sql = "SELECT * FROM members WHERE member_number = '$identifier' AND is_active = 1";
    } else {
        $identifier = mysqli_real_escape_string($conn, $_POST['email']);
        $sql = "SELECT * FROM members WHERE email = '$identifier' AND is_active = 1";
    }
    
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) == 1) {
        $member = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $member['password_hash']) || md5($password) == $member['password_hash']) {
            $_SESSION['member_id'] = $member['id'];
            $_SESSION['member_name'] = $member['first_name'] . ' ' . $member['surname'];
            $_SESSION['member_number'] = $member['member_number'];
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid password!';
        }
    } else {
        $error = 'Member not found or account is inactive!';
    }
    
    closeDbConnection($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ONIX Njangi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 480px;
        }
        
        .login-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 25px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .login-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .login-body {
            padding: 35px 30px;
        }
        
        .login-type-tabs {
            display: flex;
            margin-bottom: 25px;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 5px;
        }
        
        .login-type-tab {
            flex: 1;
            padding: 12px 10px;
            text-align: center;
            cursor: pointer;
            border-radius: 8px;
            color: #666;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .login-type-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .login-field {
            display: none;
        }
        
        .login-field.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            border-left: 4px solid #dc3545;
        }
        
        .success-msg {
            background: #d4edda;
            color: #155724;
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            border-left: 4px solid #28a745;
        }
        
        .btn-register-link {
            display: block;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .btn-register-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
        }
        
        .btn-set-password {
            display: block;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .btn-set-password:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.3);
        }
        
        .back-home {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .back-home a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-size: 14px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        
        .back-home a:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
            color: #999;
            font-size: 14px;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e9ecef;
        }
        
        .divider span {
            padding: 0 15px;
        }
        
        .help-text {
            background: #fff3cd;
            color: #856404;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: center;
        }
        
        @media (max-width: 500px) {
            .login-body {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="back-home">
            <a href="index.php">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
        
        <div class="login-box">
            <div class="login-header">
                <h1><i class="fas fa-hand-holding-heart"></i> Welcome Back</h1>
                <p>Login to your ONIX Njangi account</p>
            </div>
            
            <div class="login-body">
                <?php if (!empty($error)): ?>
                    <div class="error-msg">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="success-msg">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <div class="help-text">
                    <i class="fas fa-info-circle"></i> 
                    Members added by admin can login with their <strong>Member Number</strong>
                </div>
                
                <form method="POST" action="">
                    <div class="login-type-tabs">
                        <div class="login-type-tab" onclick="switchTab('phone')">
                            <i class="fas fa-phone"></i> Phone
                        </div>
                        <div class="login-type-tab active" onclick="switchTab('member')">
                            <i class="fas fa-id-badge"></i> Member #
                        </div>
                    </div>
                    
                    <input type="hidden" name="login_type" id="login_type" value="member">
                    
                    <div class="login-field" id="phone_field">
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" id="phone_input" placeholder="Enter your phone number">
                        </div>
                    </div>
                    
                    <div class="login-field active" id="member_field">
                        <div class="form-group">
                            <label>Member Number</label>
                            <input type="text" name="member_number" id="member_input" placeholder="e.g., M20260404001" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Enter your password" required>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>
                
                <div class="divider"><span>OR</span></div>
                
                <a href="set_password.php" class="btn-set-password">
                    <i class="fas fa-key"></i> First Time? Set Your Password
                </a>
                
                <div style="margin-top: 15px;">
                    <a href="register.php" class="btn-register-link">
                        <i class="fas fa-user-plus"></i> Create New Account
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(type) {
            document.querySelectorAll('.login-type-tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.login-field').forEach(field => field.classList.remove('active'));
            
            document.querySelector(`[onclick="switchTab('${type}')"]`).classList.add('active');
            document.getElementById(type + '_field').classList.add('active');
            document.getElementById('login_type').value = type;
            
            if (type === 'phone') {
                document.getElementById('phone_input').setAttribute('required', 'required');
                document.getElementById('member_input').removeAttribute('required');
            } else {
                document.getElementById('member_input').setAttribute('required', 'required');
                document.getElementById('phone_input').removeAttribute('required');
            }
        }
    </script>
</body>
</html>
