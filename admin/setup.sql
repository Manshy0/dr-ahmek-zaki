-- ==========================================
-- Dr. Ahmed Zaki Admin Panel
-- MySQL Database Setup Script
-- ==========================================
-- Run this script once to create all necessary tables
-- Database: drahmed
-- ==========================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ==========================================
-- USERS TABLE
-- ==========================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) DEFAULT '',
    `role` ENUM('admin', 'editor', 'author', 'contributor') NOT NULL DEFAULT 'editor',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_login` DATETIME DEFAULT NULL,
    INDEX `idx_username` (`username`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: Admin@123)
INSERT INTO `users` (`username`, `password`, `name`, `email`, `role`, `created_at`) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@drahmed.com', 'admin', NOW())
ON DUPLICATE KEY UPDATE `id` = `id`;

-- ==========================================
-- LOGIN ATTEMPTS TABLE (for brute force protection)
-- ==========================================
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `attempt_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `last_attempt` DATETIME DEFAULT NULL,
    INDEX `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- ACTIVITY LOG TABLE
-- ==========================================
CREATE TABLE IF NOT EXISTS `activity_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user` VARCHAR(50) NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` JSON DEFAULT NULL,
    `ip` VARCHAR(45) DEFAULT '',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- POSTS TABLE (for blog and news)
-- ==========================================
CREATE TABLE IF NOT EXISTS `posts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `type` ENUM('blog', 'news', 'treatment', 'page') NOT NULL DEFAULT 'blog',
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `excerpt` TEXT DEFAULT NULL,
    `content` LONGTEXT DEFAULT NULL,
    `featured_image` VARCHAR(500) DEFAULT '',
    `author` VARCHAR(50) NOT NULL DEFAULT 'admin',
    `status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'published',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_type_slug` (`type`, `slug`),
    INDEX `idx_type` (`type`),
    INDEX `idx_status` (`status`),
    INDEX `idx_author` (`author`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- MEDIA TABLE
-- ==========================================
CREATE TABLE IF NOT EXISTS `media` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `filename` VARCHAR(255) NOT NULL,
    `path` VARCHAR(500) NOT NULL,
    `type` VARCHAR(50) DEFAULT 'image',
    `size` INT UNSIGNED DEFAULT 0,
    `alt_text` VARCHAR(255) DEFAULT '',
    `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_type` (`type`),
    INDEX `idx_uploaded` (`uploaded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- MENUS TABLE
-- ==========================================
CREATE TABLE IF NOT EXISTS `menus` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(100) NOT NULL,
    `url` VARCHAR(500) NOT NULL,
    `position` INT UNSIGNED NOT NULL DEFAULT 0,
    `parent_id` INT UNSIGNED DEFAULT NULL,
    `target` ENUM('_self', '_blank') DEFAULT '_self',
    INDEX `idx_position` (`position`),
    FOREIGN KEY (`parent_id`) REFERENCES `menus`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- SEO SETTINGS TABLE
-- ==========================================
CREATE TABLE IF NOT EXISTS `seo_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page_path` VARCHAR(255) NOT NULL UNIQUE,
    `meta_title` VARCHAR(255) DEFAULT '',
    `meta_description` TEXT DEFAULT NULL,
    `og_image` VARCHAR(500) DEFAULT '',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_page_path` (`page_path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- SETTINGS TABLE (key-value store)
-- ==========================================
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT DEFAULT NULL,
    INDEX `idx_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO `settings` (`setting_key`, `value`) VALUES 
    ('site_name', 'Dr. Ahmed Zaki'),
    ('site_tagline', 'Orthopedic Surgeon'),
    ('contact_email', 'info@drahmed.com'),
    ('contact_phone', '+971 XX XXX XXXX'),
    ('social_facebook', ''),
    ('social_instagram', ''),
    ('social_linkedin', ''),
    ('social_youtube', '')
ON DUPLICATE KEY UPDATE `id` = `id`;

-- ==========================================
-- IMAGE REPLACEMENTS TABLE
-- Tracks original image -> replacement image
-- ==========================================
CREATE TABLE IF NOT EXISTS `image_replacements` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `original_path` VARCHAR(500) NOT NULL UNIQUE,
    `new_path` VARCHAR(500) NOT NULL,
    `alt_text` VARCHAR(255) DEFAULT '',
    `updated_by` VARCHAR(50) DEFAULT 'admin',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_original` (`original_path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert appearance defaults
INSERT INTO `settings` (`setting_key`, `value`) VALUES 
    ('logo_url', '/wp-content/uploads/2025/05/P-1-1024x201.png'),
    ('favicon_url', '/wp-content/uploads/2025/06/cropped-favicon-180x180.png'),
    ('topbar_enabled', '1'),
    ('topbar_text', 'Book Your Appointment Today'),
    ('topbar_phone', '+971 XX XXX XXXX'),
    ('topbar_email', 'info@drahmed.com'),
    ('topbar_bg_color', '#0a4d68'),
    ('topbar_text_color', '#ffffff'),
    ('primary_color', '#0a4d68'),
    ('whatsapp_number', '')
ON DUPLICATE KEY UPDATE `id` = `id`;

-- ==========================================
-- APPOINTMENTS TABLE (optional)
-- ==========================================
CREATE TABLE IF NOT EXISTS `appointments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `patient_name` VARCHAR(100) NOT NULL,
    `patient_email` VARCHAR(100) DEFAULT '',
    `patient_phone` VARCHAR(20) NOT NULL,
    `preferred_date` DATE DEFAULT NULL,
    `preferred_time` TIME DEFAULT NULL,
    `message` TEXT DEFAULT NULL,
    `status` ENUM('pending', 'confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_date` (`preferred_date`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- CONTACT MESSAGES TABLE
-- ==========================================
CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) DEFAULT '',
    `subject` VARCHAR(255) DEFAULT '',
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_is_read` (`is_read`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================
-- DONE!
-- ==========================================
-- Default admin login:
-- Username: admin
-- Password: Admin@123
-- 
-- IMPORTANT: Change this password immediately!
-- ==========================================
