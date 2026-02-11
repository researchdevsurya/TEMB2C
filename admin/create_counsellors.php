<?php
session_start();
require '../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!$username || !$email || !$password) {
        $err = "All fields are required.";
    } else {
        // Check existing
        $chk = $pdo->prepare("SELECT id FROM counsellors WHERE email=?");
        $chk->execute([$email]);
        if ($chk->rowCount()) {
            $err = "A counsellor with this email already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO counsellors (username, email, password) VALUES (?,?,?)")
                ->execute([$username, $email, $hash]);
            $msg = "Counsellor created successfully!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Counsellor | Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<?php include 'sidebar.php'; ?>
</head>

<body class="bg-gray-50 min-h-screen flex">

<div class="flex-1 overflow-y-auto">

  <header class="bg-white border-b px-8 py-6">
    <h1 class="text-2xl font-extrabold text-gray-800 flex items-center gap-3">
      <i class="fas fa-user-plus text-purple-600"></i> Add Counsellor
    </h1>
    <p class="text-sm text-gray-500 mt-1">Create a new counsellor account</p>
  </header>

  <div class="p-8 max-w-lg">

    <?php if ($msg): ?>
    <div class="alert alert-success flex items-center gap-2"><i class="fas fa-check-circle"></i> <?= $msg ?></div>
    <?php endif; ?>

    <?php if ($err): ?>
    <div class="alert alert-error flex items-center gap-2"><i class="fas fa-exclamation-circle"></i> <?= $err ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
      <form method="POST" class="space-y-5">

        <div>
          <label class="form-label">Full Name</label>
          <input name="username" required class="form-input" placeholder="Dr. Jane Smith">
        </div>

        <div>
          <label class="form-label">Email</label>
          <input type="email" name="email" required class="form-input" placeholder="counsellor@example.com">
        </div>

        <div>
          <label class="form-label">Password</label>
          <input type="password" name="password" required class="form-input" placeholder="Min 6 characters" minlength="6">
        </div>

        <button class="btn-primary w-full py-3.5">
          <i class="fas fa-user-plus"></i> Create Counsellor
        </button>

      </form>
    </div>

  </div>
</div>

</body>
</html>