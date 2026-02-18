<?php
require 'db.php';

try {
    // 1. Mail Queue Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mail_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            to_email VARCHAR(255) NOT NULL,
            to_name VARCHAR(255),
            subject VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            alt_body TEXT,
            status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
            attempts INT DEFAULT 0,
            last_attempt DATETIME NULL,
            sent_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (status)
        ) ENGINE=InnoDB;
    ");
    echo "Table 'mail_queue' created or already exists.<br>";

    // 2. Rate Limits / Access Logs Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            action_type VARCHAR(50) NOT NULL,
            attempt_count INT DEFAULT 1,
            last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP,
            blocked_until DATETIME NULL,
            INDEX (ip_address, action_type)
        ) ENGINE=InnoDB;
    ");
    echo "Table 'rate_limits' created or already exists.<br>";

    echo "Security setup completed successfully!";

} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
?>
