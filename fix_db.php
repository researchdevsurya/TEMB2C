<?php
require 'db.php';

try {
    // Attempt to drop the unique index that prevents concurrent bookings
    $pdo->exec("ALTER TABLE student_bookings DROP INDEX booked_date");
    
    echo '<div style="font-family: sans-serif; text-align: center; padding: 50px;">';
    echo '<h1 style="color: green;">Success! Database Fixed.</h1>';
    echo '<p>The restrictive constraint has been removed. You can now make concurrent bookings.</p>';
    echo '<a href="index.php">Go to Home</a>';
    echo '</div>';

} catch (PDOException $e) {
    echo '<div style="font-family: sans-serif; text-align: center; padding: 50px;">';
    
    if (strpos($e->getMessage(), "check that column/key exists") !== false) {
        echo '<h1 style="color: orange;">Already Fixed!</h1>';
        echo '<p>The constraint was already removed previously.</p>';
    } else {
        echo '<h1 style="color: red;">Error</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    
    echo '<a href="index.php">Go to Home</a>';
    echo '</div>';
}
?>
