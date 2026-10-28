<?php
/**
 * Migration: Create sidebar_blocks table and seed default widgets
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';

$db = get_db();

$db->exec("
CREATE TABLE IF NOT EXISTS `sidebar_blocks` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `block_type` VARCHAR(50) NOT NULL DEFAULT 'custom_html',
    `content` TEXT NULL,
    `page_target` VARCHAR(100) NOT NULL DEFAULT 'all',
    `position_order` INTEGER NOT NULL DEFAULT 10,
    `show_title` INTEGER NOT NULL DEFAULT 1,
    `status` INTEGER NOT NULL DEFAULT 1,
    `settings` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);
");

// Check if any blocks exist
$count = (int)$db->query("SELECT COUNT(*) FROM `sidebar_blocks`")->fetchColumn();

if ($count === 0) {
    $stmt = $db->prepare("
        INSERT INTO `sidebar_blocks` 
        (`title`, `block_type`, `content`, `page_target`, `position_order`, `show_title`, `status`, `settings`) 
        VALUES (:title, :btype, :content, :page, :ord, :stitle, :status, :settings)
    ");

    $defaultBlocks = [
        [
            ':title'    => 'Sidebar Advertisement',
            ':btype'    => 'ad_banner',
            ':content'  => '',
            ':page'     => 'all',
            ':ord'      => 10,
            ':stitle'   => 0,
            ':status'   => 1,
            ':settings' => json_encode(['position' => 'sidebar']),
        ],
        [
            ':title'    => 'Latest & Popular News',
            ':btype'    => 'latest_popular',
            ':content'  => '',
            ':page'     => 'all',
            ':ord'      => 20,
            ':stitle'   => 0,
            ':status'   => 1,
            ':settings' => json_encode(['limit' => 7]),
        ],
        [
            ':title'    => 'আমার এলাকার খবর',
            ':btype'    => 'district_filter',
            ':content'  => '',
            ':page'     => 'all',
            ':ord'      => 30,
            ':stitle'   => 1,
            ':status'   => 1,
            ':settings' => json_encode([]),
        ],
    ];

    foreach ($defaultBlocks as $block) {
        $stmt->execute($block);
    }

    echo "sidebar_blocks table created and 3 default blocks seeded successfully.\n";
} else {
    echo "sidebar_blocks table already exists with $count blocks.\n";
}
