<?php
session_start();
require '../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$events = $pdo->query("SELECT * FROM schedule_master ORDER BY id DESC")->fetchAll();
$successMsg = $_SESSION['success_msg'] ?? null;
unset($_SESSION['success_msg']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Events | Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<?php include 'sidebar.php'; ?>
</head>

<body class="bg-gray-50 min-h-screen flex">

<div class="flex-1 overflow-y-auto">

  <header class="bg-white border-b px-8 py-6 flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-extrabold text-gray-800 flex items-center gap-3">
        <i class="fas fa-calendar-alt text-indigo-600"></i> Events
      </h1>
      <p class="text-sm text-gray-500 mt-1">Manage counselling event schedules</p>
    </div>
    <a href="create_event.php" class="btn-primary"><i class="fas fa-plus"></i> New Event</a>
  </header>

  <div class="p-8">

    <?php if ($successMsg): ?>
    <div class="alert alert-success flex items-center gap-2 animate-fade-in">
      <i class="fas fa-check-circle"></i> <?= $successMsg ?>
    </div>
    <?php endif; ?>

    <?php if (!$events): ?>
    <div class="bg-white rounded-2xl p-10 text-center shadow-sm">
      <i class="fas fa-calendar-plus text-gray-300 text-4xl mb-4"></i>
      <p class="text-gray-500">No events created yet.</p>
      <a href="create_event.php" class="btn-primary mt-4"><i class="fas fa-plus"></i> Create Event</a>
    </div>
    <?php else: ?>

    <div class="grid gap-6">
      <?php foreach ($events as $e): ?>
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition animate-fade-in-up">

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center flex-shrink-0">
              <i class="fas fa-star text-white"></i>
            </div>
            <div>
              <h3 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($e['event_name']) ?></h3>
              <p class="text-sm text-gray-400">Created <?= date('d M Y', strtotime($e['created_at'])) ?></p>
            </div>
          </div>
          <div class="flex gap-2">
            <a href="edit_event.php?id=<?= $e['id'] ?>" class="btn-secondary text-sm px-4 py-2">
              <i class="fas fa-edit"></i> Edit
            </a>
            <a href="delete_event.php?id=<?= $e['id'] ?>" onclick="return confirm('Delete this event?')" class="btn-danger text-sm px-4 py-2">
              <i class="fas fa-trash"></i> Delete
            </a>
          </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <div class="bg-blue-50 rounded-xl px-4 py-3">
            <p class="text-xs text-blue-500 mb-1">Psychometric</p>
            <p class="text-sm font-bold text-blue-700"><?= $e['psychometric_date_1'] ?? $e['psychometric_date1'] ?? '—' ?></p>
            <p class="text-sm font-bold text-blue-700"><?= $e['psychometric_date_2'] ?? $e['psychometric_date2'] ?? '—' ?></p>
          </div>
          <div class="bg-green-50 rounded-xl px-4 py-3">
            <p class="text-xs text-green-500 mb-1">Group Session 1</p>
            <p class="text-sm font-bold text-green-700"><?= $e['session1_slot_1'] ?? $e['block1_date1'] ?? '—' ?></p>
            <p class="text-sm font-bold text-green-700"><?= $e['session1_slot_2'] ?? $e['block1_date2'] ?? '—' ?></p>
          </div>
          <div class="bg-emerald-50 rounded-xl px-4 py-3">
            <p class="text-xs text-emerald-500 mb-1">Group Session 2</p>
            <p class="text-sm font-bold text-emerald-700"><?= $e['session2_slot_1'] ?? $e['block2_date1'] ?? '—' ?></p>
            <p class="text-sm font-bold text-emerald-700"><?= $e['session2_slot_2'] ?? $e['block2_date2'] ?? '—' ?></p>
          </div>
          <div class="bg-purple-50 rounded-xl px-4 py-3">
            <p class="text-xs text-purple-500 mb-1">1:1 Range</p>
            <p class="text-sm font-bold text-purple-700"><?= $e['counselling_from'] ?? '—' ?></p>
            <p class="text-sm font-bold text-purple-700"><?= $e['counselling_to'] ?? '—' ?></p>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php endif; ?>
  </div>
</div>

</body>
</html>
