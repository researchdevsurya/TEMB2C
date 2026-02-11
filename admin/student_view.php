<?php
session_start();
require '../db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) die("Invalid request");

$stmt = $pdo->prepare("SELECT * FROM students WHERE id=?");
$stmt->execute([$id]);
$s = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$s) die("Student not found");

/* Avatar initial */
$initial = strtoupper(substr($s['username'], 0, 1));

/* DOB + Age */
$age = '';
$dobFormatted = '';
if (!empty($s['dob'])) {
    $dob = new DateTime($s['dob']);
    $dobFormatted = $dob->format('F j, Y');
    $age = (new DateTime())->diff($dob)->y;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin | Student Profile</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet"
 href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-100 min-h-screen flex">

<!-- SIDEBAR -->
<?php include 'sidebar.php'; ?>

<!-- MAIN -->
<div class="flex-1 flex flex-col overflow-hidden">

    <!-- HEADER -->
    <header class="bg-white border-b px-8 py-5 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center">
                <i class="fas fa-user-graduate mr-3 text-indigo-600"></i>
                Student Profile
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Complete student information
            </p>
        </div>

        <a href="students.php"
           class="text-sm text-indigo-600 hover:underline flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back to Students
        </a>
    </header>

    <!-- CONTENT -->
    <main class="flex-1 overflow-y-auto p-8">

        <!-- BASIC INFO -->
        <div class="bg-white rounded-2xl shadow p-6 mb-8 flex items-center gap-6">
            <div class="w-20 h-20 rounded-full bg-indigo-600
                        flex items-center justify-center
                        text-white text-2xl font-bold">
                <?= $initial ?>
            </div>

            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    <?= htmlspecialchars($s['username']) ?>
                </h2>
                <p class="text-sm text-gray-500 break-all">
                    <?= htmlspecialchars($s['email']) ?>
                </p>

                <div class="flex gap-2 mt-2 flex-wrap">
                    <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">
                        Class <?= $s['std'] ?>
                    </span>
                    <span class="px-3 py-1 bg-green-100 text-green-700 text-xs rounded-full">
                        <?= $s['gender'] ?>
                    </span>
                    <?php if ($age): ?>
                    <span class="px-3 py-1 bg-purple-100 text-purple-700 text-xs rounded-full">
                        <?= $age ?> yrs
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- DETAILS GRID -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- PERSONAL DETAILS -->
            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800">
                    <i class="fas fa-id-card mr-2 text-blue-500"></i>
                    Personal Details
                </h3>

                <div class="space-y-3 text-sm">
                    <p><span class="text-gray-500">Full Name</span><br>
                        <span class="font-medium"><?= htmlspecialchars($s['username']) ?></span>
                    </p>

                    <p><span class="text-gray-500">Email</span><br>
                        <span class="font-medium"><?= htmlspecialchars($s['email']) ?></span>
                    </p>

                    <p><span class="text-gray-500">Contact Number</span><br>
                        <span class="font-medium"><?= htmlspecialchars($s['contact_number']) ?></span>
                    </p>

                    <p><span class="text-gray-500">Date of Birth</span><br>
                        <span class="font-medium"><?= $dobFormatted ?> (<?= $age ?> yrs)</span>
                    </p>
                </div>
            </div>

            <!-- ACADEMIC DETAILS -->
            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800">
                    <i class="fas fa-school mr-2 text-indigo-500"></i>
                    Academic Details
                </h3>

                <div class="space-y-3 text-sm">
                    <p><span class="text-gray-500">Class</span><br>
                        <span class="font-medium"><?= $s['std'] ?></span>
                    </p>

                    <?php if ($s['board']): ?>
                    <p><span class="text-gray-500">Board</span><br>
                        <span class="font-medium"><?= htmlspecialchars($s['board']) ?></span>
                    </p>
                    <?php endif; ?>

                    <?php if ($s['stream']): ?>
                    <p><span class="text-gray-500">Stream</span><br>
                        <span class="font-medium"><?= htmlspecialchars($s['stream']) ?></span>
                    </p>
                    <?php endif; ?>

                    <?php if ($s['specialization']): ?>
                    <p><span class="text-gray-500">Specialization</span><br>
                        <span class="font-medium"><?= htmlspecialchars($s['specialization']) ?></span>
                    </p>
                    <?php endif; ?>

                    <p><span class="text-gray-500">School Name</span><br>
                        <span class="font-medium"><?= htmlspecialchars($s['school_name']) ?></span>
                    </p>
                </div>
            </div>

            <!-- ADDRESS -->
            <div class="bg-white rounded-2xl shadow p-6 md:col-span-2">
                <h3 class="text-lg font-semibold mb-3 text-gray-800">
                    <i class="fas fa-map-marker-alt mr-2 text-emerald-500"></i>
                    Address
                </h3>

                <p class="text-sm text-gray-700 whitespace-pre-line">
                    <?= htmlspecialchars($s['address']) ?>
                </p>
            </div>

        </div>

    </main>
</div>

</body>
</html>