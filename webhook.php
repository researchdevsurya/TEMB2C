<?php
/**
 * Razorpay Webhook Handler
 * 
 * Register this URL in Razorpay Dashboard → Webhooks:
 *   https://yourdomain.com/webhook.php
 * 
 * Events handled:
 *   - payment.captured  → Mark session payment as PAID
 *   - payment.failed    → Log failure, send failure email
 *   - token.confirmed   → Update mandate token status
 *   - subscription.charged → Mark recurring charge as paid
 */
require 'db.php';
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';
require __DIR__ . '/mail_helper.php';

use Razorpay\Api\Api;

/* ─── Read raw payload ─── */
$rawPayload = file_get_contents("php://input");
$payload = json_decode($rawPayload, true);

if (!$payload || !isset($payload['event'])) {
    http_response_code(400);
    exit("Invalid payload");
}

/* ─── Verify webhook signature (optional but recommended) ─── */
$webhookSignature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';
if (RZP_WEBHOOK_SECRET !== 'YOUR_WEBHOOK_SECRET_HERE' && $webhookSignature) {
    try {
        $api = new Api(RZP_KEY_ID, RZP_KEY_SECRET);
        $api->utility->verifyWebhookSignature($rawPayload, $webhookSignature, RZP_WEBHOOK_SECRET);
    } catch (Exception $e) {
        http_response_code(400);
        error_log("Webhook signature verification failed: " . $e->getMessage());
        exit("Signature verification failed");
    }
}

$event = $payload['event'];

/* ─── Log every webhook event ─── */
$paymentEntity = $payload['payload']['payment']['entity'] ?? [];
$paymentId     = $paymentEntity['id'] ?? null;
$orderId       = $paymentEntity['order_id'] ?? null;
$notes         = $paymentEntity['notes'] ?? [];
$bookingId     = $notes['booking_id'] ?? null;

$pdo->prepare("
    INSERT INTO payment_logs 
    (booking_id, razorpay_order_id, razorpay_payment_id, payment_type, event_type, raw_payload, status)
    VALUES (?,?,?,?,?,?,?)
")->execute([
    $bookingId,
    $orderId,
    $paymentId,
    'WEBHOOK',
    $event,
    $rawPayload,
    'RECEIVED'
]);

/* ─── Handle events ─── */
switch ($event) {

    case 'payment.captured':
        /* 
         * Auto-debit payment was captured successfully.
         * Update the payments table.
         */
        if ($paymentId) {
            // First try matching by razorpay_order_id
            $updated = $pdo->prepare("
                UPDATE payments 
                SET payment_status='PAID', 
                    razorpay_payment_id=?, 
                    paid_at=NOW()
                WHERE razorpay_order_id=? AND payment_status='PENDING'
            ");
            $updated->execute([$paymentId, $orderId]);

            // If no match by order_id, try matching PENDING payment by booking_id
            if ($updated->rowCount() === 0 && $bookingId) {
                $pdo->prepare("
                    UPDATE payments 
                    SET payment_status='PAID',
                        razorpay_payment_id=?,
                        paid_at=NOW()
                    WHERE booking_id=? AND payment_status='PENDING' AND auto_pay='YES'
                    ORDER BY scheduled_date ASC
                    LIMIT 1
                ")->execute([$paymentId, $bookingId]);
            }

            // Send receipt email
            if ($bookingId) {
                $payRow = $pdo->prepare("
                    SELECT p.*, s.email, s.username 
                    FROM payments p 
                    JOIN students s ON s.id = p.student_id
                    WHERE p.razorpay_payment_id=?
                    LIMIT 1
                ");
                $payRow->execute([$paymentId]);
                $p = $payRow->fetch(PDO::FETCH_ASSOC);
                if ($p) {
                    sendPaymentReceipt($p['email'], $p['username'], $p['payment_for'], (float)$p['amount'], $paymentId);
                }
            }
        }
        break;

    case 'payment.failed':
        /*
         * Payment attempt failed.
         * Log and notify student.
         */
        if ($bookingId) {
            $payRow = $pdo->prepare("
                SELECT p.*, s.email, s.username 
                FROM payments p 
                JOIN students s ON s.id = p.student_id
                WHERE p.booking_id=? AND p.payment_status='PENDING'
                ORDER BY p.scheduled_date ASC
                LIMIT 1
            ");
            $payRow->execute([$bookingId]);
            $p = $payRow->fetch(PDO::FETCH_ASSOC);
            if ($p) {
                // Update payment status
                $pdo->prepare("UPDATE payments SET payment_status='FAILED' WHERE id=?")
                    ->execute([$p['id']]);
                // Send failure email
                sendPaymentFailed($p['email'], $p['username'], $p['payment_for'], (float)$p['amount']);
            }
        }
        break;

    case 'token.confirmed':
        /*
         * Recurring token/emandate confirmed.
         * Update booking mandate status.
         */
        $tokenEntity = $payload['payload']['token']['entity'] ?? [];
        $tokenId = $tokenEntity['id'] ?? null;
        
        if ($tokenId) {
            $pdo->prepare("
                UPDATE student_bookings 
                SET razorpay_token_id=?, subscription_status='active', mandate_status='ACTIVE'
                WHERE razorpay_customer_id=? AND subscription_status='authenticated'
            ")->execute([
                $tokenId,
                $tokenEntity['customer_id'] ?? ''
            ]);
        }
        break;

    case 'subscription.charged':
        /*
         * Subscription billing cycle charged.
         */
        $subscriptionEntity = $payload['payload']['subscription']['entity'] ?? [];
        $subId = $subscriptionEntity['id'] ?? null;

        if ($subId && $paymentId) {
            $pdo->prepare("
                UPDATE payments 
                SET payment_status='PAID',
                    razorpay_payment_id=?,
                    razorpay_subscription_id=?,
                    paid_at=NOW()
                WHERE razorpay_subscription_id=? AND payment_status='PENDING'
                ORDER BY scheduled_date ASC
                LIMIT 1
            ")->execute([$paymentId, $subId, $subId]);
        }
        break;

    case 'subscription.cancelled':
        /*
         * Subscription was cancelled.
         */
        $subscriptionEntity = $payload['payload']['subscription']['entity'] ?? [];
        $subId = $subscriptionEntity['id'] ?? null;
        if ($subId) {
            $pdo->prepare("
                UPDATE student_bookings 
                SET subscription_status='cancelled', mandate_status='CANCELLED'
                WHERE razorpay_subscription_id=?
            ")->execute([$subId]);
        }
        break;
}

http_response_code(200);
echo json_encode(['status' => 'ok']);
