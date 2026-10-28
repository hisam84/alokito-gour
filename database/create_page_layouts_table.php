<?php
/**
 * Create page_layouts table and seed initial layout for homepage
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';

$pdo = get_db();

// 1. Create table in SQLite
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `page_layouts` (
        `id` INTEGER PRIMARY KEY AUTOINCREMENT,
        `page_slug` TEXT NOT NULL UNIQUE,
        `title` TEXT NOT NULL,
        `layout_data` TEXT NOT NULL,
        `status` INTEGER DEFAULT 1,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    );
");

echo "Table `page_layouts` created/verified successfully.\n";

// 2. Default Homepage Layout Data
$defaultHomeLayout = [
    [
        'id' => 'sec_hero_lead',
        'type' => 'hero_lead',
        'title' => 'প্রধান ও শীর্ষ সংবাদ (Hero Lead Section)',
        'settings' => [
            'bottom_thumbs' => 3,
            'sub_leads' => 3
        ]
    ],
    [
        'id' => 'sec_ad_after_lead',
        'type' => 'ad_banner',
        'title' => 'বিজ্ঞাপন ব্যানার (লিডের পর)',
        'settings' => [
            'position' => 'after_lead'
        ]
    ],
    [
        'id' => 'sec_area_filter',
        'type' => 'district_filter',
        'title' => 'আমার এলাকার খবর',
        'container' => 'main',
        'settings' => []
    ],
    [
        'id' => 'sec_cat_chapai',
        'type' => 'category_block',
        'title' => 'চাঁপাইনবাবগঞ্জ',
        'settings' => [
            'category_slug' => 'chapai-nawabganj',
            'style' => 'featured_list',
            'limit' => 5,
            'show_more' => true
        ]
    ],
    [
        'id' => 'sec_cat_national',
        'type' => 'category_grid',
        'title' => 'জাতীয়',
        'settings' => [
            'category_slug' => 'national',
            'limit' => 4,
            'show_more' => true
        ]
    ],
    [
        'id' => 'sec_cat_politics_intl',
        'type' => 'category_split',
        'title' => 'রাজনীতি ও আন্তর্জাতিক',
        'settings' => [
            'left_category' => 'politics',
            'left_title' => 'রাজনীতি',
            'right_category' => 'international',
            'right_title' => 'আন্তর্জাতিক',
            'limit' => 4
        ]
    ],
    [
        'id' => 'sec_cat_economy_law',
        'type' => 'category_split',
        'title' => 'অর্থনীতি ও আইন-অধিকার',
        'settings' => [
            'left_category' => 'economy',
            'left_title' => 'অর্থনীতি',
            'right_category' => 'law-rights',
            'right_title' => 'আইন ও অধিকার',
            'limit' => 4
        ]
    ],
    [
        'id' => 'sec_cat_tech_religion',
        'type' => 'category_split',
        'title' => 'বিজ্ঞান ও প্রযুক্তি এবং ধর্ম',
        'settings' => [
            'left_category' => 'tech',
            'left_title' => 'বিজ্ঞান ও প্রযুক্তি',
            'right_category' => 'religion',
            'right_title' => 'ধর্ম ও জীবন',
            'limit' => 4
        ]
    ],
    [
        'id' => 'sec_cat_jobs_edu',
        'type' => 'category_split',
        'title' => 'চাকরি ও ক্যারিয়ার এবং শিক্ষা-সাহিত্য',
        'settings' => [
            'left_category' => 'jobs-career',
            'left_title' => 'চাকরি ও ক্যারিয়ার',
            'right_category' => 'education-literature',
            'right_title' => 'শিক্ষা ও সাহিত্য',
            'limit' => 4
        ]
    ],
    [
        'id' => 'sec_full_country_sports',
        'type' => 'full_width_split',
        'title' => 'সারাদেশ ও খেলাধুলা',
        'settings' => [
            'left_category' => 'country',
            'left_title' => 'সারাদেশ',
            'right_category' => 'sports',
            'right_title' => 'খেলাধুলা',
            'limit' => 4
        ]
    ],
    [
        'id' => 'sec_gallery',
        'type' => 'multimedia_gallery',
        'title' => 'ছবি ও ভিডিও গ্যালারি',
        'settings' => [
            'title' => 'ছবি ও ভিডিও গ্যালারি',
            'limit' => 4
        ]
    ]
];

$checkStmt = $pdo->prepare("SELECT id FROM page_layouts WHERE page_slug = ?");
$checkStmt->execute(['home']);
$exists = $checkStmt->fetch();

$jsonLayout = json_encode($defaultHomeLayout, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

if ($exists) {
    echo "Homepage layout already exists. Ready.\n";
} else {
    $insStmt = $pdo->prepare("INSERT INTO page_layouts (page_slug, title, layout_data, status) VALUES (?, ?, ?, 1)");
    $insStmt->execute(['home', 'প্রচ্ছদ পাতা (Homepage)', $jsonLayout]);
    echo "Default Homepage layout seeded successfully.\n";
}
