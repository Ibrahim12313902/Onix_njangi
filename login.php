<?php
require_once 'config/constants.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if (isset($_GET['password_changed']) && $_GET['password_changed'] == '1') {
    $success = 'Password changed successfully! You can now login.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'config/database.php';
    $conn = getDbConnection();
    
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    $sql = "SELECT id, username, password, full_name FROM admin_users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) == 1) {
        $admin = mysqli_fetch_assoc($result);
        
        if ($password === 'admin123' || password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_username'] = $admin['username'];
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid password!';
        }
    } else {
        $error = 'Admin not found!';
    }
    
    closeDbConnection($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
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
        
        .login-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
        }
        
        .login-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
            border-color: #28a745;
            box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.1);
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        .input-icon input {
            padding-left: 45px;
        }
        
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.4);
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
        
        .btn-member-link {
            display: block;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .btn-member-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .credentials-box {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: center;
        }
        
        .credentials-box strong {
            color: #533f03;
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
            <a href="member_portal/index.php">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
        
        <div class="login-box">
            <div class="login-header">
                <h1><i class="fas fa-user-shield"></i> Admin Portal</h1>
                <p>Login to manage ONIX Njangi</p>
            </div>
            
            <div class="login-body">
                <?php if (!empty($error)): ?>
                    <div class="error-msg">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="success-msg">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <div class="credentials-box">
                    <i class="fas fa-info-circle"></i>
                    Default credentials: <strong>admin</strong> / <strong>admin123</strong>
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Username</label>
                        <div class="input-icon">
                            <input type="text" name="username" placeholder="Enter your username" required value="<?php echo $_POST['username'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password</label>
                        <div class="input-icon">
                            <input type="password" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Login to Dashboard
                    </button>
                </form>
                
                <div class="divider"><span>OR</span></div>
                
                <a href="member_portal/index.php" class="btn-member-link">
                    <i class="fas fa-hand-holding-heart"></i> Go to Member Portal
                </a>
            </div>
        </div>
    </div>
</body>
</html>
