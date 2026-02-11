<?php
session_start();
require '../db.php';

if (!isset($_SESSION['counsellor_id'])) {
    header("Location: login.php");
    exit;
}

$cid = $_SESSION['counsellor_id'];
$name = $_SESSION['counsellor_name'] ?? 'Counsellor';

// Stats
$totalSlots = $pdo->prepare("SELECT COUNT(*) FROM counsellor_slots WHERE counsellor_id=?");
$totalSlots->execute([$cid]);
$totalSlots = $totalSlots->fetchColumn();

$bookedSlots = $pdo->prepare("SELECT COUNT(*) FROM counsellor_slots WHERE counsellor_id=? AND booked=1");
$bookedSlots->execute([$cid]);
$bookedSlots = $bookedSlots->fetchColumn();

$availSlots = $totalSlots - $bookedSlots;

// Upcoming
$upcoming = $pdo->prepare("
    SELECT cs.slot_date, cs.start_time, s.username as student_name
    FROM counsellor_slots cs
    LEFT JOIN student_bookings sb ON sb.counsellor_slot_id = cs.id
    LEFT JOIN students s ON s.id = sb.student_id
    WHERE cs.counsellor_id=? AND cs.booked=1 AND cs.slot_date >= CURDATE()
    ORDER BY cs.slot_date, cs.start_time
    LIMIT 5
");
$upcoming->execute([$cid]);
$upcomingList = $upcoming->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | Counsellor</title>
<script src="https://cdn.tailwindcss.com"></script>
<?php include 'sidebar.php'; ?>
</head>

<body class="bg-gray-50 min-h-screen flex">

<div class="flex-1 overflow-y-auto">

  <!-- WELCOME -->
  <div class="bg-gradient-to-r from-purple-700 via-fuchsia-700 to-pink-700 px-8 py-8">
    <div class="relative">
      <p class="text-white/60 text-sm mb-1">👋 Welcome back</p>
      <h1 class="text-3xl font-extrabold text-white"><?= htmlspecialchars($name) ?></h1>
      <p class="text-white/50 mt-1">Manage your availability and appointments</p>
    </div>
  </div>

  <div class="p-8">

    <!-- STAT CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
      <div class="stat-card animate-fade-in-up">
        <div class="stat-icon bg-purple-50 text-purple-600"><i class="fas fa-calendar-alt"></i></div>
        <div class="stat-value"><?= $totalSlots ?></div>
        <div class="stat-label">Total Slots</div>
      </div>
      <div class="stat-card animate-fade-in-up delay-100">
        <div class="stat-icon bg-green-50 text-green-600"><i class="fas fa-check-circle"></i></div>
        <div class="stat-value"><?= $bookedSlots ?></div>
        <div class="stat-label">Booked</div>
      </div>
      <div class="stat-card animate-fade-in-up delay-200">
        <div class="stat-icon bg-blue-50 text-blue-600"><i class="fas fa-clock"></i></div>
        <div class="stat-value"><?= $availSlots ?></div>
        <div class="stat-label">Available</div>
      </div>
    </div>

    <!-- UPCOMING -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden animate-fade-in-up delay-300">
      <div class="px-6 py-5 border-b border-gray-100">
        <h2 class="text-lg font-bold text-gray-800">Upcoming Appointments</h2>
        <p class="text-sm text-gray-500">Next booked sessions</p>
      </div>

      <?php if (!$upcomingList): ?>
        <div class="p-8 text-center text-gray-400">
          <i class="fas fa-calendar-check text-3xl mb-3"></i>
          <p>No upcoming appointments</p>
        </div>
      <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Time</th>
            <th>Student</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($upcomingList as $u): ?>
          <tr>
            <td class="font-semibold text-sm"><?= date('d M Y', strtotime($u['slot_date'])) ?></td>
            <td class="text-sm"><?= htmlspecialchars($u['start_time']) ?></td>
            <td>
              <div class="flex items-center gap-2">
                <div class="avatar avatar-sm bg-gradient-to-br from-purple-500 to-pink-500">
                  <?= $u['student_name'] ? strtoupper(substr($u['student_name'], 0, 1)) : '?' ?>
                </div>
                <span class="text-sm font-medium"><?= htmlspecialchars($u['student_name'] ?? 'Unknown') ?></span>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

  </div>
</div>

</body>
</html>