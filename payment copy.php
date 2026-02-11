<?php
session_start();
require 'db.php';

$MERCHANT_KEY = "CLfpE1";
$SALT = "v6MnJO69DWLJGiT3B6vYL96lC5YEUp1U";

$booking_id = $_GET['booking_id'];

/* FETCH BOOKING */
$stmt=$pdo->prepare("SELECT * FROM student_bookings WHERE id=?");
$stmt->execute([$booking_id]);
$booking=$stmt->fetch();

$amount = 1; // change fee amount here
$txnid = "TEM".time();

/* SAVE TXN IN DB */
$pdo->prepare("UPDATE student_bookings SET txnid=?, amount=? WHERE id=?")
    ->execute([$txnid,$amount,$booking_id]);

/* HASH GENERATION */
$hash_string = $MERCHANT_KEY.'|'.$txnid.'|'.$amount.'|TEM Booking|Student|student@test.com|||||||||||'.$SALT;
$hash = strtolower(hash('sha512', $hash_string));
?>

<form action="https://test.payu.in/_payment" method="post" name="payuForm">

<input type="hidden" name="key" value="<?= $MERCHANT_KEY ?>" />
<input type="hidden" name="txnid" value="<?= $txnid ?>" />
<input type="hidden" name="amount" value="<?= $amount ?>" />
<input type="hidden" name="productinfo" value="TEM Booking" />
<input type="hidden" name="firstname" value="Student" />
<input type="hidden" name="email" value="student@test.com" />

<input type="hidden" name="surl" value="http://localhost/temb2c/success.php" />
<input type="hidden" name="furl" value="http://localhost/temb2c/fail.php" />

<input type="hidden" name="hash" value="<?= $hash ?>" />

</form>

<script>
document.payuForm.submit();
</script>
