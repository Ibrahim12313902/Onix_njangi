<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();

// Get report type from URL
$report_type = isset($_GET['type']) ? $_GET['type'] : 'summary';
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');

// Get summary statistics
$summary_sql = "SELECT 
                    record_type,
                    COUNT(*) as total_records,
                    SUM(amount) as total_amount,
                    MAX(amount) as max_amount,
                    MIN(amount) as min_amount,
                    AVG(amount) as avg_amount
                FROM saved_records 
                WHERE is_active = 1
                GROUP BY record_type";
$summary_result = mysqli_query($conn, $summary_sql);

// Get monthly breakdown for current year
$monthly_sql = "SELECT 
                    MONTH(record_date) as month,
                    COUNT(*) as record_count,
                    SUM(amount) as total
                FROM saved_records 
                WHERE is_active = 1 
                AND YEAR(record_date) = $year
                GROUP BY MONTH(record_date)
                ORDER BY month";
$monthly_result = mysqli_query($conn, $monthly_sql);

// Get yearly totals
$yearly_sql = "SELECT 
                    YEAR(record_date) as year,
                    COUNT(*) as record_count,
                    SUM(amount) as total
                FROM saved_records 
                WHERE is_active = 1
                GROUP BY YEAR(record_date)
                ORDER BY year DESC";
$yearly_result = mysqli_query($conn, $yearly_sql);

// Get all records for detailed report
$details_sql = "SELECT * FROM saved_records 
                WHERE is_active = 1 
                ORDER BY record_date DESC 
                LIMIT 100";
$details_result = mysqli_query($conn, $details_sql);

closeDbConnection($conn);
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .report-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .report-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 10px;
        }
        
        .report-tab {
            padding: 10px 20px;
            border-radius: 8px 8px 0 0;
            background: #f8f9fa;
            color: #6c757d;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .report-tab:hover {
            background: #e9ecef;
        }
        
        .report-tab.active {
            background: #667eea;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .stat-card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .stat-row .label {
            color: #6c757d;
        }
        
        .stat-row .value {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .amount-highlight {
            color: #28a745;
            font-size: 18px;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .report-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .print-btn, .export-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .print-btn {
            background: #17a2b8;
            color: white;
        }
        
        .export-btn {
            background: #28a745;
            color: white;
        }
        
        .print-btn:hover, .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <?php include '../includes/notification_setup.php'; ?>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="report-header">
                <h1><i class="fas fa-chart-bar"></i> Saved Records Reports</h1>
                <p>Comprehensive analysis of all your saved records</p>
            </div>
            
            <div class="report-tabs">
                <a href="reports.php?type=summary" class="report-tab <?php echo $report_type == 'summary' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i> Summary
                </a>
                <a href="reports.php?type=monthly" class="report-tab <?php echo $report_type == 'monthly' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i> Monthly
                </a>
                <a href="reports.php?type=yearly" class="report-tab <?php echo $report_type == 'yearly' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar"></i> Yearly
                </a>
                <a href="reports.php?type=details" class="report-tab <?php echo $report_type == 'details' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> Details
                </a>
            </div>
            
            <div class="report-actions">
                <button onclick="window.print()" class="print-btn">
                    <i class="fas fa-print"></i> Print Report
                </button>
                <button onclick="exportToExcel()" class="export-btn">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </button>
            </div>
            
            <?php if ($report_type == 'summary'): ?>
                <!-- Summary Report -->
                <div class="stats-grid">
                    <?php 
                    $grand_total = 0;
                    $total_records = 0;
                    while ($stat = mysqli_fetch_assoc($summary_result)): 
                        $grand_total += $stat['total_amount'];
                        $total_records += $stat['total_records'];
                    ?>
                    <div class="stat-card">
                        <h3>
                            <?php 
                            $icons = [
                                'current_month' => '📅 Current Month',
                                'last_quarter' => '📊 Last Quarter',
                                'achievement' => '🏆 Achievement',
                                'piggie_box' => '🐷 Piggie Box'
                            ];
                            echo $icons[$stat['record_type']] ?? ucfirst($stat['record_type']);
                            ?>
                        </h3>
                        <div class="stat-row">
                            <span class="label">Total Records:</span>
                            <span class="value"><?php echo $stat['total_records']; ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="label">Total Amount:</span>
                            <span class="value amount-highlight"><?php echo formatCurrency($stat['total_amount']); ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="label">Average:</span>
                            <span class="value"><?php echo formatCurrency($stat['avg_amount']); ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="label">Highest:</span>
                            <span class="value"><?php echo formatCurrency($stat['max_amount']); ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="label">Lowest:</span>
                            <span class="value"><?php echo formatCurrency($stat['min_amount']); ?></span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    
                    <!-- Grand Total Card -->
                    <div class="stat-card" style="background: linear-gradient(135deg, #667eea20, #764ba220);">
                        <h3>📊 GRAND TOTAL</h3>
                        <div class="stat-row">
                            <span class="label">All Records:</span>
                            <span class="value"><?php echo $total_records; ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="label">Total Amount:</span>
                            <span class="value" style="color: #667eea; font-size: 24px;"><?php echo formatCurrency($grand_total); ?></span>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($report_type == 'monthly'): ?>
                <!-- Monthly Report -->
                <div class="chart-container">
                    <h2><i class="fas fa-calendar-alt"></i> Monthly Breakdown - <?php echo $year; ?></h2>
                    
                    <!-- Month selector -->
                    <form method="GET" style="margin: 20px 0;">
                        <input type="hidden" name="type" value="monthly">
                        <select name="year" onchange="this.form.submit()">
                            <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </form>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Records</th>
                                <th>Total Amount (FCFA)</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $monthly_total = 0;
                            $months_data = [];
                            while ($row = mysqli_fetch_assoc($monthly_result)) {
                                $months_data[$row['month']] = $row;
                                $monthly_total += $row['total'];
                            }
                            
                            for ($m = 1; $m <= 12; $m++):
                                $data = $months_data[$m] ?? null;
                                $month_name = date('F', mktime(0, 0, 0, $m, 1));
                            ?>
                            <tr>
                                <td><strong><?php echo $month_name; ?></strong></td>
                                <td><?php echo $data ? $data['record_count'] : 0; ?></td>
                                <td><?php echo $data ? formatCurrency($data['total']) : '-'; ?></td>
                                <td>
                                    <?php if ($data && $monthly_total > 0): ?>
                                        <?php echo round(($data['total'] / $monthly_total * 100), 1); ?>%
                                    <?php else: ?>
                                        0%
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>TOTAL</th>
                                <th><?php echo array_sum(array_column($months_data, 'record_count')); ?></th>
                                <th><?php echo formatCurrency($monthly_total); ?></th>
                                <th>100%</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
            <?php elseif ($report_type == 'yearly'): ?>
                <!-- Yearly Report -->
                <div class="chart-container">
                    <h2><i class="fas fa-calendar"></i> Yearly Summary</h2>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Year</th>
                                <th>Records</th>
                                <th>Total Amount (FCFA)</th>
                                <th>Average per Record</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($yearly_result)): ?>
                            <tr>
                                <td><strong><?php echo $row['year']; ?></strong></td>
                                <td><?php echo $row['record_count']; ?></td>
                                <td><?php echo formatCurrency($row['total']); ?></td>
                                <td><?php echo formatCurrency($row['total'] / $row['record_count']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
            <?php elseif ($report_type == 'details'): ?>
                <!-- Detailed Report -->
                <div class="chart-container">
                    <h2><i class="fas fa-list"></i> Detailed Records (Last 100)</h2>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Title</th>
                                <th>Amount (FCFA)</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($details_result)): ?>
                            <tr>
                                <td><?php echo formatDate($row['record_date']); ?></td>
                                <td>
                                    <?php 
                                    $badge_color = '';
                                    switch($row['record_type']) {
                                        case 'current_month': $badge_color = 'badge-success'; break;
                                        case 'last_quarter': $badge_color = 'badge-info'; break;
                                        case 'achievement': $badge_color = 'badge-warning'; break;
                                        case 'piggie_box': $badge_color = 'badge-danger'; break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_color; ?>">
                                        <?php echo $row['record_type']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo formatCurrency($row['amount']); ?></td>
                                <td><?php echo htmlspecialchars(substr($row['description'] ?? '', 0, 50)); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        function exportToExcel() {
            // Create CSV content
            var csv = [];
            var rows = document.querySelectorAll('table.data-table tr');
            
            for (var i = 0; i < rows.length; i++) {
                var row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (var j = 0; j < cols.length; j++) {
                    row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
                }
                
                csv.push(row.join(','));
            }
            
            // Download CSV
            var csvContent = csv.join('\n');
            var blob = new Blob([csvContent], { type: 'text/csv' });
            var url = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'saved_records_report.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>