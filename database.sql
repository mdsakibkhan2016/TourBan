-- ============================================================
-- TourBan production database schema
-- Import once via phpMyAdmin / MySQL client on your host.
-- Do NOT use config/init_database.php in production.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `address` TEXT NULL,
    `phone` VARCHAR(20) NULL,
    `birthdate` DATE NULL,
    `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `role` VARCHAR(20) NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Email verification / password-reset OTPs
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `otp_verifications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `code` VARCHAR(10) NOT NULL,
    `purpose` VARCHAR(30) NOT NULL DEFAULT 'registration',
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_otp_user` (`user_id`),
    KEY `idx_otp_email_purpose` (`email`, `purpose`),
    CONSTRAINT `fk_otp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Password reset tokens (hashes only — never store raw tokens)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_reset_user` (`user_id`),
    KEY `idx_reset_hash` (`token_hash`),
    CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Persistent remember-me tokens (hashes only)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `remember_tokens` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `token_hash` CHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_remember_hash` (`token_hash`),
    KEY `idx_remember_user` (`user_id`),
    CONSTRAINT `fk_remember_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Destinations catalog
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `destinations` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(120) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `country` VARCHAR(100) NOT NULL DEFAULT '',
    `region` VARCHAR(50) NOT NULL DEFAULT '',
    `description` TEXT NULL,
    `image_url` VARCHAR(500) NOT NULL DEFAULT '',
    `price_from` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `duration_days` INT(11) NOT NULL DEFAULT 0,
    `group_size` VARCHAR(50) NOT NULL DEFAULT '',
    `rating` DECIMAL(2,1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_dest_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Bookings
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `booking_ref` VARCHAR(20) NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `travel_date` DATE NULL,
    `travelers` INT(11) NOT NULL DEFAULT 1,
    `special_requests` TEXT NULL,
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_booking_ref` (`booking_ref`),
    KEY `idx_booking_user` (`user_id`),
    KEY `idx_booking_status` (`status`),
    CONSTRAINT `fk_booking_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Booking line items
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `booking_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `booking_id` INT(11) NOT NULL,
    `destination_id` INT(11) NOT NULL,
    `destination_name` VARCHAR(150) NOT NULL,
    `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `quantity` INT(11) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_item_booking` (`booking_id`),
    KEY `idx_item_destination` (`destination_id`),
    CONSTRAINT `fk_item_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_item_destination` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Seed destinations (matches public catalog)
-- ------------------------------------------------------------
INSERT IGNORE INTO `destinations`
    (`slug`, `name`, `country`, `region`, `description`, `image_url`, `price_from`, `duration_days`, `group_size`, `rating`)
VALUES
    ('rome-italy', 'Rome, Italy', 'Italy', 'Europe',
     'Explore the eternal city with its ancient history, magnificent architecture, and world-renowned cuisine.',
     'https://images.unsplash.com/photo-1515859005217-8a1f08870f59?w=400&h=250&fit=crop&crop=center&auto=format&q=80',
     599.00, 7, '2-8 People', 4.9),
    ('santorini-greece', 'Santorini, Greece', 'Greece', 'Europe',
     'Experience breathtaking sunsets and crystal-clear waters in this stunning island paradise.',
     'https://images.unsplash.com/photo-1613395877344-13d4a8e0d49e?w=400&h=250&fit=crop&crop=center&auto=format&q=80',
     799.00, 5, '2-6 People', 4.8),
    ('bali-indonesia', 'Bali, Indonesia', 'Indonesia', 'Asia',
     'Discover tropical beaches, ancient temples, and lush rice terraces in the Island of the Gods.',
     'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=400&h=250&fit=crop&crop=center&auto=format&q=80',
     699.00, 8, '2-10 People', 4.7),
    ('paris-france', 'Paris, France', 'France', 'Europe',
     'Fall in love with the City of Light — iconic landmarks, art, fashion, and cuisine.',
     'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=400&h=250&fit=crop&crop=center&auto=format&q=80',
     749.00, 6, '2-8 People', 4.8),
    ('tokyo-japan', 'Tokyo, Japan', 'Japan', 'Asia',
     'Experience the perfect blend of tradition and futuristic city life in Japan capital.',
     'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=400&h=250&fit=crop&crop=center&auto=format&q=80',
     899.00, 9, '2-6 People', 4.9),
    ('maldives', 'Maldives', 'Maldives', 'Asia',
     'Luxury overwater villas, turquoise lagoons, and world-class snorkeling.',
     'https://images.unsplash.com/photo-1514282401047-d79a71a590e8?w=400&h=250&fit=crop&crop=center&auto=format&q=80',
     1299.00, 6, '2-4 People', 4.9);

SET FOREIGN_KEY_CHECKS = 1;
