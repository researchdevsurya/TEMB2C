<?php
session_start();
require 'db.php';

if (isset($_SESSION['student_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM students WHERE email=?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            throw new Exception("Invalid email or password");
        }

        $_SESSION['student_id'] = $user['id'];
        $_SESSION['student_name'] = $user['username'];
        $_SESSION['student_std'] = $user['std'];
        header("Location: dashboard.php");
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include 'header.php'; ?>
<title>Login | TEM Academy</title>
<style>
  .login-blob {
    position: fixed;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.25;
    animation: blobFloat 8s ease-in-out infinite;
  }
  @keyframes blobFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -20px) scale(1.05); }
    66% { transform: translate(-20px, 15px) scale(0.95); }
  }
  .login-card {
    animation: fadeInUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) both;
  }
  @keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
  }
</style>
</head>

<body class="bg-gradient-auth min-h-screen flex items-center justify-center px-4 relative overflow-hidden">

<!-- BLOBS -->
<div class="login-blob w-96 h-96 bg-purple-500 -top-20 -left-20"></div>
<div class="login-blob w-80 h-80 bg-indigo-400 bottom-10 right-10" style="animation-delay: 3s;"></div>

<!-- BACK LINK -->
<a href="index.php" class="fixed top-6 left-6 z-20 text-white/60 hover:text-white flex items-center gap-2 text-sm font-medium transition">
  <i class="fas fa-arrow-left"></i> Home
</a>

<!-- LOGIN CARD -->
<div class="login-card relative z-10 w-full max-w-md">

  <!-- LOGO -->
  <div class="text-center mb-8">
    <div class="w-16 h-16 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center mx-auto mb-4 border border-white/10">
      <i class="fas fa-graduation-cap text-white text-2xl"></i>
    </div>
    <h1 class="text-2xl font-bold text-white">Welcome Back</h1>
    <p class="text-white/50 text-sm mt-1">Sign in to your student account</p>
  </div>

  <!-- FORM CARD -->
  <div class="bg-white/10 backdrop-blur-xl border border-white/15 rounded-2xl p-8 shadow-2xl">

    <?php if ($error): ?>
    <div class="bg-red-500/15 border border-red-400/20 text-red-200 px-4 py-3 rounded-xl text-sm font-medium mb-5 flex items-center gap-2">
      <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" onsubmit="showLoader()">

      <div class="mb-5">
        <label class="block text-white/70 text-sm font-semibold mb-2">Email Address</label>
        <div class="relative">
          <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-white/30"></i>
          <input id="email" name="email" type="email"  required
                 placeholder="you@example.com"
                 class="w-full bg-white/10 border border-white/15 text-white placeholder-white/30 rounded-xl py-3.5 pl-11 pr-4 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/20 outline-none transition">
        </div>
      </div>

      <div class="mb-5">
        <label class="block text-white/70 text-sm font-semibold mb-2">Password</label>
        <div class="relative">
          <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-white/30"></i>
          <input name="password" type="password" required
                 placeholder="••••••••"
                 class="w-full bg-white/10 border border-white/15 text-white placeholder-white/30 rounded-xl py-3.5 pl-11 pr-4 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/20 outline-none transition">
        </div>
      </div>

      <label class="flex items-center gap-2 mb-6 cursor-pointer">
        <input type="checkbox" id="remember" class="w-4 h-4 rounded border-white/20 bg-white/10 text-indigo-500 focus:ring-indigo-400">
        <span class="text-white/50 text-sm">Remember my email</span>
      </label>

      <button id="loginBtn" class="w-full bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-400 hover:to-purple-500 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 transition-all flex items-center justify-center gap-2">
        <span id="btnText">Sign In</span>
        <span id="btnSpinner" class="hidden"><span class="spinner"></span></span>
        <i id="btnArrow" class="fas fa-arrow-right text-sm"></i>
      </button>

    </form>
  </div>

  <!-- FOOTER -->
  <p class="text-center text-white/40 text-sm mt-6">
    Don't have an account?
    <a href="signup.php" class="text-indigo-300 font-semibold hover:text-indigo-200 transition">Create here</a>
  </p>

</div>

<script>
// Remember email
window.onload = function(){
    const email = localStorage.getItem("remember_email");
    if(email){
        document.getElementById("email").value = email;
        document.getElementById("remember").checked = true;
    }
};

function showLoader(){
    const email = document.getElementById("email").value;
    const remember = document.getElementById("remember").checked;
    if(remember) localStorage.setItem("remember_email", email);
    else localStorage.removeItem("remember_email");

    document.getElementById("btnText").textContent = "Signing in...";
    document.getElementById("btnArrow").classList.add("hidden");
    document.getElementById("btnSpinner").classList.remove("hidden");
    document.getElementById("loginBtn").disabled = true;
    document.getElementById("loginBtn").classList.add("opacity-80");
}
</script>

</body>
</html>
