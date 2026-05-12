<?php
require_once 'config/database.php';
require_once 'config/constants.php';

echo "<h2>Database Connection Test</h2>";

$conn = getDbConnection();

if ($conn) {
    echo "<p style='color: green;'>✓ Database connected successfully!</p>";
    
    // Check if saved_records table exists
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'saved_records'");
    if (mysqli_num_rows($result) > 0) {
        echo "<p style='color: green;'>✓ saved_records table exists</p>";
        
        // Check records
        $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM saved_records");
        $row = mysqli_fetch_assoc($result);
        echo "<p>Total records in saved_records: " . $row['count'] . "</p>";
        
    } else {
        echo "<p style='color: red;'>✗ saved_records table does not exist!</p>";
    }
    
    closeDbConnection($conn);
} else {
    echo "<p style='color: red;'>✗ Database connection failed!</p>";
}

echo "<br><a href='saved_records/current_month.php'>Go to Current Month</a>";
?>