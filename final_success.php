<?php
require 'db.php';
require __DIR__ . '/vendor/autoload.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

$keyId     = "rzp_test_SDwKTskm09k6GX";
$keySecret = "5o7G2ENkl3wE3EUUSb3CKD9v";

$api = new Api($keyId, $keySecret);

try {
    $api->utility->verifyPaymentSignature([
        'razorpay_order_id'   => $_POST['razorpay_order_id'],
        'razorpay_payment_id'=> $_POST['razorpay_payment_id'],
        'razorpay_signature' => $_POST['razorpay_signature']
    ]);

    $pdo->prepare("
      UPDATE payments
      SET payment_status='PAID',
          razorpay_payment_id=?,
          paid_at=NOW()
      WHERE razorpay_order_id=?
    ")->execute([
        $_POST['razorpay_payment_id'],
        $_POST['razorpay_order_id']
    ]);

    header("Location: payments.php");
    exit;

} catch (SignatureVerificationError $e) {
    echo "Final payment failed";
}
