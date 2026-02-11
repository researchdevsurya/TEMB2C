<?php
require 'db.php';
require __DIR__.'/vendor/autoload.php';

use Razorpay\Api\Api;

$api = new Api("rzp_live_xxx","xxx");

/* Payments scheduled tomorrow */
$stmt = $pdo->query("
  SELECT p.*, b.razorpay_customer_id
  FROM payments p
  JOIN student_bookings b ON b.id=p.booking_id
  WHERE p.auto_pay='YES'
    AND p.payment_status='PENDING'
    AND p.scheduled_date = DATE_ADD(CURDATE(),INTERVAL 1 DAY)
");

foreach ($stmt as $p) {

  $payment = $api->payment->create([
    'amount' => $p['amount']*100,
    'currency' => 'INR',
    'customer_id' => $p['razorpay_customer_id'],
    'recurring' => 1
  ]);

}
