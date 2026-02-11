<?php
session_start();
require 'db.php';
require __DIR__ . '/vendor/autoload.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

$keyId     = "rzp_test_SDwKTskm09k6GX";
$keySecret = "5o7G2ENkl3wE3EUUSb3CKD9v";

$api = new Api($keyId, $keySecret);

/* Capture everything Razorpay sends */
$razorpayOrderId   = $_POST['razorpay_order_id'] ?? null;
$razorpayPaymentId = $_POST['razorpay_payment_id'] ?? null;
$razorpaySignature = $_POST['razorpay_signature'] ?? null;
$rawPayload        = json_encode($_POST);

try {

    /* 1️⃣ Verify signature */
    $api->utility->verifyPaymentSignature([
        'razorpay_order_id'   => $razorpayOrderId,
        'razorpay_payment_id'=> $razorpayPaymentId,
        'razorpay_signature' => $razorpaySignature
    ]);

    /* 2️⃣ Fetch booking using order_id */
    $stmt = $pdo->prepare("
        SELECT id, student_id 
        FROM student_bookings 
        WHERE txnid = ?
        LIMIT 1
    ");
    $stmt->execute([$razorpayOrderId]);
    $booking = $stmt->fetch();

    /* 3️⃣ Update booking (token paid) */
    $pdo->prepare("
        UPDATE student_bookings
        SET 
            payment_status = 'paid',
            txnid = ?, 
            amount = 1
        WHERE id = ?
    ")->execute([
        $razorpayPaymentId,
        $booking['id']
    ]);

    /* 4️⃣ Store FULL LOG (EVERYTHING) */
    $pdo->prepare("
        INSERT INTO payment_logs
        (
            booking_id,
            student_id,
            razorpay_order_id,
            razorpay_payment_id,
            razorpay_signature,
            payment_type,
            raw_payload,
            status
        )
        VALUES (?,?,?,?,?,?,?,?)
    ")->execute([
        $booking['id'],
        $booking['student_id'],
        $razorpayOrderId,
        $razorpayPaymentId,
        $razorpaySignature,
        'TOKEN',
        $rawPayload,
        'SUCCESS'
    ]);

    /* 5️⃣ Redirect */
    header("Location: payments.php");
    exit;

} catch (SignatureVerificationError $e) {

    /* Log failure also */
    $pdo->prepare("
        INSERT INTO payment_logs
        (
            razorpay_order_id,
            razorpay_payment_id,
            razorpay_signature,
            payment_type,
            raw_payload,
            status
        )
        VALUES (?,?,?,?,?,?)
    ")->execute([
        $razorpayOrderId,
        $razorpayPaymentId,
        $razorpaySignature,
        'TOKEN',
        $rawPayload,
        'FAILED'
    ]);

    echo "Token payment verification failed";
}
