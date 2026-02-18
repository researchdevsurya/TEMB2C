<?php
session_start();
require 'db.php';
require __DIR__ . '/mail_helper.php';

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

            // Send welcome email (non-blocking)
            sendWelcomeEmail($username, $email);

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
    /* Custom clean scrollbar for the form if needed */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4 py-10 font-[Inter]">

<!-- BACK LINK -->
<a href="login.php" class="fixed top-6 left-6 z-20 text-gray-500 hover:text-blue-600 flex items-center gap-2 text-sm font-medium transition">
  <i class="fas fa-arrow-left"></i> Back to Login
</a>

<div class="w-full max-w-4xl bg-white rounded-3xl shadow-xl overflow-hidden flex flex-col md:flex-row animate-fade-in-up border border-gray-100">

  <!-- LEFT SIDE: BRANDING / INFO (Hidden on mobile) -->
  <div class="hidden md:flex flex-col justify-center items-center w-5/12 bg-blue-600 p-10 text-white relative overflow-hidden">
      <!-- Decorative circles -->
      <div class="absolute top-[-10%] right-[-10%] w-60 h-60 rounded-full bg-white opacity-10 blur-3xl"></div>
      <div class="absolute bottom-[-10%] left-[-10%] w-60 h-60 rounded-full bg-white opacity-10 blur-3xl"></div>
      
      <div class="relative z-10 text-center">
          <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-inner border border-white/30">
              <i class="fas fa-user-graduate text-3xl text-white"></i>
          </div>
          <h2 class="text-3xl font-bold mb-4">Join Us!</h2>
          <p class="text-blue-100 text-sm leading-relaxed mb-8">
              Start your journey with TEM Academy. Get expert counselling and guidance for your future career.
          </p>
          <div class="space-y-3 text-left bg-blue-700/30 p-5 rounded-xl border border-blue-500/30">
              <div class="flex items-center gap-3 text-sm">
                  <div class="w-8 h-8 rounded-full bg-blue-500/40 flex items-center justify-center flex-shrink-0">
                      <i class="fas fa-check text-xs"></i>
                  </div>
                  <span>Expert Career Guidance</span>
              </div>
              <div class="flex items-center gap-3 text-sm">
                  <div class="w-8 h-8 rounded-full bg-blue-500/40 flex items-center justify-center flex-shrink-0">
                      <i class="fas fa-check text-xs"></i>
                  </div>
                  <span>Personalized Roadmap</span>
              </div>
              <div class="flex items-center gap-3 text-sm">
                  <div class="w-8 h-8 rounded-full bg-blue-500/40 flex items-center justify-center flex-shrink-0">
                      <i class="fas fa-check text-xs"></i>
                  </div>
                  <span>Verified Mentors</span>
              </div>
          </div>
      </div>
  </div>

  <!-- RIGHT SIDE: FORM -->
  <div class="w-full md:w-7/12 p-8 md:p-12">
    
    <div class="text-center md:text-left mb-8">
      <h1 class="text-2xl font-bold text-gray-900">Create Account</h1>
      <p class="text-gray-500 text-sm mt-1">Fill in your details to register</p>
    </div>

    <?php if (isset($error)): ?>
    <div class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl text-sm font-medium mb-6 flex items-center gap-2">
      <i class="fas fa-exclamation-circle text-red-500"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-5">

      <!-- PERSONAL DETAILS SECTION -->
      <div>
        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">
          Personal Information
          <div class="h-px bg-gray-200 flex-grow"></div>
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-gray-700 text-xs font-semibold mb-1.5">Full Name <span class="text-red-500">*</span></label>
            <input name="username" required
                   class="w-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 rounded-lg py-2.5 px-4 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition text-sm"
                   placeholder="John Doe">
          </div>
          <div>
            <label class="block text-gray-700 text-xs font-semibold mb-1.5">Email <span class="text-red-500">*</span></label>
            <input type="email" name="email" required
                   class="w-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 rounded-lg py-2.5 px-4 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition text-sm"
                   placeholder="you@example.com">
          </div>
          <div>
            <label class="block text-gray-700 text-xs font-semibold mb-1.5">Phone <span class="text-red-500">*</span></label>
            <input name="contact" required
                   class="w-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 rounded-lg py-2.5 px-4 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition text-sm"
                   placeholder="10-digit number">
          </div>
          <div>
            <label class="block text-gray-700 text-xs font-semibold mb-1.5">Gender <span class="text-red-500">*</span></label>
            <select name="gender" required class="w-full bg-gray-50 border border-gray-200 text-gray-800 rounded-lg py-2.5 px-4 focus:bg-white focus:border-blue-500 outline-none transition text-sm cursor-pointer">
              <option value="">Select</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="md:col-span-2"> <!-- Date of Birth spans full width on mobile, or keep it half? Let's make it half but maybe adjust layout logic. Actually keeping it in grid is fine. -->
             <label class="block text-gray-700 text-xs font-semibold mb-1.5">Date of Birth <span class="text-red-500">*</span></label>
            <input type="date" name="dob" required
                   class="w-full bg-gray-50 border border-gray-200 text-gray-800 rounded-lg py-2.5 px-4 focus:bg-white focus:border-blue-500 outline-none transition text-sm">
          </div>
        </div>
      </div>

      <!-- ACADEMIC DETAILS SECTION -->
      <div class="pt-2">
        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">
          Academic Details
          <div class="h-px bg-gray-200 flex-grow"></div>
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-gray-700 text-xs font-semibold mb-1.5">Class/Grade <span class="text-red-500">*</span></label>
            <select name="class_level" id="class_level" required class="w-full bg-gray-50 border border-gray-200 text-gray-800 rounded-lg py-2.5 px-4 focus:bg-white focus:border-blue-500 outline-none transition text-sm cursor-pointer">
              <option value="">Select Class</option>
              <option value="8">Class 8</option>
              <option value="9">Class 9</option>
              <option value="10">Class 10</option>
              <option value="11">Class 11</option>
              <option value="12">Class 12</option>
            </select>
          </div>

          <!-- Dynamic Fields -->
          <div id="boardBox" class="hidden">
            <label class="block text-gray-700 text-xs font-semibold mb-1.5">Board</label>
            <select name="board" class="w-full bg-gray-50 border border-gray-200 text-gray-800 rounded-lg py-2.5 px-4 focus:bg-white focus:border-blue-500 outline-none transition text-sm cursor-pointer">
              <option value="">Select Board</option>
              <option value="CBSE">CBSE</option>
              <option value="ICSE">ICSE</option>
              <option value="State Board">State Board</option>
            </select>
          </div>

          <div id="streamBox" class="hidden">
            <label class="block text-gray-700 text-xs font-semibold mb-1.5">Stream</label>
            <select name="stream" id="streamSelect" class="w-full bg-gray-50 border border-gray-200 text-gray-800 rounded-lg py-2.5 px-4 focus:bg-white focus:border-blue-500 outline-none transition text-sm cursor-pointer">
              <option value="">Select Stream</option>
              <option value="Science">Science</option>
              <option value="Commerce">Commerce</option>
              <option value="Arts">Arts</option>
            </select>
          </div>

          <div id="specializationBox" class="hidden">
            <label class="block text-gray-700 text-xs font-semibold mb-1.5">Specialization</label>
            <select name="specialization" id="specSelect" class="w-full bg-gray-50 border border-gray-200 text-gray-800 rounded-lg py-2.5 px-4 focus:bg-white focus:border-blue-500 outline-none transition text-sm cursor-pointer">
              <option value="">Select</option>
            </select>
          </div>
        </div>
      </div>

       <!-- SCHOOL & ADDRESS -->
       <div class="pt-2">
           <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">
            School Info
            <div class="h-px bg-gray-200 flex-grow"></div>
          </h3>
          <div class="space-y-4">
             <div>
                <label class="block text-gray-700 text-xs font-semibold mb-1.5">School Name <span class="text-red-500">*</span></label>
                <input name="school" required
                       class="w-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 rounded-lg py-2.5 px-4 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition text-sm"
                       placeholder="Enter school name">
             </div>
             <div>
                <label class="block text-gray-700 text-xs font-semibold mb-1.5">Address <span class="text-red-500">*</span></label>
                <textarea name="address" required rows="2"
                          class="w-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 rounded-lg py-2.5 px-4 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition text-sm resize-none"
                          placeholder="Your full address"></textarea>
             </div>
          </div>
       </div>

      <!-- PASSWORD -->
      <div class="pt-2">
        <label class="block text-gray-700 text-xs font-semibold mb-1.5">Password <span class="text-red-500">*</span></label>
        <div class="relative">
          <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
          <input type="password" name="password" required
                 class="w-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 rounded-lg py-2.5 pl-11 pr-4 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition text-sm"
                 placeholder="Min 6 characters">
        </div>
      </div>

      <!-- SUBMIT -->
      <div class="pt-4">
          <button name="signup"
                  class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-600/30 hover:shadow-blue-600/50 transition-all transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
            Create Account <i class="fas fa-arrow-right"></i>
          </button>
      </div>

    </form>

    <div class="text-center mt-6">
      <p class="text-gray-500 text-sm">
        Already have an account?
        <a href="login.php" class="text-blue-600 font-bold hover:text-blue-700 transition underline decoration-2 decoration-transparent hover:decoration-blue-600">Sign In</a>
      </p>
    </div>

  </div>
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
  specSelect.innerHTML = '<option value="">Select Specialization</option>';

  const map = {
    Science: ['Engineering', 'Medical', 'Pure Science', 'Others'],
    Commerce: ['CA', 'CS', 'CMA', 'Business', 'Others'],
    Arts: ['Humanities', 'Design', 'Law', 'Others']
  };

  if (map[this.value]) {
    map[this.value].forEach(v => {
      specSelect.innerHTML += `<option value="${v}">${v}</option>`;
    });
    specializationBox.classList.remove('hidden');
  } else {
    specializationBox.classList.add('hidden');
  }
});
</script>

</body>
</html>