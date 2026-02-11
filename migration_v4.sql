-- ============================================================
-- TEM B2C – Fix Database Constraints (Migration v4)
-- Run this in phpMyAdmin or MySQL CLI on database `temb2c`
-- ============================================================

-- The `student_bookings` table had a unique constraint on (booked_date, one_to_one_slot).
-- This prevented multiple students from booking different counsellors at the same time.
-- We must drop this index to allow concurrent bookings.

ALTER TABLE `student_bookings` DROP INDEX `booked_date`;
