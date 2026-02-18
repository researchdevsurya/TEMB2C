<?php
/**
 * Final Payment Success – Verify manual session payment
 */
session_start();
require 'db.php';
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';
require __DIR__ . '/mail_helper.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

$api = new Api(RZP_KEY_ID, RZP_KEY_SECRET);

$razorpayOrderId   = $_POST['razorpay_order_id']   ?? null;
$razorpayPaymentId = $_POST['razorpay_payment_id'] ?? null;
$razorpaySignature = $_POST['razorpay_signature']  ?? null;

if (!$razorpayOrderId || !$razorpayPaymentId || !$razorpaySignature) {
    die("Invalid payment response");
}

try {
    /* Verify signature */
    $api->utility->verifyPaymentSignature([
        'razorpay_order_id'   => $razorpayOrderId,
        'razorpay_payment_id' => $razorpayPaymentId,
        'razorpay_signature'  => $razorpaySignature
    ]);

    /* Update payment status */
    $pdo->prepare("
        UPDATE payments 
        SET payment_status='PAID',
            razorpay_payment_id=?,
            paid_at=NOW()
        WHERE razorpay_order_id=?
    ")->execute([$razorpayPaymentId, $razorpayOrderId]);

    /* Log success */
    $pdo->prepare("
        INSERT INTO payment_logs 
        (razorpay_order_id, razorpay_payment_id, razorpay_signature, payment_type, event_type, raw_payload, status)
        VALUES (?,?,?,?,?,?,?)
    ")->execute([
        $razorpayOrderId,
        $razorpayPaymentId,
        $razorpaySignature,
        'FINAL',
        'payment.manual_success',
        json_encode($_POST),
        'SUCCESS'
    ]);

    /* Send receipt email */
    $payRow = $pdo->prepare("
        SELECT p.*, s.email, s.username 
        FROM payments p 
        JOIN students s ON s.id = p.student_id
        WHERE p.razorpay_order_id=?
        LIMIT 1
    ");
    $payRow->execute([$razorpayOrderId]);
    $p = $payRow->fetch(PDO::FETCH_ASSOC);
    if ($p) {
        sendPaymentReceipt($p['email'], $p['username'], $p['payment_for'], (float)$p['amount'], $razorpayPaymentId);
    }

    $_SESSION['payment_success'] = 'Payment successful! Receipt sent to your email.';
    header("Location: payments.php");
    exit;

} catch (SignatureVerificationError $e) {
    /* Log failure */
    $pdo->prepare("
        INSERT INTO payment_logs 
        (razorpay_order_id, razorpay_payment_id, razorpay_signature, payment_type, event_type, raw_payload, status)
        VALUES (?,?,?,?,?,?,?)
    ")->execute([
        $razorpayOrderId,
        $razorpayPaymentId,
        $razorpaySignature,
        'FINAL',
        'payment.signature_failed',
        json_encode($_POST),
        'FAILED'
    ]);

    echo "Payment verification failed. Please contact support.";
}
