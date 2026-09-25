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

-- Payment architecture (Commit 3)
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `booking_id` INT(11) NOT NULL,
    `transaction_id` VARCHAR(100) NOT NULL DEFAULT '',
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `payment_method` VARCHAR(30) NOT NULL DEFAULT 'sandbox',
    `payment_status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_payment_booking` (`booking_id`),
    KEY `idx_payment_status` (`payment_status`),
    CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
