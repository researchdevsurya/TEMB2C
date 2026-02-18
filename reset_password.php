<?php
session_start();
require 'db.php';

if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
    header("Location: forgot_password.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pass1 = $_POST['password'];
    $pass2 = $_POST['confirm_password'];

    if (strlen($pass1) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($pass1 !== $pass2) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($pass1, PASSWORD_DEFAULT);
        $email = $_SESSION['otp_email'];

        $stmt = $pdo->prepare("UPDATE students SET password=? WHERE email=?");
        if ($stmt->execute([$hashed, $email])) {
            // Clear session OTP data
            unset($_SESSION['otp']);
            unset($_SESSION['otp_email']);
            unset($_SESSION['otp_time']);
            unset($_SESSION['otp_verified']);

            // Redirect to login with success
            header("Location: login.php?reset=success");
            exit;
        } else {
            $error = "Failed to update password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include 'header.php'; ?>
<title>Reset Password | TEM Academy</title>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4 font-[Inter]">

<div class="w-full max-w-md bg-white rounded-3xl shadow-xl p-8 border border-gray-100 animate-fade-in-up">
    
    <div class="text-center mb-6">
        <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-lock-open text-blue-600 text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">New Password</h1>
        <p class="text-gray-500 text-sm mt-2">Create a strong password for your account.</p>
    </div>

    <?php if ($error): ?>
    <div class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl text-sm font-medium mb-6 flex items-center gap-2">
      <i class="fas fa-exclamation-circle text-red-500"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-5">
        <div>
            <label class="block text-gray-700 text-xs font-semibold mb-1.5">New Password</label>
            <div class="relative">
                <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="password" name="password" required
                       class="w-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 rounded-lg py-3 pl-11 pr-4 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition text-sm"
                       placeholder="Min 6 characters">
            </div>
        </div>

        <div>
            <label class="block text-gray-700 text-xs font-semibold mb-1.5">Confirm Password</label>
            <div class="relative">
                <i class="fas fa-check-circle absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="password" name="confirm_password" required
                       class="w-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 rounded-lg py-3 pl-11 pr-4 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition text-sm"
                       placeholder="Re-enter password">
            </div>
        </div>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-600/30 hover:shadow-blue-600/50 transition-all flex items-center justify-center gap-2">
            Update Password <i class="fas fa-arrow-right text-sm"></i>
        </button>
    </form>

</div>

</body>
</html>
