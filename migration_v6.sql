-- ============================================================
-- TEM B2C – Migration v6: Booking Academic Details
-- Run in phpMyAdmin or MySQL CLI on database `temb2c`
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+05:30";

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS migrate_v6()
BEGIN

    -- Add academic columns to student_bookings
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

CALL migrate_v6();
DROP PROCEDURE IF EXISTS migrate_v6;

COMMIT;
