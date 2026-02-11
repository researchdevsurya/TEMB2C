<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$name = $_SESSION['counsellor_name'] ?? 'Counsellor';
$initial = strtoupper(substr($name, 0, 1));

$currentPage = basename($_SERVER['PHP_SELF']);

function isActive($page) {
    global $currentPage;
    return $currentPage === $page
        ? 'text-blue-600 font-semibold'
        : 'text-gray-600 hover:text-blue-600';
}
?>
<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- TOP NAVBAR -->
<header class="sticky top-0 z-50 bg-white shadow-md">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between items-center h-16">

            <!-- LOGO -->
            <div class="flex items-center">
                <div class="w-9 h-9 rounded-lg bg-green-600 flex items-center justify-center mr-2">
                    <i class="fas fa-user-tie text-white"></i>
                </div>
                <span class="text-xl font-bold text-gray-800">TEM Counsellor Portal</span>
            </div>

            <!-- DESKTOP NAV -->
            <nav class="hidden md:flex items-center space-x-6">
                <a href="dashboard.php" class="<?= isActive('dashboard.php') ?>">
                    <i class="fas fa-home mr-1"></i> Dashboard
                </a>

                <a href="upload.php" class="<?= isActive('upload.php') ?>">
                    <i class="fas fa-clock mr-1"></i> Upload
                </a>

                <a href="appointments.php" class="<?= isActive('appointments.php') ?>">
                    <i class="fas fa-calendar-check mr-1"></i> Appointments
                </a>

                <a href="profile.php" class="<?= isActive('profile.php') ?>">
                    <i class="fas fa-user-circle mr-1"></i> Profile
                </a>

                <a href="logout.php" class="text-red-600 font-semibold">
                    <i class="fas fa-sign-out-alt mr-1"></i> Logout
                </a>
            </nav>

            <!-- USER + MOBILE BTN -->
            <div class="flex items-center space-x-4">

                <!-- USER -->
                <div class="hidden md:flex items-center">
                    <div class="w-9 h-9 rounded-full bg-green-600 flex items-center justify-center text-white font-bold mr-2">
                        <?= $initial ?>
                    </div>
                    <div class="leading-tight">
                        <p class="text-sm font-semibold"><?= htmlspecialchars($name) ?></p>
                        <p class="text-xs text-gray-500">Counsellor</p>
                    </div>
                </div>

                <!-- MOBILE MENU BUTTON -->
                <button id="mobileMenuBtn" class="md:hidden text-2xl text-gray-700">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

        </div>
    </div>
</header>

<!-- MOBILE MENU -->
<div id="mobileMenu" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40"></div>

    <div class="absolute right-0 top-0 w-64 h-full bg-white shadow-xl p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold">Menu</h2>
            <button id="closeMobileMenu" class="text-2xl">&times;</button>
        </div>

        <!-- USER -->
        <div class="flex items-center mb-6 p-3 bg-gray-100 rounded-xl">
            <div class="w-10 h-10 rounded-full bg-green-600 flex items-center justify-center text-white font-bold mr-3">
                <?= $initial ?>
            </div>
            <div>
                <p class="font-semibold"><?= htmlspecialchars($name) ?></p>
                <p class="text-sm text-gray-500">Counsellor</p>
            </div>
        </div>

        <nav class="space-y-3">
            <a href="dashboard.php" class="block <?= isActive('dashboard.php') ?>">
                <i class="fas fa-home mr-2"></i> Dashboard
            </a>

            <a href="upload.php" class="block <?= isActive('upload.php') ?>">
                <i class="fas fa-clock mr-2"></i> UPload
            </a>

            <a href="appointments.php" class="block <?= isActive('appointments.php') ?>">
                <i class="fas fa-calendar-check mr-2"></i> Appointments
            </a>

            <a href="profile.php" class="block <?= isActive('profile.php') ?>">
                <i class="fas fa-user-circle mr-2"></i> Profile
            </a>

            <hr>

            <a href="logout.php" class="block text-red-600 font-semibold">
                <i class="fas fa-sign-out-alt mr-2"></i> Logout
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
        if (e.target === menu) menu.classList.add('hidden');
    });
});
</script>