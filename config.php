<?php
/**
 * Centralized Configuration for TEM B2C
 * All Razorpay keys, email settings, and fee amounts in one place.
 */

/* ─── Razorpay ─── */
define('RZP_KEY_ID',     'rzp_test_SDwKTskm09k6GX');
define('RZP_KEY_SECRET', '5o7G2ENkl3wE3EUUSb3CKD9v');
define('RZP_WEBHOOK_SECRET', 'YOUR_WEBHOOK_SECRET_HERE'); // set in Razorpay Dashboard → Webhooks

/* ─── Session Fee Amounts (INR) ─── */
define('FEE_PSYCHOMETRIC', 999);
define('FEE_GROUP',        999);
define('FEE_ONE_TO_ONE',  1999);
define('FEE_TOKEN',          1);   // ₹1 token at booking

define('AMOUNT_MAP', [
    'PSYCHOMETRIC' => FEE_PSYCHOMETRIC,
    'GROUP'        => FEE_GROUP,
    'ONE_TO_ONE'   => FEE_ONE_TO_ONE,
]);

/* ─── SMTP / Email ─── */
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',      587);
define('SMTP_USER',     'suryatestapi@gmail.com');   // ← replace
define('SMTP_PASS',     'iwbglqhixayxufrm');       // ← replace (Google App Password)
define('SMTP_FROM_NAME','TEM Academy');

/* ─── MSG91 Settings ─── */
define('MSG91_AUTH_KEY', '492747A7IJv2i8D6985bf13P1'); // Replace with actual key
define('MSG91_DOMAIN', 'mail.educationdecision.com'); // Replace with verified domain
define('MSG91_FROM_EMAIL', 'noreply@educationdecision.com'); // Replace
define('MSG91_TEMPLATE_ID', 'global_otp');

/* ─── Base URL ─── */

define('BASE_URL', 'http://localhost/temb2c');
