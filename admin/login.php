<?php
session_start();
require '../db.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $pass  = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email=?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($pass, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['username'];
        header("Location: dashboard.php");
        exit;
    } else {
        $err = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login | TEM</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="../style.css">
</head>

<body class="bg-gradient-page min-h-screen flex items-center justify-center px-4">

<div class="floating-blob w-72 h-72 from-indigo-600 to-purple-600" style="top:-5%;left:-5%"></div>
<div class="floating-blob w-60 h-60 from-purple-500 to-pink-500" style="bottom:5%;right:-5%;animation-delay:-3s"></div>

<div class="glass-card-static max-w-md w-full p-10 text-center animate-fade-in-up relative z-10">

  <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-700 flex items-center justify-center mx-auto mb-6 shadow-lg shadow-indigo-200">
    <i class="fas fa-shield-halved text-white text-2xl"></i>
  </div>

  <h1 class="text-2xl font-extrabold text-gray-800 mb-1">Admin Portal</h1>
  <p class="text-gray-500 text-sm mb-6">Sign in to manage the platform</p>

  <?php if ($err): ?>
  <div class="alert alert-error flex items-center gap-2 text-left">
    <i class="fas fa-exclamation-circle"></i> <?= $err ?>
  </div>
  <?php endif; ?>

  <form method="POST" class="space-y-4 text-left">
    <div>
      <label class="form-label">Email</label>
      <input type="email" name="email" required class="form-input" placeholder="admin@tem.in">
    </div>
    <div>
      <label class="form-label">Password</label>
      <input type="password" name="password" required class="form-input" placeholder="••••••••">
    </div>
    <button class="btn-primary w-full py-3.5">
      <i class="fas fa-sign-in-alt"></i> Sign In
    </button>
  </form>

</div>

</body>
</html>
