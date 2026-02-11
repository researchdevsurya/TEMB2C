<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$name = $_SESSION['student_name'] ?? 'Student';
$std  = $_SESSION['student_std'] ?? '';
$initial = strtoupper(substr($name, 0, 1));

$currentPage = basename($_SERVER['PHP_SELF']);

function isActive($page) {
    global $currentPage;
    return $currentPage === $page;
}

// Generate a gradient color based on the initial
$colors = ['from-indigo-500 to-purple-600', 'from-blue-500 to-cyan-500', 'from-emerald-500 to-teal-600', 'from-orange-500 to-red-500', 'from-pink-500 to-rose-600'];
$avatarGrad = $colors[ord($initial) % count($colors)];
?>

<!-- TOP NAVBAR -->
<header class="sticky top-0 z-50 bg-white/80 backdrop-blur-xl border-b border-gray-100/50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6">
    <div class="flex justify-between items-center h-16">

      <!-- LOGO -->
      <a href="dashboard.php" class="flex items-center gap-2.5 group">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center shadow-md shadow-indigo-200 group-hover:shadow-lg group-hover:shadow-indigo-300 transition-all">
          <i class="fas fa-graduation-cap text-white text-sm"></i>
        </div>
        <span class="text-lg font-bold bg-gradient-to-r from-indigo-700 to-purple-700 bg-clip-text text-transparent">TEM Portal</span>
      </a>

      <!-- DESKTOP NAV -->
      <nav class="hidden md:flex items-center gap-1">
        <?php
        $navItems = [
          ['dashboard.php', 'fa-home', 'Dashboard'],
          ['booknow.php', 'fa-calendar-plus', 'Book Now'],
          ['schedule.php', 'fa-calendar-check', 'My Booking'],
          ['payments.php', 'fa-credit-card', 'Payments'],
          ['profile.php', 'fa-user-circle', 'Profile'],
        ];
        foreach ($navItems as $item):
          $active = isActive($item[0]);
        ?>
        <a href="<?= $item[0] ?>"
           class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all <?= $active
             ? 'bg-indigo-50 text-indigo-700 shadow-sm'
             : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' ?>">
          <i class="fas <?= $item[1] ?> text-xs <?= $active ? 'text-indigo-500' : '' ?>"></i>
          <?= $item[2] ?>
        </a>
        <?php endforeach; ?>
      </nav>

      <!-- USER + MOBILE -->
      <div class="flex items-center gap-3">
        <!-- USER PILL (desktop) -->
        <div class="hidden md:flex items-center gap-2.5 bg-gray-50 rounded-full pl-1 pr-4 py-1">
          <div class="w-8 h-8 rounded-full bg-gradient-to-br <?= $avatarGrad ?> flex items-center justify-center text-white font-bold text-xs shadow-sm">
            <?= $initial ?>
          </div>
          <div class="leading-tight">
            <p class="text-xs font-semibold text-gray-700"><?= htmlspecialchars($name) ?></p>
            <p class="text-[10px] text-gray-400"><?= $std ? "Class $std" : "Student" ?></p>
          </div>
        </div>

        <!-- LOGOUT (desktop) -->
        <a href="logout.php" class="hidden md:flex items-center gap-1.5 text-gray-400 hover:text-red-500 text-sm transition" title="Logout">
          <i class="fas fa-sign-out-alt"></i>
        </a>

        <!-- MOBILE MENU BUTTON -->
        <button id="mobileMenuBtn" class="md:hidden w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition">
          <i class="fas fa-bars"></i>
        </button>
      </div>

    </div>
  </div>
</header>

<!-- MOBILE MENU -->
<div id="mobileMenu" class="fixed inset-0 z-[60] hidden">
  <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

  <div class="absolute right-0 top-0 w-72 h-full bg-white shadow-2xl p-6 animate-slide-in">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-lg font-bold text-gray-800">Menu</h2>
      <button id="closeMobileMenu" class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-gray-200">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <!-- USER -->
    <div class="flex items-center gap-3 mb-6 p-3 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl">
      <div class="w-10 h-10 rounded-full bg-gradient-to-br <?= $avatarGrad ?> flex items-center justify-center text-white font-bold text-sm">
        <?= $initial ?>
      </div>
      <div>
        <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($name) ?></p>
        <p class="text-xs text-gray-500"><?= $std ? "Class $std" : "Student" ?></p>
      </div>
    </div>

    <nav class="space-y-1.5">
      <?php foreach ($navItems as $item):
        $active = isActive($item[0]);
      ?>
      <a href="<?= $item[0] ?>"
         class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition <?= $active
           ? 'bg-indigo-50 text-indigo-700'
           : 'text-gray-600 hover:bg-gray-50' ?>">
        <i class="fas <?= $item[1] ?> w-4 text-center <?= $active ? 'text-indigo-500' : 'text-gray-400' ?>"></i>
        <?= $item[2] ?>
      </a>
      <?php endforeach; ?>

      <hr class="my-3 border-gray-100">

      <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-red-500 hover:bg-red-50 transition">
        <i class="fas fa-sign-out-alt w-4 text-center"></i> Logout
      </a>
    </nav>
  </div>
</div>

<!-- MOBILE SCRIPT -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const openBtn = document.getElementById('mobileMenuBtn');
  const closeBtn = document.getElementById('closeMobileMenu');
  const menu = document.getElementById('mobileMenu');

  openBtn?.addEventListener('click', () => menu.classList.remove('hidden'));
  closeBtn?.addEventListener('click', () => menu.classList.add('hidden'));
  menu?.addEventListener('click', e => {
    if (e.target === menu || e.target.closest('.absolute.inset-0.bg-black\\/50')) {
      menu.classList.add('hidden');
    }
  });
});
</script>