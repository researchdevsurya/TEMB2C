<?php
/**
 * Mail Helper – uses PHPMailer for all email notifications
 */
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Internal: get a configured PHPMailer instance
 */
function getMailer(): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;
    $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
    $mail->isHTML(true);
    return $mail;
}

/**
 * Send Welcome Email after signup (Queued)
 */
function sendWelcomeEmail(string $name, string $email): bool {
    global $pdo;
    try {
        $subject = 'Welcome to TEM Academy!';
        $body = "
        <div style='font-family:Inter,Arial,sans-serif;max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;'>
            <div style='background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:32px;text-align:center;'>
                <h1 style='color:#fff;margin:0;font-size:24px;'>Welcome to TEM Academy! 🎓</h1>
            </div>
            <div style='padding:32px;'>
                <p style='font-size:16px;color:#374151;'>Hi <strong>{$name}</strong>,</p>
                <p style='color:#6b7280;line-height:1.6;'>Your account has been created successfully. You can now book counselling sessions and manage your payments from your dashboard.</p>
                <div style='text-align:center;margin:24px 0;'>
                    <a href='" . BASE_URL . "/login.php' style='background:#6366f1;color:#fff;padding:12px 32px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block;'>Go to Dashboard</a>
                </div>
                <p style='color:#9ca3af;font-size:13px;'>If you didn't create this account, please ignore this email.</p>
            </div>
        </div>";
        $altBody = "Welcome to TEM Academy, {$name}! Your account is ready. Login at " . BASE_URL . "/login.php";

        $stmt = $pdo->prepare("INSERT INTO mail_queue (to_email, to_name, subject, body, alt_body, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$email, $name, $subject, $body, $altBody]);
        
        return true;
    } catch (Exception $e) {
        error_log("Welcome email queue failed for {$email}: " . $e->getMessage());
        return false;
    }
}


/**
 * Send Booking Confirmation after mandate authorization (Queued)
 */
function sendBookingConfirmation(string $email, string $name, array $booking): bool {
    global $pdo;
    try {
        $subject = 'Booking Confirmed – TEM Academy';

        $psyDate   = date('D, d M Y', strtotime($booking['selected_psychometric_date']));
        $g1Date    = date('D, d M Y', strtotime($booking['group_session1_date']));
        $g2Date    = date('D, d M Y', strtotime($booking['group_session2_date']));
        $oneDate   = date('D, d M Y', strtotime($booking['booked_date']));
        $oneTime   = $booking['one_to_one_slot'];

        $body = "
        <div style='font-family:Inter,Arial,sans-serif;max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;'>
            <div style='background:linear-gradient(135deg,#059669,#10b981);padding:32px;text-align:center;'>
                <h1 style='color:#fff;margin:0;font-size:24px;'>Booking Confirmed! ✅</h1>
            </div>
            <div style='padding:32px;'>
                <p style='font-size:16px;color:#374151;'>Hi <strong>{$name}</strong>,</p>
                <p style='color:#6b7280;'>Your sessions have been booked. Auto-pay mandate is active — fees will be deducted before each session.</p>
                <table style='width:100%;border-collapse:collapse;margin:20px 0;'>
                    <tr style='border-bottom:1px solid #e5e7eb;'>
                        <td style='padding:10px;color:#6b7280;'>Psychometric Test</td>
                        <td style='padding:10px;font-weight:bold;color:#374151;'>{$psyDate}</td>
                        <td style='padding:10px;color:#059669;font-weight:bold;'>₹" . FEE_PSYCHOMETRIC . "</td>
                    </tr>
                    <tr style='border-bottom:1px solid #e5e7eb;'>
                        <td style='padding:10px;color:#6b7280;'>{$booking['group_session1']}</td>
                        <td style='padding:10px;font-weight:bold;color:#374151;'>{$g1Date}</td>
                        <td style='padding:10px;color:#059669;font-weight:bold;'>₹" . FEE_GROUP . "</td>
                    </tr>
                    <tr style='border-bottom:1px solid #e5e7eb;'>
                        <td style='padding:10px;color:#6b7280;'>{$booking['group_session2']}</td>
                        <td style='padding:10px;font-weight:bold;color:#374151;'>{$g2Date}</td>
                        <td style='padding:10px;color:#059669;font-weight:bold;'>₹" . FEE_GROUP . "</td>
                    </tr>
                    <tr>
                        <td style='padding:10px;color:#6b7280;'>1:1 Counselling</td>
                        <td style='padding:10px;font-weight:bold;color:#374151;'>{$oneDate} at {$oneTime}</td>
                        <td style='padding:10px;color:#059669;font-weight:bold;'>₹" . FEE_ONE_TO_ONE . "</td>
                    </tr>
                </table>
                <p style='color:#6b7280;font-size:13px;'>Counsellor: <strong>{$booking['counsellor_name']}</strong></p>
                <div style='text-align:center;margin:24px 0;'>
                    <a href='" . BASE_URL . "/payments.php' style='background:#059669;color:#fff;padding:12px 32px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block;'>View Payments</a>
                </div>
            </div>
        </div>";
        $altBody = "Booking confirmed! Psychometric: {$psyDate}, Group 1: {$g1Date}, Group 2: {$g2Date}, 1:1: {$oneDate} at {$oneTime}. Counsellor: {$booking['counsellor_name']}";

        $stmt = $pdo->prepare("INSERT INTO mail_queue (to_email, to_name, subject, body, alt_body, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$email, $name, $subject, $body, $altBody]);
        return true;

    } catch (Exception $e) {
        error_log("Booking email queue failed for {$email}: " . $e->getMessage());
        return false;
    }
}


/**
 * Send Payment Receipt after successful charge
 */
function sendPaymentReceipt(string $email, string $name, string $sessionType, float $amount, string $paymentId): bool {
    try {
        $mail = getMailer();
        $mail->addAddress($email, $name);
        $mail->Subject = "Payment Received – ₹{$amount} – TEM Academy";

        $sessionLabel = match($sessionType) {
            'PSYCHOMETRIC' => 'Psychometric Test',
            'GROUP'        => 'Group Session',
            'ONE_TO_ONE'   => '1:1 Counselling Session',
            default        => $sessionType
        };

        $mail->Body = "
        <div style='font-family:Inter,Arial,sans-serif;max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;'>
            <div style='background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:32px;text-align:center;'>
                <h1 style='color:#fff;margin:0;font-size:24px;'>Payment Received 💳</h1>
            </div>
            <div style='padding:32px;'>
                <p style='font-size:16px;color:#374151;'>Hi <strong>{$name}</strong>,</p>
                <p style='color:#6b7280;'>Your payment has been successfully processed.</p>
                <div style='background:#f3f4f6;border-radius:8px;padding:20px;margin:20px 0;'>
                    <p style='margin:0 0 8px;color:#6b7280;font-size:13px;'>Session</p>
                    <p style='margin:0 0 16px;font-weight:bold;color:#374151;'>{$sessionLabel}</p>
                    <p style='margin:0 0 8px;color:#6b7280;font-size:13px;'>Amount</p>
                    <p style='margin:0 0 16px;font-weight:bold;color:#059669;font-size:24px;'>₹{$amount}</p>
                    <p style='margin:0 0 8px;color:#6b7280;font-size:13px;'>Transaction ID</p>
                    <p style='margin:0;font-weight:bold;color:#374151;font-size:12px;'>{$paymentId}</p>
                </div>
            </div>
        </div>";
        $mail->AltBody = "Payment of ₹{$amount} received for {$sessionLabel}. Transaction ID: {$paymentId}";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Payment receipt email failed for {$email}: " . $e->getMessage());
        return false;
    }
}

/**
 * Send Payment Failed notification
 */
function sendPaymentFailed(string $email, string $name, string $sessionType, float $amount): bool {
    try {
        $mail = getMailer();
        $mail->addAddress($email, $name);
        $mail->Subject = 'Payment Failed – Action Required – TEM Academy';

        $sessionLabel = match($sessionType) {
            'PSYCHOMETRIC' => 'Psychometric Test',
            'GROUP'        => 'Group Session',
            'ONE_TO_ONE'   => '1:1 Counselling Session',
            default        => $sessionType
        };

        $mail->Body = "
        <div style='font-family:Inter,Arial,sans-serif;max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;'>
            <div style='background:linear-gradient(135deg,#dc2626,#ef4444);padding:32px;text-align:center;'>
                <h1 style='color:#fff;margin:0;font-size:24px;'>Payment Failed ❌</h1>
            </div>
            <div style='padding:32px;'>
                <p style='font-size:16px;color:#374151;'>Hi <strong>{$name}</strong>,</p>
                <p style='color:#6b7280;'>Your auto-debit payment of <strong>₹{$amount}</strong> for <strong>{$sessionLabel}</strong> could not be processed.</p>
                <p style='color:#6b7280;'>Please make the payment manually to avoid missing your session.</p>
                <div style='text-align:center;margin:24px 0;'>
                    <a href='" . BASE_URL . "/payments.php' style='background:#dc2626;color:#fff;padding:12px 32px;border-radius:8px;text-decoration:none;font-weight:bold;display:inline-block;'>Pay Now</a>
                </div>
            </div>
        </div>";
        $mail->AltBody = "Payment of ₹{$amount} for {$sessionLabel} failed. Please pay manually at " . BASE_URL . "/payments.php";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Payment failed email error for {$email}: " . $e->getMessage());
        return false;
    }
}

/**
 * Send OTP Email via MSG91
 */
function sendOtpEmail(string $email, string $otp, string $name = 'User'): bool {
    $curl = curl_init();

    $data = [
        "recipients" => [
            [
                "to" => [
                    [
                        "email" => $email,
                        "name"  => $name
                    ]
                ],
                "variables" => [
                    "company_name" => "TEM Academy",
                    "otp"          => $otp
                ]
            ]
        ],
        "from" => [
            "email" => MSG91_FROM_EMAIL
        ],
        "domain" => MSG91_DOMAIN,
        "template_id" => MSG91_TEMPLATE_ID
    ];

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://control.msg91.com/api/v5/email/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Accept: application/json',
            'authkey: ' . MSG91_AUTH_KEY,
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        error_log("MSG91 OTP Error: " . $err);
        return false;
    } else {
        // Decode response to check for success status if needed, 
        // but for now we assume if no curl error, it's sent or queued.
        // Optionally log response: error_log("MSG91 Response: " . $response);
        return true;
    }
}


