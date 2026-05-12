<?php
// Notification handler for AJAX requests
require_once '../config/constants.php';
require_once '../config/database.php';

$conn = getDbConnection();
$response = ['success' => false, 'data' => []];

// Get admin notifications
function getAdminNotifications($conn) {
    $notifications = [];
    
    // Get pending hand requests
    $sql = "SELECT 'hand_request' as type, hr.id, hr.created_at,
            CONCAT(m.first_name, ' ', m.surname) as member_name,
            ht.hand_type_name, hr.amount,
            'New hand request' as title
            FROM hand_requests hr
            JOIN members m ON hr.member_id = m.id
            JOIN hand_types ht ON hr.hand_type_id = ht.id
            WHERE hr.status = 'pending'
            ORDER BY hr.created_at DESC";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }
    
    // Get recent chat messages
    $sql = "SELECT 'chat_message' as type, gm.id, gm.created_at,
            CONCAT(m.first_name, ' ', m.surname) as member_name,
            gm.message,
            'New chat message' as title
            FROM group_messages gm
            JOIN members m ON gm.member_id = m.id
            WHERE gm.is_admin_message = 0
            ORDER BY gm.created_at DESC
            LIMIT 10";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }
    
    // Sort by date
    usort($notifications, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    return array_slice($notifications, 0, 20);
}

// Get member notifications
function getMemberNotifications($conn, $member_id) {
    $notifications = [];
    
    // Get member's own notifications
    $sql = "SELECT * FROM member_notifications 
            WHERE member_id = '$member_id' 
            ORDER BY created_at DESC 
            LIMIT 20";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }
    
    // Get recent chat messages from group
    $sql = "SELECT 'group_chat' as type, gm.id, gm.created_at, gm.message,
            'Group Chat' as title, gm.is_admin_message,
            CASE WHEN gm.is_admin_message = 1 THEN 'Admin' 
                 ELSE CONCAT(m.first_name, ' ', m.surname) END as sender_name
            FROM group_messages gm
            LEFT JOIN members m ON gm.member_id = m.id
            ORDER BY gm.created_at DESC
            LIMIT 10";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }
    
    // Sort by date
    usort($notifications, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    return array_slice($notifications, 0, 20);
}

// Handle requests
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['action'])) {
        if ($_GET['action'] == 'get_admin') {
            $response['success'] = true;
            $response['data'] = getAdminNotifications($conn);
        } elseif ($_GET['action'] == 'get_member' && isset($_GET['member_id'])) {
            $response['success'] = true;
            $response['data'] = getMemberNotifications($conn, (int)$_GET['member_id']);
        } elseif ($_GET['action'] == 'count_admin') {
            // Count pending hand requests
            $sql = "SELECT COUNT(*) as count FROM hand_requests WHERE status = 'pending'";
            $pending_hands = mysqli_fetch_assoc(mysqli_query($conn, $sql))['count'];
            
            // Count unread chat messages (last 24 hours)
            $sql = "SELECT COUNT(*) as count FROM group_messages WHERE is_admin_message = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            $new_chats = mysqli_fetch_assoc(mysqli_query($conn, $sql))['count'];
            
            $response['success'] = true;
            $response['count'] = $pending_hands + $new_chats;
        } elseif ($_GET['action'] == 'count_member' && isset($_GET['member_id'])) {
            $member_id = (int)$_GET['member_id'];
            $sql = "SELECT COUNT(*) as count FROM member_notifications WHERE member_id = '$member_id' AND is_read = 0";
            $unread = mysqli_fetch_assoc(mysqli_query($conn, $sql))['count'];
            
            $response['success'] = true;
            $response['count'] = $unread;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['action'])) {
        if ($data['action'] == 'mark_read' && isset($data['id']) && isset($data['type'])) {
            if ($data['type'] == 'member_notification') {
                $id = (int)$data['id'];
                $sql = "UPDATE member_notifications SET is_read = 1 WHERE id = $id";
                mysqli_query($conn, $sql);
                $response['success'] = true;
            }
        }
    }
}

closeDbConnection($conn);
echo json_encode($response);
