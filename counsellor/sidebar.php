<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$currentPage = basename($_SERVER['PHP_SELF']);
$isSubDir = true;

function counActive($page) {
    global $currentPage;
    return $currentPage === $page ? 'active' : '';
}
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="../style.css">

<style>
  .counsellor-sidebar {
    width: 260px;
    min-height: 100vh;
    background: linear-gradient(135deg, #581c87 0%, #831843 100%);
    padding: 28px 20px;
    display: flex;
    flex-direction: column;
    position: sticky;
    top: 0;
    z-index: 30;
  }
  .counsellor-sidebar .nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 12px;
    color: rgba(255,255,255,0.6);
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
  }
  .counsellor-sidebar .nav-link:hover {
    background: rgba(255,255,255,0.1);
    color: white;
  }
  .counsellor-sidebar .nav-link.active {
    background: rgba(255,255,255,0.15);
    color: white;
    font-weight: 600;
  }
</style>

<aside class="counsellor-sidebar" id="counSidebar">
  <div class="flex items-center gap-3 mb-8 px-2">
    <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center">
      <i class="fas fa-user-tie text-white text-lg"></i>
    </div>
    <div>
      <p class="font-bold text-white text-lg">Counsellor</p>
      <p class="text-white/40 text-xs">Session Portal</p>
    </div>
  </div>

  <nav class="space-y-1 flex-1">
    <a href="dashboard.php" class="nav-link <?= counActive('dashboard.php') ?>">
      <i class="fas fa-home"></i> Dashboard
    </a>
    <a href="upload.php" class="nav-link <?= counActive('upload.php') ?>">
      <i class="fas fa-upload"></i> Upload Slots
    </a>
    <a href="appointments.php" class="nav-link <?= counActive('appointments.php') ?>">
      <i class="fas fa-calendar-check"></i> Appointments
    </a>
  </nav>

  <div class="pt-4 border-t border-white/10">
    <a href="logout.php" class="nav-link text-red-300 hover:text-red-200 hover:bg-red-500/10">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </div>
</aside>
