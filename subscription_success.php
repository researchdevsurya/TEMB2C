<?php
/**
 * Subscription Success – Handle mandate auth callback
 * 
 * After Razorpay Checkout completes, the student is redirected here.
 * We verify the payment signature, update DB, create a recurring token, 
 * and send booking confirmation email.
 */
session_start();
require 'db.php';
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';
require __DIR__ . '/mail_helper.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

$api = new Api(RZP_KEY_ID, RZP_KEY_SECRET);

/* Capture POST data */
$razorpayOrderId   = $_POST['razorpay_order_id']   ?? null;
$razorpayPaymentId = $_POST['razorpay_payment_id'] ?? null;
$razorpaySignature = $_POST['razorpay_signature']  ?? null;
$bookingId         = $_POST['booking_id']           ?? null;
$rawPayload        = json_encode($_POST);

if (!$razorpayOrderId || !$razorpayPaymentId || !$razorpaySignature || !$bookingId) {
    die("Invalid payment response");
}

try {

    /* ─── 1. Verify signature ─── */
    $api->utility->verifyPaymentSignature([
        'razorpay_order_id'   => $razorpayOrderId,
        'razorpay_payment_id' => $razorpayPaymentId,
        'razorpay_signature'  => $razorpaySignature
    ]);

    /* ─── 2. Fetch payment to get token ─── */
    $payment = $api->payment->fetch($razorpayPaymentId);
    $tokenId = $payment['token_id'] ?? null;

    /* ─── 3. Fetch booking ─── */
    $stmt = $pdo->prepare("
        SELECT sb.*, s.email, s.username 
        FROM student_bookings sb 
        JOIN students s ON s.id = sb.student_id
        WHERE sb.id = ? AND sb.txnid = ?
    ");
    $stmt->execute([$bookingId, $razorpayOrderId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) throw new Exception("Booking not found for this order");

    /* ─── 4. Update booking with auth details ─── */
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
        FEE_TOKEN,
        $bookingId
    ]);

    /* ─── 5. Log success ─── */
    $pdo->prepare("
        INSERT INTO payment_logs 
        (booking_id, student_id, razorpay_order_id, razorpay_payment_id, 
         razorpay_signature, payment_type, event_type, raw_payload, status)
        VALUES (?,?,?,?,?,?,?,?,?)
    ")->execute([
        $bookingId,
        $booking['student_id'],
        $razorpayOrderId,
        $razorpayPaymentId,
        $razorpaySignature,
        'TOKEN',
        'payment.authorized',
        $rawPayload,
        'SUCCESS'
    ]);

    /* ─── 6. Send booking confirmation email ─── */
    sendBookingConfirmation($booking['email'], $booking['username'], $booking);

    /* ─── 7. Redirect to payments page ─── */
    $_SESSION['payment_success'] = 'Auto-pay mandate activated! Session fees will be deducted automatically.';
    header("Location: payments.php");
    exit;

} catch (SignatureVerificationError $e) {

    /* Log failure */
    $pdo->prepare("
        INSERT INTO payment_logs 
        (booking_id, razorpay_order_id, razorpay_payment_id, razorpay_signature, 
         payment_type, event_type, raw_payload, status)
        VALUES (?,?,?,?,?,?,?,?)
    ")->execute([
        $bookingId,
        $razorpayOrderId,
        $razorpayPaymentId,
        $razorpaySignature,
        'TOKEN',
        'payment.signature_failed',
        $rawPayload,
        'FAILED'
    ]);

    echo "Payment verification failed. Please try again.";

} catch (Exception $e) {
    error_log("Subscription success error: " . $e->getMessage());
    echo "An error occurred: " . htmlspecialchars($e->getMessage());
}
