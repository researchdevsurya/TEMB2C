<?php
session_start();
require 'db.php';

if (isset($_SESSION['student_id'])) {
    header("Location: dashboard.php");
    exit;
}

if (isset($_POST['signup'])) {

    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $contact  = trim($_POST['contact']);
    $gender   = $_POST['gender'];
    $class    = $_POST['class_level'];
    $board    = $_POST['board'] ?? null;
    $stream   = $_POST['stream'] ?? null;
    $spec     = $_POST['specialization'] ?? null;
    $dob      = $_POST['dob'];
    $school   = trim($_POST['school']);
    $address  = trim($_POST['address']);
    $password = $_POST['password'];

    if (!$username || !$email || !$contact || !$gender || !$class || !$dob || !$school || !$address || !$password) {
        $error = "Please fill all required fields.";
    } else {

        $check = $pdo->prepare("SELECT id FROM students WHERE email=?");
        $check->execute([$email]);

        if ($check->rowCount() > 0) {
            $error = "Email already registered.";
        } else {

            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO students
                (username,email,contact_number,gender,std,board,stream,specialization,
                 school_name,address,dob,password)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
            ");

            $stmt->execute([
                $username, $email, $contact, $gender, $class,
                $board, $stream, $spec, $school, $address, $dob, $hashed
            ]);

            $_SESSION['student_id'] = $pdo->lastInsertId();
            $_SESSION['student_name'] = $username;
            $_SESSION['student_std'] = $class;
            header("Location: dashboard.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include 'header.php'; ?>
<title>Sign Up | TEM Academy</title>
<style>
  .signup-blob {
    position: fixed;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.2;
    animation: blobFloat 8s ease-in-out infinite;
  }
  @keyframes blobFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -20px) scale(1.05); }
    66% { transform: translate(-20px, 15px) scale(0.95); }
  }
</style>
</head>

<body class="bg-gradient-auth min-h-screen flex items-center justify-center px-4 py-10 relative ">

<!-- BLOBS -->
<div class="signup-blob w-96 h-96 bg-purple-600 -top-20 -right-20"></div>
<div class="signup-blob w-72 h-72 bg-blue-500 bottom-20 left-10" style="animation-delay: 4s;"></div>

<!-- BACK LINK -->
<a href="login.php" class="fixed top-6 left-6 z-20 text-white/60 hover:text-white flex items-center gap-2 text-sm font-medium transition">
  <i class="fas fa-arrow-left"></i> Back to Login
</a>

<div class="relative z-10 w-full max-w-3xl animate-fade-in-up">

  <!-- HEADER -->
  <div class="text-center mb-8">
    <div class="w-16 h-16 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center mx-auto mb-4 border border-white/10">
      <i class="fas fa-user-plus text-white text-2xl"></i>
    </div>
    <h1 class="text-2xl font-bold text-white">Create Your Account</h1>
    <p class="text-white/50 text-sm mt-1">Join TEM Academy to book counselling sessions</p>
  </div>

  <!-- FORM CARD -->
  <div class="bg-white/10 backdrop-blur-xl border border-white/15 rounded-2xl p-8 shadow-2xl">

    <?php if (isset($error)): ?>
    <div class="bg-red-500/15 border border-red-400/20 text-red-200 px-4 py-3 rounded-xl text-sm font-medium mb-5 flex items-center gap-2">
      <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">

      <!-- PERSONAL DETAILS -->
      <div>
        <h3 class="text-white font-semibold text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
          <i class="fas fa-user text-indigo-300 text-xs"></i> Personal Details
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-white/60 text-xs font-semibold mb-1.5">Full Name *</label>
            <input name="username" required
                   class="w-full bg-white/10 border border-white/15 text-white placeholder-white/30 rounded-xl py-3 px-4 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/20 outline-none transition text-sm"
                   placeholder="Enter full name">
          </div>
          <div>
            <label class="block text-white/60 text-xs font-semibold mb-1.5">Email *</label>
            <input type="email" name="email" required
                   class="w-full bg-white/10 border border-white/15 text-white placeholder-white/30 rounded-xl py-3 px-4 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/20 outline-none transition text-sm"
                   placeholder="you@example.com">
          </div>
          <div>
            <label class="block text-white/60 text-xs font-semibold mb-1.5">Contact Number *</label>
            <input name="contact" required
                   class="w-full bg-white/10 border border-white/15 text-white placeholder-white/30 rounded-xl py-3 px-4 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/20 outline-none transition text-sm"
                   placeholder="10-digit mobile">
          </div>
          <div>
            <label class="block text-white/60 text-xs font-semibold mb-1.5">Gender *</label>
            <select name="gender" required class="w-full bg-white/10 border border-white/15 text-white rounded-xl py-3 px-4 focus:border-indigo-400 outline-none transition text-sm">
              <option value="" class="text-gray-800">Select Gender</option>
              <option value="Male" class="text-gray-800">Male</option>
              <option value="Female" class="text-gray-800">Female</option>
              <option value="Other" class="text-gray-800">Other</option>
            </select>
          </div>
          <div>
            <label class="block text-white/60 text-xs font-semibold mb-1.5">Date of Birth *</label>
            <input type="date" name="dob" required
                   class="w-full bg-white/10 border border-white/15 text-white rounded-xl py-3 px-4 focus:border-indigo-400 outline-none transition text-sm">
          </div>
        </div>
      </div>

      <!-- DIVIDER -->
      <div class="border-t border-white/10"></div>

      <!-- ACADEMIC -->
      <div>
        <h3 class="text-white font-semibold text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
          <i class="fas fa-book text-indigo-300 text-xs"></i> Academic Details
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-white/60 text-xs font-semibold mb-1.5">Class *</label>
            <select name="class_level" id="class_level" required class="w-full bg-white/10 border border-white/15 text-white rounded-xl py-3 px-4 focus:border-indigo-400 outline-none transition text-sm">
              <option value="" class="text-gray-800">Select Class</option>
              <option value="8" class="text-gray-800">Class 8</option>
              <option value="9" class="text-gray-800">Class 9</option>
              <option value="10" class="text-gray-800">Class 10</option>
              <option value="11" class="text-gray-800">Class 11</option>
              <option value="12" class="text-gray-800">Class 12</option>
            </select>
          </div>

          <div id="boardBox" class="hidden">
            <label class="block text-white/60 text-xs font-semibold mb-1.5">Board</label>
            <select name="board" class="w-full bg-white/10 border border-white/15 text-white rounded-xl py-3 px-4 focus:border-indigo-400 outline-none transition text-sm">
              <option value="" class="text-gray-800">Select Board</option>
              <option value="CBSE" class="text-gray-800">CBSE</option>
              <option value="ICSE" class="text-gray-800">ICSE</option>
              <option value="State Board" class="text-gray-800">State Board</option>
            </select>
          </div>

          <div id="streamBox" class="hidden">
            <label class="block text-white/60 text-xs font-semibold mb-1.5">Stream</label>
            <select name="stream" id="streamSelect" class="w-full bg-white/10 border border-white/15 text-white rounded-xl py-3 px-4 focus:border-indigo-400 outline-none transition text-sm">
              <option value="" class="text-gray-800">Select Stream</option>
              <option value="Science" class="text-gray-800">Science</option>
              <option value="Commerce" class="text-gray-800">Commerce</option>
              <option value="Arts" class="text-gray-800">Arts</option>
            </select>
          </div>

          <div id="specializationBox" class="hidden">
            <label class="block text-white/60 text-xs font-semibold mb-1.5">Specialization</label>
            <select name="specialization" id="specSelect" class="w-full bg-white/10 border border-white/15 text-white rounded-xl py-3 px-4 focus:border-indigo-400 outline-none transition text-sm">
              <option value="" class="text-gray-800">Select</option>
            </select>
          </div>
        </div>
      </div>

      <!-- DIVIDER -->
      <div class="border-t border-white/10"></div>

      <!-- SCHOOL -->
      <div>
        <h3 class="text-white font-semibold text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
          <i class="fas fa-school text-indigo-300 text-xs"></i> School Information
        </h3>
        <div class="space-y-4">
          <div>
            <label class="block text-white/60 text-xs font-semibold mb-1.5">School / College Name *</label>
            <input name="school" required
                   class="w-full bg-white/10 border border-white/15 text-white placeholder-white/30 rounded-xl py-3 px-4 focus:border-indigo-400 outline-none transition text-sm"
                   placeholder="Enter school name">
          </div>
          <div>
            <label class="block text-white/60 text-xs font-semibold mb-1.5">Address *</label>
            <textarea name="address" required rows="2"
                      class="w-full bg-white/10 border border-white/15 text-white placeholder-white/30 rounded-xl py-3 px-4 focus:border-indigo-400 outline-none transition text-sm resize-none"
                      placeholder="Enter your address"></textarea>
          </div>
        </div>
      </div>

      <!-- DIVIDER -->
      <div class="border-t border-white/10"></div>

      <!-- PASSWORD -->
      <div>
        <label class="block text-white/60 text-xs font-semibold mb-1.5">Create Password *</label>
        <div class="relative">
          <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-white/30"></i>
          <input type="password" name="password" required
                 class="w-full bg-white/10 border border-white/15 text-white placeholder-white/30 rounded-xl py-3 pl-11 pr-4 focus:border-indigo-400 outline-none transition text-sm"
                 placeholder="Min 6 characters">
        </div>
      </div>

      <button name="signup"
              class="w-full bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-400 hover:to-purple-500 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 transition-all flex items-center justify-center gap-2">
        Create Account <i class="fas fa-arrow-right text-sm"></i>
      </button>

    </form>
  </div>

  <p class="text-center text-white/40 text-sm mt-6">
    Already have an account?
    <a href="login.php" class="text-indigo-300 font-semibold hover:text-indigo-200 transition">Sign In</a>
  </p>

</div>

<script>
const classLevel = document.getElementById('class_level');
const boardBox = document.getElementById('boardBox');
const streamBox = document.getElementById('streamBox');
const specializationBox = document.getElementById('specializationBox');

classLevel.addEventListener('change', () => {
  boardBox.classList.add('hidden');
  streamBox.classList.add('hidden');
  specializationBox.classList.add('hidden');

  if (classLevel.value && classLevel.value <= 10) boardBox.classList.remove('hidden');
  if (classLevel.value >= 11) streamBox.classList.remove('hidden');
});

document.getElementById('streamSelect').addEventListener('change', function() {
  const specSelect = document.getElementById('specSelect');
  specSelect.innerHTML = '<option value="" class="text-gray-800">Select Specialization</option>';

  const map = {
    Science: ['Engineering', 'Medical', 'Pure Science', 'Others'],
    Commerce: ['CA', 'CS', 'CMA', 'Business', 'Others'],
    Arts: ['Humanities', 'Design', 'Law', 'Others']
  };

  if (map[this.value]) {
    map[this.value].forEach(v => {
      specSelect.innerHTML += `<option value="${v}" class="text-gray-800">${v}</option>`;
    });
    specializationBox.classList.remove('hidden');
  } else {
    specializationBox.classList.add('hidden');
  }
});
</script>

</body>
</html>