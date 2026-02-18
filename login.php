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
        require_once 'security_helper.php';
        $security = new SecurityHelper($pdo);
        $ip = SecurityHelper::getClientIP();

        // 1. Check Rate Limit
        if (!$security->checkRateLimit($ip, 'login', 5, 15)) {
            throw new Exception("Too many failed attempts. Please try again after 15 minutes.");
        }

        $email = trim($_POST['email']);

        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM students WHERE email=?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $security->logFailure($ip, 'login');
            throw new Exception("Invalid email or password");
        }
        
        // 2. Clear attempts on success
        $security->clearAttempts($ip, 'login');


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
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4 font-[Inter]">

<!-- BACK LINK -->
<a href="index.php" class="fixed top-6 left-6 z-20 text-gray-500 hover:text-blue-600 flex items-center gap-2 text-sm font-medium transition">
  <i class="fas fa-arrow-left"></i> Home
</a>

<div class="w-full max-w-4xl bg-white rounded-3xl shadow-xl overflow-hidden flex flex-col md:flex-row animate-fade-in-up border border-gray-100">

  <!-- LEFT SIDE: BRANDING / INFO (Hidden on mobile) -->
  <div class="hidden md:flex flex-col justify-center items-center w-5/12 bg-blue-600 p-10 text-white relative overflow-hidden">
      <!-- Decorative circles -->
      <div class="absolute top-[-10%] right-[-10%] w-60 h-60 rounded-full bg-white opacity-10 blur-3xl"></div>
      <div class="absolute bottom-[-10%] left-[-10%] w-60 h-60 rounded-full bg-white opacity-10 blur-3xl"></div>
      
      <div class="relative z-10 text-center">
          <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-inner border border-white/30">
              <i class="fas fa-graduation-cap text-3xl text-white"></i>
          </div>
          <h2 class="text-3xl font-bold mb-4">Welcome Back!</h2>
          <p class="text-blue-100 text-sm leading-relaxed mb-6">
              Sign in to continue your journey with TEM Academy. Access your dashboard, counselling sessions, and more.
          </p>
      </div>
  </div>

  <!-- RIGHT SIDE: FORM -->
  <div class="w-full md:w-7/12 p-8 md:p-12 flex flex-col justify-center">
    
    <div class="text-center md:text-left mb-8">
      <h1 class="text-2xl font-bold text-gray-900">Sign In</h1>
      <p class="text-gray-500 text-sm mt-1">Enter your credentials to access your account</p>
    </div>

    <?php if ($error): ?>
    <div class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl text-sm font-medium mb-5 flex items-center gap-2">
      <i class="fas fa-exclamation-circle text-red-500"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" onsubmit="showLoader()" class="space-y-5">

      <div>
        <label class="block text-gray-700 text-xs font-semibold mb-1.5">Email Address</label>
        <div class="relative">
          <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
          <input id="email" name="email" type="email" required
                 class="w-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 rounded-lg py-2.5 pl-11 pr-4 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition text-sm"
                 placeholder="you@example.com">
        </div>
      </div>

      <div>
        <label class="block text-gray-700 text-xs font-semibold mb-1.5">Password</label>
        <div class="relative">
          <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
          <input name="password" type="password" required
                 class="w-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 rounded-lg py-2.5 pl-11 pr-4 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition text-sm"
                 placeholder="••••••••">
        </div>
      </div>

      <div class="flex items-center justify-between">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" id="remember" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span class="text-gray-500 text-sm">Remember me</span>
        </label>
        <a href="forgot_password.php" class="text-sm text-blue-600 hover:text-blue-700 font-medium hover:underline">Forgot Password?</a>
      </div>

      <button id="loginBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-600/30 hover:shadow-blue-600/50 transition-all transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
        <span id="btnText">Sign In</span>
        <span id="btnSpinner" class="hidden"><span class="spinner"></span></span>
        <i id="btnArrow" class="fas fa-arrow-right text-sm"></i>
      </button>

    </form>

    <div class="text-center mt-8">
      <p class="text-gray-500 text-sm">
        Don't have an account?
        <a href="signup.php" class="text-blue-600 font-bold hover:text-blue-700 transition underline decoration-2 decoration-transparent hover:decoration-blue-600">Create Account</a>
      </p>
    </div>

  </div>

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
    
    // Add disabled state visual
    const btn = document.getElementById("loginBtn");
    btn.disabled = true;
    btn.classList.add("opacity-75", "cursor-not-allowed");
}
</script>

</body>
</html>
