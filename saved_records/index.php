<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();

// Get current month records
$current_month_sql = "SELECT * FROM saved_records 
                      WHERE record_type = 'current_month' 
                      AND is_active = 1 
                      ORDER BY record_date DESC 
                      LIMIT 5";
$current_month_result = mysqli_query($conn, $current_month_sql);

// Get last quarter records
$last_quarter_sql = "SELECT * FROM saved_records 
                     WHERE record_type = 'last_quarter' 
                     AND is_active = 1 
                     ORDER BY record_date DESC 
                     LIMIT 5";
$last_quarter_result = mysqli_query($conn, $last_quarter_sql);

// Get achievements
$achievements_sql = "SELECT * FROM saved_records 
                     WHERE record_type = 'achievement' 
                     AND is_active = 1 
                     ORDER BY record_date DESC 
                     LIMIT 5";
$achievements_result = mysqli_query($conn, $achievements_sql);

// Get piggie box data
$piggie_box_sql = "SELECT * FROM saved_records 
                   WHERE record_type = 'piggie_box' 
                   AND is_active = 1 
                   ORDER BY record_date DESC 
                   LIMIT 5";
$piggie_box_result = mysqli_query($conn, $piggie_box_sql);

// Get piggie box total
$piggie_total_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM piggie_box_transactions 
                     WHERE transaction_type IN ('DEPOSIT', 'INTEREST')";
$piggie_total_result = mysqli_query($conn, $piggie_total_sql);
$piggie_total = mysqli_fetch_assoc($piggie_total_result)['total'] ?? 0;

// Get withdrawals total
$withdrawals_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM piggie_box_transactions 
                    WHERE transaction_type = 'WITHDRAWAL'";
$withdrawals_result = mysqli_query($conn, $withdrawals_sql);
$withdrawals_total = mysqli_fetch_assoc($withdrawals_result)['total'] ?? 0;

$current_balance = $piggie_total - $withdrawals_total;

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Records - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .saved-records-container {
            padding: 20px;
        }
        
        .records-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .records-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }
        
        .record-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .record-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .section-header {
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .section-header i {
            font-size: 30px;
        }
        
        .section-header h2 {
            font-size: 24px;
            margin: 0;
            flex: 1;
        }
        
        .section-header .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .section-header .btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .section-header .btn:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.05);
        }
        
        .section-content {
            padding: 20px;
        }
        
        .record-card {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .record-card:hover {
            background: #f0f2f5;
            transform: translateX(5px);
        }
        
        .record-card.current-month {
            border-left-color: #28a745;
        }
        
        .record-card.last-quarter {
            border-left-color: #17a2b8;
        }
        
        .record-card.achievement {
            border-left-color: #ffc107;
        }
        
        .record-card.piggie-box {
            border-left-color: #dc3545;
        }
        
        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .record-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .record-date {
            font-size: 14px;
            color: #6c757d;
        }
        
        .record-amount {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .record-description {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .record-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .record-actions .btn-sm {
            padding: 5px 12px;
            font-size: 12px;
        }
        
        .piggie-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-mini-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-mini-card .stat-label {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .stat-mini-card .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-mini-card.total { border-top: 3px solid #28a745; }
        .stat-mini-card.withdrawals { border-top: 3px solid #dc3545; }
        .stat-mini-card.balance { border-top: 3px solid #17a2b8; }
        
        .view-all-link {
            display: block;
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .view-all-link:hover {
            background: #e9ecef;
            color: #764ba2;
        }
        
        .empty-records {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-records i {
            font-size: 48px;
            color: #dee2e6;
            margin-bottom: 15px;
        }
        
        .quick-actions {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .quick-actions h3 {
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 50px auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            padding: 20px;
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .close {
            color: white;
            font-size: 28px;
            cursor: pointer;
        }
        
        .close:hover {
            transform: scale(1.2);
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .modal-footer {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 0 0 15px 15px;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }
        
        @media (max-width: 1024px) {
            .records-grid {
                grid-template-columns: 1fr;
            }
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
                <h1><i class="fas fa-save"></i> Saved Records</h1>
                <div class="page-actions">
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Record
                    </a>
                    <a href="reports.php" class="btn btn-info">
                        <i class="fas fa-chart-bar"></i> View Reports
                    </a>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            
            <div class="records-grid">
                <!-- CURRENT MONTH SECTION -->
                <div class="record-section">
                    <div class="section-header">
                        <i class="fas fa-calendar-alt"></i>
                        <h2>Current Month</h2>
                        <div class="header-actions">
                            <a href="current_month.php" class="btn btn-sm">View All</a>
                            <a href="create.php?type=current_month" class="btn btn-sm">
                                <i class="fas fa-plus"></i>
                            </a>
                        </div>
                    </div>
                    <div class="section-content">
                        <?php if (mysqli_num_rows($current_month_result) > 0): ?>
                            <?php while ($record = mysqli_fetch_assoc($current_month_result)): ?>
                                <div class="record-card current-month">
                                    <div class="record-header">
                                        <span class="record-title"><?php echo htmlspecialchars($record['title']); ?></span>
                                        <span class="record-date"><?php echo formatDate($record['record_date']); ?></span>
                                    </div>
                                    <?php if ($record['amount'] > 0): ?>
                                        <div class="record-amount"><?php echo formatCurrency($record['amount']); ?></div>
                                    <?php endif; ?>
                                    <div class="record-description"><?php echo htmlspecialchars($record['description'] ?? ''); ?></div>
                                    <div class="record-actions">
                                        <a href="view.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-records">
                                <i class="fas fa-calendar-times"></i>
                                <p>No current month records found</p>
                                <a href="create.php?type=current_month" class="btn btn-sm btn-primary">
                                    Add First Record
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- LAST QUARTER SECTION -->
                <div class="record-section">
                    <div class="section-header" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                        <i class="fas fa-chart-line"></i>
                        <h2>Last Quarter</h2>
                        <div class="header-actions">
                            <a href="last_quarter.php" class="btn btn-sm">View All</a>
                            <a href="create.php?type=last_quarter" class="btn btn-sm">
                                <i class="fas fa-plus"></i>
                            </a>
                        </div>
                    </div>
                    <div class="section-content">
                        <?php if (mysqli_num_rows($last_quarter_result) > 0): ?>
                            <?php while ($record = mysqli_fetch_assoc($last_quarter_result)): ?>
                                <div class="record-card last-quarter">
                                    <div class="record-header">
                                        <span class="record-title"><?php echo htmlspecialchars($record['title']); ?></span>
                                        <span class="record-date"><?php echo formatDate($record['record_date']); ?></span>
                                    </div>
                                    <?php if ($record['amount'] > 0): ?>
                                        <div class="record-amount"><?php echo formatCurrency($record['amount']); ?></div>
                                    <?php endif; ?>
                                    <div class="record-description"><?php echo htmlspecialchars($record['description'] ?? ''); ?></div>
                                    <div class="record-actions">
                                        <a href="view.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-records">
                                <i class="fas fa-chart-line"></i>
                                <p>No quarter records found</p>
                                <a href="create.php?type=last_quarter" class="btn btn-sm btn-primary">
                                    Add First Record
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- ACHIEVEMENT SECTION -->
                <div class="record-section">
                    <div class="section-header" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);">
                        <i class="fas fa-trophy"></i>
                        <h2>Achievements</h2>
                        <div class="header-actions">
                            <a href="achievements.php" class="btn btn-sm">View All</a>
                            <a href="create.php?type=achievement" class="btn btn-sm">
                                <i class="fas fa-plus"></i>
                            </a>
                        </div>
                    </div>
                    <div class="section-content">
                        <?php if (mysqli_num_rows($achievements_result) > 0): ?>
                            <?php while ($record = mysqli_fetch_assoc($achievements_result)): ?>
                                <div class="record-card achievement">
                                    <div class="record-header">
                                        <span class="record-title"><?php echo htmlspecialchars($record['title']); ?></span>
                                        <span class="record-date"><?php echo formatDate($record['record_date']); ?></span>
                                    </div>
                                    <?php if ($record['amount'] > 0): ?>
                                        <div class="record-amount"><?php echo formatCurrency($record['amount']); ?></div>
                                    <?php endif; ?>
                                    <div class="record-description"><?php echo htmlspecialchars($record['description'] ?? ''); ?></div>
                                    <div class="record-actions">
                                        <a href="view.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-records">
                                <i class="fas fa-trophy"></i>
                                <p>No achievements yet</p>
                                <a href="create.php?type=achievement" class="btn btn-sm btn-primary">
                                    Add First Achievement
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- PIGGIE BOX SECTION -->
                <div class="record-section">
                    <div class="section-header" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
                        <i class="fas fa-piggy-bank"></i>
                        <h2>Piggie Box</h2>
                        <div class="header-actions">
                            <a href="piggie_box.php" class="btn btn-sm">Manage</a>
                            <a href="piggie_box_transactions.php" class="btn btn-sm">
                                <i class="fas fa-history"></i>
                            </a>
                        </div>
                    </div>
                    <div class="section-content">
                        <!-- Piggie Box Stats -->
                        <div class="piggie-stats">
                            <div class="stat-mini-card total">
                                <div class="stat-label">Total Deposits</div>
                                <div class="stat-value"><?php echo formatCurrency($piggie_total); ?></div>
                            </div>
                            <div class="stat-mini-card withdrawals">
                                <div class="stat-label">Withdrawals</div>
                                <div class="stat-value"><?php echo formatCurrency($withdrawals_total); ?></div>
                            </div>
                            <div class="stat-mini-card balance">
                                <div class="stat-label">Balance</div>
                                <div class="stat-value"><?php echo formatCurrency($current_balance); ?></div>
                            </div>
                        </div>
                        
                        <?php if (mysqli_num_rows($piggie_box_result) > 0): ?>
                            <?php while ($record = mysqli_fetch_assoc($piggie_box_result)): ?>
                                <div class="record-card piggie-box">
                                    <div class="record-header">
                                        <span class="record-title"><?php echo htmlspecialchars($record['title']); ?></span>
                                        <span class="record-date"><?php echo formatDate($record['record_date']); ?></span>
                                    </div>
                                    <?php if ($record['amount'] > 0): ?>
                                        <div class="record-amount"><?php echo formatCurrency($record['amount']); ?></div>
                                    <?php endif; ?>
                                    <div class="record-description"><?php echo htmlspecialchars($record['description'] ?? ''); ?></div>
                                    <div class="record-actions">
                                        <a href="view.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-records">
                                <i class="fas fa-piggy-bank"></i>
                                <p>No piggie box records</p>
                                <a href="create.php?type=piggie_box" class="btn btn-sm btn-primary">
                                    Initialize Piggie Box
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <a href="generate_current_month.php" class="btn btn-success" onclick="return confirm('Generate current month report?')">
                        <i class="fas fa-sync"></i> Generate Current Month Report
                    </a>
                    <a href="generate_quarter.php" class="btn btn-info" onclick="return confirm('Generate quarter report?')">
                        <i class="fas fa-chart-bar"></i> Generate Quarter Report
                    </a>
                    <a href="add_achievement.php" class="btn btn-warning">
                        <i class="fas fa-medal"></i> Add Achievement
                    </a>
                    <button onclick="openDepositModal()" class="btn btn-danger">
                        <i class="fas fa-plus-circle"></i> Piggie Box Deposit
                    </button>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Deposit Modal -->
    <div id="depositModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Make Piggie Box Deposit</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form action="piggie_box_deposit.php" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Amount (FCFA) *</label>
                        <input type="number" name="amount" required class="form-control" min="100" step="100">
                    </div>
                    <div class="form-group">
                        <label>Date *</label>
                        <input type="date" name="deposit_date" value="<?php echo date('Y-m-d'); ?>" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3" class="form-control" placeholder="Reason for deposit"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Process Deposit</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        function openDepositModal() {
            document.getElementById('depositModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('depositModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('depositModal')) {
                closeModal();
            }
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>