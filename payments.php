<?php
session_start();
require 'db.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['student_id'];

/* FETCH LATEST BOOKING */
$stmt = $pdo->prepare("
    SELECT * FROM student_bookings
    WHERE student_id = ?
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute([$student_id]);
$booking = $stmt->fetch();

/* FETCH PAYMENTS (REAL PAYMENTS) */
$payStmt = $pdo->prepare("
    SELECT payment_for, payment_status
    FROM payments
    WHERE student_id = ?
");
$payStmt->execute([$student_id]);

$payments = [];
foreach ($payStmt->fetchAll() as $p) {
    $payments[$p['payment_for']] = $p['payment_status'];
}

/* HELPER: ENABLE PAY BUTTON 1 DAY BEFORE */
function canPayNow($date) {
    if (!$date) return false;
    $today = new DateTime(date('Y-m-d'));
    $session = new DateTime($date);
    $diff = (int)$today->diff($session)->format('%r%a');
    return $diff <= 1 && $diff >= 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payments | TEM Portal</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50 h-screen ">

<div class=" h-screen">

    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN -->
    <main class="flex-1 overflow-y-auto">

        <!-- MOBILE TOP BAR -->
        <div class="bg-white border-b p-4 flex items-center lg:hidden">
            <button class="text-gray-700 text-xl mr-4">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="text-lg font-bold text-gray-800">Payments</h1>
        </div>

        <div class="max-w-6xl mx-auto p-6">

            <!-- HEADER -->
            <div class="bg-white rounded-2xl p-6 shadow mb-8">
                <h1 class="text-2xl font-bold text-gray-800">
                    Payments & Transactions
                </h1>
                <p class="text-gray-500 mt-1">
                    View your booking and session payment status
                </p>
            </div>

            <!-- TABLE -->
            <div class="bg-white rounded-2xl shadow p-6">

                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    Payment History
                </h2>

                <div class="overflow-x-auto">
                    <table class="w-full border border-gray-200 rounded-lg overflow-hidden">
                        <thead class="bg-gray-100 text-gray-700 text-sm">
                            <tr>
                                <th class="p-3 text-left">Date</th>
                                <th class="p-3 text-left">Amount</th>
                                <th class="p-3 text-left">Status</th>
                                <th class="p-3 text-left">Remarks</th>
                            </tr>
                        </thead>

                        <tbody class="text-sm">

                        <?php if ($booking): ?>

                            <!-- TOKEN PAYMENT -->
                            <tr class="border-t">
                                <td class="p-3">
                                    <?= date('d M Y', strtotime($booking['created_at'])) ?>
                                </td>
                                <td class="p-3 font-semibold">
                                    ₹<?= number_format($booking['amount'], 2) ?>
                                </td>
                                <td class="p-3 font-medium <?= $booking['payment_status']=='paid' ? 'text-green-600' : 'text-yellow-600' ?>">
                                    <?= ucfirst($booking['payment_status']) ?>
                                </td>
                                <td class="p-3 text-gray-500">
                                    Booking Confirmation (Token)
                                </td>
                            </tr>

                            <!-- PSYCHOMETRIC -->
                            <?php $psyPaid = ($payments['PSYCHOMETRIC'] ?? '') === 'PAID'; ?>
                            <tr class="border-t">
                                <td class="p-3">
                                    <?= date('d M Y', strtotime($booking['selected_psychometric_date'])) ?>
                                </td>
                                <td class="p-3 font-semibold">₹799</td>
                                <td class="p-3 font-medium <?= $psyPaid ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= $psyPaid ? 'Paid' : 'Pending' ?>
                                </td>
                                <td class="p-3">
                                    <?php if ($psyPaid): ?>
                                        Psychometric Test Fee
                                    <?php else: ?>
                                        <a href="final_payment.php?booking_id=<?= $booking['id'] ?>&type=PSYCHOMETRIC"
                                           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-lg text-sm">
                                            Pay Now
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- GROUP SESSION -->
                            <?php
                            $groupPaid = ($payments['GROUP'] ?? '') === 'PAID';
                            $groupEnabled = canPayNow($booking['group_session1_date']);
                            ?>
                            <tr class="border-t">
                                <td class="p-3">
                                    <?= $booking['group_session1_date'] ? date('d M Y', strtotime($booking['group_session1_date'])) : '-' ?>
                                </td>
                                <td class="p-3 font-semibold">₹799</td>
                                <td class="p-3 font-medium <?= $groupPaid ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= $groupPaid ? 'Paid' : 'Pending' ?>
                                </td>
                                <td class="p-3">
                                    <?php if ($groupPaid): ?>
                                        Group Session Fee
                                    <?php elseif ($groupEnabled): ?>
                                        <a href="final_payment.php?booking_id=<?= $booking['id'] ?>&type=GROUP"
                                           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-lg text-sm">
                                            Pay Now
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-sm">
                                            Available 1 day before session
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- ONE TO ONE -->
                            <?php
                            $onePaid = ($payments['ONE_TO_ONE'] ?? '') === 'PAID';
                            $oneEnabled = canPayNow($booking['booked_date']);
                            ?>
                            <tr class="border-t">
                                <td class="p-3">
                                    <?= date('d M Y', strtotime($booking['booked_date'])) ?>
                                </td>
                                <td class="p-3 font-semibold">₹1000</td>
                                <td class="p-3 font-medium <?= $onePaid ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= $onePaid ? 'Paid' : 'Pending' ?>
                                </td>
                                <td class="p-3">
                                    <?php if ($onePaid): ?>
                                        1 : 1 Session Fee
                                    <?php elseif ($oneEnabled): ?>
                                        <a href="final_payment.php?booking_id=<?= $booking['id'] ?>&type=ONE_TO_ONE"
                                           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-lg text-sm">
                                            Pay Now
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-sm">
                                            Available 1 day before session
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php else: ?>

                            <!-- NO DATA -->
                            <tr class="border-t">
                                <td colspan="4" class="p-6 text-center text-gray-500">
                                    No bookings found
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>
                    </table>
                </div>
            </div>

            <!-- INFO BOX -->
            <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6 mt-8">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-500 text-lg mr-3 mt-1"></i>
                    <div>
                        <p class="font-medium text-blue-800">
                            Payment Information
                        </p>
                        <p class="text-sm text-blue-700 mt-1">
                            Booking confirmation is paid first.
                            Session fees are payable close to the scheduled date.
                        </p>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>

        </div>
    </main>
</div>

</body>
</html>

<!--  -->