<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = '';
if (!empty($search)) {
    $where = "WHERE hand_status_name LIKE '%$search%' OR hand_status_number LIKE '%$search%'";
}

// Get total count
$sql = "SELECT COUNT(*) as total FROM hand_status $where";
$result = mysqli_query($conn, $sql);
$total_rows = mysqli_fetch_assoc($result)['total'];
$total_pages = ceil($total_rows / $limit);

// Get hand statuses
$sql = "SELECT * FROM hand_status $where ORDER BY registration_date DESC LIMIT $limit OFFSET $offset";
$statuses_result = mysqli_query($conn, $sql);

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hand Status - <?php echo SITE_NAME; ?></title>
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
                <h1><i class="fas fa-clipboard-check"></i> Hand Status</h1>
                <div class="page-actions">
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Hand Status
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
                    <h3>Manage Hand Status</h3>
                    <div class="search-box">
                        <form method="GET" action="">
                            <input type="text" name="search" placeholder="Search hand status..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <?php if (!empty($search)): ?>
                                <a href="index.php" class="btn btn-sm btn-secondary">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($statuses_result) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Status Number</th>
                                    <th>Status Name</th>
                                    <th>Description</th>
                                    <th>Registration Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $counter = $offset + 1;
                                while ($status = mysqli_fetch_assoc($statuses_result)): 
                                ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($status['hand_status_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($status['hand_status_name']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($status['description'] ?? 'N/A', 0, 50)); ?></td>
                                    <td><?php echo formatDate($status['registration_date']); ?></td>
                                    <td class="actions">
                                        <a href="edit.php?id=<?php echo $status['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $status['id']; ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Delete this hand status?')" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="page-link">&laquo; Previous</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="page-link">Next &raquo;</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list fa-4x"></i>
                            <h3>No Hand Status Found</h3>
                            <p><?php echo !empty($search) ? 'No results match your search.' : 'Add your first hand status.'; ?></p>
                            <a href="create.php" class="btn btn-primary">Add New Hand Status</a>
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