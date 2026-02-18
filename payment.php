<?php
/**
 * Payment – Create Razorpay Subscription + e-Mandate
 * 
 * Flow: Student completes booking → redirected here → Razorpay Subscription
 * created → Checkout opens → Student authorizes mandate → redirect to
 * subscription_success.php
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

/* FETCH BOOKING */
$stmt = $pdo->prepare("SELECT sb.*, s.email, s.contact_number, s.username 
                        FROM student_bookings sb 
                        JOIN students s ON s.id = sb.student_id 
                        WHERE sb.id=? AND sb.student_id=?");
$stmt->execute([$booking_id, $_SESSION['student_id']]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) die("Booking not found");

$api = new Api(RZP_KEY_ID, RZP_KEY_SECRET);

/* ─── STEP 1: Create Razorpay Customer (if not exists) ─── */
$customerId = $booking['razorpay_customer_id'];

if (!$customerId) {
    $customer = $api->customer->create([
        'name'    => $booking['username'],
        'email'   => $booking['email'],
        'contact' => $booking['contact_number'],
        'notes'   => ['student_id' => $booking['student_id']]
    ]);
    $customerId = $customer['id'];

    $pdo->prepare("UPDATE student_bookings SET razorpay_customer_id=? WHERE id=?")
        ->execute([$customerId, $booking_id]);
}

/* ─── STEP 2: Create Razorpay Plan (or use existing) ─── */
// We create a Plan programmatically for this booking's total.
// Each session = 1 charge cycle. Total 3 sessions after booking:
//   Psychometric (₹799) + Group (₹799) + One-to-One (₹1000)
// BUT Razorpay Plans have fixed amount per cycle.
// Strategy: Create a subscription with the token amount for authorization,
// then use the recurring token to charge per-session amounts via auto_debit.
//
// Alternative: Use Razorpay's e-mandate with "token" payment method.
// We'll create an order with recurring flag for mandate registration.

/* ─── STEP 2: Create auth order for e-mandate ─── */
$tokenAmount = FEE_TOKEN * 100; // ₹1 in paise

$order = $api->order->create([
    'amount'          => $tokenAmount,
    'currency'        => 'INR',
    'receipt'         => 'MANDATE_' . $booking_id . '_' . time(),
    'payment_capture' => 1,
    'notes'           => [
        'booking_id' => $booking_id,
        'student_id' => $booking['student_id'],
        'purpose'    => 'e-mandate authorization'
    ]
]);

$order_id = $order['id'];

/* ─── STEP 3: Save order + customer to booking ─── */
$pdo->prepare("
    UPDATE student_bookings
    SET txnid=?, amount=?, razorpay_customer_id=?, subscription_status='created'
    WHERE id=?
")->execute([$order_id, FEE_TOKEN, $customerId, $booking_id]);

/* ─── STEP 4: Create pending payment records for all sessions ─── */
$sessions = [
    ['type' => 'PSYCHOMETRIC', 'date' => $booking['selected_psychometric_date'], 'amount' => FEE_PSYCHOMETRIC],
    ['type' => 'GROUP',        'date' => $booking['group_session1_date'],         'amount' => FEE_GROUP],
    ['type' => 'ONE_TO_ONE',   'date' => $booking['booked_date'],                 'amount' => FEE_ONE_TO_ONE],
];

foreach ($sessions as $sess) {
    // Check if payment already exists
    $chk = $pdo->prepare("SELECT id FROM payments WHERE booking_id=? AND payment_for=?");
    $chk->execute([$booking_id, $sess['type']]);
    if ($chk->rowCount() == 0) {
        $pdo->prepare("
            INSERT INTO payments (booking_id, student_id, payment_for, amount, scheduled_date, auto_pay)
            VALUES (?,?,?,?,?,?)
        ")->execute([
            $booking_id,
            $booking['student_id'],
            $sess['type'],
            $sess['amount'],
            $sess['date'],
            'YES'
        ]);
    }
}

/* ─── LOG ─── */
$pdo->prepare("
    INSERT INTO payment_logs (booking_id, student_id, razorpay_order_id, payment_type, event_type, raw_payload, status)
    VALUES (?,?,?,?,?,?,?)
")->execute([
    $booking_id,
    $booking['student_id'],
    $order_id,
    'TOKEN',
    'order.created',
    json_encode(['order' => $order->toArray(), 'customer_id' => $customerId]),
    'INITIATED'
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Complete Payment | TEM Academy</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    body { font-family: 'Inter', system-ui, sans-serif; }
    .loading-pulse { animation: pulse 2s ease-in-out infinite; }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
</style>
</head>
<body class="bg-gradient-to-br from-indigo-50 via-white to-purple-50 min-h-screen flex items-center justify-center">

<div class="max-w-md w-full mx-4">
    <div class="bg-white rounded-2xl shadow-xl p-8 text-center" id="paymentCard">
        <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-shield-alt text-indigo-600 text-2xl"></i>
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-2">Setup Auto-Pay Mandate</h2>
        <p class="text-gray-500 text-sm mb-6">
            A ₹<?= FEE_TOKEN ?> authorization charge will set up auto-pay for your sessions.
            Future payments will be deducted automatically before each session.
        </p>

        <div class="bg-gray-50 rounded-xl p-4 mb-6 text-left">
            <p class="text-xs text-gray-400 uppercase font-semibold mb-3">Session Fees</p>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Psychometric Test</span>
                    <span class="font-bold text-gray-800">₹<?= FEE_PSYCHOMETRIC ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Group Session</span>
                    <span class="font-bold text-gray-800">₹<?= FEE_GROUP ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">1:1 Counselling</span>
                    <span class="font-bold text-gray-800">₹<?= FEE_ONE_TO_ONE ?></span>
                </div>
                <div class="border-t pt-2 mt-2 flex justify-between">
                    <span class="text-gray-600 font-semibold">Auth Charge Now</span>
                    <span class="font-bold text-indigo-600">₹<?= FEE_TOKEN ?></span>
                </div>
            </div>
        </div>

        <button onclick="openCheckout()" id="payBtn"
                class="w-full bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-400 hover:to-purple-500 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-indigo-500/25 transition-all flex items-center justify-center gap-2">
            <i class="fas fa-lock"></i> Authorize & Setup Auto-Pay
        </button>

        <p class="text-xs text-gray-400 mt-4">
            <i class="fas fa-shield-alt"></i> Secured by Razorpay. Payments auto-deducted 1 day before each session.
        </p>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
function openCheckout() {
    var options = {
        key: "<?= RZP_KEY_ID ?>",
        amount: "<?= $tokenAmount ?>",
        currency: "INR",
        name: "TEM Academy",
        description: "Auto-Pay Mandate Setup",
        order_id: "<?= $order_id ?>",
        customer_id: "<?= $customerId ?>",
        prefill: {
            name: "<?= htmlspecialchars($booking['username']) ?>",
            email: "<?= htmlspecialchars($booking['email']) ?>",
            contact: "<?= htmlspecialchars($booking['contact_number']) ?>"
        },
        recurring: "1",
        notes: {
            booking_id: "<?= $booking_id ?>",
            purpose: "e-mandate authorization"
        },
        theme: {
            color: "#6366f1"
        },
        handler: function(response) {
            // Redirect to success handler
            var form = document.createElement("form");
            form.method = "POST";
            form.action = "subscription_success.php";
            
            var fields = {
                razorpay_payment_id: response.razorpay_payment_id,
                razorpay_order_id: response.razorpay_order_id,
                razorpay_signature: response.razorpay_signature,
                booking_id: "<?= $booking_id ?>"
            };

            for (var key in fields) {
                var input = document.createElement("input");
                input.type = "hidden";
                input.name = key;
                input.value = fields[key];
                form.appendChild(input);
            }

            document.body.appendChild(form);
            form.submit();
        },
        modal: {
            ondismiss: function() {
                document.getElementById('payBtn').textContent = 'Try Again';
            }
        }
    };

    new Razorpay(options).open();
}

// Auto-open checkout
window.onload = function() {
    setTimeout(openCheckout, 500);
};
</script>

</body>
</html>
