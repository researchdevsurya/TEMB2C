<?php
session_start();
require 'db.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['student_id'];

/* FETCH STUDENT */
$stmt = $pdo->prepare("SELECT username, std FROM students WHERE id=?");
$stmt->execute([$student_id]);
$user = $stmt->fetch();
if (!$user) die("Student not found");

/* STORE FOR SIDEBAR */
$_SESSION['student_name'] = $user['username'];
$_SESSION['student_std']  = $user['std'];

/* FETCH LATEST BOOKING */
$stmt = $pdo->prepare("
    SELECT sb.*, sb.id as bookid, sm.*
    FROM student_bookings sb
    JOIN schedule_master sm ON sm.id = sb.schedule_id
    WHERE sb.student_id = ?
    ORDER BY sb.id DESC
    LIMIT 1
");
$stmt->execute([$student_id]);
$booking = $stmt->fetch();

/* FETCH PAYMENTS (SOURCE OF TRUTH) */
$paymentMap = [];
if ($booking) {
    $p = $pdo->prepare("
        SELECT payment_for, payment_status, scheduled_date
        FROM payments
        WHERE student_id = ?
    ");
    $p->execute([$student_id]);
    foreach ($p->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $paymentMap[$row['payment_for']] = $row;
    }
}

/* HELPERS */
function isPaid($map, $key) {
    return isset($map[$key]) && strtoupper($map[$key]['payment_status']) === 'PAID';
}

function canPay($date) {
    if (!$date) return false;
    $today = new DateTime(date('Y-m-d'));
    $d = new DateTime($date);
    $diff = (int)$today->diff($d)->format('%r%a');
    return $diff <= 1 && $diff >= 0;
}

function tokenPaid($booking) {
    return isset($booking['payment_status']) && strtolower($booking['payment_status']) === 'paid';
}

function formatDate($d) {
    if (!$d) return '—';
    return date('d M Y', strtotime($d));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include 'header.php'; ?>
<title>Dashboard | TEM Portal</title>
</head>

<body class="bg-gradient-page">

<?php include 'sidebar.php'; ?>

<main class="max-w-6xl mx-auto px-4 sm:px-6 py-8">

<!-- WELCOME HERO -->
<div class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-700 rounded-2xl p-8 mb-8 animate-fade-in-up shadow-xl shadow-indigo-200/50">
  <div class="absolute inset-0 overflow-hidden">
    <div class="absolute -right-10 -top-10 w-40 h-40 rounded-full bg-white/10"></div>
    <div class="absolute right-20 bottom-0 w-24 h-24 rounded-full bg-white/5"></div>
    <div class="absolute left-1/2 -bottom-10 w-60 h-60 rounded-full bg-purple-500/20"></div>
  </div>
  <div class="relative z-10">
    <div class="flex items-center gap-2 mb-2">
      <span class="text-white/60 text-sm">👋 Welcome back</span>
    </div>
    <h1 class="text-3xl font-extrabold text-white mb-1">
      <?= htmlspecialchars($user['username']) ?>
    </h1>
    <p class="text-white/60">
      Class <?= htmlspecialchars($user['std']) ?> • TEM Career Counselling Portal
    </p>
  </div>
</div>

<?php if (!$booking): ?>

<!-- NO BOOKING -->
<div class="glass-card-static p-10 text-center animate-fade-in-up delay-100">
  <div class="w-20 h-20 rounded-full bg-indigo-50 flex items-center justify-center mx-auto mb-6">
    <i class="fas fa-calendar-plus text-indigo-500 text-3xl"></i>
  </div>
  <h2 class="text-xl font-bold text-gray-800 mb-2">No Active Booking</h2>
  <p class="text-gray-500 mb-6 max-w-md mx-auto">
    Book your counselling session to get started with psychometric testing, group sessions, and 1:1 career guidance.
  </p>
  <a href="booknow.php" class="btn-primary text-base px-8 py-3.5">
    <i class="fas fa-rocket"></i> Book Your Session
  </a>
</div>

<?php else: ?>

<!-- QUICK STATS -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 animate-fade-in-up delay-100">

  <div class="stat-card">
    <div class="stat-icon bg-blue-50 text-blue-600"><i class="fas fa-brain"></i></div>
    <div class="stat-label mb-1">Psychometric</div>
    <?php if (isPaid($paymentMap,'PSYCHOMETRIC')): ?>
      <span class="badge badge-success"><i class="fas fa-check-circle"></i> Paid</span>
    <?php else: ?>
      <span class="badge badge-warning"><i class="fas fa-clock"></i> Pending</span>
    <?php endif; ?>
  </div>

  <div class="stat-card">
    <div class="stat-icon bg-green-50 text-green-600"><i class="fas fa-users"></i></div>
    <div class="stat-label mb-1">Group Sessions</div>
    <?php if (isPaid($paymentMap,'GROUP')): ?>
      <span class="badge badge-success"><i class="fas fa-check-circle"></i> Paid</span>
    <?php else: ?>
      <span class="badge badge-warning"><i class="fas fa-clock"></i> Pending</span>
    <?php endif; ?>
  </div>

  <div class="stat-card">
    <div class="stat-icon bg-purple-50 text-purple-600"><i class="fas fa-user-tie"></i></div>
    <div class="stat-label mb-1">1:1 Session</div>
    <?php if (isPaid($paymentMap,'ONE_TO_ONE')): ?>
      <span class="badge badge-success"><i class="fas fa-check-circle"></i> Paid</span>
    <?php else: ?>
      <span class="badge badge-warning"><i class="fas fa-clock"></i> Pending</span>
    <?php endif; ?>
  </div>

  <div class="stat-card">
    <div class="stat-icon bg-amber-50 text-amber-600"><i class="fas fa-receipt"></i></div>
    <div class="stat-label mb-1">Token Payment</div>
    <?php if (tokenPaid($booking)): ?>
      <span class="badge badge-success"><i class="fas fa-check-circle"></i> Done</span>
    <?php else: ?>
      <span class="badge badge-danger"><i class="fas fa-exclamation-circle"></i> Unpaid</span>
    <?php endif; ?>
  </div>

</div>

<!-- SESSION PROGRESS -->
<div class="glass-card-static p-8 mb-8 animate-fade-in-up delay-200">
  <h3 class="section-title mb-6">My Session Progress</h3>

  <div class="grid md:grid-cols-3 gap-6">

    <!-- PSYCHOMETRIC -->
    <div class="border border-gray-100 rounded-2xl p-6 bg-gradient-to-br from-blue-50/50 to-white">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
          <i class="fas fa-brain text-blue-600"></i>
        </div>
        <h4 class="font-bold text-gray-800">Psychometric Test</h4>
      </div>
      <p class="text-sm text-gray-500 mb-3">Date: <span class="font-semibold text-gray-700"><?= formatDate($booking['selected_psychometric_date']) ?></span></p>

      <?php if (isPaid($paymentMap,'PSYCHOMETRIC')): ?>
        <span class="badge badge-success"><i class="fas fa-check-circle"></i> Payment Complete</span>
      <?php elseif (tokenPaid($booking)): ?>
        <span class="badge badge-warning mb-3"><i class="fas fa-clock"></i> Payment Pending</span>
        <a href="final_payment.php?booking_id=<?= $booking['bookid'] ?>&type=PSYCHOMETRIC"
           class="btn-primary w-full py-2.5 text-sm mt-2">Pay Now</a>
      <?php else: ?>
        <span class="text-gray-400 text-sm">Complete token payment first</span>
      <?php endif; ?>
    </div>

    <!-- GROUP -->
    <div class="border border-gray-100 rounded-2xl p-6 bg-gradient-to-br from-green-50/50 to-white">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
          <i class="fas fa-users text-green-600"></i>
        </div>
        <h4 class="font-bold text-gray-800">Group Sessions</h4>
      </div>
      <p class="text-sm text-gray-500 mb-1"><?= htmlspecialchars($booking['group_session1']) ?> – <?= formatDate($booking['group_session1_date']) ?></p>
      <p class="text-sm text-gray-500 mb-3"><?= htmlspecialchars($booking['group_session2']) ?> – <?= formatDate($booking['group_session2_date']) ?></p>

      <?php if (isPaid($paymentMap,'GROUP')): ?>
        <span class="badge badge-success"><i class="fas fa-check-circle"></i> Payment Complete</span>
      <?php elseif (tokenPaid($booking) && isset($paymentMap['GROUP']) && canPay($paymentMap['GROUP']['scheduled_date'])): ?>
        <span class="badge badge-warning mb-3"><i class="fas fa-clock"></i> Payment Pending</span>
        <a href="final_payment.php?booking_id=<?= $booking['bookid'] ?>&type=GROUP"
           class="btn-primary w-full py-2.5 text-sm mt-2">Pay Now</a>
      <?php elseif (!tokenPaid($booking)): ?>
        <span class="text-gray-400 text-sm">Complete token payment first</span>
      <?php else: ?>
        <span class="text-gray-400 text-sm">Pay available 1 day before session</span>
      <?php endif; ?>
    </div>

    <!-- ONE TO ONE -->
    <div class="border border-gray-100 rounded-2xl p-6 bg-gradient-to-br from-purple-50/50 to-white">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center">
          <i class="fas fa-user-tie text-purple-600"></i>
        </div>
        <h4 class="font-bold text-gray-800">1:1 Counselling</h4>
      </div>
      <p class="text-sm text-gray-500 mb-1">Date: <span class="font-semibold text-gray-700"><?= formatDate($booking['booked_date']) ?></span></p>
      <p class="text-sm text-gray-500 mb-3">Time: <span class="font-semibold text-gray-700"><?= $booking['one_to_one_slot'] ?></span></p>

      <?php if (isPaid($paymentMap,'ONE_TO_ONE')): ?>
        <span class="badge badge-success"><i class="fas fa-check-circle"></i> Payment Complete</span>
      <?php elseif (tokenPaid($booking) && isset($paymentMap['ONE_TO_ONE']) && canPay($paymentMap['ONE_TO_ONE']['scheduled_date'])): ?>
        <span class="badge badge-warning mb-3"><i class="fas fa-clock"></i> Payment Pending</span>
        <a href="final_payment.php?booking_id=<?= $booking['bookid'] ?>&type=ONE_TO_ONE"
           class="btn-primary w-full py-2.5 text-sm mt-2">Pay Now</a>
      <?php elseif (!tokenPaid($booking)): ?>
        <span class="text-gray-400 text-sm">Complete token payment first</span>
      <?php else: ?>
        <span class="text-gray-400 text-sm">Pay available 1 day before session</span>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php endif; ?>

<!-- ABOUT SECTION -->
<div class="glass-card-static p-8 animate-fade-in-up delay-300">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
    <div>
      <h3 class="section-title mb-2">About TEM Academy</h3>
      <p class="text-gray-500 max-w-xl">
        TEM provides comprehensive career guidance through psychometric analysis, interactive group counselling, and personalized one-on-one sessions with expert counsellors.
      </p>
    </div>
    <?php if (!$booking): ?>
    <a href="booknow.php" class="btn-primary flex-shrink-0">
      <i class="fas fa-calendar-plus"></i> Book Now
    </a>
    <?php endif; ?>
  </div>
</div>

</main>

<?php include 'footer.php'; ?>
</body>
</html>
