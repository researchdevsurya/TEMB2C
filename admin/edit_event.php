<?php
session_start();
require '../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? die("Invalid ID");
$stmt = $pdo->prepare("SELECT * FROM schedule_master WHERE id=?");
$stmt->execute([$id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$event) die("Event not found");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        UPDATE schedule_master SET
            event_name=?,
            psychometric_date_1=?, psychometric_date_2=?,
            session1_name=?, session1_slot_1=?, session1_slot_2=?,
            session2_name=?, session2_slot_1=?, session2_slot_2=?,
            counselling_from=?, counselling_to=?,
            counsellor_name=?
        WHERE id=?
    ");
    $stmt->execute([
        $_POST['event_name'],
        $_POST['psy_date_1'], $_POST['psy_date_2'],
        $_POST['session1_name'], $_POST['session1_slot_1'], $_POST['session1_slot_2'],
        $_POST['session2_name'], $_POST['session2_slot_1'], $_POST['session2_slot_2'],
        $_POST['counselling_from'], $_POST['counselling_to'],
        $_POST['counsellor_name'],
        $id
    ]);

    $_SESSION['success_msg'] = "Event updated successfully!";
    header("Location: events.php");
    exit;
}

// Map both old and new column names
$en = $event['event_name'];
$p1 = $event['psychometric_date_1'] ?? $event['psychometric_date1'] ?? '';
$p2 = $event['psychometric_date_2'] ?? $event['psychometric_date2'] ?? '';
$s1n = $event['session1_name'] ?? $event['block1_session1'] ?? '';
$s1d1 = $event['session1_slot_1'] ?? $event['block1_date1'] ?? '';
$s1d2 = $event['session1_slot_2'] ?? $event['block1_date2'] ?? '';
$s2n = $event['session2_name'] ?? $event['block2_session1'] ?? '';
$s2d1 = $event['session2_slot_1'] ?? $event['block2_date1'] ?? '';
$s2d2 = $event['session2_slot_2'] ?? $event['block2_date2'] ?? '';
$cf = $event['counselling_from'] ?? '';
$ct = $event['counselling_to'] ?? '';
$cn = $event['counsellor_name'] ?? $event['counsellors_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Event | Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<?php include 'sidebar.php'; ?>
</head>

<body class="bg-gray-50 min-h-screen flex">

<div class="flex-1 overflow-y-auto">

  <header class="bg-white border-b px-8 py-6">
    <h1 class="text-2xl font-extrabold text-gray-800 flex items-center gap-3">
      <i class="fas fa-edit text-indigo-600"></i> Edit Event
    </h1>
    <p class="text-sm text-gray-500 mt-1">Update event: <?= htmlspecialchars($en) ?></p>
  </header>

  <div class="p-8 max-w-4xl">
    <form method="POST" class="space-y-8">

      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center"><i class="fas fa-tag text-indigo-600 text-sm"></i></div>
          Event Details
        </h3>
        <label class="form-label">Event Name</label>
        <input name="event_name" required class="form-input" value="<?= htmlspecialchars($en) ?>">
      </div>

      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center"><i class="fas fa-brain text-blue-600 text-sm"></i></div>
          Psychometric Test Dates
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div><label class="form-label">Date Option A</label><input type="date" name="psy_date_1" required value="<?= $p1 ?>" class="form-input"></div>
          <div><label class="form-label">Date Option B</label><input type="date" name="psy_date_2" required value="<?= $p2 ?>" class="form-input"></div>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center"><i class="fas fa-users text-green-600 text-sm"></i></div>
          Group Session 1
        </h3>
        <div class="mb-4"><label class="form-label">Session Name</label><input name="session1_name" required value="<?= htmlspecialchars($s1n) ?>" class="form-input"></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div><label class="form-label">Date A</label><input type="date" name="session1_slot_1" required value="<?= $s1d1 ?>" class="form-input"></div>
          <div><label class="form-label">Date B</label><input type="date" name="session1_slot_2" required value="<?= $s1d2 ?>" class="form-input"></div>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center"><i class="fas fa-user-friends text-emerald-600 text-sm"></i></div>
          Group Session 2
        </h3>
        <div class="mb-4"><label class="form-label">Session Name</label><input name="session2_name" required value="<?= htmlspecialchars($s2n) ?>" class="form-input"></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div><label class="form-label">Date A</label><input type="date" name="session2_slot_1" required value="<?= $s2d1 ?>" class="form-input"></div>
          <div><label class="form-label">Date B</label><input type="date" name="session2_slot_2" required value="<?= $s2d2 ?>" class="form-input"></div>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center"><i class="fas fa-comments text-purple-600 text-sm"></i></div>
          1:1 Counselling Range
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div><label class="form-label">From</label><input type="date" name="counselling_from" required value="<?= $cf ?>" class="form-input"></div>
          <div><label class="form-label">To</label><input type="date" name="counselling_to" required value="<?= $ct ?>" class="form-input"></div>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center"><i class="fas fa-user-tie text-amber-600 text-sm"></i></div>
          Counsellor
        </h3>
        <label class="form-label">Counsellor Name</label>
        <input name="counsellor_name" required value="<?= htmlspecialchars($cn) ?>" class="form-input">
      </div>

      <div class="flex gap-3">
        <button class="btn-primary flex-1 py-4"><i class="fas fa-save"></i> Update Event</button>
        <a href="events.php" class="btn-secondary flex-1 py-4 text-center">Cancel</a>
      </div>

    </form>
  </div>
</div>

</body>
</html>