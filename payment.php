

<?php
session_start();
require 'db.php';
require __DIR__ . '/vendor/autoload.php';

use Razorpay\Api\Api;

$keyId     = "rzp_test_SDwKTskm09k6GX";
$keySecret = "5o7G2ENkl3wE3EUUSb3CKD9v";

$booking_id = $_GET['booking_id'] ?? die("Invalid booking");

/* FETCH BOOKING */
$stmt = $pdo->prepare("SELECT * FROM student_bookings WHERE id=?");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

if (!$booking) die("Booking not found");

/* TOKEN AMOUNT */
$amount = 100; // ₹1

$api = new Api($keyId, $keySecret);

/* CREATE ORDER */
$order = $api->order->create([
    'receipt'  => 'TOKEN_'.$booking_id.'_'.time(),
    'amount'   => $amount,
    'currency' => 'INR'
]);

$order_id = $order['id'];

/* SAVE TOKEN TXN */
$pdo->prepare("
  UPDATE student_bookings
  SET txnid=?, amount=?
  WHERE id=?
")->execute([$order_id, 1, $booking_id]);
?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
new Razorpay({
  key: "<?= $keyId ?>",
  amount: "<?= $amount ?>",
  currency: "INR",
  name: "TEM Academy",
  description: "Booking Confirmation",
  order_id: "<?= $order_id ?>",
  handler: function (r) {
    var f=document.createElement("form");
    f.method="POST";
    f.action="token_success.php";
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
