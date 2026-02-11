<?php
session_start();
require '../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Stats
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalCounsellors = $pdo->query("SELECT COUNT(*) FROM counsellors")->fetchColumn();
$totalEvents = $pdo->query("SELECT COUNT(*) FROM schedule_master")->fetchColumn();
$totalBookings = $pdo->query("SELECT COUNT(*) FROM student_bookings")->fetchColumn();
$paidBookings = $pdo->query("SELECT COUNT(*) FROM student_bookings WHERE payment_status='paid'")->fetchColumn();

// Recent bookings
$recentBookings = $pdo->query("
    SELECT sb.*, s.username, s.email, sm.event_name
    FROM student_bookings sb
    JOIN students s ON s.id = sb.student_id
    JOIN schedule_master sm ON sm.id = sb.schedule_id
    ORDER BY sb.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | TEM</title>
<script src="https://cdn.tailwindcss.com"></script>
<?php $isSubDir = true; ?>
<?php include 'sidebar.php'; // includes CSS ?>
</head>

<body class="bg-gray-50 min-h-screen flex">

<!-- sidebar already included -->

<!-- MAIN -->
<div class="flex-1 overflow-y-auto">

  <!-- HEADER -->
  <header class="bg-white border-b px-8 py-6">
    <h1 class="text-2xl font-extrabold text-gray-800">Dashboard</h1>
    <p class="text-sm text-gray-500 mt-1">Welcome back, Admin</p>
  </header>

  <div class="p-8">

    <!-- STAT CARDS -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

      <div class="stat-card animate-fade-in-up">
        <div class="stat-icon bg-indigo-50 text-indigo-600"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-value"><?= $totalStudents ?></div>
        <div class="stat-label">Students</div>
      </div>

      <div class="stat-card animate-fade-in-up delay-100">
        <div class="stat-icon bg-purple-50 text-purple-600"><i class="fas fa-user-tie"></i></div>
        <div class="stat-value"><?= $totalCounsellors ?></div>
        <div class="stat-label">Counsellors</div>
      </div>

      <div class="stat-card animate-fade-in-up delay-200">
        <div class="stat-icon bg-blue-50 text-blue-600"><i class="fas fa-calendar-alt"></i></div>
        <div class="stat-value"><?= $totalEvents ?></div>
        <div class="stat-label">Events</div>
      </div>

      <div class="stat-card animate-fade-in-up delay-300">
        <div class="stat-icon bg-green-50 text-green-600"><i class="fas fa-check-circle"></i></div>
        <div class="stat-value"><?= $paidBookings ?><span class="text-gray-400 text-base font-normal">/<?= $totalBookings ?></span></div>
        <div class="stat-label">Paid Bookings</div>
      </div>

    </div>

    <!-- RECENT BOOKINGS -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden animate-fade-in-up delay-400">
      <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
        <div>
          <h2 class="text-lg font-bold text-gray-800">Recent Bookings</h2>
          <p class="text-sm text-gray-500">Latest student registrations</p>
        </div>
        <a href="students.php" class="text-indigo-600 text-sm font-semibold hover:underline">View All →</a>
      </div>

      <?php if (!$recentBookings): ?>
        <div class="p-8 text-center text-gray-400">No bookings yet</div>
      <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>Student</th>
            <th>Event</th>
            <th>Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentBookings as $b): ?>
          <tr>
            <td>
              <div class="flex items-center gap-3">
                <div class="avatar avatar-sm bg-gradient-to-br from-indigo-500 to-purple-500">
                  <?= strtoupper(substr($b['username'], 0, 1)) ?>
                </div>
                <div>
                  <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($b['username']) ?></p>
                  <p class="text-xs text-gray-400"><?= htmlspecialchars($b['email']) ?></p>
                </div>
              </div>
            </td>
            <td class="text-sm"><?= htmlspecialchars($b['event_name']) ?></td>
            <td class="text-sm text-gray-500"><?= date('d M Y', strtotime($b['created_at'])) ?></td>
            <td>
              <span class="badge <?= $b['payment_status']=='paid' ? 'badge-success' : 'badge-warning' ?>">
                <?= ucfirst($b['payment_status']) ?>
              </span>
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
