<?php
session_start();
// If already logged in, redirect to dashboard
if (isset($_SESSION['student_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include 'header.php'; ?>
<title>TEM Academy – Career Counselling Portal</title>
<style>
  .hero-blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.3;
    animation: blobFloat 8s ease-in-out infinite;
  }
  @keyframes blobFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -20px) scale(1.05); }
    66% { transform: translate(-20px, 15px) scale(0.95); }
  }
  .feature-card { transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
  .feature-card:hover { transform: translateY(-6px); }
</style>
</head>

<body class="bg-gradient-auth relative overflow-x-hidden">

<!-- BLOBS -->
<div class="hero-blob w-96 h-96 bg-purple-500 top-10 -left-20" style="position:fixed;"></div>
<div class="hero-blob w-80 h-80 bg-indigo-400 bottom-10 right-10" style="position:fixed; animation-delay: 3s;"></div>
<div class="hero-blob w-64 h-64 bg-blue-500 top-1/2 left-1/3" style="position:fixed; animation-delay: 5s;"></div>

<div class="relative z-10 min-h-screen flex flex-col">

  <!-- NAV -->
  <nav class="flex items-center justify-between px-6 md:px-12 py-5">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
        <i class="fas fa-graduation-cap text-white text-lg"></i>
      </div>
      <span class="text-xl font-bold text-white">TEM Academy</span>
    </div>
    <div class="flex items-center gap-3">
      <a href="login.php" class="text-white/80 hover:text-white font-medium px-4 py-2 transition">Login</a>
      <a href="signup.php" class="bg-white/15 backdrop-blur-sm text-white font-semibold px-5 py-2.5 rounded-xl hover:bg-white/25 transition border border-white/20">
        Sign Up
      </a>
    </div>
  </nav>

  <!-- HERO -->
  <main class="flex-1 flex items-center justify-center px-6 py-12">
    <div class="max-w-4xl mx-auto text-center animate-fade-in-up">

      <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full text-white/80 text-sm font-medium mb-8 border border-white/10">
        <i class="fas fa-sparkles"></i>
        Professional Career Counselling Platform
      </div>

      <h1 class="text-4xl md:text-6xl font-extrabold text-white mb-6 leading-tight">
        Discover Your
        <span class="bg-gradient-to-r from-amber-300 via-yellow-300 to-orange-300 bg-clip-text text-transparent">
          Career Path
        </span>
      </h1>

      <p class="text-lg md:text-xl text-white/70 max-w-2xl mx-auto mb-10 leading-relaxed">
        Book psychometric tests, group sessions, and one-on-one counselling 
        with expert mentors. Your future starts here.
      </p>

      <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
        <a href="signup.php" class="bg-white text-indigo-700 font-bold px-8 py-4 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all text-lg">
          Get Started Free <i class="fas fa-arrow-right ml-2"></i>
        </a>
        <a href="login.php" class="text-white font-semibold px-8 py-4 rounded-xl border-2 border-white/30 hover:bg-white/10 transition-all text-lg">
          Student Login
        </a>
      </div>

      <!-- FEATURES GRID -->
      <div class="grid md:grid-cols-3 gap-6 animate-fade-in-up delay-300">

        <div class="feature-card bg-white/10 backdrop-blur-md border border-white/10 rounded-2xl p-6 text-left">
          <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center mb-4">
            <i class="fas fa-brain text-blue-300 text-xl"></i>
          </div>
          <h3 class="text-white font-bold text-lg mb-2">Psychometric Test</h3>
          <p class="text-white/60 text-sm">Scientific assessment to identify your strengths, interests and ideal career direction.</p>
        </div>

        <div class="feature-card bg-white/10 backdrop-blur-md border border-white/10 rounded-2xl p-6 text-left">
          <div class="w-12 h-12 rounded-xl bg-green-500/20 flex items-center justify-center mb-4">
            <i class="fas fa-users text-green-300 text-xl"></i>
          </div>
          <h3 class="text-white font-bold text-lg mb-2">Group Sessions</h3>
          <p class="text-white/60 text-sm">Interactive group counselling covering careers, skills, and future planning strategies.</p>
        </div>

        <div class="feature-card bg-white/10 backdrop-blur-md border border-white/10 rounded-2xl p-6 text-left">
          <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center mb-4">
            <i class="fas fa-user-tie text-purple-300 text-xl"></i>
          </div>
          <h3 class="text-white font-bold text-lg mb-2">1:1 Counselling</h3>
          <p class="text-white/60 text-sm">Personalized sessions with expert counsellors focused on your unique career path.</p>
        </div>

      </div>

    </div>
  </main>

  <!-- FOOTER -->
  <footer class="text-center py-6 text-white/40 text-sm">
    © <?= date('Y') ?> TEM Academy. All rights reserved.
  </footer>

</div>

</body>
</html>
