<?php

require 'vendor/autoload.php';


session_start();
require 'db.php';

// if (isset($_SESSION['student_id'])) {
//     header("Location: dashboard.php");
//     exit;
// }
require 'config.php'; // contains DB + Razorpay keys


use Razorpay\Api\Api;

if (!isset($_GET['booking_id'])) {
    die("Invalid request");
}

$booking_id = (int)$_GET['booking_id'];

$api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

/* ─────────────────────────────────────────
   STEP 1: Fetch Booking + Student Info
─────────────────────────────────────────── */

$stmt = $pdo->prepare("
    SELECT sb.*, s.username, s.email, s.contact_number, s.razorpay_customer_id
    FROM student_bookings sb
    inner JOIN students s ON sb.student_id = s.id
    WHERE sb.id = ?
");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    die("Booking not found");
}

if ($booking['subscription_status'] === 'active') {
    header("Location: schedule.php");
    exit;
}

$customerId = $booking['razorpay_customer_id'];

/* ─────────────────────────────────────────
   STEP 2: Create / Fetch Razorpay Customer
─────────────────────────────────────────── */

if (empty($customerId)) {

    try {

        $customer = $api->customer->create([
            'name'    => $booking['username'],
            'email'   => $booking['email'],
            'contact' => $booking['contact_number'],
            'notes'   => [
                'student_id' => $booking['student_id']
            ]
        ]);

        $customerId = $customer['id'];

        // Save in students table
        $pdo->prepare("
            UPDATE students
            SET razorpay_customer_id = ?
            WHERE id = ?
        ")->execute([$customerId, $booking['student_id']]);

    } catch (Exception $e) {

        if (strpos($e->getMessage(), 'already exists') !== false) {

            $customers = $api->customer->all([
                'email' => $booking['email']
            ]);

            if (!empty($customers['items'])) {

                $customerId = $customers['items'][0]['id'];

                $pdo->prepare("
                    UPDATE students
                    SET razorpay_customer_id = ?
                    WHERE id = ?
                ")->execute([$customerId, $booking['student_id']]);

            } else {
                die("Customer error: " . $e->getMessage());
            }

        } else {
            die("Customer creation failed: " . $e->getMessage());
        }
    }
}

/* ─────────────────────────────────────────
   STEP 3: Create Order (Refresh Safe)
─────────────────────────────────────────── */

$tokenAmount = 100; // ₹1 mandate test amount (100 paise)

if (!empty($booking['txnid'])) {

    $order_id = $booking['txnid'];

} else {

    try {

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

        $pdo->prepare("
            UPDATE student_bookings
            SET txnid = ?, razorpay_customer_id = ?, subscription_status='created'
            WHERE id = ?
        ")->execute([$order_id, $customerId, $booking_id]);

    } catch (Exception $e) {
        die("Order creation failed: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'header.php'; ?>
    <title>Complete Payment | TEM Portal</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        .pay-illustration {
            width: 120px;
            height: 120px;
            background: rgba(99, 102, 241, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .pay-illustration i {
            font-size: 3rem;
            color: var(--primary-600);
        }
    </style>
</head>
<body class="bg-gradient-page">

<?php include 'sidebar.php'; ?>

<main class="max-w-3xl mx-auto px-4 sm:px-6 py-12">

    <div class="glass-card p-8 sm:p-12 text-center animate-fade-in-up">
        
        <div class="pay-illustration">
            <i class="fas fa-file-signature"></i>
        </div>

        <h2 class="text-2xl font-bold text-gray-900 mb-2">Authorize Auto-Debit</h2>
        <p class="text-gray-500 mb-8 max-w-md mx-auto">
            To confirm your booking, please authorize the e-mandate. 
            A token amount of <strong>₹<?= number_format($tokenAmount / 100, 2) ?></strong> will be charged.
        </p>

        <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-8 text-left max-w-sm mx-auto">
            <div class="flex justify-between mb-2">
                <span class="text-gray-500 text-sm">Booking ID</span>
                <span class="font-semibold text-gray-800">#<?= $booking_id ?></span>
            </div>
            <div class="flex justify-between mb-2">
                <span class="text-gray-500 text-sm">Student</span>
                <span class="font-semibold text-gray-800"><?= htmlspecialchars($booking['username']) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500 text-sm">Amount</span>
                <span class="font-bold text-primary-600">₹<?= number_format($tokenAmount / 100, 2) ?></span>
            </div>
        </div>

        <button id="rzp-button1" class="btn-primary py-3 px-8 text-lg w-full sm:w-auto shadow-lg hover:shadow-xl transform transition hover:-translate-y-1">
            <i class="fas fa-qrcode mr-2"></i> Pay with UPI / Card
        </button>

        <p class="text-xs text-gray-400 mt-6">
            Secured by Razorpay. Use any UPI App (GooglePay, PhonePe, Paytm) to scan and pay.
        </p>
    </div>

</main>

<script>
function openPay() {

    var options = {
        key: "<?= RAZORPAY_KEY_ID ?>",
        amount: "<?= $tokenAmount ?>", // 100 paise (₹1)
        currency: "INR",
        name: "TEM Portal",
        description: "E-Mandate Authorization",
        image: "https://yourdomain.com/logo.png", 
        order_id: "<?= $order_id ?>",
        customer_id: "<?= $customerId ?>",
        recurring: true,

        // Enable ALL payment methods
        method: {
            upi: true,
            card: true,
            netbanking: true,
            wallet: true,
            emi: true
        },

        // Show UPI first with QR
        config: {
            display: {
                blocks: {
                    upi: {
                        name: "Pay using UPI",
                        instruments: [
                            { method: "upi" }
                        ]
                    },
                    card: {
                        name: "Pay using Card",
                        instruments: [
                            { method: "card" }
                        ]
                    },
                    netbanking: {
                        name: "Netbanking",
                        instruments: [
                            { method: "netbanking" }
                        ]
                    }
                },
                sequence: ["block.upi", "block.card", "block.netbanking"],
                preferences: {
                    show_default_blocks: true
                }
            }
        },

        prefill: {
            name: "<?= htmlspecialchars($booking['username']) ?>",
            email: "<?= htmlspecialchars($booking['email']) ?>",
            contact: "<?= htmlspecialchars($booking['contact_number']) ?>"
        },

        notes: {
            booking_id: "<?= $booking_id ?>",
            purpose: "mandate"
        },

        theme: {
            color: "#6366f1"
        },

        handler: function (response) {
            window.location.href =
                "mandate_success.php?booking_id=<?= $booking_id ?>" +
                "&payment_id=" + response.razorpay_payment_id +
                "&order_id=" + response.razorpay_order_id +
                "&signature=" + response.razorpay_signature;
        },

        modal: {
            confirm_close: true,
            ondismiss: function () {
                console.log("Payment popup closed");
            }
        }
    };

    var rzp = new Razorpay(options);
    rzp.open();
}

// Auto open on load
window.onload = function () {
    setTimeout(openPay, 500);
};

document.getElementById('rzp-button1').onclick = function(e){
    openPay();
    e.preventDefault();
}
</script>

<?php include 'footer.php'; ?>
</body>
</html>