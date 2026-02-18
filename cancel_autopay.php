<?php
/**
 * Cancel Auto-Pay
 * 
 * Cancels the Razorpay token/mandate and updates DB.
 */
session_start();
require 'db.php';
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';

use Razorpay\Api\Api;

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

$bookingId = $_GET['booking_id'] ?? null;
if (!$bookingId) die("Invalid request");

/* Fetch booking */
$stmt = $pdo->prepare("SELECT * FROM student_bookings WHERE id=? AND student_id=?");
$stmt->execute([$bookingId, $_SESSION['student_id']]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) die("Booking not found");

$api = new Api(RZP_KEY_ID, RZP_KEY_SECRET);

try {
    /* If there's a subscription, cancel it */
    if ($booking['razorpay_subscription_id']) {
        $api->subscription->fetch($booking['razorpay_subscription_id'])->cancel([
            'cancel_at_cycle_end' => 0 // Cancel immediately
        ]);
    }

    /* If there's a token, delete it */
    if ($booking['razorpay_token_id'] && $booking['razorpay_customer_id']) {
        try {
            $api->customer->fetch($booking['razorpay_customer_id'])
                ->tokens->delete($booking['razorpay_token_id']);
        } catch (Exception $e) {
            // Token may already be expired/deleted, just log
            error_log("Token delete warning: " . $e->getMessage());
        }
    }
} catch (Exception $e) {
    error_log("Razorpay cancel error: " . $e->getMessage());
    // Continue even if API call fails – still update DB
}

/* Update DB */
$pdo->prepare("
    UPDATE student_bookings 
    SET subscription_status = 'cancelled', 
        mandate_status = 'CANCELLED'
    WHERE id = ?
")->execute([$bookingId]);

/* Mark remaining pending payments as manual */
$pdo->prepare("
    UPDATE payments 
    SET auto_pay = 'NO'
    WHERE booking_id = ? AND payment_status = 'PENDING'
")->execute([$bookingId]);

/* Log */
$pdo->prepare("
    INSERT INTO payment_logs 
    (booking_id, student_id, payment_type, event_type, raw_payload, status)
    VALUES (?,?,?,?,?,?)
")->execute([
    $bookingId,
    $_SESSION['student_id'],
    'TOKEN',
    'mandate.cancelled',
    json_encode(['cancelled_by' => 'student', 'booking_id' => $bookingId]),
    'SUCCESS'
]);

$_SESSION['payment_success'] = 'Auto-pay has been cancelled. Please pay manually before each session.';
header("Location: payments.php");
exit;
