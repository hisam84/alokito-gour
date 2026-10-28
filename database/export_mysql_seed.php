<?php
/**
 * Export SQLite schema and data into setup.sql for cPanel MySQL deployment
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';

$pdo = get_db();

$passwordHash = password_hash('Admin@123456', PASSWORD_DEFAULT);

$sql = <<<'SQL'
-- Database: alokito_gour
-- Charset: utf8mb4_unicode_ci
-- Generated for cPanel MySQL Deployment

CREATE DATABASE IF NOT EXISTS `alokito_gour` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `alokito_gour`;

-- 1. Settings Table
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `site_title` VARCHAR(255) NOT NULL DEFAULT 'Alokito Gour - আলোকিত গৌড়',
  `site_tagline` VARCHAR(255) NULL DEFAULT 'সত্যের সাথে, আগামীর পথে',
  `logo_path` VARCHAR(255) NULL DEFAULT 'https://alokitogour.com/photos/logo-2024-09-18-02-37-58.png',
  `favicon_path` VARCHAR(255) NULL DEFAULT 'https://alokitogour.com/photos/favicon-2024-12-22-17-06-58.png',
  `editor_name` VARCHAR(150) NULL DEFAULT 'মোহাঃ আব্দুল্লাহ',
  `publisher_name` VARCHAR(150) NULL DEFAULT 'মোহাঃ আব্দুল্লাহ',
  `office_address` TEXT NULL,
  `contact_phone` VARCHAR(100) NULL DEFAULT '01333479192 (সম্পাদক), 01333479193 (অফিস)',
  `contact_email` VARCHAR(100) NULL DEFAULT 'editor@alokitogour.com',
  `facebook_url` VARCHAR(255) NULL DEFAULT 'https://facebook.com/alokitogour',
  `youtube_url` VARCHAR(255) NULL DEFAULT 'https://youtube.com/@alokitogour',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('superadmin', 'editor', 'reporter') DEFAULT 'superadmin',
  `status` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL UNIQUE,
  `order_index` INT DEFAULT 0,
  `show_in_menu` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Posts Table
CREATE TABLE IF NOT EXISTS `posts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `title` VARCHAR(300) NOT NULL,
  `slug` VARCHAR(350) NOT NULL UNIQUE,
  `excerpt` TEXT NULL,
  `content` LONGTEXT NOT NULL,
  `thumbnail` VARCHAR(255) NULL,
  `caption` VARCHAR(255) NULL,
  `reporter_name` VARCHAR(150) NULL DEFAULT 'নিজস্ব প্রতিবেদক',
  `is_lead` TINYINT(1) DEFAULT 0,
  `is_breaking` TINYINT(1) DEFAULT 0,
  `views` INT DEFAULT 0,
  `status` ENUM('published', 'draft') DEFAULT 'published',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`category_id`),
  INDEX (`is_lead`),
  INDEX (`is_breaking`),
  INDEX (`views`),
  INDEX (`created_at`),
  CONSTRAINT `fk_posts_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_posts_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Advertisements Table
CREATE TABLE IF NOT EXISTS `advertisements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `position` VARCHAR(50) NOT NULL UNIQUE,
  `title` VARCHAR(100) NULL,
  `image_path` VARCHAR(255) NULL,
  `target_url` VARCHAR(255) NULL,
  `custom_code` TEXT NULL,
  `status` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- SEED DATA
-- ========================================================

-- Settings
INSERT INTO `settings` (`id`, `site_title`, `site_tagline`, `logo_path`, `favicon_path`, `editor_name`, `publisher_name`, `office_address`, `contact_phone`, `contact_email`, `facebook_url`, `youtube_url`)
VALUES (1, 'Alokito Gour - আলোকিত গৌড়', 'সত্যের সাথে, আগামীর পথে', 'https://alokitogour.com/photos/logo-2024-09-18-02-37-58.png', 'https://alokitogour.com/photos/favicon-2024-12-22-17-06-58.png', 'মোহাঃ আব্দুল্লাহ', 'মোহাঃ আব্দুল্লাহ', 'ক্লাব সুপার মার্কেট (৪র্থ তলা), সদর, চাঁপাইনবাবগঞ্জ।', '01333479192 (সম্পাদক), 01333479193 (অফিস)', 'editor@alokitogour.com', 'https://facebook.com/alokitogour', 'https://youtube.com/@alokitogour')
ON DUPLICATE KEY UPDATE `id` = 1;

-- Users (admin@alokitogour.com / Admin@123456)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`)
VALUES (1, 'সম্পাদক', 'admin@alokitogour.com', '$2y$10$e7dC3u0/vF6RvhjN5Q3BLe2Wz1FkXw6uL.5uG8d1sU6b7a2d8s0eK', 'superadmin', 1)
ON DUPLICATE KEY UPDATE `email` = `email`;

-- Default Ad Slots
INSERT INTO `advertisements` (`position`, `title`, `image_path`, `target_url`, `custom_code`, `status`) VALUES
('header_right', 'Header Leaderboard Ad', '', '#', '<div class="text-center p-3 border bg-light text-muted small" style="min-height:90px; display:flex; align-items:center; justify-content:center;">বিজ্ঞাপন দিন (৭২৮x৯০)</div>', 1),
('after_lead', 'After Lead Banner', '', '#', '<div class="text-center p-3 border bg-light text-muted small" style="min-height:90px; display:flex; align-items:center; justify-content:center;">বিজ্ঞাপন দিন (৯৭০x৯০)</div>', 1),
('sidebar', 'Sidebar Rectangle Ad', '', '#', '', 1),
('inside_article', 'In-Article Ad', '', '#', '<div class="text-center p-3 border bg-light text-muted small my-3" style="min-height:100px; display:flex; align-items:center; justify-content:center;">বিজ্ঞাপন দিন (ইন-আর্টিকেল)</div>', 1)
ON DUPLICATE KEY UPDATE `position` = VALUES(`position`);

SQL;

// Dump Categories
$sql .= "\n-- Categories\nINSERT INTO `categories` (`id`, `name`, `slug`, `order_index`, `show_in_menu`) VALUES\n";
$cats = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
$catLines = [];
foreach ($cats as $c) {
    $name = addslashes($c['name']);
    $slug = addslashes($c['slug']);
    $ord = (int)$c['order_index'];
    $menu = (int)$c['show_in_menu'];
    $catLines[] = "({$c['id']}, '{$name}', '{$slug}', {$ord}, {$menu})";
}
$sql .= implode(",\n", $catLines) . "\nON DUPLICATE KEY UPDATE `name` = VALUES(`name`);\n\n";

// Dump Posts
$sql .= "-- Posts\nINSERT INTO `posts` (`id`, `category_id`, `user_id`, `title`, `slug`, `excerpt`, `content`, `thumbnail`, `reporter_name`, `is_lead`, `is_breaking`, `views`, `status`, `created_at`) VALUES\n";
$posts = $pdo->query("SELECT * FROM posts ORDER BY id ASC")->fetchAll();
$postLines = [];
foreach ($posts as $p) {
    $cid = (int)$p['category_id'];
    $uid = 1;
    $title = addslashes($p['title']);
    $slug = addslashes($p['slug']);
    $excerpt = addslashes($p['excerpt'] ?? '');
    $content = addslashes($p['content'] ?? '');
    $thumb = addslashes($p['thumbnail'] ?? '');
    $rep = addslashes($p['reporter_name'] ?? 'নিজস্ব প্রতিবেদক');
    $lead = (int)$p['is_lead'];
    $break = (int)$p['is_breaking'];
    $views = (int)$p['views'];
    $st = addslashes($p['status'] ?? 'published');
    $dt = addslashes($p['created_at']);
    $postLines[] = "({$p['id']}, {$cid}, {$uid}, '{$title}', '{$slug}', '{$excerpt}', '{$content}', '{$thumb}', '{$rep}', {$lead}, {$break}, {$views}, '{$st}', '{$dt}')";
}
$sql .= implode(",\n", $postLines) . "\nON DUPLICATE KEY UPDATE `title` = VALUES(`title`);\n";

file_put_contents(dirname(__DIR__) . '/setup.sql', $sql);
echo "Exported setup.sql successfully with " . count($cats) . " categories and " . count($posts) . " posts.\n";
