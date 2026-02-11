<?php
session_start();
require '../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    $stmt = $pdo->prepare("
        INSERT INTO schedule_master (
            event_name,
            psychometric_date_1, psychometric_date_2,
            session1_name, session1_slot_1, session1_slot_2,
            session2_name, session2_slot_1, session2_slot_2,
            counselling_from, counselling_to,
            counsellor_name
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    $stmt->execute([
        $_POST['event_name'],
        $_POST['psy_date_1'],
        $_POST['psy_date_2'],
        $_POST['session1_name'],
        $_POST['session1_slot_1'],
        $_POST['session1_slot_2'],
        $_POST['session2_name'],
        $_POST['session2_slot_1'],
        $_POST['session2_slot_2'],
        $_POST['counselling_from'],
        $_POST['counselling_to'],
        $_POST['counsellor_name']
    ]);

    $_SESSION['success_msg'] = "Event created successfully!";
    header("Location: events.php");
    exit;
}

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Event | Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<?php include 'sidebar.php'; ?>
</head>

<body class="bg-gray-50 min-h-screen flex">

<div class="flex-1 overflow-y-auto">

  <header class="bg-white border-b px-8 py-6">
    <h1 class="text-2xl font-extrabold text-gray-800 flex items-center gap-3">
      <i class="fas fa-plus-circle text-indigo-600"></i> Create Event
    </h1>
    <p class="text-sm text-gray-500 mt-1">Set up a new counselling event schedule</p>
  </header>

  <div class="p-8 max-w-4xl">
    <form method="POST" class="space-y-8">

      <!-- EVENT NAME -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center"><i class="fas fa-tag text-indigo-600 text-sm"></i></div>
          Event Details
        </h3>
        <label class="form-label">Event Name</label>
        <input name="event_name" required class="form-input" placeholder="e.g. February 2026 Public Counselling Event">
      </div>

      <!-- PSYCHOMETRIC -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center"><i class="fas fa-brain text-blue-600 text-sm"></i></div>
          Psychometric Test Dates
        </h3>
        <p class="text-sm text-gray-500 mb-4">Student will choose ONE of the two dates</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="form-label">Date Option A</label>
            <input type="date" name="psy_date_1" required value="<?= $today ?>" class="form-input">
          </div>
          <div>
            <label class="form-label">Date Option B</label>
            <input type="date" name="psy_date_2" required value="<?= $today ?>" class="form-input">
          </div>
        </div>
      </div>

      <!-- GROUP SESSION 1 -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center"><i class="fas fa-users text-green-600 text-sm"></i></div>
          Group Session 1
        </h3>
        <div class="mb-4">
          <label class="form-label">Session Name</label>
          <input name="session1_name" placeholder="e.g. Group Session 1" required value="Group Session 1" class="form-input">
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="form-label">Date Option A</label>
            <input type="date" name="session1_slot_1" required value="<?= $today ?>" class="form-input">
          </div>
          <div>
            <label class="form-label">Date Option B</label>
            <input type="date" name="session1_slot_2" required value="<?= $today ?>" class="form-input">
          </div>
        </div>
      </div>

      <!-- GROUP SESSION 2 -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center"><i class="fas fa-user-friends text-emerald-600 text-sm"></i></div>
          Group Session 2
        </h3>
        <div class="mb-4">
          <label class="form-label">Session Name</label>
          <input name="session2_name" placeholder="e.g. Group Session 2" required value="Group Session 2" class="form-input">
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="form-label">Date Option A</label>
            <input type="date" name="session2_slot_1" required value="<?= $today ?>" class="form-input">
          </div>
          <div>
            <label class="form-label">Date Option B</label>
            <input type="date" name="session2_slot_2" required value="<?= $today ?>" class="form-input">
          </div>
        </div>
      </div>

      <!-- 1:1 COUNSELLING -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center"><i class="fas fa-comments text-purple-600 text-sm"></i></div>
          1:1 Counselling Date Range
        </h3>
        <p class="text-sm text-gray-500 mb-4">Students can pick a date within this range for their 1:1 session</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="form-label">From Date</label>
            <input type="date" name="counselling_from" required value="<?= $today ?>" class="form-input">
          </div>
          <div>
            <label class="form-label">To Date</label>
            <input type="date" name="counselling_to" required value="<?= $today ?>" class="form-input">
          </div>
        </div>
      </div>

      <!-- COUNSELLOR -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center"><i class="fas fa-user-tie text-amber-600 text-sm"></i></div>
          Counsellor Info
        </h3>
        <label class="form-label">Counsellor Name (Reference)</label>
        <input name="counsellor_name" placeholder="e.g. David Sir and Medhavi Mam" required class="form-input">
      </div>

      <!-- SUBMIT -->
      <button class="btn-primary w-full py-4 text-base">
        <i class="fas fa-check-circle"></i> Create Event
      </button>

    </form>
  </div>
</div>

</body>
</html>