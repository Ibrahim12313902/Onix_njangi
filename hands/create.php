<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

// Generate hand number
$hand_number = 'H' . date('Ymd') . rand(1000, 9999);

$conn = getDbConnection();
$error = '';
$success = '';

// Get members for dropdown
$members_sql = "SELECT id, member_number, first_name, middle_name, surname FROM members ORDER BY first_name";
$members_result = mysqli_query($conn, $members_sql);

// Get hand types for dropdown
$hand_types_sql = "SELECT id, hand_type_number, hand_type_name FROM hand_types ORDER BY hand_type_name";
$hand_types_result = mysqli_query($conn, $hand_types_sql);

// Get hand statuses for dropdown
$hand_status_sql = "SELECT id, hand_status_number, hand_status_name FROM hand_status ORDER BY hand_status_name";
$hand_status_result = mysqli_query($conn, $hand_status_sql);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $hand_number = mysqli_real_escape_string($conn, $_POST['hand_number']);
    $member_id = mysqli_real_escape_string($conn, $_POST['member_id']);
    $hand_type_id = mysqli_real_escape_string($conn, $_POST['hand_type_id']);
    $hand_status_id = mysqli_real_escape_string($conn, $_POST['hand_status_id']);
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $opening_date = mysqli_real_escape_string($conn, $_POST['opening_date']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    
    if (empty($member_id) || empty($hand_type_id) || empty($hand_status_id)) {
        $error = 'Please select all required fields!';
    } else {
        // Check if hand number exists
        $check_sql = "SELECT id FROM hands WHERE hand_number = '$hand_number'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Hand number already exists!';
        } else {
            $sql = "INSERT INTO hands (hand_number, member_id, hand_type_id, hand_status_id, amount, opening_date, notes) 
                    VALUES ('$hand_number', '$member_id', '$hand_type_id', '$hand_status_id', '$amount', '$opening_date', '$notes')";
            
            if (mysqli_query($conn, $sql)) {
                $success = 'Hand opened successfully!';
                $hand_number = 'H' . date('Ymd') . rand(1000, 9999);
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
    <title>Open Hand - <?php echo SITE_NAME; ?></title>
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
                <h1><i class="fas fa-hand-holding-heart"></i> Member Hand Opening Form</h1>
                <div class="page-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Hands
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
                    <h3>Create New Hand / Manage Hand / Print All Hands</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="hand_number">Hand Number *</label>
                            <input type="text" id="hand_number" name="hand_number" 
                                   value="<?php echo htmlspecialchars($hand_number); ?>" 
                                   required readonly class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="member_id">Member Information *</label>
                            <select id="member_id" name="member_id" required class="form-control">
                                <option value="">Select Member</option>
                                <?php while ($member = mysqli_fetch_assoc($members_result)): ?>
                                <option value="<?php echo $member['id']; ?>">
                                    <?php echo $member['member_number'] . ' - ' . 
                                          $member['first_name'] . ' ' . 
                                          ($member['middle_name'] ? $member['middle_name'] . ' ' : '') . 
                                          $member['surname']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="hand_type_id">Hand Type *</label>
                                <select id="hand_type_id" name="hand_type_id" required class="form-control">
                                    <option value="">Select Hand Type</option>
                                    <?php while ($type = mysqli_fetch_assoc($hand_types_result)): ?>
                                    <option value="<?php echo $type['id']; ?>">
                                        <?php echo $type['hand_type_name'] . ' (' . $type['hand_type_number'] . ')'; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="hand_status_id">Hand Status *</label>
                                <select id="hand_status_id" name="hand_status_id" required class="form-control">
                                    <option value="">Select Status</option>
                                    <?php while ($status = mysqli_fetch_assoc($hand_status_result)): ?>
                                    <option value="<?php echo $status['id']; ?>">
                                        <?php echo $status['hand_status_name']; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="amount">Amount (FCFA)</label>
                                <input type="number" id="amount" name="amount" 
                                       value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>" 
                                       placeholder="Enter amount" class="form-control" min="0" step="100">
                            </div>
                            
                            <div class="form-group">
                                <label for="opening_date">Opening Date</label>
                                <input type="date" id="opening_date" name="opening_date" 
                                       value="<?php echo htmlspecialchars($_POST['opening_date'] ?? date('Y-m-d')); ?>" 
                                       class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea id="notes" name="notes" rows="3" 
                                      placeholder="Additional notes (optional)"
                                      class="form-control"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-hand-holding-heart"></i> Open Hand
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset
                            </button>
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