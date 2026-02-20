-- ============================================================
-- TEM B2C – Migration v7: Fix Payment & Booking Schema
-- Run in phpMyAdmin or MySQL CLI on database `temb2c`
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+05:30";

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS migrate_v7()
BEGIN

    -- 1. Add razorpay_customer_id to students (Required for payment.php)
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='students' AND COLUMN_NAME='razorpay_customer_id') THEN
        ALTER TABLE `students` ADD COLUMN `razorpay_customer_id` varchar(100) DEFAULT NULL AFTER `contact_number`;
    END IF;

    -- 2. Add academic columns to student_bookings (Previously cancelled in v6)
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='student_bookings' AND COLUMN_NAME='class_std') THEN
        ALTER TABLE `student_bookings` ADD COLUMN `class_std` varchar(10) DEFAULT NULL AFTER `student_id`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='student_bookings' AND COLUMN_NAME='board') THEN
        ALTER TABLE `student_bookings` ADD COLUMN `board` varchar(50) DEFAULT NULL AFTER `class_std`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='student_bookings' AND COLUMN_NAME='stream') THEN
        ALTER TABLE `student_bookings` ADD COLUMN `stream` varchar(50) DEFAULT NULL AFTER `board`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='student_bookings' AND COLUMN_NAME='specialization') THEN
        ALTER TABLE `student_bookings` ADD COLUMN `specialization` varchar(100) DEFAULT NULL AFTER `stream`;
    END IF;

END //
DELIMITER ;

CALL migrate_v7();
DROP PROCEDURE IF EXISTS migrate_v7;

COMMIT;
