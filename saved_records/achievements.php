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

// Get filter parameters
$filter_type = isset($_GET['type']) ? mysqli_real_escape_string($conn, $_GET['type']) : '';
$filter_year = isset($_GET['year']) ? (int)$_GET['year'] : 0;

// Build where clause
$where = "WHERE record_type = 'achievement' AND is_active = 1";
if (!empty($filter_type) && $filter_type != 'all') {
    $where .= " AND title LIKE '%$filter_type%'";
}
if ($filter_year > 0) {
    $where .= " AND YEAR(record_date) = $filter_year";
}

// Get total count
$sql = "SELECT COUNT(*) as total FROM saved_records $where";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Error in query: " . mysqli_error($conn));
}

$total_rows = mysqli_fetch_assoc($result)['total'];
$total_pages = ceil($total_rows / $limit);

// Get records
$sql = "SELECT * FROM saved_records 
        $where 
        ORDER BY record_date DESC 
        LIMIT $limit OFFSET $offset";
$records_result = mysqli_query($conn, $sql);

if (!$records_result) {
    die("Error in query: " . mysqli_error($conn));
}

// Get achievements from the dedicated achievements table if it exists
$achievements_table_exists = false;
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'achievements'");
if (mysqli_num_rows($check_table) > 0) {
    $achievements_table_exists = true;
    $achievements_sql = "SELECT * FROM achievements WHERE is_active = 1 ORDER BY achievement_date DESC LIMIT 10";
    $achievements_result = mysqli_query($conn, $achievements_sql);
}

// Get achievement statistics
$stats_sql = "SELECT 
                COUNT(*) as total_achievements,
                SUM(CASE WHEN amount > 0 THEN 1 ELSE 0 END) as monetary_achievements,
                YEAR(record_date) as year,
                COUNT(*) as year_count
              FROM saved_records 
              WHERE record_type = 'achievement' AND is_active = 1
              GROUP BY YEAR(record_date)
              ORDER BY year DESC";
$stats_result = mysqli_query($conn, $stats_sql);

// Get years for filter
$years_sql = "SELECT DISTINCT YEAR(record_date) as year 
              FROM saved_records 
              WHERE record_type = 'achievement' AND is_active = 1
              ORDER BY year DESC";
$years_result = mysqli_query($conn, $years_sql);

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Achievements - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .achievements-header {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .achievements-header h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .achievements-header p {
            font-size: 18px;
            opacity: 0.9;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 30px;
        }
        
        .stat-content h3 {
            font-size: 16px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .stat-content .number {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .achievement-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .achievement-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .achievement-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 193, 7, 0.2);
        }
        
        .achievement-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #ffc107;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .achievement-badge i {
            font-size: 16px;
        }
        
        .achievement-icon {
            height: 120px;
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
        }
        
        .achievement-content {
            padding: 25px;
        }
        
        .achievement-title {
            font-size: 22px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .achievement-date {
            color: #ffc107;
            font-size: 14px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .achievement-description {
            color: #6c757d;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .achievement-amount {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .achievement-amount .label {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .achievement-amount .value {
            font-size: 28px;
            font-weight: bold;
            color: #28a745;
        }
        
        .achievement-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }
        
        .achievement-actions {
            display: flex;
            gap: 10px;
        }
        
        .trophy-gold { color: #ffc107; }
        .trophy-silver { color: #c0c0c0; }
        .trophy-bronze { color: #cd7f32; }
        
        .year-badge {
            background: #ffc107;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .achievement-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
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
            <!-- Header -->
            <div class="achievements-header">
                <h1><i class="fas fa-trophy"></i> Achievements & Milestones</h1>
                <p>Celebrating every success and milestone of our Njangi group</p>
            </div>
            
            <!-- Action Buttons -->
            <div class="page-actions" style="margin-bottom: 20px;">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Saved Records
                </a>
                <a href="create.php?type=achievement" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Achievement
                </a>
                <a href="#" onclick="window.print()" class="btn btn-info">
                    <i class="fas fa-print"></i> Print Achievements
                </a>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <?php 
            $total_achievements = 0;
            $monetary_achievements = 0;
            $unique_years = [];
            
            mysqli_data_seek($stats_result, 0);
            while ($stat = mysqli_fetch_assoc($stats_result)) {
                $total_achievements += $stat['year_count'];
                $monetary_achievements += $stat['monetary_achievements'];
                $unique_years[] = $stat['year'];
            }
            ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Achievements</h3>
                        <div class="number"><?php echo $total_achievements; ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <h3>This Year</h3>
                        <div class="number">
                            <?php 
                            $this_year = date('Y');
                            $this_year_count = 0;
                            mysqli_data_seek($stats_result, 0);
                            while ($stat = mysqli_fetch_assoc($stats_result)) {
                                if ($stat['year'] == $this_year) {
                                    $this_year_count = $stat['year_count'];
                                    break;
                                }
                            }
                            echo $this_year_count;
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Years Active</h3>
                        <div class="number"><?php echo count($unique_years); ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Monetary Milestones</h3>
                        <div class="number"><?php echo $monetary_achievements; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Filter Section -->
            <div class="filter-section">
                <h3><i class="fas fa-filter"></i> Filter Achievements</h3>
                <form method="GET" class="form-row" style="align-items: flex-end;">
                    <div class="form-group">
                        <label>Year</label>
                        <select name="year" class="form-control">
                            <option value="0">All Years</option>
                            <?php 
                            mysqli_data_seek($years_result, 0);
                            while ($year = mysqli_fetch_assoc($years_result)): 
                            ?>
                            <option value="<?php echo $year['year']; ?>" 
                                <?php echo ($filter_year == $year['year']) ? 'selected' : ''; ?>>
                                <?php echo $year['year']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Achievement Type</label>
                        <select name="type" class="form-control">
                            <option value="all">All Types</option>
                            <option value="members" <?php echo ($filter_type == 'members') ? 'selected' : ''; ?>>Member Milestones</option>
                            <option value="savings" <?php echo ($filter_type == 'savings') ? 'selected' : ''; ?>>Savings Milestones</option>
                            <option value="hands" <?php echo ($filter_type == 'hands') ? 'selected' : ''; ?>>Hand Milestones</option>
                            <option value="special" <?php echo ($filter_type == 'special') ? 'selected' : ''; ?>>Special Events</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Apply Filter</button>
                        <a href="achievements.php" class="btn btn-secondary">Clear</a>
                    </div>
                </form>
            </div>
            
            <!-- Achievements Grid -->
            <?php if (mysqli_num_rows($records_result) > 0): ?>
                <div class="achievement-grid">
                    <?php 
                    $counter = 1;
                    while ($record = mysqli_fetch_assoc($records_result)): 
                        $icon = $record['icon'] ?? 'fas fa-trophy';
                        $color = $record['color'] ?? '#ffc107';
                        
                        // Determine trophy color based on achievement type or counter
                        $trophy_class = 'trophy-gold';
                        if ($counter % 3 == 0) $trophy_class = 'trophy-silver';
                        elseif ($counter % 3 == 1) $trophy_class = 'trophy-bronze';
                    ?>
                    <div class="achievement-card">
                        <div class="achievement-badge">
                            <i class="fas fa-star"></i> Achievement
                        </div>
                        <div class="achievement-icon" style="background: linear-gradient(135deg, <?php echo $color; ?> 0%, <?php echo adjustBrightness($color, -20); ?> 100%);">
                            <i class="<?php echo $icon; ?>"></i>
                        </div>
                        <div class="achievement-content">
                            <h3 class="achievement-title"><?php echo htmlspecialchars($record['title']); ?></h3>
                            <div class="achievement-date">
                                <i class="fas fa-calendar-check <?php echo $trophy_class; ?>"></i>
                                <?php echo formatDate($record['record_date']); ?>
                            </div>
                            <div class="achievement-description">
                                <?php echo nl2br(htmlspecialchars($record['description'] ?? 'No description provided.')); ?>
                            </div>
                            
                            <?php if ($record['amount'] > 0): ?>
                            <div class="achievement-amount">
                                <div class="label">Milestone Amount</div>
                                <div class="value"><?php echo formatCurrency($record['amount']); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="achievement-footer">
                                <div class="year-badge">
                                    <i class="fas fa-calendar"></i> <?php echo date('Y', strtotime($record['record_date'])); ?>
                                </div>
                                <div class="achievement-actions">
                                    <a href="view.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Delete this achievement?')" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php 
                    $counter++;
                    endwhile; 
                    ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?><?php echo $filter_year ? '&year='.$filter_year : ''; ?><?php echo $filter_type ? '&type='.$filter_type : ''; ?>" 
                           class="page-link">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $filter_year ? '&year='.$filter_year : ''; ?><?php echo $filter_type ? '&type='.$filter_type : ''; ?>" 
                           class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?><?php echo $filter_year ? '&year='.$filter_year : ''; ?><?php echo $filter_type ? '&type='.$filter_type : ''; ?>" 
                           class="page-link">Next &raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-trophy fa-4x"></i>
                    <h3>No Achievements Found</h3>
                    <p><?php echo !empty($filter_type) || $filter_year > 0 ? 'No achievements match your filter criteria.' : 'Start by adding your first achievement.'; ?></p>
                    <a href="create.php?type=achievement" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add First Achievement
                    </a>
                </div>
            <?php endif; ?>
            
            <!-- Dedicated Achievements Table Section (if exists) -->
            <?php if ($achievements_table_exists && isset($achievements_result) && mysqli_num_rows($achievements_result) > 0): ?>
            <div class="card" style="margin-top: 40px;">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Achievement Categories</h3>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Achievement</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 1;
                            while ($ach = mysqli_fetch_assoc($achievements_result)): 
                            ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td>
                                    <i class="<?php echo htmlspecialchars($ach['icon'] ?? 'fas fa-trophy'); ?>" style="color: <?php echo htmlspecialchars($ach['color'] ?? '#ffc107'); ?>;"></i>
                                    <?php echo htmlspecialchars($ach['title']); ?>
                                </td>
                                <td><?php echo htmlspecialchars(substr($ach['description'] ?? '', 0, 100)); ?></td>
                                <td><?php echo formatDate($ach['achievement_date']); ?></td>
                                <td>
                                    <span class="badge badge-success">Active</span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../js/script.js"></script>
</body>
</html>
<?php
// Helper function to adjust brightness of hex color
function adjustBrightness($hex, $percent) {
    // Implementation if needed
    return $hex;
}
?>