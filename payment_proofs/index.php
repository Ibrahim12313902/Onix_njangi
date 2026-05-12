<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';

$conn = getDbConnection();

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action == 'approve' || $action == 'reject') {
        $status = ($action == 'approve') ? 'approved' : 'rejected';
        $notes = mysqli_real_escape_string($conn, $_GET['notes'] ?? '');
        
        mysqli_query($conn, "UPDATE payment_proofs SET 
            status = '$status', 
            admin_notes = '$notes', 
            reviewed_by = '" . $_SESSION['admin_id'] . "',
            reviewed_at = NOW() 
            WHERE id = '$id'");
        
        $sql = "SELECT * FROM payment_proofs WHERE id = '$id'";
        $result = mysqli_query($conn, $sql);
        $proof = mysqli_fetch_assoc($result);
        
        if ($action == 'approve' && $proof) {
            mysqli_query($conn, "INSERT INTO contributions (hand_id, amount, contribution_date, payment_method, reference_number, notes)
                VALUES ('" . $proof['hand_id'] . "', '" . $proof['amount'] . "', '" . $proof['payment_date'] . "', '" . ($proof['payment_method'] ?? 'Proof Upload') . "', '" . ($proof['reference_number'] ?? 'PROOF-' . $id) . "', 'Approved via proof upload')");
            
            $msg = "Your payment proof has been approved! Amount: " . formatCurrency($proof['amount']) . " has been added to your contributions.";
            mysqli_query($conn, "INSERT INTO member_notifications (member_id, title, message, type) VALUES ('" . $proof['member_id'] . "', 'Payment Approved', '$msg', 'success')");
        } elseif ($action == 'reject' && $proof) {
            $msg = "Your payment proof has been rejected. Reason: " . ($notes ? $notes : 'No reason provided');
            mysqli_query($conn, "INSERT INTO member_notifications (member_id, title, message, type) VALUES ('" . $proof['member_id'] . "', 'Payment Rejected', '$msg', 'danger')");
        }
        
        header('Location: index.php?success=Proof ' . ($action == 'approve' ? 'approved' : 'rejected') . ' successfully!');
        exit();
    }
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where = "1=1";
if ($status_filter) {
    $where .= " AND pp.status = '$status_filter'";
}

$sql = "SELECT pp.*, m.first_name, m.surname, m.phone, m.member_number,
        h.hand_number, ht.hand_type_name
        FROM payment_proofs pp
        JOIN members m ON pp.member_id = m.id
        JOIN hands h ON pp.hand_id = h.id
        JOIN hand_types ht ON h.hand_type_id = ht.id
        WHERE $where
        ORDER BY pp.created_at DESC";
$proofs_result = mysqli_query($conn, $sql);

$sql = "SELECT status, COUNT(*) as count FROM payment_proofs GROUP BY status";
$result = mysqli_query($conn, $sql);
$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
while ($row = mysqli_fetch_assoc($result)) {
    $counts[$row['status']] = $row['count'];
}

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Proofs - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .proof-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .proof-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .proof-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .proof-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            cursor: pointer;
            background: #f0f0f0;
        }
        .proof-image-placeholder {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
        }
        .proof-info {
            padding: 15px;
        }
        .proof-member {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .proof-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        .proof-name {
            font-weight: 600;
            color: #333;
        }
        .proof-hand {
            font-size: 12px;
            color: #666;
        }
        .proof-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        .proof-detail {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
        }
        .proof-detail .label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
        }
        .proof-detail .value {
            font-weight: 600;
            color: #333;
        }
        .proof-detail .value.success { color: #28a745; }
        .proof-detail .value.danger { color: #dc3545; }
        .proof-payment-info {
            background: #e8f4fd;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .proof-payment-info .method {
            font-weight: 600;
            color: #667eea;
            margin-bottom: 5px;
        }
        .proof-payment-info .ref {
            font-size: 13px;
            color: #666;
        }
        .proof-actions {
            display: flex;
            gap: 10px;
        }
        .proof-actions .btn {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            border: none;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-approve { background: #28a745; color: white; }
        .btn-approve:hover { background: #218a39; }
        .btn-reject { background: #dc3545; color: white; }
        .btn-reject:hover { background: #c82333; }
        .btn-view { background: #667eea; color: white; }
        .btn-view:hover { background: #5a6fd6; }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .filter-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            padding: 0 20px;
        }
        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #e9ecef;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        .filter-btn:hover { border-color: #667eea; }
        .filter-btn.active { background: #667eea; color: white; border-color: #667eea; }
        .empty-state {
            text-align: center;
            padding: 60px;
            color: #999;
        }
        .empty-state i { font-size: 64px; margin-bottom: 20px; }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal.show { display: flex; }
        .modal-content {
            background: white;
            border-radius: 15px;
            max-width: 90%;
            max-height: 90vh;
            overflow: auto;
        }
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 { margin: 0; }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        .modal-body { padding: 20px; }
        .modal-body img {
            max-width: 100%;
            max-height: 70vh;
            border-radius: 8px;
        }
        .reject-form {
            margin-top: 20px;
        }
        .reject-form textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            min-height: 80px;
            margin-bottom: 10px;
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
                <h1><i class="fas fa-file-invoice"></i> Payment Proofs</h1>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success" style="padding: 15px; background: #d4edda; color: #155724; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            
            <div class="filter-bar">
                <button class="filter-btn <?php echo !$status_filter ? 'active' : ''; ?>" onclick="location.href='index.php'">All (<?php echo array_sum($counts); ?>)</button>
                <button class="filter-btn <?php echo $status_filter == 'pending' ? 'active' : ''; ?>" onclick="location.href='index.php?status=pending'">
                    <i class="fas fa-clock"></i> Pending (<?php echo $counts['pending']; ?>)
                </button>
                <button class="filter-btn <?php echo $status_filter == 'approved' ? 'active' : ''; ?>" onclick="location.href='index.php?status=approved'">
                    <i class="fas fa-check"></i> Approved (<?php echo $counts['approved']; ?>)
                </button>
                <button class="filter-btn <?php echo $status_filter == 'rejected' ? 'active' : ''; ?>" onclick="location.href='index.php?status=rejected'">
                    <i class="fas fa-times"></i> Rejected (<?php echo $counts['rejected']; ?>)
                </button>
            </div>
            
            <?php if (mysqli_num_rows($proofs_result) > 0): ?>
                <div class="proof-grid">
                    <?php while ($proof = mysqli_fetch_assoc($proofs_result)): ?>
                    <div class="proof-card">
                        <?php if ($proof['proof_image'] && file_exists('../' . $proof['proof_image'])): ?>
                        <img src="../<?php echo htmlspecialchars($proof['proof_image']); ?>" class="proof-image" onclick="openModal('<?php echo htmlspecialchars($proof['proof_image']); ?>', '<?php echo $proof['id']; ?>')">
                        <?php else: ?>
                        <div class="proof-image-placeholder">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <?php endif; ?>
                        
                        <div class="proof-info">
                            <div class="proof-member">
                                <div class="proof-avatar">
                                    <?php echo strtoupper(substr($proof['first_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="proof-name"><?php echo htmlspecialchars($proof['first_name'] . ' ' . $proof['surname']); ?></div>
                                    <div class="proof-hand"><?php echo htmlspecialchars($proof['member_number']); ?></div>
                                </div>
                            </div>
                            
                            <div class="proof-details">
                                <div class="proof-detail">
                                    <div class="label">Hand</div>
                                    <div class="value"><?php echo htmlspecialchars($proof['hand_number']); ?></div>
                                </div>
                                <div class="proof-detail">
                                    <div class="label">Amount</div>
                                    <div class="value success"><?php echo formatCurrency($proof['amount']); ?></div>
                                </div>
                                <div class="proof-detail">
                                    <div class="label">Payment Date</div>
                                    <div class="value"><?php echo formatDate($proof['payment_date']); ?></div>
                                </div>
                                <div class="proof-detail">
                                    <div class="label">Submitted</div>
                                    <div class="value"><?php echo date('d/m/Y H:i', strtotime($proof['created_at'])); ?></div>
                                </div>
                            </div>
                            
                            <?php if ($proof['payment_method'] || $proof['reference_number']): ?>
                            <div class="proof-payment-info">
                                <div class="method">
                                    <i class="fas fa-credit-card"></i> <?php echo htmlspecialchars($proof['payment_method'] ?? 'N/A'); ?>
                                </div>
                                <?php if ($proof['reference_number']): ?>
                                <div class="ref">
                                    <i class="fas fa-hashtag"></i> Ref: <?php echo htmlspecialchars($proof['reference_number']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <div style="text-align: center; margin-bottom: 15px;">
                                <span class="status-badge status-<?php echo $proof['status']; ?>">
                                    <?php if ($proof['status'] == 'pending'): ?><i class="fas fa-clock"></i> Pending
                                    <?php elseif ($proof['status'] == 'approved'): ?><i class="fas fa-check"></i> Approved
                                    <?php else: ?><i class="fas fa-times"></i> Rejected
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <?php if ($proof['status'] == 'pending'): ?>
                            <div class="proof-actions">
                                <button class="btn btn-view" onclick="openModal('<?php echo htmlspecialchars($proof['proof_image'] ?? ''); ?>', '<?php echo $proof['id']; ?>')">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <a href="index.php?action=approve&id=<?php echo $proof['id']; ?>" class="btn btn-approve" onclick="return confirm('Approve this payment of <?php echo formatCurrency($proof['amount']); ?>?')">
                                    <i class="fas fa-check"></i> Approve
                                </a>
                                <button class="btn btn-reject" onclick="showRejectForm(<?php echo $proof['id']; ?>)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            
                            <div class="reject-form" id="rejectForm_<?php echo $proof['id']; ?>" style="display: none; margin-top: 10px;">
                                <textarea placeholder="Enter rejection reason..." id="notes_<?php echo $proof['id']; ?>"></textarea>
                                <button class="btn btn-reject" style="width: 100%;" onclick="rejectProof(<?php echo $proof['id']; ?>)">
                                    <i class="fas fa-paper-plane"></i> Submit Rejection
                                </button>
                            </div>
                            <?php else: ?>
                            <?php if ($proof['admin_notes']): ?>
                            <div style="background: #f8f9fa; padding: 10px; border-radius: 8px; font-size: 13px; color: #666;">
                                <strong>Admin Note:</strong> <?php echo htmlspecialchars($proof['admin_notes']); ?>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-file-invoice" style="color: #ddd;"></i>
                    <h2>No Payment Proofs Found</h2>
                    <p>There are no payment proofs matching your filter.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <!-- Image Modal -->
    <div class="modal" id="imageModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-image"></i> Payment Proof</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <img id="modalImage" src="" alt="Payment Proof">
            </div>
        </div>
    </div>
    
    <script>
        function openModal(imagePath, proofId) {
            var modal = document.getElementById('imageModal');
            var img = document.getElementById('modalImage');
            
            if (imagePath) {
                img.src = '../' + imagePath;
                img.style.display = 'block';
            } else {
                img.style.display = 'none';
                document.getElementById('modalBody').innerHTML += '<p style="text-align: center; padding: 40px; color: #666;">No image uploaded for this payment.</p>';
            }
            
            modal.classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('imageModal').classList.remove('show');
        }
        
        window.onclick = function(event) {
            if (event.target.id == 'imageModal') {
                closeModal();
            }
        }
        
        function showRejectForm(id) {
            var form = document.getElementById('rejectForm_' + id);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
        
        function rejectProof(id) {
            var notes = document.getElementById('notes_' + id).value;
            if (!notes) {
                alert('Please enter a rejection reason');
                return;
            }
            location.href = 'index.php?action=reject&id=' + id + '&notes=' + encodeURIComponent(notes);
        }
    </script>
</body>
</html>
