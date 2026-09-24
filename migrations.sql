-- ============================================================
-- TourBan migrations for EXISTING databases
-- Run once via phpMyAdmin / MySQL client on hosts that already
-- imported an older database.sql.
-- New installs only need database.sql (already contains these).
-- NOTE: skip any statement whose column already exists
-- (MySQL reports "Duplicate column name" otherwise).
-- ============================================================

-- Email verification (skipped on hosts where the users table pre-existed)
ALTER TABLE `users`
    ADD COLUMN `is_verified` TINYINT(1) NOT NULL DEFAULT 0;

-- Admin panel: role column (skipped on hosts where the users table
-- already existed when database.sql was imported)
ALTER TABLE `users`
    ADD COLUMN `role` VARCHAR(20) NOT NULL DEFAULT 'user';

-- Admin panel: account activation flag (1 = active, 0 = deactivated)
ALTER TABLE `users`
    ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1;

-- Admin panel: destination category (Beach, City, Cultural, ...)
ALTER TABLE `destinations`
    ADD COLUMN `category` VARCHAR(50) NOT NULL DEFAULT '';
