<?php
/**
 * Migration: Add sidebar ad custom columns to posts table
 * Alokito Gour Clone
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';

$pdo = get_db();

// 1. Check existing columns in SQLite
$cols = [];
$stmt = $pdo->query("PRAGMA table_info(`posts`)");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $cols[] = $row['name'];
}

$columnsToAdd = [
    'sidebar_ad_type' => "TEXT DEFAULT 'global'",
    'sidebar_ad_image' => "TEXT NULL",
    'sidebar_ad_url' => "TEXT NULL",
    'sidebar_ad_code' => "TEXT NULL",
];

foreach ($columnsToAdd as $colName => $colDef) {
    if (!in_array($colName, $cols, true)) {
        $pdo->exec("ALTER TABLE `posts` ADD COLUMN `{$colName}` {$colDef}");
        echo "Added column `{$colName}` to posts table.\n";
    } else {
        echo "Column `{$colName}` already exists.\n";
    }
}

// 2. Also ensure 'news_sidebar' page layout exists in page_layouts
$checkLayout = $pdo->prepare("SELECT id FROM page_layouts WHERE page_slug = ?");
$checkLayout->execute(['news_sidebar']);
if (!$checkLayout->fetch()) {
    $newsSidebarLayout = [
        [
            'id' => 'sec_side_top_ad',
            'type' => 'ad_banner',
            'title' => 'ডান পাশের মূল বিজ্ঞাপন ব্যানার (Top Sidebar Ad)',
            'container' => 'main',
            'status' => 1,
            'settings' => ['position' => 'sidebar']
        ],
        [
            'id' => 'sec_side_tabs',
            'type' => 'sidebar_tabs',
            'title' => 'সর্বশেষ ও জনপ্রিয় সংবাদ ট্যাব',
            'container' => 'main',
            'status' => 1,
            'settings' => ['limit' => 7]
        ],
        [
            'id' => 'sec_side_district',
            'type' => 'district_filter',
            'title' => 'আমার এলাকার খবর (জেলা ফিল্টার)',
            'container' => 'main',
            'status' => 1,
            'settings' => []
        ],
        [
            'id' => 'sec_side_bottom_ad',
            'type' => 'ad_banner',
            'title' => 'ডান পাশের নিচের বিজ্ঞাপন (Bottom Sidebar Ad)',
            'container' => 'main',
            'status' => 1,
            'settings' => ['position' => 'sidebar_bottom', 'custom_code' => '']
        ]
    ];

    $ins = $pdo->prepare("INSERT INTO page_layouts (page_slug, title, layout_data, status) VALUES (?, ?, ?, 1)");
    $ins->execute([
        'news_sidebar',
        'নিউজ পেজের সাইডবার ব্লক (News Article Sidebar)',
        json_encode($newsSidebarLayout, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    ]);
    echo "Default 'news_sidebar' layout initialized successfully.\n";
} else {
    echo "'news_sidebar' layout already exists.\n";
}

echo "Migration finished successfully.\n";
