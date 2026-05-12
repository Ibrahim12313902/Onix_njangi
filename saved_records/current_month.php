<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();

// Check if connection is successful
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Pagination
$limit = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total count with error checking
$sql = "SELECT COUNT(*) as total FROM saved_records 
        WHERE record_type = 'current_month' AND is_active = 1";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Error in query: " . mysqli_error($conn));
}

$total_rows = mysqli_fetch_assoc($result)['total'];
$total_pages = ceil($total_rows / $limit);

// Get records with error checking
$sql = "SELECT * FROM saved_records 
        WHERE record_type = 'current_month' AND is_active = 1 
        ORDER BY record_date DESC 
        LIMIT $limit OFFSET $offset";
$records_result = mysqli_query($conn, $sql);

if (!$records_result) {
    die("Error in query: " . mysqli_error($conn));
}

// Get current month total
$total_sql = "SELECT SUM(amount) as total FROM saved_records 
              WHERE record_type = 'current_month' AND is_active = 1";
$total_result = mysqli_query($conn, $total_sql);

if (!$total_result) {
    die("Error in total query: " . mysqli_error($conn));
}

$grand_total = mysqli_fetch_assoc($total_result)['total'] ?? 0;

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Current Month Records - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .summary-card {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .summary-card h2 {
            margin: 0;
            font-size: 24px;
        }
        
        .summary-card .total {
            font-size: 32px;
            font-weight: bold;
        }
        
        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
                <h1><i class="fas fa-calendar-alt"></i> Current Month Records</h1>
                <div class="page-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <a href="create.php?type=current_month" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Record
                    </a>
                    <a href="#" onclick="window.print()" class="btn btn-info">
                        <i class="fas fa-print"></i> Print
                    </a>
                </div>
            </div>
            
            <!-- Summary Card -->
            <div class="summary-card">
                <div>
                    <h2><i class="fas fa-chart-line"></i> Total Contributions</h2>
                    <p>All current month records</p>
                </div>
                <div class="total"><?php echo formatCurrency($grand_total); ?></div>
            </div>
            
            <!-- Records Table -->
            <div class="card">
                <div class="card-header">
                    <h3>Current Month Records</h3>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($records_result) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th>Amount (FCFA)</th>
                                    <th>Description</th>
                                    <th>Record Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $counter = $offset + 1;
                                while ($record = mysqli_fetch_assoc($records_result)): 
                                ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($record['title']); ?></strong></td>
                                    <td class="amount"><?php echo formatCurrency($record['amount']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($record['description'] ?? '', 0, 50)); ?></td>
                                    <td><?php echo formatDate($record['record_date']); ?></td>
                                    <td class="actions">
                                        <a href="view.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-danger"
                                           onclick="return confirm('Delete this record?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2">Grand Total</th>
                                    <th class="amount"><?php echo formatCurrency($grand_total); ?></th>
                                    <th colspan="3"></th>
                                </tr>
                            </tfoot>
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
                            <i class="fas fa-calendar-times fa-4x"></i>
                            <h3>No Current Month Records</h3>
                            <p>Start by adding your first current month record.</p>
                            <a href="create.php?type=current_month" class="btn btn-primary">
                                Add First Record
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