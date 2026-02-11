<?php
session_start();
require '../db.php';

if (!isset($_SESSION['counsellor_id'])) {
    header("Location: login.php");
    exit;
}

$cid = $_SESSION['counsellor_id'];

// Get events for filter
$events = $pdo->query("SELECT id, event_name FROM schedule_master ORDER BY id DESC")->fetchAll();
$selectedEvent = $_GET['event_id'] ?? ($events[0]['id'] ?? '');

// CSV export
if (isset($_GET['export']) && $selectedEvent) {
    $slots = $pdo->prepare("
        SELECT cs.slot_date, cs.start_time, cs.end_time, cs.booked,
               s.username as student_name, s.email as student_email
        FROM counsellor_slots cs
        LEFT JOIN student_bookings sb ON sb.counsellor_slot_id = cs.id
        LEFT JOIN students s ON s.id = sb.student_id
        WHERE cs.counsellor_id=? AND cs.event_id=?
        ORDER BY cs.slot_date, cs.start_time
    ");
    $slots->execute([$cid, $selectedEvent]);
    $data = $slots->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="appointments_export.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Start', 'End', 'Status', 'Student', 'Email']);
    foreach ($data as $d) {
        fputcsv($out, [
            $d['slot_date'], $d['start_time'], $d['end_time'],
            $d['booked'] ? 'Booked' : 'Available',
            $d['student_name'] ?? '', $d['student_email'] ?? ''
        ]);
    }
    fclose($out);
    exit;
}

// Get slots
$slots = [];
if ($selectedEvent) {
    $stmt = $pdo->prepare("
        SELECT cs.*, s.username as student_name
        FROM counsellor_slots cs
        LEFT JOIN student_bookings sb ON sb.counsellor_slot_id = cs.id
        LEFT JOIN students s ON s.id = sb.student_id
        WHERE cs.counsellor_id=? AND cs.event_id=?
        ORDER BY cs.slot_date, cs.start_time
    ");
    $stmt->execute([$cid, $selectedEvent]);
    $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Group by date
$grouped = [];
foreach ($slots as $s) {
    $grouped[$s['slot_date']][] = $s;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Appointments | Counsellor</title>
<script src="https://cdn.tailwindcss.com"></script>
<?php include 'sidebar.php'; ?>
</head>

<body class="bg-gray-50 min-h-screen flex">

<div class="flex-1 overflow-y-auto">

  <header class="bg-white border-b px-8 py-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-extrabold text-gray-800 flex items-center gap-3">
        <i class="fas fa-calendar-check text-purple-600"></i> Appointments
      </h1>
      <p class="text-sm text-gray-500 mt-1">View and manage your booked slots</p>
    </div>
    <?php if ($selectedEvent): ?>
    <a href="?event_id=<?= $selectedEvent ?>&export=1" class="btn-secondary text-sm">
      <i class="fas fa-file-csv"></i> Export CSV
    </a>
    <?php endif; ?>
  </header>

  <div class="p-8">

    <!-- EVENT SELECTOR -->
    <form method="GET" class="mb-6">
      <div class="flex items-center gap-3">
        <select name="event_id" class="form-input max-w-xs" onchange="this.form.submit()">
          <?php foreach ($events as $ev): ?>
            <option value="<?= $ev['id'] ?>" <?= $selectedEvent==$ev['id']?'selected':'' ?>>
              <?= htmlspecialchars($ev['event_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>

    <?php if (!$grouped): ?>
      <div class="bg-white rounded-2xl p-10 text-center shadow-sm">
        <i class="fas fa-calendar-xmark text-gray-300 text-4xl mb-4"></i>
        <p class="text-gray-500">No slots found for this event.</p>
        <a href="upload.php" class="btn-primary mt-4"><i class="fas fa-upload"></i> Upload Slots</a>
      </div>
    <?php else: ?>

    <div class="space-y-6">
      <?php foreach ($grouped as $date => $daySlots): ?>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
          <h3 class="font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-calendar-day text-purple-500"></i>
            <?= date('l, d M Y', strtotime($date)) ?>
          </h3>
          <p class="text-xs text-gray-400 mt-1"><?= count($daySlots) ?> slots</p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 p-6">
          <?php foreach ($daySlots as $slot): ?>
          <div class="rounded-xl p-3 text-center border transition <?= $slot['booked']
            ? 'bg-purple-50 border-purple-200'
            : 'bg-gray-50 border-gray-100' ?>">
            <p class="font-bold text-sm <?= $slot['booked'] ? 'text-purple-700' : 'text-gray-600' ?>">
              <?= htmlspecialchars($slot['start_time']) ?>
            </p>
            <?php if ($slot['booked']): ?>
              <p class="text-xs text-purple-500 mt-1 truncate">
                <i class="fas fa-user text-[10px]"></i>
                <?= htmlspecialchars($slot['student_name'] ?? 'Booked') ?>
              </p>
            <?php else: ?>
              <p class="text-xs text-green-500 mt-1">
                <i class="fas fa-check-circle text-[10px]"></i> Open
              </p>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php endif; ?>
  </div>
</div>

</body>
</html>