<?php
session_start();
require 'db.php';
require __DIR__ . '/vendor/autoload.php';

use Razorpay\Api\Api;

$keyId     = "rzp_test_SDwKTskm09k6GX";
$keySecret = "5o7G2ENkl3wE3EUUSb3CKD9v";

$booking_id = $_GET['booking_id'];
$type       = $_GET['type']; // PSYCHOMETRIC / GROUP / ONE_TO_ONE

$amountMap = [
    'PSYCHOMETRIC' => 799,
    'GROUP'        => 799,
    'ONE_TO_ONE'   => 1000
];

$amount = $amountMap[$type] ?? die("Invalid type");

/* INSERT PAYMENT RECORD */
$stmt = $pdo->prepare("
  INSERT INTO payments
  (booking_id, student_id, payment_for, amount)
  SELECT id, student_id, ?, ?
  FROM student_bookings WHERE id=?
");
$stmt->execute([$type, $amount, $booking_id]);

$payment_id = $pdo->lastInsertId();

$api = new Api($keyId, $keySecret);

/* CREATE ORDER */
$order = $api->order->create([
    'receipt'  => 'FINAL_'.$payment_id,
    'amount'   => $amount * 100,
    'currency' => 'INR'
]);

/* UPDATE ORDER ID */
$pdo->prepare("
  UPDATE payments
  SET razorpay_order_id=?
  WHERE id=?
")->execute([$order['id'], $payment_id]);

/* 🔥 LOG ORDER CREATION (INITIATED) */
$pdo->prepare("
  INSERT INTO payment_logs
  (
    booking_id,
    student_id,
    razorpay_order_id,
    payment_type,
    raw_payload,
    status
  )
  SELECT
    id,
    student_id,
    ?,
    'FINAL',
    ?,
    'INITIATED'
  FROM student_bookings
  WHERE id=?
")->execute([
    $order['id'],
    json_encode($order),
    $booking_id
]);
?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
new Razorpay({
  key: "<?= $keyId ?>",
  amount: "<?= $amount * 100 ?>",
  currency: "INR",
  name: "TEM Academy",
  description: "Final Payment",
  order_id: "<?= $order['id'] ?>",
  handler: function (r) {
    var f=document.createElement("form");
    f.method="POST";
    f.action="final_success.php";
    for (var k in r){
      var i=document.createElement("input");
      i.type="hidden"; i.name=k; i.value=r[k];
      f.appendChild(i);
    }
    document.body.appendChild(f);
    f.submit();
  }
}).open();
</script>
