<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

// Generate member number
$member_number = 'M' . date('Ymd') . rand(1000, 9999);

// Generate member type number (for display)
$member_type_number = 'MT' . date('Ymd') . rand(100, 999);

$conn = getDbConnection();

// Get member types for dropdown
$sql = "SELECT id, member_type_number, member_type_name FROM member_types ORDER BY member_type_name";
$member_types_result = mysqli_query($conn, $sql);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect form data
    $member_number = mysqli_real_escape_string($conn, $_POST['member_number']);
    $member_type_id = mysqli_real_escape_string($conn, $_POST['member_type_id']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $middle_name = mysqli_real_escape_string($conn, $_POST['middle_name']);
    $surname = mysqli_real_escape_string($conn, $_POST['surname']);
    $nationality = mysqli_real_escape_string($conn, $_POST['nationality']);
    $date_of_birth = mysqli_real_escape_string($conn, $_POST['date_of_birth']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    
    // Validate required fields
    if (empty($first_name) || empty($surname) || empty($date_of_birth)) {
        $error = 'Please fill in all required fields!';
    } else {
        // Check if member number exists
        $check_sql = "SELECT id FROM members WHERE member_number = '$member_number'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Member number already exists!';
        } else {
            // Insert member
            $sql = "INSERT INTO members (member_number, member_type_id, first_name, middle_name, 
                    surname, nationality, date_of_birth, gender) 
                    VALUES ('$member_number', '$member_type_id', '$first_name', '$middle_name', 
                    '$surname', '$nationality', '$date_of_birth', '$gender')";
            
            if (mysqli_query($conn, $sql)) {
                $success = 'Member registered successfully!';
                // Reset form
                $member_number = 'M' . date('Ymd') . rand(1000, 9999);
                $first_name = $middle_name = $surname = $nationality = '';
                $date_of_birth = '';
                $gender = 'Male';
            } else {
                $error = 'Error: ' . mysqli_error($conn);
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
    <title>Add Member - <?php echo SITE_NAME; ?></title>
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
                <h1><i class="fas fa-user-plus"></i> Add New Member</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Members
                </a>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3>Member Registration Form</h3>
                    <p>Fill all fields as required</p>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="member_number">Member Number *</label>
                                <input type="text" id="member_number" name="member_number" 
                                       value="<?php echo htmlspecialchars($member_number); ?>" 
                                       required readonly>
                                <small>Auto-generated</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="member_type_id">Member Type *</label>
                                <select id="member_type_id" name="member_type_id" required>
                                    <option value="">Select Member Type</option>
                                    <?php while ($type = mysqli_fetch_assoc($member_types_result)): ?>
                                    <option value="<?php echo $type['id']; ?>">
                                        <?php echo htmlspecialchars($type['member_type_name'] . ' (' . $type['member_type_number'] . ')'); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name *</label>
                                <input type="text" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($first_name ?? ''); ?>" 
                                       required placeholder="Enter first name">
                            </div>
                            
                            <div class="form-group">
                                <label for="middle_name">Middle Name</label>
                                <input type="text" id="middle_name" name="middle_name" 
                                       value="<?php echo htmlspecialchars($middle_name ?? ''); ?>" 
                                       placeholder="Enter middle name">
                            </div>
                            
                            <div class="form-group">
                                <label for="surname">Surname *</label>
                                <input type="text" id="surname" name="surname" 
                                       value="<?php echo htmlspecialchars($surname ?? ''); ?>" 
                                       required placeholder="Enter surname">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nationality">Nationality *</label>
                                <select id="nationality" name="nationality" required>
                                    <option value="Cameroonian" selected>Cameroonian</option>
                                    <option value="Ghanaian">Ghanaian</option>
                                    <option value="Nigerian">Nigerian</option>
                                    <option value="Ivorian">Ivorian</option>
                                    <option value="Senegalese">Senegalese</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth *</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" 
                                       value="<?php echo htmlspecialchars($date_of_birth ?? ''); ?>" 
                                       required max="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Gender *</label>
                                <div class="radio-group">
                                    <label class="radio-label">
                                        <input type="radio" name="gender" value="Male" 
                                               <?php echo ($gender ?? 'Male') == 'Male' ? 'checked' : ''; ?>>
                                        Male
                                    </label>
                                    <label class="radio-label">
                                        <input type="radio" name="gender" value="Female"
                                               <?php echo ($gender ?? '') == 'Female' ? 'checked' : ''; ?>>
                                        Female
                                    </label>
                                    <label class="radio-label">
                                        <input type="radio" name="gender" value="Others"
                                               <?php echo ($gender ?? '') == 'Others' ? 'checked' : ''; ?>>
                                        Others
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Register Member
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset Form
                            </button>
                            <a href="index.php" class="btn btn-outline">Cancel</a>
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