<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';
require_once '../includes/notification_setup.php';

$conn = getDbConnection();

$error = '';
$success = '';

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve'])) {
        $request_id = (int)$_POST['request_id'];
        $hand_status_id = (int)$_POST['hand_status_id'];
        
        $sql = "SELECT * FROM hand_requests WHERE id = $request_id AND status = 'pending'";
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) == 1) {
            $request = mysqli_fetch_assoc($result);
            
            $hand_number = 'H' . date('Ymd') . rand(1000, 9999);
            
            $sql = "SELECT * FROM hand_types WHERE id = " . $request['hand_type_id'];
            $ht_result = mysqli_query($conn, $sql);
            $hand_type = mysqli_fetch_assoc($ht_result);
            
            $sql = "INSERT INTO hands (hand_number, member_id, hand_type_id, hand_status_id, amount, opening_date, notes)
                    VALUES ('$hand_number', '" . $request['member_id'] . "', '" . $request['hand_type_id'] . "', '$hand_status_id', '" . $request['amount'] . "', CURDATE(), '" . mysqli_real_escape_string($conn, $request['notes']) . "')";
            
            if (mysqli_query($conn, $sql)) {
                $new_hand_id = mysqli_insert_id($conn);
                
                $sql = "UPDATE hand_requests SET 
                        status = 'approved', 
                        hand_number = '$hand_number',
                        reviewed_by = " . $_SESSION['admin_id'] . ",
                        reviewed_at = NOW() 
                        WHERE id = $request_id";
                mysqli_query($conn, $sql);
                
                $sql = "SELECT first_name, surname FROM members WHERE id = " . $request['member_id'];
                $member = mysqli_fetch_assoc(mysqli_query($conn, $sql));
                
                $notification_title = "Hand Request Approved!";
                $notification_msg = "Your request for a " . $hand_type['hand_type_name'] . " hand has been approved. Your hand number is $hand_number. You can now start making contributions.";
                $sql = "INSERT INTO member_notifications (member_id, title, message, type) 
                        VALUES ('" . $request['member_id'] . "', '$notification_title', '$notification_msg', 'success')";
                mysqli_query($conn, $sql);
                
                $success = "Hand '$hand_number' created successfully for " . $member['first_name'] . " " . $member['surname'] . "!";
            } else {
                $error = 'Error creating hand: ' . mysqli_error($conn);
            }
        }
    }
    
    if (isset($_POST['reject'])) {
        $request_id = (int)$_POST['request_id'];
        $rejection_reason = mysqli_real_escape_string($conn, $_POST['rejection_reason']);
        
        if (empty($rejection_reason)) {
            $error = 'Please provide a reason for rejection!';
        } else {
            $sql = "SELECT hr.*, ht.hand_type_name, m.first_name, m.surname 
                    FROM hand_requests hr 
                    JOIN hand_types ht ON hr.hand_type_id = ht.id 
                    JOIN members m ON hr.member_id = m.id 
                    WHERE hr.id = $request_id AND hr.status = 'pending'";
            $result = mysqli_query($conn, $sql);
            
            if (mysqli_num_rows($result) == 1) {
                $request = mysqli_fetch_assoc($result);
                
                $sql = "UPDATE hand_requests SET 
                        status = 'rejected', 
                        rejection_reason = '$rejection_reason',
                        reviewed_by = " . $_SESSION['admin_id'] . ",
                        reviewed_at = NOW() 
                        WHERE id = $request_id";
                
                if (mysqli_query($conn, $sql)) {
                    $notification_title = "Hand Request Rejected";
                    $notification_msg = "Your request for a " . $request['hand_type_name'] . " hand has been rejected. Reason: $rejection_reason";
                    $sql = "INSERT INTO member_notifications (member_id, title, message, type) 
                            VALUES ('" . $request['member_id'] . "', '$notification_title', '$notification_msg', 'danger')";
                    mysqli_query($conn, $sql);
                    
                    $success = "Request from " . $request['first_name'] . " " . $request['surname'] . " has been rejected.";
                }
            }
        }
    }
}

$status_filter = $_GET['status'] ?? 'all';
$where_clause = '';
if ($status_filter == 'pending') $where_clause = "WHERE hr.status = 'pending'";
elseif ($status_filter == 'approved') $where_clause = "WHERE hr.status = 'approved'";
elseif ($status_filter == 'rejected') $where_clause = "WHERE hr.status = 'rejected'";

$sql = "SELECT hr.*, ht.hand_type_name, m.member_number, m.first_name, m.surname, m.phone,
        a.full_name as reviewed_by_name
        FROM hand_requests hr 
        JOIN hand_types ht ON hr.hand_type_id = ht.id 
        JOIN members m ON hr.member_id = m.id 
        LEFT JOIN admin_users a ON hr.reviewed_by = a.id
        $where_clause
        ORDER BY hr.created_at DESC";
$result = mysqli_query($conn, $sql);

$sql = "SELECT status, COUNT(*) as count FROM hand_requests GROUP BY status";
$status_counts = mysqli_query($conn, $sql);
$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
while ($row = mysqli_fetch_assoc($status_counts)) {
    $counts[$row['status']] = $row['count'];
}

$sql = "SELECT * FROM hand_status ORDER BY hand_status_name";
$hand_statuses = mysqli_query($conn, $sql);

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hand Requests - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-clipboard-list"></i> Hand Requests Management</h1>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 25px;">
                <div class="card" style="text-align: center;">
                    <div style="font-size: 32px; font-weight: bold; color: #333;"><?php echo array_sum($counts); ?></div>
                    <div style="color: #666; font-size: 13px;">Total Requests</div>
                </div>
                <div class="card" style="text-align: center; border-left: 4px solid #ffc107;">
                    <div style="font-size: 32px; font-weight: bold; color: #ffc107;"><?php echo $counts['pending']; ?></div>
                    <div style="color: #666; font-size: 13px;">Pending</div>
                </div>
                <div class="card" style="text-align: center; border-left: 4px solid #28a745;">
                    <div style="font-size: 32px; font-weight: bold; color: #28a745;"><?php echo $counts['approved']; ?></div>
                    <div style="color: #666; font-size: 13px;">Approved</div>
                </div>
                <div class="card" style="text-align: center; border-left: 4px solid #dc3545;">
                    <div style="font-size: 32px; font-weight: bold; color: #dc3545;"><?php echo $counts['rejected']; ?></div>
                    <div style="color: #666; font-size: 13px;">Rejected</div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Filter</h3>
                </div>
                <div class="card-body">
                    <div style="display: flex; gap: 10px;">
                        <a href="index.php" class="btn <?php echo $status_filter == 'all' ? 'btn-primary' : 'btn-secondary'; ?>">All</a>
                        <a href="index.php?status=pending" class="btn <?php echo $status_filter == 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">
                            <i class="fas fa-clock"></i> Pending (<?php echo $counts['pending']; ?>)
                        </a>
                        <a href="index.php?status=approved" class="btn <?php echo $status_filter == 'approved' ? 'btn-primary' : 'btn-secondary'; ?>">
                            <i class="fas fa-check"></i> Approved
                        </a>
                        <a href="index.php?status=rejected" class="btn <?php echo $status_filter == 'rejected' ? 'btn-primary' : 'btn-secondary'; ?>">
                            <i class="fas fa-times"></i> Rejected
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Hand Requests</h3>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($result) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Hand Type</th>
                                <th>Amount</th>
                                <th>Requested</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 35px; height: 35px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                            <?php echo strtoupper(substr($row['first_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['surname']); ?></strong>
                                            <div style="font-size: 11px; color: #666;">
                                                <?php echo htmlspecialchars($row['member_number']); ?> | <?php echo htmlspecialchars($row['phone']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($row['hand_type_name']); ?></td>
                                <td><strong><?php echo number_format($row['amount'], 0, '.', ','); ?> FCFA</strong></td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($row['created_at'])); ?>
                                    <div style="font-size: 11px; color: #999;"><?php echo date('H:i', strtotime($row['created_at'])); ?></div>
                                </td>
                                <td>
                                    <?php if ($row['status'] == 'pending'): ?>
                                    <span class="badge badge-warning"><i class="fas fa-clock"></i> Pending</span>
                                    <?php elseif ($row['status'] == 'approved'): ?>
                                    <span class="badge badge-success"><i class="fas fa-check"></i> Approved</span>
                                    <?php else: ?>
                                    <span class="badge badge-danger"><i class="fas fa-times"></i> Rejected</span>
                                    <?php endif; ?>
                                    <?php if ($row['status'] == 'approved' && $row['hand_number']): ?>
                                    <div style="font-size: 11px; color: #28a745; margin-top: 5px;">
                                        Hand: <?php echo $row['hand_number']; ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($row['status'] == 'rejected' && !empty($row['rejection_reason'])): ?>
                                    <div style="font-size: 11px; color: #dc3545; margin-top: 5px;">
                                        Reason: <?php echo htmlspecialchars(substr($row['rejection_reason'], 0, 30)); ?>...
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] == 'pending'): ?>
                                    <button class="btn btn-sm btn-success" onclick="openApproveModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['first_name'] . ' ' . $row['surname'])); ?>')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="openRejectModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['first_name'] . ' ' . $row['surname'])); ?>')">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                    <?php else: ?>
                                    <span style="color: #999; font-size: 12px;">
                                        By <?php echo htmlspecialchars($row['reviewed_by_name'] ?? 'System'); ?>
                                    </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list fa-4x" style="color: #ddd;"></i>
                        <h3>No Requests Found</h3>
                        <p>There are no hand requests matching your filter.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Approve Modal -->
    <div class="modal" id="approveModal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3><i class="fas fa-check-circle" style="color: #28a745;"></i> Approve Hand Request</h3>
                <button type="button" class="modal-close" onclick="closeModal('approveModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <p style="margin-bottom: 20px;">
                        You are about to approve the hand request for <strong id="approveMemberName"></strong>.
                        The hand will be created with the following details:
                    </p>
                    
                    <input type="hidden" name="request_id" id="approveRequestId">
                    
                    <div class="form-group">
                        <label>Hand Status *</label>
                        <select name="hand_status_id" required>
                            <option value="">-- Select Status --</option>
                            <?php mysqli_data_seek($hand_statuses, 0); ?>
                            <?php while ($status = mysqli_fetch_assoc($hand_statuses)): ?>
                            <option value="<?php echo $status['id']; ?>"><?php echo htmlspecialchars($status['hand_status_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div style="background: #d4edda; padding: 15px; border-radius: 8px; margin-top: 15px;">
                        <i class="fas fa-info-circle" style="color: #155724;"></i>
                        <span style="color: #155724;">The member will be automatically notified of this approval.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('approveModal')">Cancel</button>
                    <button type="submit" name="approve" class="btn btn-success"><i class="fas fa-check"></i> Approve & Create Hand</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Reject Modal -->
    <div class="modal" id="rejectModal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3><i class="fas fa-times-circle" style="color: #dc3545;"></i> Reject Hand Request</h3>
                <button type="button" class="modal-close" onclick="closeModal('rejectModal')">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <p style="margin-bottom: 15px;">
                        You are about to reject the hand request for <strong id="rejectMemberName"></strong>.
                    </p>
                    
                    <input type="hidden" name="request_id" id="rejectRequestId">
                    
                    <div class="form-group">
                        <label>Rejection Reason *</label>
                        <textarea name="rejection_reason" placeholder="Please provide a reason for rejection..." required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; min-height: 100px;"></textarea>
                    </div>
                    
                    <div style="background: #f8d7da; padding: 15px; border-radius: 8px; margin-top: 15px;">
                        <i class="fas fa-info-circle" style="color: #721c24;"></i>
                        <span style="color: #721c24;">The member will be automatically notified with this reason.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('rejectModal')">Cancel</button>
                    <button type="submit" name="reject" class="btn btn-danger"><i class="fas fa-times"></i> Reject Request</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="../js/script.js"></script>
    <script>
        function openApproveModal(requestId, memberName) {
            document.getElementById('approveRequestId').value = requestId;
            document.getElementById('approveMemberName').textContent = memberName;
            document.getElementById('approveModal').classList.add('show');
        }
        
        function openRejectModal(requestId, memberName) {
            document.getElementById('rejectRequestId').value = requestId;
            document.getElementById('rejectMemberName').textContent = memberName;
            document.getElementById('rejectModal').classList.add('show');
        }
        
        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
    </script>
</body>
</html>
