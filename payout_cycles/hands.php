<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?error=No ID provided');
    exit();
}

$cycle_id = mysqli_real_escape_string($conn, $_GET['id']);

$sql = "SELECT * FROM payout_cycles WHERE id = '$cycle_id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header('Location: index.php?error=Cycle not found');
    exit();
}

$cycle = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_hands']) && isset($_POST['hands_to_add'])) {
        $hands_to_add = $_POST['hands_to_add'];
        
        $sql = "SELECT MAX(position_order) as max_pos FROM cycle_hands WHERE cycle_id = '$cycle_id'";
        $result = mysqli_query($conn, $sql);
        $max_pos = mysqli_fetch_assoc($result)['max_pos'] ?? 0;
        
        $added_count = 0;
        foreach ($hands_to_add as $hand_id) {
            $hand_id = (int)$hand_id;
            
            // Check if hand already belongs to this cycle
            $check_sql = "SELECT id FROM cycle_hands WHERE cycle_id = '$cycle_id' AND hand_id = '$hand_id'";
            $check_result = mysqli_query($conn, $check_sql);
            
            // Check if hand already belongs to another cycle
            $other_cycle_sql = "SELECT payout_cycle_id FROM hands WHERE id = '$hand_id' AND payout_cycle_id IS NOT NULL AND payout_cycle_id != '' AND payout_cycle_id != '$cycle_id'";
            $other_result = mysqli_query($conn, $other_cycle_sql);
            
            if (mysqli_num_rows($check_result) == 0 && mysqli_num_rows($other_result) == 0) {
                $max_pos++;
                $added_count++;
                
                $sql = "SELECT ht.payment_period_days 
                        FROM hands h 
                        JOIN hand_types ht ON h.hand_type_id = ht.id 
                        WHERE h.id = '$hand_id'";
                $result = mysqli_query($conn, $sql);
                $row = mysqli_fetch_assoc($result);
                $deadline_days = $row['payment_period_days'] ?? 30;
                $deadline_date = date('Y-m-d', strtotime($cycle['start_date'] . ' + ' . ($max_pos * $deadline_days) . ' days'));
                
                mysqli_query($conn, "INSERT INTO cycle_hands (cycle_id, hand_id, position_order) VALUES ('$cycle_id', '$hand_id', '$max_pos')");
                
                $sql = "INSERT INTO payment_deadlines (hand_id, deadline_date, amount_due) 
                         SELECT h.id, '$deadline_date', COALESCE(ht.default_amount, 0)
                         FROM hands h 
                         JOIN hand_types ht ON h.hand_type_id = ht.id 
                         WHERE h.id = '$hand_id'";
                mysqli_query($conn, $sql);
                
                mysqli_query($conn, "UPDATE hands SET payout_position = '$max_pos', payout_cycle_id = '$cycle_id' WHERE id = '$hand_id'");
            }
        }
        
        $sql = "SELECT COUNT(*) as count FROM cycle_hands WHERE cycle_id = '$cycle_id'";
        $result = mysqli_query($conn, $sql);
        $new_count = mysqli_fetch_assoc($result)['count'];
        mysqli_query($conn, "UPDATE payout_cycles SET total_hands = '$new_count' WHERE id = '$cycle_id'");
        
        header('Location: hands.php?id=' . $cycle_id . '&success=' . $added_count . ' hands added successfully!');
        exit();
    }
    
    if (isset($_POST['remove_hand']) && $_POST['remove_hand'] == '1' && !empty($_POST['hand_id'])) {
        $hand_id = (int)$_POST['hand_id'];
        
        mysqli_query($conn, "DELETE FROM payment_deadlines WHERE hand_id = '$hand_id'");
        mysqli_query($conn, "DELETE FROM cycle_hands WHERE cycle_id = '$cycle_id' AND hand_id = '$hand_id'");
        mysqli_query($conn, "UPDATE hands SET payout_position = NULL, payout_cycle_id = NULL WHERE id = '$hand_id'");
        
        $sql = "SELECT id, hand_id, position_order FROM cycle_hands WHERE cycle_id = '$cycle_id' ORDER BY position_order ASC";
        $result = mysqli_query($conn, $sql);
        $pos = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            mysqli_query($conn, "UPDATE cycle_hands SET position_order = '$pos' WHERE id = '" . $row['id'] . "'");
            mysqli_query($conn, "UPDATE hands SET payout_position = '$pos' WHERE id = '" . $row['hand_id'] . "'");
            $pos++;
        }
        
        $sql = "SELECT COUNT(*) as count FROM cycle_hands WHERE cycle_id = '$cycle_id'";
        $result = mysqli_query($conn, $sql);
        $new_count = mysqli_fetch_assoc($result)['count'];
        mysqli_query($conn, "UPDATE payout_cycles SET total_hands = '$new_count' WHERE id = '$cycle_id'");
        
        header('Location: hands.php?id=' . $cycle_id . '&success=Hand removed from cycle!');
        exit();
    }
    
    if (isset($_POST['update_positions']) && !isset($_POST['remove_hand'])) {
        $positions = $_POST['position'];
        
        foreach ($positions as $hand_id => $new_position) {
            $hand_id = mysqli_real_escape_string($conn, $hand_id);
            $new_position = (int)$new_position;
            
            $sql = "SELECT payout_position FROM hands WHERE id = '$hand_id'";
            $result = mysqli_query($conn, $sql);
            $row = mysqli_fetch_assoc($result);
            $old_position = $row['payout_position'] ?? 0;
            
            $reason = mysqli_real_escape_string($conn, $_POST['reason'] ?? 'Position modified by admin');
            
            mysqli_query($conn, "UPDATE hands SET payout_position = '$new_position' WHERE id = '$hand_id'");
            mysqli_query($conn, "UPDATE cycle_hands SET position_order = '$new_position' WHERE cycle_id = '$cycle_id' AND hand_id = '$hand_id'");
            
            mysqli_query($conn, "INSERT INTO position_history (hand_id, old_position, new_position, reason) 
                VALUES ('$hand_id', '$old_position', '$new_position', '$reason')");
        }
        
        header('Location: hands.php?id=' . $cycle_id . '&success=Positions updated successfully!');
        exit();
    }
}

$sql = "SELECT ch.id as cycle_hand_id, ch.hand_id, ch.position_order, h.hand_number, m.first_name, m.middle_name, m.surname, 
        ht.hand_type_name, ht.payment_period_days, ht.payment_period_type
        FROM cycle_hands ch
        JOIN hands h ON ch.hand_id = h.id
        JOIN members m ON h.member_id = m.id
        JOIN hand_types ht ON h.hand_type_id = ht.id
        WHERE ch.cycle_id = '$cycle_id'
        ORDER BY ch.position_order ASC";
$hands_result = mysqli_query($conn, $sql);

$hands = [];
while ($hand = mysqli_fetch_assoc($hands_result)) {
    $hands[] = $hand;
}

usort($hands, function($a, $b) {
    return $a['position_order'] - $b['position_order'];
});

$sql = "SELECT * FROM hand_types ORDER BY hand_type_name ASC";
$hand_types_result = mysqli_query($conn, $sql);
$hand_types = [];

while ($ht = mysqli_fetch_assoc($hand_types_result)) {
    $current_hand_ids = array_map(function($h) { return $h['hand_id']; }, $hands);
    
    // Only show hands that don't belong to ANY payout cycle (or belong to this cycle already)
    $sql2 = "SELECT h.*, m.first_name, m.surname 
              FROM hands h 
              JOIN members m ON h.member_id = m.id 
              WHERE h.hand_type_id = '" . $ht['id'] . "'
              AND (h.payout_cycle_id IS NULL OR h.payout_cycle_id = '' OR h.payout_cycle_id = 0 OR h.payout_cycle_id = '" . $cycle_id . "')";
    
    if (!empty($current_hand_ids)) {
        $ids_placeholder = implode(',', $current_hand_ids);
        $sql2 .= " AND h.id NOT IN ($ids_placeholder)";
    }
    
    $sql2 .= " ORDER BY h.opening_date ASC";
    $result2 = mysqli_query($conn, $sql2);
    $ht['available_hands'] = [];
    while ($hand = mysqli_fetch_assoc($result2)) {
        // Additional check: only include hands that truly don't have a cycle
        if (empty($hand['payout_cycle_id']) || $hand['payout_cycle_id'] == 0 || $hand['payout_cycle_id'] == $cycle_id) {
            $ht['available_hands'][] = $hand;
        }
    }
    $hand_types[] = $ht;
}

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Hands - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hand-type-group {
            margin-bottom: 25px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        .hand-type-header {
            background: #f0f0f0;
            padding: 12px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            border-bottom: 1px solid #ddd;
        }
        .hand-type-header h4 {
            margin: 0;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .hand-type-header .count {
            background: #667eea;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }
        .hand-type-list {
            padding: 10px;
            background: #fafafa;
        }
        .hand-item {
            display: flex;
            align-items: center;
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .hand-item:last-child {
            border-bottom: none;
        }
        .hand-item label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            cursor: pointer;
            flex: 1;
        }
        .hand-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
        }
        .remove-btn {
            color: #dc3545;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 14px;
        }
        .remove-btn:hover {
            color: #a71d2a;
        }
        .tab-container {
            margin-bottom: 20px;
        }
        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .tab-btn {
            padding: 10px 20px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 5px;
        }
        .tab-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
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
                <h1><i class="fas fa-hands"></i> Manage Hands in Cycle</h1>
                <div class="page-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Cycles
                    </a>
                    <a href="view.php?id=<?php echo $cycle_id; ?>" class="btn btn-info">
                        <i class="fas fa-eye"></i> View Cycle
                    </a>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            
            <div class="info-box" style="margin-bottom: 20px;">
                <h4><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($cycle['cycle_name']); ?></h4>
                <p><strong>Total Hands in Cycle:</strong> <?php echo count($hands); ?></p>
                <p><strong>Start Date:</strong> <?php echo formatDate($cycle['start_date']); ?></p>
            </div>
            
            <div class="tab-container">
                <div class="tab-buttons">
                    <button type="button" class="tab-btn active" onclick="showTab('current')">
                        <i class="fas fa-list"></i> Current Hands (<?php echo count($hands); ?>)
                    </button>
                    <button type="button" class="tab-btn" onclick="showTab('add')">
                        <i class="fas fa-plus"></i> Add Hands
                    </button>
                </div>
                
                <div id="current-tab" class="tab-content active">
                    <div class="card">
                        <div class="card-header">
                            <h3>Hands in Cycle (Ordered by Position)</h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($hands) > 0): ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="update_positions" value="1">
                                    
                                    <div class="form-group">
                                        <label for="reason">Reason for Position Change</label>
                                        <input type="text" name="reason" id="reason" 
                                               class="form-control" placeholder="e.g., Member requested position change">
                                    </div>
                                    
                                    <input type="hidden" name="remove_hand" id="remove_hand" value="">
                                    <input type="hidden" name="hand_id" id="hand_id" value="">
                                    
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Current #</th>
                                                <th>Hand Number</th>
                                                <th>Member</th>
                                                <th>Hand Type</th>
                                                <th>New Position</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($hands as $hand): ?>
                                            <tr>
                                                <td><span class="badge badge-info">#<?php echo $hand['position_order']; ?></span></td>
                                                <td><strong><?php echo htmlspecialchars($hand['hand_number']); ?></strong></td>
                                                <td>
                                                    <?php echo htmlspecialchars(
                                                        $hand['first_name'] . ' ' . 
                                                        ($hand['middle_name'] ? $hand['middle_name'] . ' ' : '') . 
                                                        $hand['surname']
                                                    ); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($hand['hand_type_name']); ?></td>
                                                <td>
                                                    <input type="number" name="position[<?php echo $hand['hand_id']; ?>]" 
                                                           value="<?php echo $hand['position_order']; ?>" 
                                                           min="1" max="<?php echo count($hands); ?>" 
                                                           class="form-control" style="width: 80px;">
                                                </td>
                                                <td>
                                                    <button type="button" class="remove-btn" onclick="removeHand(<?php echo $hand['hand_id']; ?>)">
                                                        <i class="fas fa-trash"></i> Remove
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    
                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Update Positions
                                        </button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-hand-holding fa-4x"></i>
                                    <h3>No Hands in Cycle</h3>
                                    <p>Click "Add Hands" tab to add hands to this cycle.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div id="add-tab" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h3>Add Hands to Cycle</h3>
                        </div>
                        <div class="card-body">
                            <?php 
                            $has_available = false;
                            foreach ($hand_types as $ht) {
                                if (count($ht['available_hands']) > 0) {
                                    $has_available = true;
                                    break;
                                }
                            }
                            ?>
                            
                            <?php if ($has_available): ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="add_hands" value="1">
                                    <p class="info-box" style="background: #e8f4fd; border-color: #b8daff;">
                                        <i class="fas fa-info-circle"></i> Select hands to add. They will be added at the end of the current list.
                                    </p>
                                    
                                    <?php foreach ($hand_types as $ht): ?>
                                    <?php if (count($ht['available_hands']) > 0): ?>
                                    <div class="hand-type-group">
                                        <div class="hand-type-header">
                                            <h4>
                                                <i class="fas fa-hand-holding"></i>
                                                <?php echo htmlspecialchars($ht['hand_type_name']); ?>
                                                <span class="count"><?php echo count($ht['available_hands']); ?> available</span>
                                            </h4>
                                        </div>
                                        <div class="hand-type-list">
                                            <?php foreach ($ht['available_hands'] as $hand): ?>
                                            <div class="hand-item">
                                                <label>
                                                    <input type="checkbox" name="hands_to_add[]" value="<?php echo $hand['id']; ?>">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($hand['hand_number']); ?></strong>
                                                        <small style="color: #666; margin-left: 10px;">
                                                            <?php echo htmlspecialchars($hand['first_name'] . ' ' . $hand['surname']); ?>
                                                        </small>
                                                    </div>
                                                </label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                    
                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Add Selected Hands
                                        </button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-check-circle fa-4x"></i>
                                    <h3>All Hands Added</h3>
                                    <p>All available hands have been added to this cycle.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../js/script.js"></script>
    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            if (tabName === 'current') {
                document.getElementById('current-tab').classList.add('active');
                document.querySelector('.tab-btn:nth-child(1)').classList.add('active');
            } else {
                document.getElementById('add-tab').classList.add('active');
                document.querySelector('.tab-btn:nth-child(2)').classList.add('active');
            }
        }
        
        function removeHand(handId) {
            if (confirm('Remove this hand from cycle?')) {
                document.getElementById('remove_hand').value = '1';
                document.getElementById('hand_id').value = handId;
                document.querySelector('form').submit();
            }
        }
    </script>
</body>
</html>