<?php
require_once 'config/constants.php';
requireLogin();
require_once 'config/database.php';

$conn = getDbConnection();

// Get counts for dashboard
$counts = [];

// Count member types
$sql = "SELECT COUNT(*) as count FROM member_types";
$result = mysqli_query($conn, $sql);
$counts['member_types'] = mysqli_fetch_assoc($result)['count'];

// Count hand types
$sql = "SELECT COUNT(*) as count FROM hand_types";
$result = mysqli_query($conn, $sql);
$counts['hand_types'] = mysqli_fetch_assoc($result)['count'];

// Count total members
$sql = "SELECT COUNT(*) as count FROM members";
$result = mysqli_query($conn, $sql);
$counts['total_members'] = mysqli_fetch_assoc($result)['count'];

// Count hands
$sql = "SELECT COUNT(*) as count FROM hands";
$result = mysqli_query($conn, $sql);
$counts['hands'] = mysqli_fetch_assoc($result)['count'];

// Count payout cycles
$sql = "SELECT COUNT(*) as count FROM payout_cycles";
$result = mysqli_query($conn, $sql);
$counts['payout_cycles'] = mysqli_fetch_assoc($result)['count'];

// Get active cycle summary
$sql = "SELECT pc.*, 
        (SELECT SUM(COALESCE(ht.default_amount, h.amount, 0)) FROM hands h 
         JOIN hand_types ht ON h.hand_type_id = ht.id 
         WHERE h.payout_cycle_id = pc.id) as total_expected,
        (SELECT COUNT(*) FROM cycle_hands ch WHERE ch.cycle_id = pc.id) as total_hands
        FROM payout_cycles pc 
        WHERE pc.status = 'active' 
        ORDER BY pc.created_at DESC LIMIT 1";
$result = mysqli_query($conn, $sql);
$active_cycle = mysqli_fetch_assoc($result) ?? null;

// Get notification counts for header
$sql = "SELECT COUNT(*) as count FROM hand_requests WHERE status = 'pending'";
$pending_hands = mysqli_fetch_assoc(mysqli_query($conn, $sql))['count'];

$sql = "SELECT COUNT(*) as count FROM group_messages WHERE is_admin_message = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$new_chats = mysqli_fetch_assoc(mysqli_query($conn, $sql))['count'];

$total_notifications = $pending_hands + $new_chats;

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/notification_setup.php'; ?>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                <p>Welcome, <?php echo $_SESSION['admin_name']; ?>!</p>
            </div>
            
            <!-- Dashboard Stats -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Member Types</h3>
                        <p class="stat-number"><?php echo $counts['member_types']; ?></p>
                        <a href="member_types/index.php" class="stat-link">More info <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-hand-holding"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Hand Types</h3>
                        <p class="stat-number"><?php echo $counts['hand_types']; ?></p>
                        <a href="hand_types/index.php" class="stat-link">More info <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Members</h3>
                        <p class="stat-number"><?php echo $counts['total_members']; ?></p>
                        <a href="members/index.php" class="stat-link">More info <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-hands-helping"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Hands</h3>
                        <p class="stat-number"><?php echo $counts['hands']; ?></p>
                        <a href="hands/index.php" class="stat-link">More info <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Payout Cycles</h3>
                        <p class="stat-number"><?php echo $counts['payout_cycles']; ?></p>
                        <a href="payout_cycles/index.php" class="stat-link">More info <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            
            <?php if ($active_cycle): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-star"></i> Active Payout Cycle</h3>
                </div>
                <div class="card-body">
                    <div class="active-cycle-info">
                        <div class="cycle-detail">
                            <label>Cycle Name</label>
                            <span><?php echo htmlspecialchars($active_cycle['cycle_name']); ?></span>
                        </div>
                        <div class="cycle-detail">
                            <label>Start Date</label>
                            <span><?php echo formatDate($active_cycle['start_date']); ?></span>
                        </div>
                        <div class="cycle-detail">
                            <label>End Date</label>
                            <span><?php echo formatDate($active_cycle['end_date']); ?></span>
                        </div>
                        <div class="cycle-detail">
                            <label>Total Hands</label>
                            <span><?php echo $active_cycle['total_hands']; ?></span>
                        </div>
                        <div class="cycle-detail">
                            <label>Total Expected</label>
                            <span><?php echo formatCurrency($active_cycle['total_expected'] ?? 0); ?></span>
                        </div>
                    </div>
                    <div class="text-center" style="margin-top: 20px;">
                        <a href="payout_cycles/view.php?id=<?php echo $active_cycle['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> View Full Details
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Saved Records Section -->
            <div class="saved-records">
                <h2><i class="fas fa-save"></i> SAVED RECORDS</h2>
                <div class="records-grid">
                    <div class="record-item">
                        <h4>Current Month</h4>
                        <p>View this month's contributions</p>
                    </div>
                    <div class="record-item">
                        <h4>Last Quarter</h4>
                        <p>Last 3 months records</p>
                    </div>
                    <div class="record-item">
                        <h4>Achievement</h4>
                        <p>System milestones</p>
                    </div>
                    <div class="record-item">
                        <h4>Piggie Box</h4>
                        <p>Emergency savings</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    <script src="js/script.js"></script>
</body>
</html>