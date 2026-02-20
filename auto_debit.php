<?php
/**
 * Auto-Debit Cron Job
 * 
 * Run daily via cron/Task Scheduler:
 *   php auto_debit.php
 * 
 * Charges students whose sessions are scheduled for tomorrow.
 * Uses Razorpay recurring payment API with the saved token.
 */
require __DIR__ . '/db.php';
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';
require __DIR__ . '/mail_helper.php';

use Razorpay\Api\Api;

$api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

echo "[" . date('Y-m-d H:i:s') . "] Auto-debit cron started\n";

/* ─── Find PENDING payments scheduled for tomorrow with active mandates ─── */
$stmt = $pdo->query("
    SELECT 
        p.id AS payment_id,
        p.booking_id,
        p.student_id,
        p.payment_for,
        p.amount,
        p.scheduled_date,
        b.razorpay_customer_id,
        b.razorpay_token_id,
        b.subscription_status,
        s.email,
        s.username
    FROM payments p
    JOIN student_bookings b ON b.id = p.booking_id
    JOIN students s ON s.id = p.student_id
    WHERE p.auto_pay = 'YES'
      AND p.payment_status = 'PENDING'
      AND p.scheduled_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
      AND b.razorpay_customer_id IS NOT NULL
      AND b.razorpay_token_id IS NOT NULL
      AND b.subscription_status IN ('authenticated', 'active')
      AND b.mandate_status = 'ACTIVE'
");

$count = 0;
$success = 0;
$failed = 0;

foreach ($stmt as $p) {
    $count++;
    echo "  Processing: Payment #{$p['payment_id']} | {$p['payment_for']} | ₹{$p['amount']} | Student: {$p['username']}\n";

    try {
        /* ─── Create recurring payment via token ─── */
        $paymentData = [
            'amount'      => (int)($p['amount'] * 100), // paise
            'currency'    => 'INR',
            'customer_id' => $p['razorpay_customer_id'],
            'token'       => $p['razorpay_token_id'],
            'recurring'   => '1',
            'description' => 'TEM Academy - ' . match($p['payment_for']) {
                'PSYCHOMETRIC' => 'Psychometric Test Fee',
                'GROUP'        => 'Group Session Fee',
                'ONE_TO_ONE'   => '1:1 Counselling Fee',
                default        => $p['payment_for']
            },
            'notes' => [
                'booking_id'  => $p['booking_id'],
                'payment_for' => $p['payment_for'],
                'student_id'  => $p['student_id']
            ]
        ];

        $rzpPayment = $api->payment->createRecurring($paymentData);
        
        /* ─── Update payment record ─── */
        $pdo->prepare("
            UPDATE payments 
            SET razorpay_payment_id = ?,
                razorpay_order_id = ?
            WHERE id = ?
        ")->execute([
            $rzpPayment['id'],
            $rzpPayment['order_id'] ?? null,
            $p['payment_id']
        ]);

        /* ─── Log ─── */
        $pdo->prepare("
            INSERT INTO payment_logs 
            (booking_id, student_id, razorpay_payment_id, payment_type, event_type, raw_payload, status)
            VALUES (?,?,?,?,?,?,?)
        ")->execute([
            $p['booking_id'],
            $p['student_id'],
            $rzpPayment['id'],
            'RECURRING',
            'auto_debit.initiated',
            json_encode($rzpPayment->toArray()),
            'INITIATED'
        ]);

        echo "    ✓ Payment initiated: {$rzpPayment['id']}\n";
        $success++;

    } catch (Exception $e) {
        $failed++;
        echo "    ✗ FAILED: {$e->getMessage()}\n";

        /* Log failure */
        $pdo->prepare("
            INSERT INTO payment_logs 
            (booking_id, student_id, payment_type, event_type, raw_payload, status)
            VALUES (?,?,?,?,?,?)
        ")->execute([
            $p['booking_id'],
            $p['student_id'],
            'RECURRING',
            'auto_debit.failed',
            json_encode(['error' => $e->getMessage()]),
            'FAILED'
        ]);

        /* Send failure email */
        sendPaymentFailed($p['email'], $p['username'], $p['payment_for'], (float)$p['amount']);
    }
}

echo "\n[" . date('Y-m-d H:i:s') . "] Done. Processed: {$count} | Success: {$success} | Failed: {$failed}\n";
