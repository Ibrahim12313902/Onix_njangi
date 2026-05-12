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

// Get total count
$sql = "SELECT COUNT(*) as total FROM saved_records 
        WHERE record_type = 'last_quarter' AND is_active = 1";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Error in query: " . mysqli_error($conn));
}

$total_rows = mysqli_fetch_assoc($result)['total'];
$total_pages = ceil($total_rows / $limit);

// Get records
$sql = "SELECT * FROM saved_records 
        WHERE record_type = 'last_quarter' AND is_active = 1 
        ORDER BY record_date DESC 
        LIMIT $limit OFFSET $offset";
$records_result = mysqli_query($conn, $sql);

if (!$records_result) {
    die("Error in query: " . mysqli_error($conn));
}

// Get total sum
$total_sql = "SELECT SUM(amount) as total FROM saved_records 
              WHERE record_type = 'last_quarter' AND is_active = 1";
$total_result = mysqli_query($conn, $total_sql);

if (!$total_result) {
    die("Error in total query: " . mysqli_error($conn));
}

$grand_total = mysqli_fetch_assoc($total_result)['total'] ?? 0;

// Get quarters breakdown
$quarters_sql = "SELECT 
                    YEAR(record_date) as year,
                    QUARTER(record_date) as quarter,
                    COUNT(*) as count,
                    SUM(amount) as total
                 FROM saved_records 
                 WHERE record_type = 'last_quarter' AND is_active = 1
                 GROUP BY YEAR(record_date), QUARTER(record_date)
                 ORDER BY year DESC, quarter DESC";
$quarters_result = mysqli_query($conn, $quarters_sql);

closeDbConnection($conn);

// Function to get quarter name
function getQuarterName($quarter) {
    $names = ['Q1 (Jan-Mar)', 'Q2 (Apr-Jun)', 'Q3 (Jul-Sep)', 'Q4 (Oct-Dec)'];
    return $names[$quarter - 1] ?? 'Q' . $quarter;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Last Quarter Records - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .summary-card {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .quarters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .quarter-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .quarter-card h3 {
            color: #17a2b8;
            margin-bottom: 10px;
        }
        
        .quarter-card .amount {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .quarter-card .count {
            color: #6c757d;
            font-size: 14px;
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
                <h1><i class="fas fa-chart-line"></i> Last Quarter Records</h1>
                <div class="page-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <a href="create.php?type=last_quarter" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Record
                    </a>
                    <a href="#" onclick="window.print()" class="btn btn-info">
                        <i class="fas fa-print"></i> Print Report
                    </a>
                </div>
            </div>
            
            <!-- Summary Card -->
            <div class="summary-card">
                <div>
                    <h2><i class="fas fa-chart-bar"></i> Total Quarterly Contributions</h2>
                    <p>All quarter records combined</p>
                </div>
                <div class="total"><?php echo formatCurrency($grand_total); ?></div>
            </div>
            
            <!-- Quarters Breakdown -->
            <?php if (mysqli_num_rows($quarters_result) > 0): ?>
            <div class="quarters-grid">
                <?php while ($quarter = mysqli_fetch_assoc($quarters_result)): ?>
                <div class="quarter-card">
                    <h3><?php echo $quarter['year'] . ' ' . getQuarterName($quarter['quarter']); ?></h3>
                    <div class="amount"><?php echo formatCurrency($quarter['total']); ?></div>
                    <div class="count"><?php echo $quarter['count']; ?> records</div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
            
            <!-- Records Table -->
            <div class="card">
                <div class="card-header">
                    <h3>All Quarter Records</h3>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($records_result) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th>Quarter</th>
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
                                    $year = date('Y', strtotime($record['record_date']));
                                    $quarter = ceil(date('n', strtotime($record['record_date'])) / 3);
                                ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($record['title']); ?></strong></td>
                                    <td><?php echo 'Q' . $quarter . ' ' . $year; ?></td>
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
                                    <th colspan="3">Grand Total</th>
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
                            <i class="fas fa-chart-line fa-4x"></i>
                            <h3>No Quarter Records</h3>
                            <p>Start by adding your first quarter record.</p>
                            <a href="create.php?type=last_quarter" class="btn btn-primary">
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