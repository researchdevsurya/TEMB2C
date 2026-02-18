-- ============================================================
-- TEM B2C – Migration v5: Razorpay Subscription + Email
-- Run in phpMyAdmin or MySQL CLI on database `temb2c`
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+05:30";

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS migrate_v5()
BEGIN

    -- 1. student_bookings: add subscription columns
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='student_bookings' AND COLUMN_NAME='razorpay_subscription_id') THEN
        ALTER TABLE `student_bookings` ADD COLUMN `razorpay_subscription_id` varchar(120) DEFAULT NULL AFTER `razorpay_mandate_id`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='student_bookings' AND COLUMN_NAME='razorpay_token_id') THEN
        ALTER TABLE `student_bookings` ADD COLUMN `razorpay_token_id` varchar(120) DEFAULT NULL AFTER `razorpay_subscription_id`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='student_bookings' AND COLUMN_NAME='subscription_status') THEN
        ALTER TABLE `student_bookings` ADD COLUMN `subscription_status` enum('created','authenticated','active','completed','cancelled','halted','pending') DEFAULT 'created' AFTER `razorpay_token_id`;
    END IF;

    -- 2. students: track welcome email sent
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='students' AND COLUMN_NAME='welcome_email_sent') THEN
        ALTER TABLE `students` ADD COLUMN `welcome_email_sent` tinyint(1) DEFAULT 0 AFTER `password`;
    END IF;

    -- 3. payments: add subscription_payment_id for tracking sub charges
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='razorpay_subscription_id') THEN
        ALTER TABLE `payments` ADD COLUMN `razorpay_subscription_id` varchar(120) DEFAULT NULL AFTER `razorpay_payment_id`;
    END IF;

    -- 4. payment_logs: add subscription events
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payment_logs' AND COLUMN_NAME='event_type') THEN
        ALTER TABLE `payment_logs` ADD COLUMN `event_type` varchar(100) DEFAULT NULL AFTER `payment_type`;
    END IF;

END //
DELIMITER ;

CALL migrate_v5();
DROP PROCEDURE IF EXISTS migrate_v5;

COMMIT;
