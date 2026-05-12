<?php
require_once 'config.php';

if (isMemberLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

require_once '../config/database.php';
$conn = getDbConnection();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $surname = mysqli_real_escape_string($conn, $_POST['surname']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($first_name) || empty($surname) || empty($phone) || empty($password)) {
        $error = 'Please fill in all required fields!';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters!';
    } else {
        // Check if phone already exists
        if (!empty($phone)) {
            $check = mysqli_query($conn, "SELECT id FROM members WHERE phone = '$phone' AND password_hash IS NOT NULL AND password_hash != ''");
            if (mysqli_num_rows($check) > 0) {
                $error = 'This phone number is already registered! Please login instead.';
            }
        }
        
        // Check if email already exists (if provided and not empty)
        if (!empty($email) && empty($error)) {
            $check = mysqli_query($conn, "SELECT id FROM members WHERE email = '$email' AND password_hash IS NOT NULL AND password_hash != ''");
            if (mysqli_num_rows($check) > 0) {
                $error = 'This email is already registered! Please login instead.';
            }
        }
        
        if (empty($error)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Check if member exists by phone (without password)
            $check = mysqli_query($conn, "SELECT id FROM members WHERE phone = '$phone'");
            
            if (mysqli_num_rows($check) > 0) {
                // Update existing member without password
                $member = mysqli_fetch_assoc($check);
                $member_id = $member['id'];
                
                $sql = "UPDATE members SET 
                        password_hash = '$password_hash',
                        email = " . (!empty($email) ? "'$email'" : "email") . ",
                        is_active = 1
                        WHERE id = '$member_id'";
                
                if (mysqli_query($conn, $sql)) {
                    header('Location: login.php?registered=1');
                    exit();
                } else {
                    $error = 'Error: ' . mysqli_error($conn);
                }
            } else {
                // Create new member
                $new_member_number = 'M' . date('Ymd') . rand(1000, 9999);
                
                $sql = "INSERT INTO members (member_number, first_name, surname, phone, email, password_hash, is_active, registration_date)
                        VALUES ('$new_member_number', '$first_name', '$surname', '$phone', " . (!empty($email) ? "'$email'" : "NULL") . ", '$password_hash', 1, NOW())";
                
                if (mysqli_query($conn, $sql)) {
                    header('Location: login.php?registered=1');
                    exit();
                } else {
                    $error = 'Error creating account: ' . mysqli_error($conn);
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
    <title>Register - ONIX Njangi</title>
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
        
        .register-container {
            width: 100%;
            max-width: 480px;
        }
        
        .register-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
        }
        
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 25px;
            text-align: center;
        }
        
        .register-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .register-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .register-body {
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
        
        .form-group label span {
            color: #dc3545;
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .btn-register {
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
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .login-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        .login-link:hover {
            text-decoration: underline;
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
        
        .terms {
            font-size: 12px;
            color: #666;
            text-align: center;
            margin-top: 20px;
            line-height: 1.6;
        }
        
        @media (max-width: 500px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .register-body {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="back-home">
            <a href="index.php">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
        
        <div class="register-box">
            <div class="register-header">
                <h1><i class="fas fa-user-plus"></i> Join ONIX Njangi</h1>
                <p>Create your account and start saving today</p>
            </div>
            
            <div class="register-body">
                <?php if (!empty($error)): ?>
                    <div class="error-msg">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name <span>*</span></label>
                            <input type="text" name="first_name" placeholder="Your first name" required value="<?php echo $_POST['first_name'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Surname <span>*</span></label>
                            <input type="text" name="surname" placeholder="Your surname" required value="<?php echo $_POST['surname'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number <span>*</span></label>
                        <input type="tel" name="phone" placeholder="6XX XXX XXX" required value="<?php echo $_POST['phone'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="your@email.com (optional)" value="<?php echo $_POST['email'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Password <span>*</span></label>
                            <input type="password" name="password" placeholder="Min 6 characters" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label>Confirm Password <span>*</span></label>
                            <input type="password" name="confirm_password" placeholder="Re-enter password" required minlength="6">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-register">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </form>
                
                <p class="terms">
                    By creating an account, you agree to our terms and conditions.
                </p>
                
                <a href="login.php" class="login-link">
                    <i class="fas fa-sign-in-alt"></i> Already have an account? Login here
                </a>
            </div>
        </div>
    </div>
</body>
</html>