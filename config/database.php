<?php
// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'onix_njangi');

// Create connection
function getDbConnection() {
    $host = DB_HOST;
    $user = DB_USER;
    $pass = DB_PASS;
    $name = DB_NAME;

    // If on Render (environment variables set), use SSL connection
    if (getenv('DB_HOST')) {
        $conn = mysqli_init();
        mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
        mysqli_real_connect($conn, $host, $user, $pass, $name, NULL, NULL, MYSQLI_CLIENT_SSL);
    } else {
        // Local development
        $conn = mysqli_connect($host, $user, $pass, $name);
    }

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    mysqli_set_charset($conn, "utf8mb4");

    return $conn;
}

function closeDbConnection($conn) {
    mysqli_close($conn);
}

function checkAndCompleteCycles($conn) {
    $today = date('Y-m-d');

    $sql = "SELECT id, cycle_name FROM payout_cycles 
            WHERE status = 'active' AND end_date IS NOT NULL AND end_date < '$today'";
    $result = mysqli_query($conn, $sql);

    while ($cycle = mysqli_fetch_assoc($result)) {
        mysqli_query($conn, "UPDATE payout_cycles SET status = 'completed', 
                            payout_completed_at = NOW() WHERE id = '" . $cycle['id'] . "'");

        $members_sql = "SELECT DISTINCT m.id, m.first_name, m.surname, m.email 
                       FROM cycle_hands ch
                       JOIN hands h ON ch.hand_id = h.id
                       JOIN members m ON h.member_id = m.id
                       WHERE ch.cycle_id = '" . $cycle['id'] . "'";
        $members_result = mysqli_query($conn, $members_sql);

        while ($member = mysqli_fetch_assoc($members_result)) {
            $message = "The cycle '" . $cycle['cycle_name'] . "' has been completed.";
            mysqli_query($conn, "INSERT INTO member_notifications 
                                 (member_id, title, message, type, created_at) 
                                 VALUES ('" . $member['id'] . "', 'Cycle Completed', 
                                         '" . mysqli_real_escape_string($conn, $message) . "', 
                                         'cycle_completed', NOW())");
        }
    }
}

function markPayoutReceived($conn, $hand_id, $cycle_id) {
    $now = date('Y-m-d H:i:s');

    mysqli_query($conn, "UPDATE hands SET payout_status = 'paid', received_at = '$now' WHERE id = '$hand_id'");

    $sql = "SELECT h.*, m.id as member_id, m.first_name, m.surname, m.email, 
            pc.cycle_name, ch.position_order
            FROM hands h
            JOIN members m ON h.member_id = m.id
            JOIN payout_cycles pc ON h.payout_cycle_id = pc.id
            JOIN cycle_hands ch ON ch.hand_id = h.id AND ch.cycle_id = pc.id
            WHERE h.id = '$hand_id'";
    $result = mysqli_query($conn, $sql);
    $data = mysqli_fetch_assoc($result);

    $member_msg = "Congratulations! You have received your payout of " . formatCurrency($data['amount']) . 
                  " from cycle '" . $data['cycle_name'] . "'.";
    mysqli_query($conn, "INSERT INTO member_notifications 
                         (member_id, title, message, type, created_at) 
                         VALUES ('" . $data['member_id'] . "', 'Payout Received', 
                                 '" . mysqli_real_escape_string($conn, $member_msg) . "', 
                                 'payout_received', '$now')");

    $all_members_sql = "SELECT DISTINCT m.id 
                        FROM cycle_hands ch
                        JOIN hands h ON ch.hand_id = h.id
                        JOIN members m ON h.member_id = m.id
                        WHERE ch.cycle_id = '$cycle_id' AND m.id != '" . $data['member_id'] . "'";
    $all_members_result = mysqli_query($conn, $all_members_sql);

    while ($member = mysqli_fetch_assoc($all_members_result)) {
        $broadcast_msg = $data['first_name'] . " " . $data['surname'] . " (Position #" . 
                         $data['position_order'] . ") has received their payout of " . 
                         formatCurrency($data['amount']) . " from cycle '" . $data['cycle_name'] . "'.";
        mysqli_query($conn, "INSERT INTO member_notifications 
                             (member_id, title, message, type, created_at) 
                             VALUES ('" . $member['id'] . "', 'Payout Update', 
                                     '" . mysqli_real_escape_string($conn, $broadcast_msg) . "', 
                                     'payout_update', '$now')");
    }

    $chat_msg = $data['first_name'] . " " . $data['surname'] . " has received their payout!";
    mysqli_query($conn, "INSERT INTO group_messages (member_id, message, is_admin_message) 
                         VALUES (0, '" . mysqli_real_escape_string($conn, $chat_msg) . "', 1)");

    $check_sql = "SELECT COUNT(*) as total,
                  SUM(CASE WHEN h.received_at IS NOT NULL OR h.payout_status = 'paid' THEN 1 ELSE 0 END) as received
                  FROM cycle_hands ch
                  JOIN hands h ON ch.hand_id = h.id
                  WHERE ch.cycle_id = '$cycle_id'";
    $check_result = mysqli_query($conn, $check_sql);
    $check_data = mysqli_fetch_assoc($check_result);

    if ($check_data['total'] == $check_data['received']) {
        mysqli_query($conn, "UPDATE payout_cycles SET status = 'completed', payout_completed_at = '$now' WHERE id = '$cycle_id'");

        $members_sql = "SELECT DISTINCT m.id 
                       FROM cycle_hands ch
                       JOIN hands h ON ch.hand_id = h.id
                       JOIN members m ON h.member_id = m.id
                       WHERE ch.cycle_id = '$cycle_id'";
        $members_result = mysqli_query($conn, $members_sql);

        while ($member = mysqli_fetch_assoc($members_result)) {
            $cycle_msg = "The cycle '" . $data['cycle_name'] . "' has been fully completed! All members have received their payouts.";
            mysqli_query($conn, "INSERT INTO member_notifications 
                                 (member_id, title, message, type, created_at) 
                                 VALUES ('" . $member['id'] . "', 'Cycle Completed', 
                                         '" . mysqli_real_escape_string($conn, $cycle_msg) . "', 
                                         'cycle_completed', '$now')");
        }

        mysqli_query($conn, "INSERT INTO group_messages (member_id, message, is_admin_message) 
                             VALUES (0, 'The cycle \"" . $data['cycle_name'] . "\" has been fully completed! All payouts have been distributed.', 1)");
    }

    return $data;
}
?>
