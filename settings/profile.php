<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();
$success = '';
$error = '';

// Get current admin info
$admin_id = $_SESSION['admin_id'];
$sql = "SELECT * FROM admin_users WHERE id = '$admin_id'";
$result = mysqli_query($conn, $sql);
$admin = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    
    // Handle profile picture upload
    $profile_pic = $admin['profile_pic'] ?? '';
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_pic']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $upload_dir = '../uploads/profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = 'admin_' . $admin_id . '_' . time() . '.' . $ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)) {
                $profile_pic = 'uploads/profiles/' . $new_filename;
            }
        }
    }
    
    $sql = "UPDATE admin_users SET 
            full_name = '$full_name',
            email = '$email',
            phone = '$phone',
            profile_pic = '$profile_pic'
            WHERE id = '$admin_id'";
    
    if (mysqli_query($conn, $sql)) {
        $_SESSION['admin_name'] = $full_name;
        $success = 'Profile updated successfully!';
        
        // Refresh admin data
        $sql = "SELECT * FROM admin_users WHERE id = '$admin_id'";
        $result = mysqli_query($conn, $sql);
        $admin = mysqli_fetch_assoc($result);
    } else {
        $error = 'Error updating profile: ' . mysqli_error($conn);
    }
}

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .profile-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .profile-avatar {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
        }
        
        .avatar-upload {
            position: absolute;
            bottom: 0;
            right: 0;
            background: white;
            color: #667eea;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .avatar-upload:hover {
            transform: scale(1.1);
        }
        
        .profile-body {
            padding: 40px;
        }
        
        .info-group {
            margin-bottom: 25px;
        }
        
        .info-label {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .edit-form {
            display: none;
        }
        
        .edit-form.active {
            display: block;
        }
        
        .view-mode {
            display: block;
        }
        
        .view-mode.hidden {
            display: none;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
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
                <h1><i class="fas fa-user-circle"></i> My Profile</h1>
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
            
            <div class="profile-container">
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <?php if (!empty($admin['profile_pic']) && file_exists('../' . $admin['profile_pic'])): ?>
                                <img src="../<?php echo htmlspecialchars($admin['profile_pic']); ?>" alt="Profile">
                            <?php else: ?>
                                <img src="../assets/default-avatar.png" alt="Profile">
                            <?php endif; ?>
                            <label for="profile_pic" class="avatar-upload">
                                <i class="fas fa-camera"></i>
                            </label>
                        </div>
                        <h2><?php echo htmlspecialchars($admin['full_name'] ?? $admin['username']); ?></h2>
                        <p><?php echo htmlspecialchars($admin['email']); ?></p>
                    </div>
                    
                    <div class="profile-body">
                        <!-- View Mode -->
                        <div id="viewMode" class="view-mode">
                            <div class="info-group">
                                <div class="info-label">Username</div>
                                <div class="info-value"><?php echo htmlspecialchars($admin['username']); ?></div>
                            </div>
                            
                            <div class="info-group">
                                <div class="info-label">Full Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($admin['full_name'] ?? 'Not set'); ?></div>
                            </div>
                            
                            <div class="info-group">
                                <div class="info-label">Email Address</div>
                                <div class="info-value"><?php echo htmlspecialchars($admin['email']); ?></div>
                            </div>
                            
                            <div class="info-group">
                                <div class="info-label">Phone</div>
                                <div class="info-value"><?php echo htmlspecialchars($admin['phone'] ?? 'Not set'); ?></div>
                            </div>
                            
                            <div class="info-group">
                                <div class="info-label">Member Since</div>
                                <div class="info-value"><?php echo formatDate($admin['created_at']); ?></div>
                            </div>
                            
                            <div class="action-buttons">
                                <button onclick="toggleEdit(true)" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> Edit Profile
                                </button>
                                <a href="password.php" class="btn btn-warning">
                                    <i class="fas fa-key"></i> Change Password
                                </a>
                            </div>
                        </div>
                        
                        <!-- Edit Mode -->
                        <div id="editMode" class="edit-form">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input type="text" id="username" value="<?php echo htmlspecialchars($admin['username']); ?>" 
                                           class="form-control" readonly disabled>
                                    <small>Username cannot be changed</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="full_name">Full Name</label>
                                    <input type="text" id="full_name" name="full_name" 
                                           value="<?php echo htmlspecialchars($admin['full_name'] ?? ''); ?>" 
                                           class="form-control" placeholder="Enter your full name">
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email Address *</label>
                                    <input type="email" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($admin['email']); ?>" 
                                           required class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="text" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>" 
                                           class="form-control" placeholder="+237 6XX XXX XXX">
                                </div>
                                
                                <div class="form-group">
                                    <label for="profile_pic">Profile Picture</label>
                                    <input type="file" id="profile_pic" name="profile_pic" 
                                           accept="image/*" class="form-control">
                                    <small>Max size: 2MB. Allowed: JPG, PNG, GIF</small>
                                </div>
                                
                                <div class="action-buttons">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                    <button type="button" onclick="toggleEdit(false)" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function toggleEdit(show) {
            if (show) {
                document.getElementById('viewMode').style.display = 'none';
                document.getElementById('editMode').style.display = 'block';
            } else {
                document.getElementById('viewMode').style.display = 'block';
                document.getElementById('editMode').style.display = 'none';
            }
        }
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>