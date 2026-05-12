<?php
require_once '../config/constants.php';
requireLogin();
require_once '../config/database.php';
require_once '../includes/notification_setup.php';

$conn = getDbConnection();
$error = '';
$success = '';

// Handle admin message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $message = mysqli_real_escape_string($conn, trim($_POST['message']));
    
    if (!empty($message)) {
        $sql = "INSERT INTO group_messages (member_id, message, is_admin_message) VALUES (0, '$message', 1)";
        if (mysqli_query($conn, $sql)) {
            $success = 'Announcement posted successfully!';
        } else {
            $error = 'Error posting message: ' . mysqli_error($conn);
        }
    }
}

// Get messages (last 100)
$sql = "SELECT gm.*, 
        CASE 
            WHEN gm.is_admin_message = 1 THEN 'Admin'
            ELSE CONCAT(m.first_name, ' ', m.surname)
        END as sender_name,
        CASE 
            WHEN gm.is_admin_message = 1 THEN 'admin'
            ELSE m.member_number
        END as sender_identifier
        FROM group_messages gm
        LEFT JOIN members m ON gm.member_id = m.id AND gm.is_admin_message = 0
        ORDER BY gm.created_at DESC
        LIMIT 100";
$messages_result = mysqli_query($conn, $sql);
$messages = mysqli_fetch_all($messages_result, MYSQLI_ASSOC);
$messages = array_reverse($messages);

// Get stats
$sql = "SELECT COUNT(*) as count FROM group_messages";
$total_messages = mysqli_fetch_assoc(mysqli_query($conn, $sql))['count'];

$sql = "SELECT COUNT(DISTINCT member_id) as count FROM group_messages WHERE is_admin_message = 0";
$active_members = mysqli_fetch_assoc(mysqli_query($conn, $sql))['count'];

$sql = "SELECT COUNT(*) as count FROM group_messages WHERE is_admin_message = 1";
$admin_messages = mysqli_fetch_assoc(mysqli_query($conn, $sql))['count'];

closeDbConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Chat - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .chat-container {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 20px;
            height: calc(100vh - 220px);
            min-height: 500px;
        }
        .chat-main {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .chat-header {
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .chat-header h3 { margin: 0; }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
        }
        .message {
            margin-bottom: 15px;
            display: flex;
            gap: 12px;
            animation: fadeIn 0.3s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .message.admin { flex-direction: row-reverse; }
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            flex-shrink: 0;
        }
        .message.admin .message-avatar { background: #28a745; color: white; }
        .message.member .message-avatar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .message-content { max-width: 70%; }
        .message-bubble {
            padding: 12px 16px;
            border-radius: 15px;
            line-height: 1.5;
        }
        .message.admin .message-bubble {
            background: #28a745;
            color: white;
            border-bottom-right-radius: 3px;
        }
        .message.member .message-bubble {
            background: white;
            color: #333;
            border-bottom-left-radius: 3px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .message-sender {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #666;
        }
        .message.admin .message-sender { text-align: right; }
        .message-time {
            font-size: 11px;
            color: #999;
            margin-top: 5px;
        }
        .message.admin .message-time { text-align: right; }
        .chat-input {
            padding: 15px;
            background: white;
            border-top: 1px solid #eee;
        }
        .chat-input form {
            display: flex;
            gap: 10px;
        }
        .chat-input input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-size: 14px;
        }
        .chat-input input:focus {
            outline: none;
            border-color: #667eea;
        }
        .chat-input button {
            padding: 12px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
        }
        .chat-input button:hover { opacity: 0.9; }
        .chat-sidebar {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .stat-box {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
        }
        .stat-box .icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 10px;
        }
        .stat-box .icon.blue { background: #e8f4fd; color: #0c5460; }
        .stat-box .icon.green { background: #d4edda; color: #155724; }
        .stat-box .icon.purple { background: #f3e5f5; color: #7b1fa2; }
        .stat-box .value { font-size: 28px; font-weight: 600; color: #333; }
        .stat-box .label { font-size: 12px; color: #666; }
        .members-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            flex: 1;
            overflow: hidden;
        }
        .members-list-header {
            padding: 15px;
            background: #f8f9fa;
            font-weight: 600;
            border-bottom: 1px solid #eee;
        }
        .members-list-body {
            padding: 10px;
            max-height: 300px;
            overflow-y: auto;
        }
        .member-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border-radius: 8px;
        }
        .member-item:hover { background: #f8f9fa; }
        .member-item .avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        .member-item .name { font-weight: 600; font-size: 14px; }
        .empty-chat {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .empty-chat i { font-size: 48px; color: #ddd; margin-bottom: 15px; }
        @media (max-width: 992px) {
            .chat-container { grid-template-columns: 1fr; }
            .chat-sidebar { display: none; }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-comments"></i> Group Chat Management</h1>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="chat-container">
                <div class="chat-main">
                    <div class="chat-header">
                        <i class="fas fa-comments"></i>
                        <h3>Group Chat</h3>
                    </div>
                    
                    <div class="chat-messages" id="chatMessages">
                        <?php if (empty($messages)): ?>
                        <div class="empty-chat">
                            <i class="fas fa-comment-dots"></i>
                            <h3>No Messages Yet</h3>
                            <p>Start the conversation!</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                        <div class="message <?php echo $msg['is_admin_message'] ? 'admin' : 'member'; ?>">
                            <div class="message-avatar">
                                <?php echo strtoupper(substr($msg['sender_name'], 0, 1)); ?>
                            </div>
                            <div class="message-content">
                                <div class="message-sender">
                                    <?php echo htmlspecialchars($msg['sender_name']); ?>
                                    <span style="color: #999; font-weight: normal;">(<?php echo htmlspecialchars($msg['sender_identifier']); ?>)</span>
                                </div>
                                <div class="message-bubble">
                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                </div>
                                <div class="message-time">
                                    <?php echo date('d M Y, H:i', strtotime($msg['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="chat-input">
                        <form method="POST">
                            <input type="text" name="message" placeholder="Post an announcement..." required>
                            <button type="submit" name="send_message">
                                <i class="fas fa-paper-plane"></i> Send
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="chat-sidebar">
                    <div class="stat-box">
                        <div class="icon blue"><i class="fas fa-comments"></i></div>
                        <div class="value"><?php echo $total_messages; ?></div>
                        <div class="label">Total Messages</div>
                    </div>
                    <div class="stat-box">
                        <div class="icon green"><i class="fas fa-users"></i></div>
                        <div class="value"><?php echo $active_members; ?></div>
                        <div class="label">Active Members</div>
                    </div>
                    <div class="stat-box">
                        <div class="icon purple"><i class="fas fa-user-shield"></i></div>
                        <div class="value"><?php echo $admin_messages; ?></div>
                        <div class="label">Announcements</div>
                    </div>
                    
                    <div class="members-list">
                        <div class="members-list-header">
                            <i class="fas fa-users"></i> Recent Chat Members
                        </div>
                        <div class="members-list-body">
                            <?php
                            $unique_members = [];
                            foreach ($messages as $msg) {
                                if (!$msg['is_admin_message'] && !isset($unique_members[$msg['member_id']])) {
                                    $unique_members[$msg['member_id']] = $msg['sender_name'];
                                }
                            }
                            $count = 0;
                            foreach ($unique_members as $id => $name):
                                if ($count >= 10) break;
                                $count++;
                            ?>
                            <div class="member-item">
                                <div class="avatar"><?php echo strtoupper(substr($name, 0, 1)); ?></div>
                                <div class="name"><?php echo htmlspecialchars($name); ?></div>
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($unique_members)): ?>
                            <p style="color: #999; text-align: center; padding: 20px;">No members have chatted yet</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Scroll to bottom of chat
        var chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>
</body>
</html>
