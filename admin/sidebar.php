<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$currentPage = basename($_SERVER['PHP_SELF']);
$isSubDir = true;

function adminActive($page) {
    global $currentPage;
    return $currentPage === $page ? 'active' : '';
}
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="../style.css">

<!-- SIDEBAR -->
<aside class="admin-sidebar" id="adminSidebar">

  <!-- LOGO -->
  <div class="flex items-center gap-3 mb-8 px-2">
    <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center">
      <i class="fas fa-shield-halved text-white text-lg"></i>
    </div>
    <div>
      <p class="font-bold text-white text-lg">TEM Admin</p>
      <p class="text-white/40 text-xs">Management Panel</p>
    </div>
  </div>

  <!-- NAV -->
  <nav class="space-y-1 flex-1">
    <p class="text-white/30 text-xs font-bold uppercase tracking-wider px-4 mb-2">Main</p>

    <a href="dashboard.php" class="nav-link <?= adminActive('dashboard.php') ?>">
      <i class="fas fa-home"></i> Dashboard
    </a>
    <a href="events.php" class="nav-link <?= adminActive('events.php') ?>">
      <i class="fas fa-calendar-alt"></i> Events
    </a>
    <a href="create_event.php" class="nav-link <?= adminActive('create_event.php') ?>">
      <i class="fas fa-plus-circle"></i> Create Event
    </a>

    <p class="text-white/30 text-xs font-bold uppercase tracking-wider px-4 mb-2 mt-6">People</p>

    <a href="students.php" class="nav-link <?= adminActive('students.php') ?>">
      <i class="fas fa-user-graduate"></i> Students
    </a>
    <a href="counsellors.php" class="nav-link <?= adminActive('counsellors.php') ?>">
      <i class="fas fa-user-tie"></i> Counsellors
    </a>
    <a href="create_counsellors.php" class="nav-link <?= adminActive('create_counsellors.php') ?>">
      <i class="fas fa-user-plus"></i> Add Counsellor
    </a>

    <p class="text-white/30 text-xs font-bold uppercase tracking-wider px-4 mb-2 mt-6">Tools</p>

    <a href="calendar.php" class="nav-link <?= adminActive('calendar.php') ?>">
      <i class="fas fa-calendar-check"></i> Calendar
    </a>
    <a href="export_event_excel.php" class="nav-link <?= adminActive('export_event_excel.php') ?>">
      <i class="fas fa-file-export"></i> Export Data
    </a>
  </nav>

  <!-- LOGOUT -->
  <div class="pt-4 border-t border-white/10">
    <a href="logout.php" class="nav-link text-red-300 hover:text-red-200 hover:bg-red-500/10">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </div>
</aside>

<!-- MOBILE: Toggle for small screens -->
<div class="md:hidden fixed top-0 left-0 right-0 z-50 bg-gradient-to-r from-indigo-900 to-purple-900 px-4 py-3 flex items-center justify-between">
  <div class="flex items-center gap-2">
    <i class="fas fa-shield-halved text-white"></i>
    <span class="text-white font-bold">TEM Admin</span>
  </div>
  <button onclick="document.getElementById('adminSidebar').classList.toggle('hidden')" class="text-white text-xl">
    <i class="fas fa-bars"></i>
  </button>
</div>
