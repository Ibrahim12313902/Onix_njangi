<?php
require_once 'config.php';

if (isMemberLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

require_once '../config/database.php';
$conn = getDbConnection();

$error = '';
$success = '';
$step = 1;
$member_data = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['verify_member'])) {
        $member_number = mysqli_real_escape_string($conn, $_POST['member_number']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        
        $sql = "SELECT * FROM members WHERE member_number = '$member_number'";
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) == 1) {
            $member = mysqli_fetch_assoc($result);
            
            if (!empty($member['phone']) && $member['phone'] == $phone) {
                $step = 2;
                $member_data = $member;
                $_SESSION['reset_member_id'] = $member['id'];
            } elseif (empty($member['phone'])) {
                $step = 2;
                $member_data = $member;
                $_SESSION['reset_member_id'] = $member['id'];
            } else {
                $error = 'Phone number does not match our records!';
            }
        } else {
            $error = 'Member number not found!';
        }
    }
    
    if (isset($_POST['set_password'])) {
        if (!isset($_SESSION['reset_member_id'])) {
            $error = 'Session expired. Please start again.';
        } else {
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters!';
            } elseif ($password !== $confirm_password) {
                $error = 'Passwords do not match!';
            } else {
                $member_id = (int)$_SESSION['reset_member_id'];
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $sql = "UPDATE members SET password_hash = '$password_hash', is_active = 1 WHERE id = $member_id";
                
                if (mysqli_query($conn, $sql)) {
                    unset($_SESSION['reset_member_id']);
                    header('Location: login.php?password_set=1');
                    exit();
                } else {
                    $error = 'Error setting password: ' . mysqli_error($conn);
                }
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
    <title>Set Password - ONIX Njangi</title>
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
        
        .container {
            width: 100%;
            max-width: 480px;
        }
        
        .box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
        }
        
        .header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 30px 25px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .body {
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
            border-color: #f59e0b;
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);
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
        
        .member-info {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .member-info .name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .member-info .number {
            font-size: 14px;
            color: #667eea;
            margin-top: 5px;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 25px;
        }
        
        .step {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }
        
        .step.active {
            background: #f59e0b;
            color: white;
        }
        
        .step.completed {
            background: #28a745;
            color: white;
        }
        
        .step.inactive {
            background: #e9ecef;
            color: #999;
        }
        
        .step-label {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .steps-container {
            margin-bottom: 30px;
        }
        
        @media (max-width: 500px) {
            .body {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="back-home">
            <a href="login.php">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
        
        <div class="box">
            <div class="header">
                <h1><i class="fas fa-key"></i> Set Your Password</h1>
                <p>Members added by admin can set their password here</p>
            </div>
            
            <div class="body">
                <?php if (!empty($error)): ?>
                    <div class="error-msg">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="steps-container">
                    <div class="step-indicator">
                        <div>
                            <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : 'inactive'; ?>">
                                <?php echo $step > 1 ? '<i class="fas fa-check"></i>' : '1'; ?>
                            </div>
                            <div class="step-label">Verify</div>
                        </div>
                        <div>
                            <div class="step <?php echo $step >= 2 ? 'active' : 'inactive'; ?>">2</div>
                            <div class="step-label">Set Password</div>
                        </div>
                    </div>
                </div>
                
                <?php if ($step == 1): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label><i class="fas fa-id-badge"></i> Member Number</label>
                        <input type="text" name="member_number" placeholder="e.g., M20260404001" required value="<?php echo $_POST['member_number'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="tel" name="phone" placeholder="Enter your registered phone number" required value="<?php echo $_POST['phone'] ?? ''; ?>">
                    </div>
                    
                    <button type="submit" name="verify_member" class="btn">
                        <i class="fas fa-search"></i> Verify My Account
                    </button>
                </form>
                <?php endif; ?>
                
                <?php if ($step == 2 && $member_data): ?>
                <div class="member-info">
                    <div class="name"><?php echo htmlspecialchars($member_data['first_name'] . ' ' . $member_data['surname']); ?></div>
                    <div class="number"><?php echo htmlspecialchars($member_data['member_number']); ?></div>
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> New Password</label>
                        <input type="password" name="password" placeholder="Min 6 characters" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Confirm Password</label>
                        <input type="password" name="confirm_password" placeholder="Re-enter your password" required minlength="6">
                    </div>
                    
                    <button type="submit" name="set_password" class="btn">
                        <i class="fas fa-check"></i> Set My Password
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
