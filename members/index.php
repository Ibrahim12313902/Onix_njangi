<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total count
$sql = "SELECT COUNT(*) as total FROM members";
$result = mysqli_query($conn, $sql);
$total_rows = mysqli_fetch_assoc($result)['total'];
$total_pages = ceil($total_rows / $limit);

// Get members with member type
$sql = "SELECT m.*, mt.member_type_name 
        FROM members m 
        LEFT JOIN member_types mt ON m.member_type_id = mt.id 
        ORDER BY m.registration_date DESC 
        LIMIT $limit OFFSET $offset";
$members_result = mysqli_query($conn, $sql);

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members - <?php echo SITE_NAME; ?></title>
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
                <h1><i class="fas fa-users"></i> Members</h1>
                <div class="page-actions">
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Member
                    </a>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    Member <?php echo $_GET['success']; ?> successfully!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    Error: <?php echo $_GET['error']; ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3>List of All Members</h3>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($members_result) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Member Number</th>
                                    <th>Full Name</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Member Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($member = mysqli_fetch_assoc($members_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['member_number']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($member['first_name'] . ' ' . 
                                              ($member['middle_name'] ? $member['middle_name'] . ' ' : '') . 
                                              $member['surname']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($member['phone'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($member['email'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($member['member_type_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($member['is_active']): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                        <a href="view.php?id=<?php echo $member['id']; ?>" 
                                           class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $member['id']; ?>" 
                                           class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="set_password.php?id=<?php echo $member['id']; ?>" 
                                           class="btn btn-sm btn-success" title="Set Password">
                                            <i class="fas fa-key"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $member['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Delete this member?')" title="Delete">
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
                                <a href="?page=<?php echo $page-1; ?>" class="page-link">&laquo; Previous</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>" 
                                   class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
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
                            <i class="fas fa-users-slash fa-3x"></i>
                            <h3>No Members Found</h3>
                            <p>Start by adding your first member.</p>
                            <a href="create.php" class="btn btn-primary">Add New Member</a>
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