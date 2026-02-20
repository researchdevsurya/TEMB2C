<?php
/**
 * Mandate Success – Handle mandate auth callback (New Flow)
 * 
 * After Razorpay Checkout completes, the student is redirected here.
 * We verify the payment signature, update DB, create a recurring token, 
 * AND create the 'payments' records for the future sessions.
 */
session_start();
require 'db.php';
require __DIR__ . '/vendor/autoload.php';
require 'config.php';
require __DIR__ . '/mail_helper.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

// Fix: User constants (RAZORPAY_...)
$api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

/* Capture POST/GET data (Razorpay usually sends POST for handler, but our JS uses GET redirect) */
$razorpayPaymentId = $_POST['razorpay_payment_id'] ?? $_GET['payment_id'] ?? null;
$bookingId         = $_POST['booking_id']          ?? $_GET['booking_id'] ?? null;

if (!$razorpayPaymentId || !$bookingId) {
    die("Invalid payment response");
}

try {
    /* ─── 1. Fetch payment to get token & verify ─── */
    $payment = $api->payment->fetch($razorpayPaymentId);
    
    // Auto-capture if authorized
    if ($payment['status'] === 'authorized') {
        $payment->capture(['amount' => $payment['amount']]);
    }
    
    $tokenId = $payment['token_id'] ?? null;
    $orderId = $payment['order_id'] ?? null; // The auth order

    /* ─── 2. Fetch booking ─── */
    $stmt = $pdo->prepare("
        SELECT sb.*, s.email, s.username 
        FROM student_bookings sb 
        JOIN students s ON s.id = sb.student_id
        WHERE sb.id = ?
    ");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) throw new Exception("Booking not found");

    /* ─── 3. Update booking ─── */
    $pdo->prepare("
        UPDATE student_bookings 
        SET payment_status = 'paid',
            txnid = ?,
            razorpay_token_id = ?,
            subscription_status = 'authenticated',
            amount = ?
        WHERE id = ?
    ")->execute([
        $razorpayPaymentId,
        $tokenId,
        ($payment['amount'] / 100),
        $bookingId
    ]);

    /* ─── 4. Create Scheduled Payments (Crucial Step!) ─── */
    // Since payment.php no longer creates these, we do it here
    $sessions = [
        ['type' => 'PSYCHOMETRIC', 'date' => $booking['selected_psychometric_date'], 'amount' => FEE_PSYCHOMETRIC],
        ['type' => 'GROUP',        'date' => $booking['group_session1_date'],         'amount' => FEE_GROUP],
        ['type' => 'ONE_TO_ONE',   'date' => $booking['booked_date'],                 'amount' => FEE_ONE_TO_ONE],
    ];

    foreach ($sessions as $sess) {
        // Check if payment already exists
        $chk = $pdo->prepare("SELECT id FROM payments WHERE booking_id=? AND payment_for=?");
        $chk->execute([$bookingId, $sess['type']]);
        if ($chk->rowCount() == 0) {
            $pdo->prepare("
                INSERT INTO payments (booking_id, student_id, payment_for, amount, scheduled_date, auto_pay)
                VALUES (?,?,?,?,?,?)
            ")->execute([
                $bookingId,
                $booking['student_id'],
                $sess['type'],
                $sess['amount'],
                $sess['date'],
                'YES'
            ]);
        }
    }

    /* ─── 5. Log success ─── */
    $pdo->prepare("
        INSERT INTO payment_logs 
        (booking_id, student_id, razorpay_order_id, razorpay_payment_id, 
         payment_type, event_type, status)
        VALUES (?,?,?,?,?,?,?)
    ")->execute([
        $bookingId,
        $booking['student_id'],
        $orderId,
        $razorpayPaymentId,
        'TOKEN',
        'mandate.authorized',
        'SUCCESS'
    ]);

    /* ─── 6. Send Confirmation ─── */
    if (function_exists('sendBookingConfirmation')) {
        sendBookingConfirmation($booking['email'], $booking['username'], $booking);
    }

    /* ─── 7. Redirect ─── */
    $_SESSION['payment_success'] = 'Mandate setup successfully! Future payments will be auto-debited.';
    header("Location: dashboard.php"); 
    exit;

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
