<?php
session_start();

if (!isset($_SESSION['otp'])) {
    header("Location: forgot_password.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = trim($_POST['otp']);
    
    // Check expiry (10 mins)
    if (time() - $_SESSION['otp_time'] > 600) {
        $error = "OTP has expired. Please try again.";
        unset($_SESSION['otp']);
        unset($_SESSION['otp_email']);
    } elseif ($entered_otp == $_SESSION['otp']) {
        $_SESSION['otp_verified'] = true;
        header("Location: reset_password.php");
        exit;
    } else {
        $error = "Invalid OTP. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include 'header.php'; ?>
<title>Verify OTP | TEM Academy</title>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4 font-[Inter]">

<div class="w-full max-w-md bg-white rounded-3xl shadow-xl p-8 border border-gray-100 animate-fade-in-up">
    
    <div class="text-center mb-6">
        <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-shield-alt text-blue-600 text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Verify OTP</h1>
        <p class="text-gray-500 text-sm mt-2">We sent a 6-digit code to <strong><?= htmlspecialchars($_SESSION['otp_email']) ?></strong></p>
    </div>

    <?php if ($error): ?>
    <div class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl text-sm font-medium mb-6 flex items-center gap-2">
      <i class="fas fa-exclamation-circle text-red-500"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <div>
            <label class="block text-gray-700 text-xs font-semibold mb-1.5">Enter OTP</label>
            <input type="text" name="otp" required maxlength="6"
                   class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-center text-2xl tracking-[0.5em] font-bold rounded-xl py-3 px-4 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition"
                   placeholder="------">
        </div>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-600/30 hover:shadow-blue-600/50 transition-all">
            Verify & Proceed
        </button>
    </form>
    
    <div class="text-center mt-6">
        <a href="forgot_password.php" class="text-sm text-gray-400 hover:text-gray-600 transition">Resend Code</a>
    </div>

</div>

</body>
</html>
