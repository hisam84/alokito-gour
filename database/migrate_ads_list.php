<?php
/**
 * Migration: Remove UNIQUE constraint from position in advertisements table
 * Add created_at, updated_at
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

try {
    $db = get_db();
    
    // Check if table has UNIQUE constraint on position
    $indices = $db->query("PRAGMA index_list(advertisements)")->fetchAll(PDO::FETCH_ASSOC);
    $hasUnique = false;
    foreach ($indices as $idx) {
        if (!empty($idx['unique'])) {
            $info = $db->query("PRAGMA index_info(" . $idx['name'] . ")")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($info as $col) {
                if ($col['name'] === 'position') {
                    $hasUnique = true;
                    break 2;
                }
            }
        }
    }

    echo "Checking unique constraint on position: " . ($hasUnique ? "Found" : "Not found") . PHP_EOL;

    // We rebuild table to allow multiple ads and remove unique constraint
    $db->beginTransaction();

    $db->exec("
        CREATE TABLE IF NOT EXISTS `advertisements_new` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `position` TEXT DEFAULT 'none',
            `title` TEXT NOT NULL,
            `image_path` TEXT NULL,
            `target_url` TEXT NULL,
            `custom_code` TEXT NULL,
            `status` INTEGER DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    $db->exec("
        INSERT INTO `advertisements_new` (`id`, `position`, `title`, `image_path`, `target_url`, `custom_code`, `status`)
        SELECT `id`, `position`, COALESCE(`title`, 'বিজ্ঞাপন #' || `id`), `image_path`, `target_url`, `custom_code`, `status`
        FROM `advertisements`;
    ");

    $db->exec("DROP TABLE `advertisements`;");
    $db->exec("ALTER TABLE `advertisements_new` RENAME TO `advertisements`;");
    $db->exec("CREATE INDEX IF NOT EXISTS `idx_ads_pos_status` ON `advertisements`(`position`, `status`);");

    $db->commit();
    echo "Migration completed successfully!" . PHP_EOL;

    // Verify
    $rows = $db->query("SELECT * FROM advertisements")->fetchAll(PDO::FETCH_ASSOC);
    echo "Current rows in advertisements: " . count($rows) . PHP_EOL;
    foreach ($rows as $r) {
        echo "- [ID {$r['id']}] {$r['title']} | Pos: {$r['position']} | Status: {$r['status']}" . PHP_EOL;
    }
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
