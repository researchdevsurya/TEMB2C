<?php
session_start();
require 'db.php';
require __DIR__ . '/config.php';

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
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

/* FETCH PAYMENTS */
$payStmt = $pdo->prepare("
    SELECT id, payment_for, payment_status, amount, scheduled_date, auto_pay, razorpay_payment_id, paid_at
    FROM payments
    WHERE student_id = ?
    ORDER BY scheduled_date ASC
");
$payStmt->execute([$student_id]);
$allPayments = $payStmt->fetchAll(PDO::FETCH_ASSOC);

$payments = [];
foreach ($allPayments as $p) {
    $payments[$p['payment_for']] = $p;
}

/* Flash message */
$flashMsg = $_SESSION['payment_success'] ?? null;
unset($_SESSION['payment_success']);

function statusBadge($status) {
    return match($status) {
        'PAID'    => '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700"><i class="fas fa-check-circle"></i> Paid</span>',
        'FAILED'  => '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700"><i class="fas fa-times-circle"></i> Failed</span>',
        default   => '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700"><i class="fas fa-clock"></i> Pending</span>',
    };
}

function mandateBadge($status) {
    return match($status) {
        'authenticated', 'active' => '<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-green-100 text-green-700"><i class="fas fa-shield-alt"></i> Auto-Pay Active</span>',
        'cancelled'               => '<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-red-100 text-red-700"><i class="fas fa-ban"></i> Cancelled</span>',
        'created'                 => '<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700"><i class="fas fa-hourglass-half"></i> Pending Setup</span>',
        default                   => '<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600"><i class="fas fa-question-circle"></i> Unknown</span>',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payments | TEM Portal</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php include 'header.php'; ?>
</head>

<body class="bg-gradient-page">

<?php include 'sidebar.php'; ?>

<main class="max-w-4xl mx-auto px-4 sm:px-6 py-8">

    <?php if ($flashMsg): ?>
    <div class="bg-green-50 border border-green-200 rounded-2xl p-4 mb-6 flex items-center gap-3 animate-fade-in-up">
        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
            <i class="fas fa-check-circle text-green-500 text-lg"></i>
        </div>
        <div>
            <p class="font-semibold text-green-800"><?= htmlspecialchars($flashMsg) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- HEADER -->
    <div class="glass-card-static p-6 mb-6 animate-fade-in-up">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Payments & Auto-Pay</h1>
                <p class="text-gray-500 text-sm mt-1">View your session payments and mandate status</p>
            </div>
            <?php if ($booking): ?>
            <div class="flex items-center gap-3">
                <?= mandateBadge($booking['subscription_status'] ?? 'created') ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($booking): ?>

    <!-- MANDATE STATUS CARD -->
    <div class="glass-card-static p-6 mb-6 animate-fade-in-up" style="animation-delay: 0.1s;">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="fas fa-credit-card text-indigo-600 text-xl"></i>
            </div>
            <div class="flex-1">
                <h3 class="font-bold text-gray-800 mb-1">Auto-Pay Mandate</h3>
                <?php if (in_array($booking['subscription_status'] ?? '', ['authenticated', 'active'])): ?>
                    <p class="text-gray-500 text-sm">Session fees are deducted automatically 1 day before each session.</p>
                    <div class="mt-3">
                        <a href="cancel_autopay.php?booking_id=<?= $booking['id'] ?>" 
                           onclick="return confirm('Cancel auto-pay? You will need to pay manually for each session.')"
                           class="text-sm text-red-500 hover:text-red-700 font-medium">
                            <i class="fas fa-times-circle"></i> Cancel Auto-Pay
                        </a>
                    </div>
                <?php elseif ($booking['subscription_status'] === 'cancelled'): ?>
                    <p class="text-gray-500 text-sm">Auto-pay was cancelled. Please pay manually before each session.</p>
                <?php elseif ($booking['payment_status'] === 'pending'): ?>
                    <p class="text-gray-500 text-sm">Please complete payment to activate auto-pay.</p>
                    <a href="payment.php?booking_id=<?= $booking['id'] ?>" class="btn-primary mt-3 inline-block text-sm">
                        <i class="fas fa-lock"></i> Setup Auto-Pay
                    </a>
                <?php else: ?>
                    <p class="text-gray-500 text-sm">Mandate setup is being processed.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- PAYMENT TABLE -->
    <div class="glass-card-static p-6 animate-fade-in-up" style="animation-delay: 0.2s;">
        <h2 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-receipt text-indigo-500 mr-2"></i>Payment History
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left p-3 text-xs text-gray-500 uppercase font-semibold">Session</th>
                        <th class="text-left p-3 text-xs text-gray-500 uppercase font-semibold">Date</th>
                        <th class="text-left p-3 text-xs text-gray-500 uppercase font-semibold">Amount</th>
                        <th class="text-left p-3 text-xs text-gray-500 uppercase font-semibold">Status</th>
                        <th class="text-left p-3 text-xs text-gray-500 uppercase font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="text-sm">

                    <!-- TOKEN PAYMENT -->
                    <tr class="border-b border-gray-100 hover:bg-gray-50/50 transition">
                        <td class="p-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-shield-alt text-indigo-500 text-xs"></i>
                                </div>
                                <span class="font-medium text-gray-700">Booking Token</span>
                            </div>
                        </td>
                        <td class="p-3 text-gray-500"><?= date('d M Y', strtotime($booking['created_at'])) ?></td>
                        <td class="p-3 font-semibold text-gray-800">₹<?= number_format($booking['amount'], 2) ?></td>
                        <td class="p-3">
                            <?= $booking['payment_status'] === 'paid' 
                                ? '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700"><i class="fas fa-check-circle"></i> Paid</span>'
                                : '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700"><i class="fas fa-clock"></i> Pending</span>' 
                            ?>
                        </td>
                        <td class="p-3 text-gray-400 text-xs">Mandate Authorization</td>
                    </tr>

                    <!-- PSYCHOMETRIC -->
                    <?php $psy = $payments['PSYCHOMETRIC'] ?? null; ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50/50 transition">
                        <td class="p-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-brain text-blue-500 text-xs"></i>
                                </div>
                                <span class="font-medium text-gray-700">Psychometric Test</span>
                            </div>
                        </td>
                        <td class="p-3 text-gray-500"><?= date('d M Y', strtotime($booking['selected_psychometric_date'])) ?></td>
                        <td class="p-3 font-semibold text-gray-800">₹<?= number_format(FEE_PSYCHOMETRIC, 2) ?></td>
                        <td class="p-3"><?= statusBadge($psy['payment_status'] ?? 'PENDING') ?></td>
                        <td class="p-3">
                            <?php if (($psy['payment_status'] ?? '') === 'PAID'): ?>
                                <span class="text-gray-400 text-xs"><?= $psy['razorpay_payment_id'] ? 'ID: ' . substr($psy['razorpay_payment_id'], 0, 15) . '...' : '' ?></span>
                            <?php elseif ($psy && ($psy['auto_pay'] ?? '') === 'YES' && in_array($booking['subscription_status'] ?? '', ['authenticated', 'active'])): ?>
                                <span class="inline-flex items-center gap-1 text-xs text-indigo-600 font-medium"><i class="fas fa-bolt"></i> Auto-Debit Scheduled</span>
                            <?php else: ?>
                                <a href="final_payment.php?booking_id=<?= $booking['id'] ?>&type=PSYCHOMETRIC"
                                   class="btn-primary text-xs px-3 py-1.5">Pay Now</a>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <!-- GROUP SESSION -->
                    <?php $grp = $payments['GROUP'] ?? null; ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50/50 transition">
                        <td class="p-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-users text-green-500 text-xs"></i>
                                </div>
                                <span class="font-medium text-gray-700">Group Session</span>
                            </div>
                        </td>
                        <td class="p-3 text-gray-500"><?= $booking['group_session1_date'] ? date('d M Y', strtotime($booking['group_session1_date'])) : '-' ?></td>
                        <td class="p-3 font-semibold text-gray-800">₹<?= number_format(FEE_GROUP, 2) ?></td>
                        <td class="p-3"><?= statusBadge($grp['payment_status'] ?? 'PENDING') ?></td>
                        <td class="p-3">
                            <?php if (($grp['payment_status'] ?? '') === 'PAID'): ?>
                                <span class="text-gray-400 text-xs"><?= $grp['razorpay_payment_id'] ? 'ID: ' . substr($grp['razorpay_payment_id'], 0, 15) . '...' : '' ?></span>
                            <?php elseif ($grp && ($grp['auto_pay'] ?? '') === 'YES' && in_array($booking['subscription_status'] ?? '', ['authenticated', 'active'])): ?>
                                <span class="inline-flex items-center gap-1 text-xs text-indigo-600 font-medium"><i class="fas fa-bolt"></i> Auto-Debit Scheduled</span>
                            <?php else: ?>
                                <a href="final_payment.php?booking_id=<?= $booking['id'] ?>&type=GROUP"
                                   class="btn-primary text-xs px-3 py-1.5">Pay Now</a>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <!-- ONE TO ONE -->
                    <?php $oto = $payments['ONE_TO_ONE'] ?? null; ?>
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="p-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-comments text-purple-500 text-xs"></i>
                                </div>
                                <span class="font-medium text-gray-700">1:1 Counselling</span>
                            </div>
                        </td>
                        <td class="p-3 text-gray-500"><?= date('d M Y', strtotime($booking['booked_date'])) ?></td>
                        <td class="p-3 font-semibold text-gray-800">₹<?= number_format(FEE_ONE_TO_ONE, 2) ?></td>
                        <td class="p-3"><?= statusBadge($oto['payment_status'] ?? 'PENDING') ?></td>
                        <td class="p-3">
                            <?php if (($oto['payment_status'] ?? '') === 'PAID'): ?>
                                <span class="text-gray-400 text-xs"><?= $oto['razorpay_payment_id'] ? 'ID: ' . substr($oto['razorpay_payment_id'], 0, 15) . '...' : '' ?></span>
                            <?php elseif ($oto && ($oto['auto_pay'] ?? '') === 'YES' && in_array($booking['subscription_status'] ?? '', ['authenticated', 'active'])): ?>
                                <span class="inline-flex items-center gap-1 text-xs text-indigo-600 font-medium"><i class="fas fa-bolt"></i> Auto-Debit Scheduled</span>
                            <?php else: ?>
                                <a href="final_payment.php?booking_id=<?= $booking['id'] ?>&type=ONE_TO_ONE"
                                   class="btn-primary text-xs px-3 py-1.5">Pay Now</a>
                            <?php endif; ?>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
    </div>

    <!-- INFO BOX -->
    <div class="glass-card-static p-5 mt-6 animate-fade-in-up" style="animation-delay: 0.3s;">
        <div class="flex items-start gap-3">
            <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-info-circle text-blue-500"></i>
            </div>
            <div>
                <p class="font-semibold text-gray-800 text-sm">How Auto-Pay Works</p>
                <ul class="text-sm text-gray-500 mt-1 space-y-1">
                    <li>• Session fees are automatically deducted <strong>1 day before</strong> each session</li>
                    <li>• You'll receive an email receipt for each payment</li>
                    <li>• If auto-debit fails, you can pay manually using the "Pay Now" button</li>
                    <li>• You can cancel auto-pay anytime from this page</li>
                </ul>
            </div>
        </div>
    </div>

    <?php else: ?>
    <div class="glass-card-static p-10 text-center animate-fade-in-up">
        <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-receipt text-gray-400 text-3xl"></i>
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-2">No Bookings Yet</h2>
        <p class="text-gray-500 mb-6">Book a session first to see your payment history.</p>
        <a href="booknow.php" class="btn-primary"><i class="fas fa-calendar-plus"></i> Book Now</a>
    </div>
    <?php endif; ?>

</main>

<?php include 'footer.php'; ?>
</body>
</html>