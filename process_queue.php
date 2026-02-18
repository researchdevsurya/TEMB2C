<?php
/**
 * Worker Script: Process Mail Queue
 * Run this via cron/task scheduler: php process_queue.php
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Prevent script from timing out
set_time_limit(0);

// For Shared Hosting/Cron: Run for max 50 seconds to avoid overlapping cron jobs
$max_execution_time = 50; 
$start_time = time();

echo "Starting Mail Queue Worker...\n";

while (true) {
    // Check if we exceeded time limit (for shared hosting cron usage)
    if ((time() - $start_time) > $max_execution_time) {
        echo "Max execution time reached. Exiting for next cron cycle.\n";
        break;
    }

    try {
        // Fetch pending emails (limit 10 per batch)
        $stmt = $pdo->prepare("SELECT * FROM mail_queue WHERE status = 'pending' AND attempts < 3 ORDER BY created_at ASC LIMIT 10");
        $stmt->execute();
        $emails = $stmt->fetchAll();

        if (count($emails) === 0) {
            // No emails, sleep for 5 seconds
            sleep(5);
            continue;
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->isHTML(true);

        foreach ($emails as $row) {
            try {
                echo "Processing email ID: {$row['id']} to {$row['to_email']}... ";
                
                $mail->clearAddresses();
                $mail->addAddress($row['to_email'], $row['to_name']);
                $mail->Subject = $row['subject'];
                $mail->Body    = $row['body'];
                $mail->AltBody = $row['alt_body'];

                $mail->send();

                // Mark as sent
                $update = $pdo->prepare("UPDATE mail_queue SET status = 'sent', sent_at = NOW() WHERE id = ?");
                $update->execute([$row['id']]);
                echo "SENT\n";

            } catch (Exception $e) {
                echo "FAILED: " . $mail->ErrorInfo . "\n";
                // Increment attempts, mark failed if > 3
                $update = $pdo->prepare("UPDATE mail_queue SET attempts = attempts + 1, last_attempt = NOW(), status = IF(attempts >= 3, 'failed', 'pending') WHERE id = ?");
                $update->execute([$row['id']]);
            }
        }

    } catch (Exception $e) {
        echo "Worker Error: " . $e->getMessage() . "\n";
        sleep(10);
    }
}
?>
