<?php
session_start();
require '../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$counsellors = $pdo->query("SELECT * FROM counsellors ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Counsellors | Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<?php include 'sidebar.php'; ?>
</head>

<body class="bg-gray-50 min-h-screen flex">

<div class="flex-1 overflow-y-auto">

  <header class="bg-white border-b px-8 py-6 flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-extrabold text-gray-800 flex items-center gap-3">
        <i class="fas fa-user-tie text-purple-600"></i> Counsellors
      </h1>
      <p class="text-sm text-gray-500 mt-1"><?= count($counsellors) ?> counsellors registered</p>
    </div>
    <a href="create_counsellors.php" class="btn-primary"><i class="fas fa-user-plus"></i> Add Counsellor</a>
  </header>

  <div class="p-8">

    <?php if (!$counsellors): ?>
      <div class="bg-white rounded-2xl p-10 text-center shadow-sm">
        <i class="fas fa-user-tie text-gray-300 text-4xl mb-4"></i>
        <p class="text-gray-500">No counsellors registered yet.</p>
        <a href="create_counsellors.php" class="btn-primary mt-4"><i class="fas fa-user-plus"></i> Add Counsellor</a>
      </div>
    <?php else: ?>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($counsellors as $c): ?>
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition">
        <div class="flex items-center gap-4 mb-4">
          <div class="w-14 h-14 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xl font-bold">
            <?= strtoupper(substr($c['username'], 0, 1)) ?>
          </div>
          <div>
            <h3 class="font-bold text-gray-800"><?= htmlspecialchars($c['username']) ?></h3>
            <p class="text-sm text-gray-500"><?= htmlspecialchars($c['email']) ?></p>
          </div>
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-400">
          <i class="fas fa-clock"></i> Joined <?= date('d M Y', strtotime($c['created_at'])) ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php endif; ?>
  </div>
</div>

</body>
</html>