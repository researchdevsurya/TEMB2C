<?php
/**
 * Final Payment – Manual payment fallback
 * 
 * Used when auto-pay is cancelled or fails, allowing student to pay manually.
 */
session_start();
require 'db.php';
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';

use Razorpay\Api\Api;

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

$booking_id = $_GET['booking_id'] ?? die("Invalid booking");
$type       = $_GET['type'] ?? die("Invalid type"); // PSYCHOMETRIC / GROUP / ONE_TO_ONE

$amount = AMOUNT_MAP[$type] ?? die("Invalid payment type");

/* Verify booking belongs to student */
$bkStmt = $pdo->prepare("SELECT id, student_id FROM student_bookings WHERE id=? AND student_id=?");
$bkStmt->execute([$booking_id, $_SESSION['student_id']]);
if (!$bkStmt->fetch()) die("Booking not found");

/* Check if already paid */
$chkPaid = $pdo->prepare("SELECT id FROM payments WHERE booking_id=? AND payment_for=? AND payment_status='PAID'");
$chkPaid->execute([$booking_id, $type]);
if ($chkPaid->rowCount() > 0) {
    $_SESSION['payment_success'] = 'This session is already paid!';
    header("Location: payments.php");
    exit;
}

/* INSERT or UPDATE payment record */
$existingPay = $pdo->prepare("SELECT id FROM payments WHERE booking_id=? AND payment_for=? AND payment_status='PENDING'");
$existingPay->execute([$booking_id, $type]);
$existingRow = $existingPay->fetch();

if ($existingRow) {
    $payment_id = $existingRow['id'];
} else {
    $stmt = $pdo->prepare("
        INSERT INTO payments (booking_id, student_id, payment_for, amount, auto_pay)
        SELECT id, student_id, ?, ?, 'NO'
        FROM student_bookings WHERE id=?
    ");
    $stmt->execute([$type, $amount, $booking_id]);
    $payment_id = $pdo->lastInsertId();
}

$api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

/* CREATE ORDER */
$order = $api->order->create([
    'receipt'  => 'FINAL_' . $payment_id . '_' . time(),
    'amount'   => $amount * 100,
    'currency' => 'INR',
    'notes'    => [
        'booking_id'  => $booking_id,
        'payment_for' => $type,
        'student_id'  => $_SESSION['student_id']
    ]
]);

/* UPDATE ORDER ID */
$pdo->prepare("UPDATE payments SET razorpay_order_id=? WHERE id=?")->execute([$order['id'], $payment_id]);

/* LOG */
$pdo->prepare("
    INSERT INTO payment_logs 
    (booking_id, student_id, razorpay_order_id, payment_type, event_type, raw_payload, status)
    VALUES (?,?,?,?,?,?,?)
")->execute([
    $booking_id,
    $_SESSION['student_id'],
    $order['id'],
    'FINAL',
    'order.created',
    json_encode($order->toArray()),
    'INITIATED'
]);

$sessionLabel = match($type) {
    'PSYCHOMETRIC' => 'Psychometric Test',
    'GROUP'        => 'Group Session',
    'ONE_TO_ONE'   => '1:1 Counselling',
    default        => $type
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pay – <?= $sessionLabel ?> | TEM Academy</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-indigo-50 via-white to-purple-50 min-h-screen flex items-center justify-center">

<div class="max-w-md w-full mx-4">
    <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-credit-card text-blue-600 text-2xl"></i>
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-2"><?= $sessionLabel ?></h2>
        <p class="text-3xl font-extrabold text-indigo-600 mb-4">₹<?= number_format($amount) ?></p>
        <p class="text-gray-500 text-sm mb-6">Complete this payment to confirm your session.</p>
        
        <button onclick="openPay()" id="payBtn"
                class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-400 hover:to-indigo-500 text-white font-bold py-3.5 rounded-xl shadow-lg transition-all">
            <i class="fas fa-lock mr-2"></i> Pay ₹<?= number_format($amount) ?>
        </button>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
function openPay() {
    new Razorpay({
        key: "<?= RAZORPAY_KEY_ID ?>",
        amount: "<?= $amount * 100 ?>",
        currency: "INR",
        name: "TEM Academy",
        description: "<?= $sessionLabel ?> Fee",
        order_id: "<?= $order['id'] ?>",
        theme: { color: "#6366f1" },
        handler: function(r) {
            var f = document.createElement("form");
            f.method = "POST";
            f.action = "final_success.php";
            for (var k in r) {
                var i = document.createElement("input");
                i.type = "hidden"; i.name = k; i.value = r[k];
                f.appendChild(i);
            }
            document.body.appendChild(f);
            f.submit();
        },
        modal: {
            ondismiss: function() {
                document.getElementById('payBtn').innerHTML = '<i class="fas fa-redo mr-2"></i> Try Again';
            }
        }
    }).open();
}
window.onload = function() { setTimeout(openPay, 500); };
</script>
</body>
</html>
