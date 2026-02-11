<?php
session_start();
require '../db.php';

if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}

/* FETCH BOOKINGS WITH STUDENT + EVENT INFO */
$stmt = $pdo->query("
SELECT 
  sb.id,
  sb.booked_date,
  sb.one_to_one_slot,
  sb.group_session1,
  sb.group_session2,
  sb.selected_psychometric_date,
  sb.counsellor_name,
  sb.payment_status,
  s.username,
  sm.event_name
FROM student_bookings sb
JOIN students s ON s.id = sb.student_id
JOIN schedule_master sm ON sm.id = sb.schedule_id
ORDER BY sb.booked_date ASC
");

$rows = $stmt->fetchAll();

/* BUILD CALENDAR EVENTS */
$events = [];
foreach($rows as $r){
    // Combine date + slot time to ISO datetime
    $start = $r['booked_date'] . 'T' . $r['one_to_one_slot'] . ':00';

    // 30 min duration
    $end = date('Y-m-d\TH:i:s', strtotime($start . ' +30 minutes'));

    $events[] = [
        'id'    => $r['id'],
        'title' => $r['username'] . ' • ' . $r['one_to_one_slot'],
        'start' => $start,
        'end'   => $end,
        'extendedProps' => [
            'student'     => $r['username'],
            'event'       => $r['event_name'],
            'psy'         => $r['selected_psychometric_date'],
            'group1'      => $r['group_session1'],
            'group2'      => $r['group_session2'],
            'counsellor'  => $r['counsellor_name'],
            'status'      => $r['payment_status']
        ]
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Admin Calendar</title>
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- FullCalendar CSS/JS -->
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
</head>

<body class="bg-gray-100 min-h-screen flex">

<?php include 'sidebar.php'; ?>

<div class="flex-1 p-6">

  <h1 class="text-2xl font-bold mb-4">Booking Timeline Calendar</h1>

  <!-- INSIGHTS -->
  <div class="grid md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-4 rounded-xl shadow">
      <p class="text-gray-500">Total Bookings</p>
      <h2 class="text-2xl font-bold"><?= count($rows) ?></h2>
    </div>

    <div class="bg-white p-4 rounded-xl shadow">
      <p class="text-gray-500">Paid</p>
      <h2 class="text-2xl font-bold">
        <?= count(array_filter($rows, fn($r)=>$r['payment_status']=='paid')) ?>
      </h2>
    </div>

    <div class="bg-white p-4 rounded-xl shadow">
      <p class="text-gray-500">Pending</p>
      <h2 class="text-2xl font-bold">
        <?= count(array_filter($rows, fn($r)=>$r['payment_status']!='paid')) ?>
      </h2>
    </div>

    <div class="bg-white p-4 rounded-xl shadow">
      <p class="text-gray-500">Counsellors</p>
      <h2 class="text-2xl font-bold">
        <?= count(array_unique(array_column($rows,'counsellor_name'))) ?>
      </h2>
    </div>
  </div>

  <!-- CALENDAR -->
  <div class="bg-white p-4 rounded-xl shadow">
    <div id="calendar"></div>
  </div>

</div>

<!-- MODAL -->
<div id="modal" class="fixed z-50 inset-0 bg-black/50 hidden items-center justify-center">
  <div class="bg-white p-6 rounded-xl w-[400px]">
    <h3 class="text-lg font-bold mb-3">Booking Details</h3>
    <div id="modalContent" class="space-y-2 text-sm"></div>
    <button onclick="closeModal()" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded">Close</button>
  </div>
</div>

<script>
const events = <?= json_encode($events) ?>;

document.addEventListener('DOMContentLoaded', function() {

  const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
    initialView: 'dayGridMonth',
    height: 750,

    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },

    events: events,

    eventClick: function(info) {
      const e = info.event.extendedProps;

      document.getElementById('modalContent').innerHTML = `
        <b>Student:</b> ${e.student}<br>
        <b>Event:</b> ${e.event}<br>
        <b>Psychometric:</b> ${e.psy}<br>
        <b>Group 1:</b> ${e.group1}<br>
        <b>Group 2:</b> ${e.group2}<br>
        <b>Counsellor:</b> ${e.counsellor}<br>
        <b>Payment:</b> ${e.status}
      `;

      document.getElementById('modal').classList.remove('hidden');
      document.getElementById('modal').classList.add('flex');
    }
  });

  calendar.render();
});

function closeModal(){
  document.getElementById('modal').classList.add('hidden');
}
</script>

</body>
</html>
