<?php
require_once 'config/database.php';
$conn = getDbConnection();

// Create payout_cycles table
$sql1 = "CREATE TABLE IF NOT EXISTS payout_cycles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cycle_number VARCHAR(20) UNIQUE NOT NULL,
    cycle_name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    status ENUM('draft', 'active', 'completed', 'cancelled') DEFAULT 'draft',
    total_hands INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql1) or die('Error creating payout_cycles: ' . mysqli_error($conn));

// Create cycle_hands table
$sql2 = "CREATE TABLE IF NOT EXISTS cycle_hands (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cycle_id INT NOT NULL,
    hand_id INT NOT NULL,
    position_order INT NOT NULL,
    FOREIGN KEY (cycle_id) REFERENCES payout_cycles(id) ON DELETE CASCADE,
    FOREIGN KEY (hand_id) REFERENCES hands(id) ON DELETE CASCADE
)";
mysqli_query($conn, $sql2) or die('Error creating cycle_hands: ' . mysqli_error($conn));

// Create payment_deadlines table
$sql3 = "CREATE TABLE IF NOT EXISTS payment_deadlines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cycle_id INT NOT NULL,
    hand_id INT NOT NULL,
    deadline_date DATE NOT NULL,
    expected_amount DECIMAL(12,2) DEFAULT 0.00,
    FOREIGN KEY (cycle_id) REFERENCES payout_cycles(id) ON DELETE CASCADE,
    FOREIGN KEY (hand_id) REFERENCES hands(id) ON DELETE CASCADE
)";
mysqli_query($conn, $sql3) or die('Error creating payment_deadlines: ' . mysqli_error($conn));

echo 'Tables created successfully!';
closeDbConnection($conn);
?>
