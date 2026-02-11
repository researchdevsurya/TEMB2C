<?php
session_start();
require '../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$search = $_GET['search'] ?? '';
$classFilter = $_GET['class'] ?? '';

$sql = "SELECT id, username, email, std, contact_number, created_at FROM students WHERE 1";
$params = [];

if ($search) {
    $sql .= " AND (username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($classFilter) {
    $sql .= " AND std = ?";
    $params[] = $classFilter;
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Students | Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<?php include 'sidebar.php'; ?>
</head>

<body class="bg-gray-50 min-h-screen flex">

<div class="flex-1 overflow-y-auto">

  <header class="bg-white border-b px-8 py-6">
    <h1 class="text-2xl font-extrabold text-gray-800 flex items-center gap-3">
      <i class="fas fa-user-graduate text-indigo-600"></i> Students
    </h1>
    <p class="text-sm text-gray-500 mt-1"><?= count($students) ?> registered students</p>
  </header>

  <div class="p-8">

    <!-- SEARCH & FILTER -->
    <form method="GET" class="flex flex-col sm:flex-row gap-3 mb-6">
      <div class="relative flex-1">
        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or email…"
               class="form-input pl-11">
      </div>
      <select name="class" class="form-input w-auto min-w-[140px]">
        <option value="">All Classes</option>
        <?php for ($i = 8; $i <= 12; $i++): ?>
          <option value="<?= $i ?>" <?= $classFilter==$i?'selected':'' ?>>Class <?= $i ?></option>
        <?php endfor; ?>
      </select>
      <button class="btn-primary px-6"><i class="fas fa-filter"></i> Filter</button>
    </form>

    <!-- TABLE -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <?php if (!$students): ?>
        <div class="p-8 text-center text-gray-400">No students found</div>
      <?php else: ?>
      <div class="overflow-x-auto">
        <table class="data-table">
          <thead>
            <tr>
              <th>Student</th>
              <th>Class</th>
              <th>Contact</th>
              <th>Joined</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $s): ?>
            <tr>
              <td>
                <div class="flex items-center gap-3">
                  <div class="avatar avatar-sm bg-gradient-to-br from-indigo-500 to-purple-500">
                    <?= strtoupper(substr($s['username'], 0, 1)) ?>
                  </div>
                  <div>
                    <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($s['username']) ?></p>
                    <p class="text-xs text-gray-400"><?= htmlspecialchars($s['email']) ?></p>
                  </div>
                </div>
              </td>
              <td><span class="badge badge-info">Class <?= $s['std'] ?></span></td>
              <td class="text-sm text-gray-500"><?= htmlspecialchars($s['contact_number']) ?></td>
              <td class="text-sm text-gray-500"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
              <td>
                <a href="student_detail.php?id=<?= $s['id'] ?>" class="text-indigo-600 text-sm font-semibold hover:underline">View</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>

</body>
</html>