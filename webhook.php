<?php
$payload = json_decode(file_get_contents("php://input"), true);

if ($payload['event']=="payment.captured") {

  $pid = $payload['payload']['payment']['entity']['id'];

  $pdo->prepare("
    UPDATE payments
    SET payment_status='PAID',
        razorpay_payment_id=?,
        paid_at=NOW()
    WHERE razorpay_payment_id IS NULL
  ")->execute([$pid]);
}
