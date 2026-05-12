<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = '';
if (!empty($search)) {
    $where = "WHERE cycle_number LIKE '%$search%' OR cycle_name LIKE '%$search%'";
}

$sql = "SELECT COUNT(*) as total FROM payout_cycles $where";
$result = mysqli_query($conn, $sql);
$total_rows = mysqli_fetch_assoc($result)['total'];
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT * FROM payout_cycles $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$cycles_result = mysqli_query($conn, $sql);

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payout Cycles - <?php echo SITE_NAME; ?></title>
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
                <h1><i class="fas fa-calendar-alt"></i> Payout Cycles</h1>
                <div class="page-actions">
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create New Cycle
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
                    <h3>Manage Payout Cycles</h3>
                    <div class="search-box">
                        <form method="GET" action="">
                            <input type="text" name="search" placeholder="Search cycles..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if (!empty($search)): ?>
                                <a href="index.php" class="btn btn-sm btn-secondary">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($cycles_result) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Cycle Number</th>
                                    <th>Cycle Name</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Hands</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $counter = $offset + 1;
                                while ($cycle = mysqli_fetch_assoc($cycles_result)): 
                                    $status_class = '';
                                    if ($cycle['status'] == 'active') $status_class = 'badge-success';
                                    elseif ($cycle['status'] == 'completed') $status_class = 'badge-info';
                                    elseif ($cycle['status'] == 'cancelled') $status_class = 'badge-danger';
                                    else $status_class = 'badge-warning';
                                    
                                    // Check if cycle can be deleted
                                    $cycle_id = $cycle['id'];
                                    $sql_check = "SELECT COUNT(*) as count FROM payment_proofs pp
                                                 JOIN cycle_hands ch ON pp.hand_id = ch.hand_id
                                                 WHERE ch.cycle_id = '$cycle_id' AND pp.status = 'approved'";
                                    $check_result = mysqli_query($conn, $sql_check);
                                    $approved_payments = mysqli_fetch_assoc($check_result)['count'];
                                    
                                    $today = date('Y-m-d');
                                    $cycle_ended = ($cycle['end_date'] && $cycle['end_date'] < $today);
                                    
                                    $sql_check2 = "SELECT COUNT(*) as total,
                                                   SUM(CASE WHEN h.received_at IS NOT NULL OR h.payout_status = 'paid' THEN 1 ELSE 0 END) as completed
                                                   FROM cycle_hands ch
                                                   JOIN hands h ON ch.hand_id = h.id
                                                   WHERE ch.cycle_id = '$cycle_id'";
                                    $check_result2 = mysqli_query($conn, $sql_check2);
                                    $payout_data = mysqli_fetch_assoc($check_result2);
                                    $all_payouts_complete = ($payout_data['total'] > 0 && $payout_data['total'] == $payout_data['completed']);
                                    
                                    $can_delete = ($approved_payments == 0) || ($cycle_ended && $all_payouts_complete);
                                ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($cycle['cycle_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($cycle['cycle_name']); ?></td>
                                    <td><?php echo formatDate($cycle['start_date']); ?></td>
                                    <td><?php echo formatDate($cycle['end_date']); ?></td>
                                    <td><span class="badge badge-info"><?php echo $cycle['total_hands']; ?></span></td>
                                    <td>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo ucfirst($cycle['status']); ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="view.php?id=<?php echo $cycle['id']; ?>" class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($cycle['status'] == 'draft'): ?>
                                        <a href="edit.php?id=<?php echo $cycle['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
                                        <a href="hands.php?id=<?php echo $cycle['id']; ?>" class="btn btn-sm btn-success" title="Manage Hands">
                                            <i class="fas fa-hands"></i>
                                        </a>
                                        <?php if ($can_delete): ?>
                                        <a href="delete.php?id=<?php echo $cycle['id']; ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Delete this cycle?');" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php else: ?>
                                        <span class="btn btn-sm btn-secondary" title="Cannot delete - has approved payments" style="opacity: 0.5; cursor: not-allowed;">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        
                        <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page-1; ?>" class="page-link">&laquo; Previous</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>" class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page+1; ?>" class="page-link">Next &raquo;</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times fa-4x"></i>
                            <h3>No Payout Cycles</h3>
                            <p>Create your first payout cycle to get started.</p>
                            <a href="create.php" class="btn btn-primary">Create New Cycle</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../js/script.js"></script>
</body>
</html>