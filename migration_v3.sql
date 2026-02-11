-- ============================================================
-- TEM B2C – Database Migration v3
-- Run this in phpMyAdmin or MySQL CLI on database `temb2c`
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+05:30";

-- -------------------------------------------------------
-- 1. counsellors table (create if not exists)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `counsellors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -------------------------------------------------------
-- 2. counsellor_slots table (create if not exists)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `counsellor_slots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `counsellor_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `slot_date` date NOT NULL,
  `start_time` varchar(10) NOT NULL,
  `end_time` varchar(10) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `booked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `counsellor_id` (`counsellor_id`),
  KEY `event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -------------------------------------------------------
-- 3. Standardize schedule_master columns
--    Safe: uses ALTER IGNORE + IF NOT EXISTS style checks
-- -------------------------------------------------------

-- Rename old columns if they exist (wrap in procedure for safety)
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS migrate_schedule_master()
BEGIN
    -- Check and rename psychometric_date1 -> psychometric_date_1
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='schedule_master' AND COLUMN_NAME='psychometric_date1') THEN
        ALTER TABLE `schedule_master` CHANGE `psychometric_date1` `psychometric_date_1` date DEFAULT NULL;
    END IF;

    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='schedule_master' AND COLUMN_NAME='psychometric_date2') THEN
        ALTER TABLE `schedule_master` CHANGE `psychometric_date2` `psychometric_date_2` date DEFAULT NULL;
    END IF;

    -- Rename block columns to session columns
    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='schedule_master' AND COLUMN_NAME='block1_session1') THEN
        ALTER TABLE `schedule_master` CHANGE `block1_session1` `session1_name` varchar(150) DEFAULT NULL;
    END IF;

    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='schedule_master' AND COLUMN_NAME='block1_date1') THEN
        ALTER TABLE `schedule_master` CHANGE `block1_date1` `session1_slot_1` date DEFAULT NULL;
    END IF;

    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='schedule_master' AND COLUMN_NAME='block1_session2') THEN
        ALTER TABLE `schedule_master` CHANGE `block1_session2` `session1_slot_2_name` varchar(150) DEFAULT NULL;
    END IF;

    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='schedule_master' AND COLUMN_NAME='block1_date2') THEN
        ALTER TABLE `schedule_master` CHANGE `block1_date2` `session1_slot_2` date DEFAULT NULL;
    END IF;

    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='schedule_master' AND COLUMN_NAME='block2_session1') THEN
        ALTER TABLE `schedule_master` CHANGE `block2_session1` `session2_name` varchar(150) DEFAULT NULL;
    END IF;

    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='schedule_master' AND COLUMN_NAME='block2_date1') THEN
        ALTER TABLE `schedule_master` CHANGE `block2_date1` `session2_slot_1` date DEFAULT NULL;
    END IF;

    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='schedule_master' AND COLUMN_NAME='block2_session2') THEN
        ALTER TABLE `schedule_master` CHANGE `block2_session2` `session2_slot_2_name` varchar(150) DEFAULT NULL;
    END IF;

    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='schedule_master' AND COLUMN_NAME='block2_date2') THEN
        ALTER TABLE `schedule_master` CHANGE `block2_date2` `session2_slot_2` date DEFAULT NULL;
    END IF;

    IF EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='schedule_master' AND COLUMN_NAME='counsellors_name') THEN
        ALTER TABLE `schedule_master` CHANGE `counsellors_name` `counsellor_name` varchar(120) DEFAULT NULL;
    END IF;

    -- Add columns if they don't exist (for fresh installs)
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='schedule_master' AND COLUMN_NAME='psychometric_date_1') THEN
        ALTER TABLE `schedule_master` ADD COLUMN `psychometric_date_1` date DEFAULT NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='schedule_master' AND COLUMN_NAME='psychometric_date_2') THEN
        ALTER TABLE `schedule_master` ADD COLUMN `psychometric_date_2` date DEFAULT NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='schedule_master' AND COLUMN_NAME='session1_name') THEN
        ALTER TABLE `schedule_master` ADD COLUMN `session1_name` varchar(150) DEFAULT NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='schedule_master' AND COLUMN_NAME='session1_slot_1') THEN
        ALTER TABLE `schedule_master` ADD COLUMN `session1_slot_1` date DEFAULT NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='schedule_master' AND COLUMN_NAME='session1_slot_2') THEN
        ALTER TABLE `schedule_master` ADD COLUMN `session1_slot_2` date DEFAULT NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='schedule_master' AND COLUMN_NAME='session2_name') THEN
        ALTER TABLE `schedule_master` ADD COLUMN `session2_name` varchar(150) DEFAULT NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='schedule_master' AND COLUMN_NAME='session2_slot_1') THEN
        ALTER TABLE `schedule_master` ADD COLUMN `session2_slot_1` date DEFAULT NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='schedule_master' AND COLUMN_NAME='session2_slot_2') THEN
        ALTER TABLE `schedule_master` ADD COLUMN `session2_slot_2` date DEFAULT NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='schedule_master' AND COLUMN_NAME='counsellor_name') THEN
        ALTER TABLE `schedule_master` ADD COLUMN `counsellor_name` varchar(120) DEFAULT NULL;
    END IF;

END //
DELIMITER ;

CALL migrate_schedule_master();
DROP PROCEDURE IF EXISTS migrate_schedule_master;

-- -------------------------------------------------------
-- 4. Add missing columns to student_bookings
-- -------------------------------------------------------
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS migrate_student_bookings()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='student_bookings' AND COLUMN_NAME='counsellor_id') THEN
        ALTER TABLE `student_bookings` ADD COLUMN `counsellor_id` int(11) DEFAULT NULL AFTER `counsellor_name`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='student_bookings' AND COLUMN_NAME='counsellor_slot_id') THEN
        ALTER TABLE `student_bookings` ADD COLUMN `counsellor_slot_id` int(11) DEFAULT NULL AFTER `counsellor_id`;
    END IF;
END //
DELIMITER ;

CALL migrate_student_bookings();
DROP PROCEDURE IF EXISTS migrate_student_bookings;

-- -------------------------------------------------------
-- 5. Add missing columns to students (board, stream, specialization)
-- -------------------------------------------------------
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS migrate_students()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='students' AND COLUMN_NAME='board') THEN
        ALTER TABLE `students` ADD COLUMN `board` varchar(50) DEFAULT NULL AFTER `std`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='students' AND COLUMN_NAME='stream') THEN
        ALTER TABLE `students` ADD COLUMN `stream` varchar(50) DEFAULT NULL AFTER `board`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='students' AND COLUMN_NAME='specialization') THEN
        ALTER TABLE `students` ADD COLUMN `specialization` varchar(100) DEFAULT NULL AFTER `stream`;
    END IF;
END //
DELIMITER ;

CALL migrate_students();
DROP PROCEDURE IF EXISTS migrate_students;

COMMIT;
