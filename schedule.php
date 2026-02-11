<?php
session_start();
require 'db.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

/* GET STUDENT BOOKING + EVENT DETAILS */
$stmt = $pdo->prepare("
    SELECT sb.*, sb.id as bookid, sm.*
    FROM student_bookings sb
    JOIN schedule_master sm ON sm.id = sb.schedule_id
    WHERE sb.student_id = ?
    ORDER BY sb.id DESC
    LIMIT 1
");
$stmt->execute([$_SESSION['student_id']]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

function fmt($d) { return $d ? date('l, d M Y', strtotime($d)) : '—'; }
function fmtShort($d) { return $d ? date('d M Y', strtotime($d)) : '—'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include 'header.php'; ?>
<title>My Booking | TEM Portal</title>
</head>

<body class="bg-gradient-page">

<?php include 'sidebar.php'; ?>

<main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

  <div class="flex items-center justify-between mb-8 animate-fade-in-up">
    <div>
      <h1 class="text-2xl font-extrabold text-gray-800">My Booking Details</h1>
      <p class="text-gray-500 text-sm mt-1">Your complete counselling schedule</p>
    </div>
  </div>

  <?php if (!$booking): ?>
    <div class="glass-card-static p-10 text-center animate-fade-in-up delay-100">
      <div class="w-20 h-20 rounded-full bg-indigo-50 flex items-center justify-center mx-auto mb-6">
        <i class="fas fa-calendar-plus text-indigo-500 text-3xl"></i>
      </div>
      <h2 class="text-xl font-bold text-gray-800 mb-2">No Booking Yet</h2>
      <p class="text-gray-500 mb-6">You haven't booked any counselling event yet.</p>
      <a href="booknow.php" class="btn-primary"><i class="fas fa-rocket"></i> Book Your Session</a>
    </div>
  <?php else: ?>

    <!-- EVENT HEADER -->
    <div class="glass-card-static p-6 mb-6 animate-fade-in-up delay-100">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-star text-white"></i>
          </div>
          <div>
            <h2 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($booking['event_name']) ?></h2>
            <p class="text-sm text-gray-500">Booked on <?= fmtShort($booking['created_at']) ?></p>
          </div>
        </div>
        <span class="<?= $booking['payment_status']=='paid' ? 'badge badge-success' : 'badge badge-warning' ?> self-start">
          <i class="fas <?= $booking['payment_status']=='paid' ? 'fa-check-circle' : 'fa-clock' ?>"></i>
          <?= $booking['payment_status']=='paid' ? 'Token Paid' : 'Payment Pending' ?>
        </span>
      </div>
    </div>

    <!-- TIMELINE -->
    <div class="space-y-4 animate-fade-in-up delay-200">

      <!-- PSYCHOMETRIC -->
      <div class="glass-card-static p-6 border-l-4 border-l-blue-500">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-brain text-blue-600 text-lg"></i>
          </div>
          <div class="flex-1">
            <h3 class="text-lg font-bold text-gray-800 mb-1">🧠 Psychometric Test</h3>
            <p class="text-sm text-gray-500 mb-3">Scientific assessment to identify your strengths, interests, and career direction.</p>
            <div class="bg-blue-50 rounded-xl px-4 py-3 inline-block">
              <p class="text-sm text-blue-600">
                <i class="fas fa-calendar-day mr-2"></i>
                <span class="font-bold"><?= fmt($booking['selected_psychometric_date']) ?></span>
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- GROUP SESSION 1 -->
      <div class="glass-card-static p-6 border-l-4 border-l-green-500">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-xl bg-green-50 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-users text-green-600 text-lg"></i>
          </div>
          <div class="flex-1">
            <h3 class="text-lg font-bold text-gray-800 mb-1">👥 <?= htmlspecialchars($booking['group_session1']) ?></h3>
            <p class="text-sm text-gray-500 mb-3">Interactive group session covering careers, skills and future planning.</p>
            <div class="bg-green-50 rounded-xl px-4 py-3 inline-block">
              <p class="text-sm text-green-600">
                <i class="fas fa-calendar-day mr-2"></i>
                <span class="font-bold"><?= fmt($booking['group_session1_date']) ?></span>
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- GROUP SESSION 2 -->
      <div class="glass-card-static p-6 border-l-4 border-l-emerald-500">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-user-friends text-emerald-600 text-lg"></i>
          </div>
          <div class="flex-1">
            <h3 class="text-lg font-bold text-gray-800 mb-1">👥 <?= htmlspecialchars($booking['group_session2']) ?></h3>
            <p class="text-sm text-gray-500 mb-3">Continued group guidance and career direction workshop.</p>
            <div class="bg-emerald-50 rounded-xl px-4 py-3 inline-block">
              <p class="text-sm text-emerald-600">
                <i class="fas fa-calendar-day mr-2"></i>
                <span class="font-bold"><?= fmt($booking['group_session2_date']) ?></span>
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- 1:1 COUNSELLING -->
      <div class="glass-card-static p-6 border-l-4 border-l-purple-500">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-comments text-purple-600 text-lg"></i>
          </div>
          <div class="flex-1">
            <h3 class="text-lg font-bold text-gray-800 mb-1">🗓 1:1 Counselling</h3>
            <p class="text-sm text-gray-500 mb-3">Personal counselling session focused on your individual career path.</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div class="bg-purple-50 rounded-xl px-4 py-3">
                <p class="text-xs text-purple-500 mb-1">Date</p>
                <p class="text-sm font-bold text-purple-700"><?= fmtShort($booking['booked_date']) ?></p>
              </div>
              <div class="bg-purple-50 rounded-xl px-4 py-3">
                <p class="text-xs text-purple-500 mb-1">Time Slot</p>
                <p class="text-sm font-bold text-purple-700"><?= htmlspecialchars($booking['one_to_one_slot']) ?></p>
              </div>
              <div class="bg-purple-50 rounded-xl px-4 py-3">
                <p class="text-xs text-purple-500 mb-1">Counsellor</p>
                <p class="text-sm font-bold text-purple-700"><?= htmlspecialchars($booking['counsellor_name'] ?? '—') ?></p>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- PAYMENT CTA -->
    <?php if ($booking['payment_status'] != 'paid'): ?>
    <div class="glass-card-static p-6 mt-6 bg-gradient-to-r from-amber-50 to-yellow-50 border border-amber-100 animate-fade-in-up delay-300">
      <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
            <i class="fas fa-exclamation-triangle text-amber-600"></i>
          </div>
          <div>
            <p class="font-bold text-amber-800">Token Payment Required</p>
            <p class="text-sm text-amber-600">Complete payment to confirm your booking.</p>
          </div>
        </div>
        <a href="payment.php?booking_id=<?= $booking['bookid'] ?>" class="btn-primary bg-gradient-to-r from-amber-500 to-orange-500 shadow-amber-200/50">
          <i class="fas fa-credit-card"></i> Pay Now
        </a>
      </div>
    </div>
    <?php endif; ?>

  <?php endif; ?>

</main>

<?php include 'footer.php'; ?>
</body>
</html>