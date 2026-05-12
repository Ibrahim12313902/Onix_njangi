<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = '';
if (!empty($search)) {
    $where = "WHERE member_type_name LIKE '%$search%' OR member_type_number LIKE '%$search%' OR description LIKE '%$search%'";
}

// Get total count
$sql = "SELECT COUNT(*) as total FROM member_types $where";
$result = mysqli_query($conn, $sql);
$total_rows = mysqli_fetch_assoc($result)['total'];
$total_pages = ceil($total_rows / $limit);

// Get member types
$sql = "SELECT * FROM member_types $where ORDER BY registration_date DESC LIMIT $limit OFFSET $offset";
$types_result = mysqli_query($conn, $sql);

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Types - <?php echo SITE_NAME; ?></title>
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
                <h1><i class="fas fa-user-tag"></i> Member Types</h1>
                <div class="page-actions">
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Member Type
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
                    <h3>Manage Member Types</h3>
                    <div class="search-box">
                        <form method="GET" action="">
                            <input type="text" name="search" placeholder="Search member types..." 
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
                    <?php if (mysqli_num_rows($types_result) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Type Number</th>
                                    <th>Type Name</th>
                                    <th>Description</th>
                                    <th>Registration Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $counter = $offset + 1;
                                while ($type = mysqli_fetch_assoc($types_result)): 
                                ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($type['member_type_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($type['member_type_name']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($type['description'] ?? 'N/A', 0, 50)) . (strlen($type['description'] ?? '') > 50 ? '...' : ''); ?></td>
                                    <td><?php echo formatDate($type['registration_date']); ?></td>
                                    <td class="actions">
                                        <a href="edit.php?id=<?php echo $type['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $type['id']; ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this member type?\nThis action cannot be undone.')" 
                                           title="Delete">
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
                            <i class="fas fa-tags fa-4x"></i>
                            <h3>No Member Types Found</h3>
                            <p><?php echo !empty($search) ? 'No results match your search criteria.' : 'Start by adding your first member type.'; ?></p>
                            <a href="create.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add New Member Type
                            </a>
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