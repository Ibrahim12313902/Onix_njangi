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

// Get hand data with joins
$sql = "SELECT h.*, 
               m.member_number, m.first_name, m.middle_name, m.surname,
               ht.hand_type_name, hs.hand_status_name
        FROM hands h
        LEFT JOIN members m ON h.member_id = m.id
        LEFT JOIN hand_types ht ON h.hand_type_id = ht.id
        LEFT JOIN hand_status hs ON h.hand_status_id = hs.id
        WHERE h.id = '$id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header('Location: index.php?error=Hand not found');
    exit();
}

$hand = mysqli_fetch_assoc($result);

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
    $member_id = mysqli_real_escape_string($conn, $_POST['member_id']);
    $hand_type_id = mysqli_real_escape_string($conn, $_POST['hand_type_id']);
    $hand_status_id = mysqli_real_escape_string($conn, $_POST['hand_status_id']);
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $opening_date = mysqli_real_escape_string($conn, $_POST['opening_date']);
    $closing_date = !empty($_POST['closing_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['closing_date']) . "'" : "NULL";
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    
    if (empty($member_id) || empty($hand_type_id) || empty($hand_status_id)) {
        $error = 'Please select all required fields!';
    } else {
        $sql = "UPDATE hands SET 
                member_id = '$member_id',
                hand_type_id = '$hand_type_id',
                hand_status_id = '$hand_status_id',
                amount = '$amount',
                opening_date = '$opening_date',
                closing_date = $closing_date,
                notes = '$notes'
                WHERE id = '$id'";
        
        if (mysqli_query($conn, $sql)) {
            $success = 'Hand updated successfully!';
            // Refresh data
            $sql = "SELECT h.*, 
                           m.member_number, m.first_name, m.middle_name, m.surname,
                           ht.hand_type_name, hs.hand_status_name
                    FROM hands h
                    LEFT JOIN members m ON h.member_id = m.id
                    LEFT JOIN hand_types ht ON h.hand_type_id = ht.id
                    LEFT JOIN hand_status hs ON h.hand_status_id = hs.id
                    WHERE h.id = '$id'";
            $result = mysqli_query($conn, $sql);
            $hand = mysqli_fetch_assoc($result);
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
    <title>Edit Hand - <?php echo SITE_NAME; ?></title>
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
                <h1><i class="fas fa-edit"></i> Edit Hand: <?php echo htmlspecialchars($hand['hand_number']); ?></h1>
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
                    <h3>Edit Hand Information</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Hand Number</label>
                            <input type="text" value="<?php echo htmlspecialchars($hand['hand_number']); ?>" 
                                   readonly disabled class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="member_id">Member *</label>
                            <select id="member_id" name="member_id" required class="form-control">
                                <option value="">Select Member</option>
                                <?php 
                                mysqli_data_seek($members_result, 0);
                                while ($member = mysqli_fetch_assoc($members_result)): 
                                ?>
                                <option value="<?php echo $member['id']; ?>" 
                                    <?php echo ($member['id'] == $hand['member_id']) ? 'selected' : ''; ?>>
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
                                    <?php 
                                    mysqli_data_seek($hand_types_result, 0);
                                    while ($type = mysqli_fetch_assoc($hand_types_result)): 
                                    ?>
                                    <option value="<?php echo $type['id']; ?>" 
                                        <?php echo ($type['id'] == $hand['hand_type_id']) ? 'selected' : ''; ?>>
                                        <?php echo $type['hand_type_name']; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="hand_status_id">Hand Status *</label>
                                <select id="hand_status_id" name="hand_status_id" required class="form-control">
                                    <option value="">Select Status</option>
                                    <?php 
                                    mysqli_data_seek($hand_status_result, 0);
                                    while ($status = mysqli_fetch_assoc($hand_status_result)): 
                                    ?>
                                    <option value="<?php echo $status['id']; ?>" 
                                        <?php echo ($status['id'] == $hand['hand_status_id']) ? 'selected' : ''; ?>>
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
                                       value="<?php echo htmlspecialchars($hand['amount']); ?>" 
                                       class="form-control" min="0" step="100">
                            </div>
                            
                            <div class="form-group">
                                <label for="opening_date">Opening Date</label>
                                <input type="date" id="opening_date" name="opening_date" 
                                       value="<?php echo htmlspecialchars($hand['opening_date']); ?>" 
                                       class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label for="closing_date">Closing Date</label>
                                <input type="date" id="closing_date" name="closing_date" 
                                       value="<?php echo htmlspecialchars($hand['closing_date'] ?? ''); ?>" 
                                       class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea id="notes" name="notes" rows="3" 
                                      class="form-control"><?php echo htmlspecialchars($hand['notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Hand
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