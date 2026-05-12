<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();
$error = '';
$success = '';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?error=No ID provided');
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Get member data
$sql = "SELECT m.*, mt.member_type_name 
        FROM members m 
        LEFT JOIN member_types mt ON m.member_type_id = mt.id 
        WHERE m.id = '$id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header('Location: index.php?error=Member not found');
    exit();
}

$member = mysqli_fetch_assoc($result);

// Get member types for dropdown
$types_sql = "SELECT id, member_type_name FROM member_types ORDER BY member_type_name";
$types_result = mysqli_query($conn, $types_sql);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $member_type_id = mysqli_real_escape_string($conn, $_POST['member_type_id']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $middle_name = mysqli_real_escape_string($conn, $_POST['middle_name']);
    $surname = mysqli_real_escape_string($conn, $_POST['surname']);
    $nationality = mysqli_real_escape_string($conn, $_POST['nationality']);
    $date_of_birth = mysqli_real_escape_string($conn, $_POST['date_of_birth']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    if (empty($first_name) || empty($surname) || empty($date_of_birth)) {
        $error = 'Please fill in all required fields!';
    } else {
        $sql = "UPDATE members SET 
                member_type_id = " . ($member_type_id ? "'$member_type_id'" : "NULL") . ",
                first_name = '$first_name',
                middle_name = '$middle_name',
                surname = '$surname',
                nationality = '$nationality',
                date_of_birth = '$date_of_birth',
                gender = '$gender',
                phone = '$phone',
                email = '$email',
                address = '$address'
                WHERE id = '$id'";
        
        if (mysqli_query($conn, $sql)) {
            $success = 'Member updated successfully!';
            // Refresh data
            $sql = "SELECT m.*, mt.member_type_name 
                    FROM members m 
                    LEFT JOIN member_types mt ON m.member_type_id = mt.id 
                    WHERE m.id = '$id'";
            $result = mysqli_query($conn, $sql);
            $member = mysqli_fetch_assoc($result);
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
    <title>Edit Member - <?php echo SITE_NAME; ?></title>
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
                <h1><i class="fas fa-user-edit"></i> Edit Member</h1>
                <div class="page-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Members
                    </a>
                    <a href="view.php?id=<?php echo $member['id']; ?>" class="btn btn-info">
                        <i class="fas fa-eye"></i> View
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
                    <h3>Edit Member Information</h3>
                    <p>Member Number: <strong><?php echo htmlspecialchars($member['member_number']); ?></strong></p>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="member_type_id">Member Type</label>
                                <select id="member_type_id" name="member_type_id" class="form-control">
                                    <option value="">Select Member Type</option>
                                    <?php while ($type = mysqli_fetch_assoc($types_result)): ?>
                                    <option value="<?php echo $type['id']; ?>" 
                                        <?php echo ($type['id'] == $member['member_type_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['member_type_name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name *</label>
                                <input type="text" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($member['first_name']); ?>" 
                                       required class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label for="middle_name">Middle Name</label>
                                <input type="text" id="middle_name" name="middle_name" 
                                       value="<?php echo htmlspecialchars($member['middle_name'] ?? ''); ?>" 
                                       class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label for="surname">Surname *</label>
                                <input type="text" id="surname" name="surname" 
                                       value="<?php echo htmlspecialchars($member['surname']); ?>" 
                                       required class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nationality">Nationality</label>
                                <input type="text" id="nationality" name="nationality" 
                                       value="<?php echo htmlspecialchars($member['nationality']); ?>" 
                                       class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth *</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" 
                                       value="<?php echo htmlspecialchars($member['date_of_birth']); ?>" 
                                       required class="form-control" max="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Gender *</label>
                                <div class="radio-group">
                                    <label>
                                        <input type="radio" name="gender" value="Male" 
                                               <?php echo $member['gender'] == 'Male' ? 'checked' : ''; ?>>
                                        Male
                                    </label>
                                    <label>
                                        <input type="radio" name="gender" value="Female"
                                               <?php echo $member['gender'] == 'Female' ? 'checked' : ''; ?>>
                                        Female
                                    </label>
                                    <label>
                                        <input type="radio" name="gender" value="Others"
                                               <?php echo $member['gender'] == 'Others' ? 'checked' : ''; ?>>
                                        Others
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($member['phone'] ?? ''); ?>" 
                                       class="form-control" placeholder="+237 6XX XXX XXX">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($member['email'] ?? ''); ?>" 
                                       class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" rows="3" 
                                      class="form-control"><?php echo htmlspecialchars($member['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Member
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
