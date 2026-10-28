<?php
/**
 * Migration: Add division, district, upazila columns to posts table
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';

$db = get_db();

// Check if columns already exist
$cols = $db->query("PRAGMA table_info(posts)")->fetchAll(PDO::FETCH_ASSOC);
$existingCols = array_column($cols, 'name');

$added = [];
if (!in_array('division', $existingCols, true)) {
    $db->exec("ALTER TABLE `posts` ADD COLUMN `division` VARCHAR(100) NULL");
    $added[] = 'division';
}

if (!in_array('district', $existingCols, true)) {
    $db->exec("ALTER TABLE `posts` ADD COLUMN `district` VARCHAR(100) NULL");
    $added[] = 'district';
}

if (!in_array('upazila', $existingCols, true)) {
    $db->exec("ALTER TABLE `posts` ADD COLUMN `upazila` VARCHAR(100) NULL");
    $added[] = 'upazila';
}

// Create indexes
$db->exec("CREATE INDEX IF NOT EXISTS idx_posts_division ON `posts`(`division`)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_posts_district ON `posts`(`district`)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_posts_upazila ON `posts`(`upazila`)");

if (!empty($added)) {
    echo "Added columns to posts table: " . implode(', ', $added) . "\n";
} else {
    echo "Location columns already exist on posts table.\n";
}

// Update seed post with Chapainawabganj location
$db->exec("
    UPDATE `posts` 
    SET `division` = 'রাজশাহী', `district` = 'চাঁপাইনবাবগঞ্জ', `upazila` = 'চাঁপাইনবাবগঞ্জ সদর' 
    WHERE `id` = 1 AND `district` IS NULL
");
echo "Updated default post location to রাজশাহী > চাঁপাইনবাবগঞ্জ > চাঁপাইনবাবগঞ্জ সদর.\n";

