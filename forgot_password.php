<?php
session_start();
require 'db.php';
require __DIR__ . '/mail_helper.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'security_helper.php';
    $security = new SecurityHelper($pdo);
    $ip = SecurityHelper::getClientIP();

    // Check Rate Limit for OTP Request
    if (!$security->checkRateLimit($ip, 'otp_request', 3, 30)) {
        $error = "Too many OTP requests. Please try again after 30 minutes.";
    } else {
        $email = trim($_POST['email']);

        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";
    } 
    else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id, username FROM students WHERE email=?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $otp = rand(100000, 999999);
            $_SESSION['otp'] = $otp;
            $_SESSION['otp_email'] = $email;
            $_SESSION['otp_time'] = time();

            // Pass username to helper
            if (sendOtpEmail($email, $otp, $user['username'])) {
                // Log success or just don't log failure? 
                // Actually we should charge for OTP request success too? 
                // No, rate limit usually counts attempts. 
                // Let's log 'attempt' every time, but maybe clear on success? 
                // For OTP, we don't clear attempts on success, we limit TOTAL requests per 30 mins.
                $security->logFailure($ip, 'otp_request'); // Increment count
                header("Location: verify_otp.php");
                exit;

            } else {

                $error = "Failed to send OTP. Please try again.";
            }
        } else {
            // For security, generic message
            $error = "If this email is registered, we have sent an OTP.";
        }
    
    }
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include 'header.php'; ?>
<title>Forgot Password | TEM Academy</title>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4 font-[Inter]">

<a href="login.php" class="fixed top-6 left-6 z-20 text-gray-500 hover:text-blue-600 flex items-center gap-2 text-sm font-medium transition">
  <i class="fas fa-arrow-left"></i> Back to Login
</a>

<div class="w-full max-w-md bg-white rounded-3xl shadow-xl p-8 border border-gray-100 animate-fade-in-up">
    
    <div class="text-center mb-6">
        <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-key text-blue-600 text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Forgot Password?</h1>
        <p class="text-gray-500 text-sm mt-2">Enter your email address to receive a verification code.</p>
    </div>

    <?php if ($error): ?>
    <div class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl text-sm font-medium mb-6 flex items-center gap-2">
      <i class="fas fa-exclamation-circle text-red-500"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <div>
            <label class="block text-gray-700 text-xs font-semibold mb-1.5">Email Address</label>
            <div class="relative">
                <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="email" name="email" required
                       class="w-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-gray-400 rounded-lg py-3 pl-11 pr-4 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition text-sm"
                       placeholder="you@example.com">
            </div>
        </div>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-600/30 hover:shadow-blue-600/50 transition-all flex items-center justify-center gap-2">
            Send OTP <i class="fas fa-paper-plane text-sm"></i>
        </button>
    </form>

</div>

</body>
</html>
