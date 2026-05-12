<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if files exist before requiring
$constants_file = '../config/constants.php';
$database_file = '../config/database.php';

if (!file_exists($constants_file)) {
    die("Constants file not found at: " . realpath($constants_file));
}
if (!file_exists($database_file)) {
    die("Database file not found at: " . realpath($database_file));
}

require_once $constants_file;
require_once $database_file;

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get database connection
$conn = getDbConnection();

// Check if connection is successful
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

echo "<!-- Debug: Database connected successfully -->";

// Check if piggie_box_transactions table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'piggie_box_transactions'");
if (mysqli_num_rows($table_check) == 0) {
    // Create the table if it doesn't exist
    $create_sql = "CREATE TABLE IF NOT EXISTS piggie_box_transactions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        transaction_type ENUM('DEPOSIT', 'WITHDRAWAL', 'INTEREST') NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        description TEXT,
        transaction_date DATE NOT NULL,
        balance_after DECIMAL(12,2),
        reference_number VARCHAR(50),
        recorded_by INT,
        recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (recorded_by) REFERENCES admin_users(id) ON DELETE SET NULL
    )";
    
    if (!mysqli_query($conn, $create_sql)) {
        die("Error creating table: " . mysqli_error($conn));
    }
    echo "<!-- Debug: Created piggie_box_transactions table -->";
}

// Check if saved_records table exists
$table_check2 = mysqli_query($conn, "SHOW TABLES LIKE 'saved_records'");
if (mysqli_num_rows($table_check2) == 0) {
    // Create the table if it doesn't exist
    $create_sql = "CREATE TABLE IF NOT EXISTS saved_records (
        id INT PRIMARY KEY AUTO_INCREMENT,
        record_type ENUM('current_month', 'last_quarter', 'achievement', 'piggie_box', 'custom') NOT NULL,
        title VARCHAR(100) NOT NULL,
        amount DECIMAL(12,2) DEFAULT 0.00,
        description TEXT,
        record_date DATE,
        icon VARCHAR(50) DEFAULT 'fas fa-save',
        color VARCHAR(20) DEFAULT '#667eea',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($conn, $create_sql)) {
        die("Error creating saved_records table: " . mysqli_error($conn));
    }
    echo "<!-- Debug: Created saved_records table -->";
}

// Get piggie box summary with error checking
$total_deposits_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM piggie_box_transactions 
                        WHERE transaction_type IN ('DEPOSIT', 'INTEREST')";
$total_deposits_result = mysqli_query($conn, $total_deposits_sql);

if (!$total_deposits_result) {
    die("Error in deposits query: " . mysqli_error($conn) . "<br>SQL: " . $total_deposits_sql);
}
$total_deposits = mysqli_fetch_assoc($total_deposits_result)['total'];

$total_withdrawals_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM piggie_box_transactions 
                           WHERE transaction_type = 'WITHDRAWAL'";
$total_withdrawals_result = mysqli_query($conn, $total_withdrawals_sql);

if (!$total_withdrawals_result) {
    die("Error in withdrawals query: " . mysqli_error($conn));
}
$total_withdrawals = mysqli_fetch_assoc($total_withdrawals_result)['total'];

$current_balance = $total_deposits - $total_withdrawals;

// Get recent transactions
$transactions_sql = "SELECT p.*, a.username as recorded_by_name 
                     FROM piggie_box_transactions p
                     LEFT JOIN admin_users a ON p.recorded_by = a.id
                     ORDER BY p.transaction_date DESC 
                     LIMIT 20";
$transactions_result = mysqli_query($conn, $transactions_sql);

if (!$transactions_result) {
    die("Error in transactions query: " . mysqli_error($conn));
}

// Get saved records for piggie box
$records_sql = "SELECT * FROM saved_records 
                WHERE record_type = 'piggie_box' AND is_active = 1 
                ORDER BY record_date DESC";
$records_result = mysqli_query($conn, $records_sql);

if (!$records_result) {
    die("Error in records query: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Piggie Box Management - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .balance-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .balance-card h2 {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        
        .balance-card .amount {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .stat-card.deposits { border-top: 4px solid #28a745; }
        .stat-card.withdrawals { border-top: 4px solid #dc3545; }
        .stat-card.balance { border-top: 4px solid #17a2b8; }
        
        .stat-card .label {
            font-size: 16px;
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            flex: 1;
            min-width: 150px;
            padding: 20px;
            border-radius: 12px;
            background: white;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .action-btn.deposit {
            color: #28a745;
            border: 2px solid #28a745;
        }
        
        .action-btn.deposit:hover {
            background: #28a745;
            color: white;
            transform: translateY(-3px);
        }
        
        .action-btn.withdraw {
            color: #dc3545;
            border: 2px solid #dc3545;
        }
        
        .action-btn.withdraw:hover {
            background: #dc3545;
            color: white;
            transform: translateY(-3px);
        }
        
        .action-btn.interest {
            color: #17a2b8;
            border: 2px solid #17a2b8;
        }
        
        .action-btn.interest:hover {
            background: #17a2b8;
            color: white;
            transform: translateY(-3px);
        }
        
        .transaction-history {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .transaction-header {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .badge-deposit {
            background: #d4edda;
            color: #155724;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-withdrawal {
            background: #f8d7da;
            color: #721c24;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-interest {
            background: #fff3cd;
            color: #856404;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .amount-positive {
            color: #28a745;
            font-weight: bold;
        }
        
        .amount-negative {
            color: #dc3545;
            font-weight: bold;
        }
        
        .debug-info {
            background: #f8f9fa;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            margin-bottom: 20px;
            font-family: monospace;
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
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
            <!-- Debug Info (remove in production) -->
            <div class="debug-info">
                <strong>Debug Information:</strong><br>
                Current file: <?php echo __FILE__; ?><br>
                Database: <?php echo DB_NAME; ?><br>
                Total Deposits: <?php echo $total_deposits; ?><br>
                Total Withdrawals: <?php echo $total_withdrawals; ?><br>
                Current Balance: <?php echo $current_balance; ?><br>
                Transactions Found: <?php echo mysqli_num_rows($transactions_result); ?><br>
                Records Found: <?php echo mysqli_num_rows($records_result); ?>
            </div>
            
            <div class="page-header">
                <h1><i class="fas fa-piggy-bank"></i> Piggie Box Management</h1>
                <div class="page-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Saved Records
                    </a>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            
            <!-- Balance Card -->
            <div class="balance-card">
                <h2><i class="fas fa-wallet"></i> Current Balance</h2>
                <div class="amount"><?php echo formatCurrency($current_balance); ?></div>
                <p>Last updated: <?php echo date('d/m/Y H:i'); ?></p>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card deposits">
                    <div class="label">Total Deposits</div>
                    <div class="value"><?php echo formatCurrency($total_deposits); ?></div>
                </div>
                <div class="stat-card withdrawals">
                    <div class="label">Total Withdrawals</div>
                    <div class="value"><?php echo formatCurrency($total_withdrawals); ?></div>
                </div>
                <div class="stat-card balance">
                    <div class="label">Net Balance</div>
                    <div class="value"><?php echo formatCurrency($current_balance); ?></div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="action-btn deposit" onclick="openModal('depositModal')">
                    <i class="fas fa-plus-circle"></i> Make Deposit
                </button>
                <button class="action-btn withdraw" onclick="openModal('withdrawModal')">
                    <i class="fas fa-minus-circle"></i> Make Withdrawal
                </button>
                <button class="action-btn interest" onclick="openModal('interestModal')">
                    <i class="fas fa-percent"></i> Add Interest
                </button>
            </div>
            
            <!-- Transaction History -->
            <div class="transaction-history">
                <div class="transaction-header">
                    <h3><i class="fas fa-history"></i> Recent Transactions</h3>
                </div>
                
                <div class="card-body">
                    <?php if (mysqli_num_rows($transactions_result) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Amount (FCFA)</th>
                                    <th>Balance After</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($trans = mysqli_fetch_assoc($transactions_result)): ?>
                                <tr>
                                    <td><?php echo formatDate($trans['transaction_date']); ?></td>
                                    <td>
                                        <?php 
                                        $badge_class = '';
                                        if ($trans['transaction_type'] == 'DEPOSIT') {
                                            $badge_class = 'badge-deposit';
                                            $type_text = 'DEPOSIT';
                                        } elseif ($trans['transaction_type'] == 'WITHDRAWAL') {
                                            $badge_class = 'badge-withdrawal';
                                            $type_text = 'WITHDRAWAL';
                                        } else {
                                            $badge_class = 'badge-interest';
                                            $type_text = 'INTEREST';
                                        }
                                        ?>
                                        <span class="<?php echo $badge_class; ?>"><?php echo $type_text; ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($trans['description'] ?? ''); ?></td>
                                    <td class="<?php echo ($trans['transaction_type'] == 'WITHDRAWAL') ? 'amount-negative' : 'amount-positive'; ?>">
                                        <?php echo ($trans['transaction_type'] == 'WITHDRAWAL' ? '- ' : '+ ') . formatCurrency($trans['amount']); ?>
                                    </td>
                                    <td><?php echo $trans['balance_after'] ? formatCurrency($trans['balance_after']) : '-'; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-exchange-alt fa-4x"></i>
                            <p>No transactions yet. Make your first deposit!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Deposit Modal -->
    <div id="depositModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Make Deposit</h3>
                <span class="close" onclick="closeModal('depositModal')">&times;</span>
            </div>
            <form method="POST" action="piggie_box_transaction_process.php">
                <input type="hidden" name="action" value="deposit">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Amount (FCFA) *</label>
                        <input type="number" name="amount" required class="form-control" min="1" step="100">
                    </div>
                    <div class="form-group">
                        <label>Transaction Date *</label>
                        <input type="date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3" class="form-control" placeholder="Reason for deposit"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Reference Number</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="Optional reference">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Process Deposit</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('depositModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Withdrawal Modal -->
    <div id="withdrawModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-minus-circle"></i> Make Withdrawal</h3>
                <span class="close" onclick="closeModal('withdrawModal')">&times;</span>
            </div>
            <form method="POST" action="piggie_box_transaction_process.php">
                <input type="hidden" name="action" value="withdrawal">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Amount (FCFA) *</label>
                        <input type="number" name="amount" required class="form-control" min="1" step="100"
                               max="<?php echo $current_balance; ?>">
                        <small>Available balance: <?php echo formatCurrency($current_balance); ?></small>
                    </div>
                    <div class="form-group">
                        <label>Transaction Date *</label>
                        <input type="date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Purpose/Reason *</label>
                        <textarea name="description" rows="3" class="form-control" required placeholder="Reason for withdrawal"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">Process Withdrawal</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('withdrawModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Interest Modal -->
    <div id="interestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-percent"></i> Add Interest</h3>
                <span class="close" onclick="closeModal('interestModal')">&times;</span>
            </div>
            <form method="POST" action="piggie_box_transaction_process.php">
                <input type="hidden" name="action" value="interest">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Interest Amount (FCFA) *</label>
                        <input type="number" name="amount" required class="form-control" min="1" step="100">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="2" class="form-control" placeholder="Interest description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-info">Add Interest</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('interestModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow: auto;
        }
        
        .modal-content {
            background-color: white;
            margin: 50px auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
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
    </style>
    
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>