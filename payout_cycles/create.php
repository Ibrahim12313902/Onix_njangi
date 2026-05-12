<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();
$error = '';
$success = '';

$cycle_number = 'PC' . date('Ymd') . rand(100, 999);

$sql = "SELECT * FROM hand_types ORDER BY hand_type_name ASC";
$hand_types_result = mysqli_query($conn, $sql);
$hand_types = [];
while ($ht = mysqli_fetch_assoc($hand_types_result)) {
    // Only show hands that don't belong to ANY payout cycle
    $sql2 = "SELECT h.*, m.first_name, m.surname 
              FROM hands h 
              JOIN members m ON h.member_id = m.id 
              WHERE h.hand_type_id = '" . $ht['id'] . "' 
              AND (h.payout_cycle_id IS NULL OR h.payout_cycle_id = '' OR h.payout_cycle_id = 0)
              ORDER BY h.opening_date ASC";
    $result2 = mysqli_query($conn, $sql2);
    $ht['hands'] = [];
    while ($hand = mysqli_fetch_assoc($result2)) {
        $ht['hands'][] = $hand;
    }
    $hand_types[] = $ht;
}

$total_hands_available = 0;
foreach ($hand_types as $ht) {
    $total_hands_available += count($ht['hands']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cycle_number = mysqli_real_escape_string($conn, $_POST['cycle_number']);
    $cycle_name = mysqli_real_escape_string($conn, $_POST['cycle_name']);
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $selected_hands = isset($_POST['hands']) ? $_POST['hands'] : [];
    
    if (empty($cycle_name) || empty($start_date)) {
        $error = 'Cycle name and start date are required!';
    } elseif (empty($selected_hands)) {
        $error = 'Please select at least one hand for the cycle!';
    } else {
        // Double-check that selected hands don't already belong to a cycle
        $hands_placeholder = implode(',', array_map('intval', $selected_hands));
        $sql = "SELECT COUNT(*) as count FROM hands WHERE id IN ($hands_placeholder) AND (payout_cycle_id IS NOT NULL AND payout_cycle_id != '' AND payout_cycle_id != 0)";
        $result = mysqli_query($conn, $sql);
        $already_in_cycle = mysqli_fetch_assoc($result)['count'];
        
        if ($already_in_cycle > 0) {
            $error = 'Some selected hands already belong to another payout cycle. Please refresh and try again.';
        } else {
        $sql = "SELECT COUNT(*) as count FROM hands WHERE id IN ($hands_placeholder)";
        $result = mysqli_query($conn, $sql);
        $hand_count = mysqli_fetch_assoc($result)['count'];
        
        $sql = "SELECT MAX(ht.payment_period_days) as max_days 
                FROM hands h 
                JOIN hand_types ht ON h.hand_type_id = ht.id 
                WHERE h.id IN (" . implode(',', array_map('intval', $selected_hands)) . ")";
        $result = mysqli_query($conn, $sql);
        $max_days = mysqli_fetch_assoc($result)['max_days'] ?? 30;
        
        $end_date = date('Y-m-d', strtotime($start_date . ' + ' . ($hand_count * $max_days) . ' days'));
        
        $sql = "INSERT INTO payout_cycles (cycle_number, cycle_name, start_date, end_date, status, total_hands) 
                VALUES ('$cycle_number', '$cycle_name', '$start_date', '$end_date', 'active', '$hand_count')";
        
        if (mysqli_query($conn, $sql)) {
            $cycle_id = mysqli_insert_id($conn);
            
            $position = 1;
            foreach ($selected_hands as $hand_id) {
                $hand_id = (int)$hand_id;
                
                $sql = "SELECT ht.payment_period_days 
                        FROM hands h 
                        JOIN hand_types ht ON h.hand_type_id = ht.id 
                        WHERE h.id = '$hand_id'";
                $result = mysqli_query($conn, $sql);
                $row = mysqli_fetch_assoc($result);
                $deadline_days = $row['payment_period_days'] ?? 30;
                $deadline_date = date('Y-m-d', strtotime($start_date . ' + ' . ($position * $deadline_days) . ' days'));
                
                mysqli_query($conn, "INSERT INTO cycle_hands (cycle_id, hand_id, position_order) VALUES ('$cycle_id', '$hand_id', '$position')");
                
                $sql = "INSERT INTO payment_deadlines (hand_id, deadline_date, amount_due) 
                         SELECT h.id, '$deadline_date', COALESCE(ht.default_amount, 0)
                         FROM hands h 
                         JOIN hand_types ht ON h.hand_type_id = ht.id 
                         WHERE h.id = '$hand_id'";
                mysqli_query($conn, $sql);
                
                mysqli_query($conn, "UPDATE hands SET payout_position = '$position', payout_cycle_id = '$cycle_id' WHERE id = '$hand_id'");
                
                $position++;
            }
            
            header('Location: index.php?success=Cycle created successfully!');
            exit();
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
    <title>Create Payout Cycle - <?php echo SITE_NAME; ?></title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        .hand-type-header h4 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .hand-type-header .count {
            background: rgba(255,255,255,0.2);
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
        }
        .hand-type-header .toggle-icon {
            transition: transform 0.3s;
        }
        .hand-type-group.collapsed .toggle-icon {
            transform: rotate(-90deg);
        }
        .hand-type-group.collapsed .hand-list {
            display: none;
        }
        .hand-list {
            padding: 15px;
            background: #f9f9f9;
        }
        .hand-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .hand-item:last-child {
            border-bottom: none;
        }
        .hand-item label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            cursor: pointer;
            flex: 1;
        }
        .hand-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .hand-info {
            display: flex;
            flex-direction: column;
        }
        .hand-info .hand-number {
            font-weight: 600;
            color: #333;
        }
        .hand-info .member-name {
            font-size: 12px;
            color: #666;
        }
        .hand-type-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
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
                <h1><i class="fas fa-plus-circle"></i> Create Payout Cycle</h1>
                <div class="page-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
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
                    <h3>Create New Payout Cycle</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="cycleForm">
                        <div class="form-group">
                            <label for="cycle_number">Cycle Number</label>
                            <input type="text" id="cycle_number" name="cycle_number" 
                                   value="<?php echo htmlspecialchars($cycle_number); ?>" 
                                   readonly class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="cycle_name">Cycle Name *</label>
                            <input type="text" id="cycle_name" name="cycle_name" 
                                   required placeholder="Enter cycle name (e.g., 2026 Cycle 1)"
                                   class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="start_date">Start Date *</label>
                            <input type="date" id="start_date" name="start_date" 
                                   required class="form-control">
                            <small>End date will be calculated automatically based on number of hands and their payment periods</small>
                        </div>
                    </form>
                    
                    <div class="info-box">
                        <h4><i class="fas fa-info-circle"></i> Select Hands by Hand Type</h4>
                        <p>Click on a hand type to expand/collapse. Check the hands you want to include in this cycle.</p>
                        <p><strong>Available Hands:</strong> <span id="selectedCount">0</span> / <?php echo $total_hands_available; ?></p>
                    </div>
                    
                    <?php foreach ($hand_types as $ht): ?>
                    <?php if (count($ht['hands']) > 0): ?>
                    <div class="hand-type-group">
                        <div class="hand-type-header" onclick="toggleGroup(this)">
                            <h4>
                                <i class="fas fa-hand-holding"></i>
                                <?php echo htmlspecialchars($ht['hand_type_name']); ?>
                                <span class="count"><?php echo count($ht['hands']); ?> hands</span>
                            </h4>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="hand-list">
                            <?php foreach ($ht['hands'] as $hand): ?>
                            <div class="hand-item">
                                <label>
                                    <input type="checkbox" name="hands[]" value="<?php echo $hand['id']; ?>" 
                                           form="cycleForm" class="hand-checkbox"
                                           onchange="updateSelectedCount()">
                                    <div class="hand-info">
                                        <span class="hand-number"><?php echo htmlspecialchars($hand['hand_number']); ?></span>
                                        <span class="member-name"><?php echo htmlspecialchars($hand['first_name'] . ' ' . $hand['surname']); ?></span>
                                    </div>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <div class="form-actions" style="margin-top: 20px;">
                        <button type="submit" form="cycleForm" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Cycle
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="selectAll()">
                            <i class="fas fa-check-square"></i> Select All
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="deselectAll()">
                            <i class="fas fa-square"></i> Deselect All
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../js/script.js"></script>
    <script>
        function toggleGroup(header) {
            header.closest('.hand-type-group').classList.toggle('collapsed');
        }
        
        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.hand-checkbox:checked');
            document.getElementById('selectedCount').textContent = checkboxes.length;
        }
        
        function selectAll() {
            document.querySelectorAll('.hand-checkbox').forEach(cb => cb.checked = true);
            updateSelectedCount();
        }
        
        function deselectAll() {
            document.querySelectorAll('.hand-checkbox').forEach(cb => cb.checked = false);
            updateSelectedCount();
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            updateSelectedCount();
        });
    </script>
</body>
</html>