<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_type = isset($_GET['hand_type']) ? (int)$_GET['hand_type'] : 0;

$sql = "SELECT * FROM hand_types ORDER BY hand_type_name ASC";
$hand_types_result = mysqli_query($conn, $sql);
$hand_types = [];

while ($ht = mysqli_fetch_assoc($hand_types_result)) {
    $where = "h.hand_type_id = '" . $ht['id'] . "'";
    if (!empty($search)) {
        $where .= " AND (h.hand_number LIKE '%$search%' OR m.first_name LIKE '%$search%' OR m.surname LIKE '%$search%')";
    }
    if ($filter_type > 0) {
        $where .= " AND h.hand_type_id = '$filter_type'";
    }
    
    $sql2 = "SELECT h.*, m.member_number, m.first_name, m.middle_name, m.surname, hs.hand_status_name
              FROM hands h 
              JOIN members m ON h.member_id = m.id 
              LEFT JOIN hand_status hs ON h.hand_status_id = hs.id
              WHERE $where
              ORDER BY h.opening_date ASC";
    $result2 = mysqli_query($conn, $sql2);
    $ht['hands'] = [];
    while ($hand = mysqli_fetch_assoc($result2)) {
        $ht['hands'][] = $hand;
    }
    $hand_types[] = $ht;
}

$all_hand_types = [];
$sql = "SELECT * FROM hand_types ORDER BY hand_type_name ASC";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $all_hand_types[] = $row;
}

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hands - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hand-type-section {
            margin-bottom: 30px;
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
        .hand-type-header:hover {
            background: linear-gradient(135deg, #5a6fd6 0%, #6a4191 100%);
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
        .hand-type-section.collapsed .toggle-icon {
            transform: rotate(-90deg);
        }
        .hand-type-section.collapsed .hand-table-container {
            display: none;
        }
        .hand-table-container {
            padding: 0;
            background: #f9f9f9;
        }
        .hand-type-info {
            padding: 10px 20px;
            background: #f0f0f0;
            border-bottom: 1px solid #ddd;
            display: flex;
            gap: 20px;
            font-size: 13px;
            color: #666;
        }
        .hand-type-info span {
            display: flex;
            align-items: center;
            gap: 5px;
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
                <h1><i class="fas fa-hands-helping"></i> Hands</h1>
                <div class="page-actions">
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Open New Hand
                    </a>
                    <a href="#" onclick="window.print()" class="btn btn-secondary">
                        <i class="fas fa-print"></i> Print All
                    </a>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3>All Hands by Hand Type</h3>
                    <div class="search-box">
                        <form method="GET" action="">
                            <select name="hand_type" class="form-control" style="width: 180px;">
                                <option value="0">All Hand Types</option>
                                <?php foreach ($all_hand_types as $ht): ?>
                                <option value="<?php echo $ht['id']; ?>" <?php echo $filter_type == $ht['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ht['hand_type_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="search" placeholder="Search hands..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if (!empty($search) || $filter_type > 0): ?>
                                <a href="index.php" class="btn btn-sm btn-secondary">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <?php 
                    $total_hands = 0;
                    foreach ($hand_types as $ht) {
                        $total_hands += count($ht['hands']);
                    }
                    ?>
                    
                    <div class="info-box" style="margin-bottom: 20px;">
                        <h4><i class="fas fa-info-circle"></i> Summary</h4>
                        <p><strong>Total Hands:</strong> <?php echo $total_hands; ?></p>
                        <p><strong>Hand Types:</strong> <?php echo count($hand_types); ?></p>
                        <p><small>Click on a hand type header to expand/collapse. Hands are listed in order of opening date.</small></p>
                    </div>
                    
                    <?php if ($total_hands > 0): ?>
                        <?php foreach ($hand_types as $ht): ?>
                        <?php if (count($ht['hands']) > 0): ?>
                        <div class="hand-type-section">
                            <div class="hand-type-header" onclick="toggleSection(this)">
                                <h4>
                                    <i class="fas fa-hand-holding"></i>
                                    <?php echo htmlspecialchars($ht['hand_type_name']); ?>
                                    <span class="count"><?php echo count($ht['hands']); ?> hands</span>
                                </h4>
                                <i class="fas fa-chevron-down toggle-icon"></i>
                            </div>
                            <div class="hand-type-info">
                                <span><i class="fas fa-calendar"></i> Payment: <?php echo $ht['payment_period_days'] ?? 30; ?> days</span>
                                <span><i class="fas fa-coins"></i> Default: <?php echo formatCurrency($ht['default_amount'] ?? 0); ?></span>
                            </div>
                            <div class="hand-table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Hand Number</th>
                                            <th>Member</th>
                                            <th>Status</th>
                                            <th>Amount (FCFA)</th>
                                            <th>Opening Date</th>
                                            <th>Position</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $counter = 1;
                                        foreach ($ht['hands'] as $hand): 
                                        ?>
                                        <tr>
                                            <td><?php echo $counter++; ?></td>
                                            <td><strong><?php echo htmlspecialchars($hand['hand_number']); ?></strong></td>
                                            <td>
                                                <?php echo htmlspecialchars(
                                                    $hand['first_name'] . ' ' . 
                                                    ($hand['middle_name'] ? $hand['middle_name'] . ' ' : '') . 
                                                    $hand['surname']
                                                ); ?><br>
                                                <small><?php echo htmlspecialchars($hand['member_number']); ?></small>
                                            </td>
                                            <td>
                                                <?php 
                                                $status_class = '';
                                                if ($hand['hand_status_name'] == 'Active') $status_class = 'badge-success';
                                                elseif ($hand['hand_status_name'] == 'Closed') $status_class = 'badge-danger';
                                                elseif ($hand['hand_status_name'] == 'Suspended') $status_class = 'badge-warning';
                                                else $status_class = 'badge-info';
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <?php echo htmlspecialchars($hand['hand_status_name'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $hand['amount'] > 0 ? formatCurrency($hand['amount']) : '-'; ?></td>
                                            <td><?php echo formatDate($hand['opening_date']); ?></td>
                                            <td>
                                                <?php if ($hand['payout_position']): ?>
                                                    <span class="badge badge-info">#<?php echo $hand['payout_position']; ?></span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Not in cycle</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="actions">
                                                <a href="view.php?id=<?php echo $hand['id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?php echo $hand['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="contributions.php?hand_id=<?php echo $hand['id']; ?>" class="btn btn-sm btn-success" title="Contributions">
                                                    <i class="fas fa-money-bill-wave"></i>
                                                </a>
                                                <a href="delete.php?id=<?php echo $hand['id']; ?>" class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Delete this hand?\nAll associated data will be lost.')" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-hand-holding-heart fa-4x"></i>
                            <h3>No Hands Found</h3>
                            <p><?php echo !empty($search) ? 'No results match your search.' : 'Start by opening your first hand.'; ?></p>
                            <a href="create.php" class="btn btn-primary">Open New Hand</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../js/script.js"></script>
    <script>
        function toggleSection(header) {
            header.closest('.hand-type-section').classList.toggle('collapsed');
        }
    </script>
</body>
</html>