<?php
session_start();
require 'db.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['student_id'];

/* FETCH STUDENT */
$stmt = $pdo->prepare("SELECT * FROM students WHERE id=?");
$stmt->execute([$student_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) die("Student not found");

/* UPDATE PROFILE */
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $contact  = trim($_POST['contact']);
    $school   = trim($_POST['school']);
    $address  = trim($_POST['address']);

    if ($username && $contact) {
        $pdo->prepare("UPDATE students SET username=?, contact_number=?, school_name=?, address=? WHERE id=?")
            ->execute([$username, $contact, $school, $address, $student_id]);

        $_SESSION['student_name'] = $username;
        $msg = "Profile updated successfully!";

        // Refresh
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id=?");
        $stmt->execute([$student_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

$initial = strtoupper(substr($user['username'], 0, 1));
$colors = ['from-indigo-500 to-purple-600', 'from-blue-500 to-cyan-500', 'from-emerald-500 to-teal-600', 'from-orange-500 to-red-500', 'from-pink-500 to-rose-600'];
$avatarGrad = $colors[ord($initial) % count($colors)];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include 'header.php'; ?>
<title>Profile | TEM Portal</title>
</head>

<body class="bg-gradient-page">

<?php include 'sidebar.php'; ?>

<main class="max-w-3xl mx-auto px-4 sm:px-6 py-8">

  <h1 class="text-2xl font-extrabold text-gray-800 mb-8 animate-fade-in-up">My Profile</h1>

  <?php if ($msg): ?>
  <div class="alert alert-success flex items-center gap-2 animate-fade-in-up">
    <i class="fas fa-check-circle"></i> <?= $msg ?>
  </div>
  <?php endif; ?>

  <!-- PROFILE HEADER -->
  <div class="glass-card-static p-8 mb-6 animate-fade-in-up delay-100">
    <div class="flex flex-col sm:flex-row items-center gap-6">
      <div class="w-20 h-20 rounded-full bg-gradient-to-br <?= $avatarGrad ?> flex items-center justify-center text-white text-3xl font-bold shadow-lg">
        <?= $initial ?>
      </div>
      <div class="text-center sm:text-left">
        <h2 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($user['username']) ?></h2>
        <p class="text-gray-500"><?= htmlspecialchars($user['email']) ?></p>
        <div class="flex flex-wrap gap-2 mt-2 justify-center sm:justify-start">
          <span class="badge badge-info">Class <?= htmlspecialchars($user['std']) ?></span>
          <?php if ($user['gender']): ?>
          <span class="badge bg-gray-100 text-gray-600"><?= htmlspecialchars($user['gender']) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- EDIT FORM -->
  <div class="glass-card-static p-8 animate-fade-in-up delay-200">
    <h3 class="section-title mb-6">Edit Information</h3>

    <form method="POST" class="space-y-5">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label class="form-label">Full Name</label>
          <input name="username" value="<?= htmlspecialchars($user['username']) ?>" required class="form-input">
        </div>

        <div>
          <label class="form-label">Email (Read Only)</label>
          <input value="<?= htmlspecialchars($user['email']) ?>" readonly class="form-input bg-gray-50 text-gray-500 cursor-not-allowed">
        </div>

        <div>
          <label class="form-label">Contact Number</label>
          <input name="contact" value="<?= htmlspecialchars($user['contact_number']) ?>" required class="form-input">
        </div>

        <div>
          <label class="form-label">Date of Birth</label>
          <input value="<?= $user['dob'] ?>" readonly class="form-input bg-gray-50 text-gray-500 cursor-not-allowed">
        </div>
      </div>

      <div>
        <label class="form-label">School / College</label>
        <input name="school" value="<?= htmlspecialchars($user['school_name']) ?>" class="form-input">
      </div>

      <div>
        <label class="form-label">Address</label>
        <textarea name="address" rows="3" class="form-input resize-none"><?= htmlspecialchars($user['address']) ?></textarea>
      </div>

      <button class="btn-primary">
        <i class="fas fa-save"></i> Save Changes
      </button>

    </form>
  </div>

</main>

<?php include 'footer.php'; ?>
</body>
</html>