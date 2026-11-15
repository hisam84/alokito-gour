<?php
/**
 * Auto-Migrator for White-Label News Portal Template
 * Ensures the database schema is updated to support all dynamic customization features.
 * Works seamlessly with both MySQL and SQLite.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';

function run_template_migrations(?PDO $db = null): array {
    if ($db === null) {
        $db = get_db();
    }

    $logs = [];
    $isSqlite = ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');

    // 1. Ensure `settings` table has all necessary custom columns
    $settingsColumns = [
        'logo_footer'               => "VARCHAR(255) NULL",
        'logo_mobile'               => "VARCHAR(255) NULL",
        'default_thumbnail'         => "VARCHAR(255) NULL",
        'watermark_path'            => "VARCHAR(255) NULL",
        'executive_editor'          => "VARCHAR(150) NULL",
        'advisory_editor'           => "VARCHAR(150) NULL",
        'gov_reg_no'                => "VARCHAR(150) NULL",
        'copyright_text'            => "TEXT NULL",
        'tech_partner_name'         => "VARCHAR(150) DEFAULT 'DataEnvelope'",
        'tech_partner_url'          => "VARCHAR(255) DEFAULT 'https://www.dataenvelope.com/'",
        'show_tech_partner'         => "TINYINT(1) DEFAULT 1",
        'footer_about_text'         => "TEXT NULL",
        'primary_color'             => "VARCHAR(20) DEFAULT '#f3730d'",
        'secondary_color'           => "VARCHAR(20) DEFAULT '#c82333'",
        'accent_color'              => "VARCHAR(20) DEFAULT '#0abb75'",
        'header_bg_color'           => "VARCHAR(20) DEFAULT '#ffffff'",
        'topbar_bg_color'           => "VARCHAR(20) DEFAULT '#f8f9fa'",
        'navbar_bg_color'           => "VARCHAR(20) DEFAULT '#ffffff'",
        'footer_bg_color'           => "VARCHAR(20) DEFAULT '#1a1e21'",
        'font_family'               => "VARCHAR(50) DEFAULT 'SolaimanLipi'",
        'font_size_base'            => "VARCHAR(10) DEFAULT '16px'",
        'theme_style'               => "VARCHAR(30) DEFAULT 'classic_news'",
        'show_topbar'               => "TINYINT(1) DEFAULT 1",
        'show_date_bangla'          => "TINYINT(1) DEFAULT 1",
        'show_date_english'         => "TINYINT(1) DEFAULT 0",
        'show_weather'              => "TINYINT(1) DEFAULT 1",
        'weather_city'              => "VARCHAR(100) DEFAULT 'ঢাকা'",
        'show_live_tv'              => "TINYINT(1) DEFAULT 0",
        'live_tv_url'               => "VARCHAR(255) NULL",
        'show_epaper'               => "TINYINT(1) DEFAULT 0",
        'epaper_url'                => "VARCHAR(255) NULL",
        'show_ticker'               => "TINYINT(1) DEFAULT 1",
        'ticker_title'              => "VARCHAR(100) DEFAULT 'সংবাদ শিরোনাম'",
        'twitter_url'               => "VARCHAR(255) NULL",
        'instagram_url'             => "VARCHAR(255) NULL",
        'linkedin_url'              => "VARCHAR(255) NULL",
        'tiktok_url'                => "VARCHAR(255) NULL",
        'whatsapp_number'           => "VARCHAR(100) NULL",
        'whatsapp_channel_url'      => "VARCHAR(255) NULL",
        'telegram_url'              => "VARCHAR(255) NULL",
        'default_division'          => "VARCHAR(100) DEFAULT 'ঢাকা'",
        'default_district'          => "VARCHAR(100) DEFAULT 'ঢাকা'",
        'meta_description'          => "TEXT NULL",
        'meta_keywords'             => "TEXT NULL",
        'ga4_id'                    => "VARCHAR(50) NULL",
        'fb_pixel_id'               => "VARCHAR(50) NULL",
        'google_site_verification'  => "VARCHAR(100) NULL",
        'custom_head_code'          => "TEXT NULL",
        'custom_footer_code'        => "TEXT NULL",
    ];

    // Get existing columns of `settings`
    $existingCols = [];
    if ($isSqlite) {
        $stmt = $db->query("PRAGMA table_info(`settings`)");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existingCols[strtolower($row['name'])] = true;
        }
    } else {
        $stmt = $db->query("DESCRIBE `settings`");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existingCols[strtolower($row['Field'])] = true;
        }
    }

    foreach ($settingsColumns as $colName => $colDef) {
        if (!isset($existingCols[strtolower($colName)])) {
            try {
                $db->exec("ALTER TABLE `settings` ADD COLUMN `{$colName}` {$colDef}");
                $logs[] = "Added column `{$colName}` to `settings`";
            } catch (Exception $e) {
                $logs[] = "Error adding `{$colName}`: " . $e->getMessage();
            }
        }
    }

    // 2. Ensure `pages` table exists
    if ($isSqlite) {
        $createPagesSql = "CREATE TABLE IF NOT EXISTS `pages` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `title` VARCHAR(255) NOT NULL,
            `slug` VARCHAR(255) NOT NULL UNIQUE,
            `content` TEXT NOT NULL,
            `meta_title` VARCHAR(255) NULL,
            `meta_description` TEXT NULL,
            `status` VARCHAR(20) DEFAULT 'published',
            `show_in_menu` TINYINT(1) DEFAULT 0,
            `show_in_footer` TINYINT(1) DEFAULT 1,
            `order_index` INT DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
    } else {
        $createPagesSql = "CREATE TABLE IF NOT EXISTS `pages` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `slug` VARCHAR(255) NOT NULL UNIQUE,
            `content` LONGTEXT NOT NULL,
            `meta_title` VARCHAR(255) NULL,
            `meta_description` TEXT NULL,
            `status` VARCHAR(20) DEFAULT 'published',
            `show_in_menu` TINYINT(1) DEFAULT 0,
            `show_in_footer` TINYINT(1) DEFAULT 1,
            `order_index` INT DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    }

    try {
        $db->exec($createPagesSql);
        $logs[] = "Verified `pages` table.";
    } catch (Exception $e) {
        $logs[] = "Error creating `pages` table: " . $e->getMessage();
    }

    // 3. Seed default legal / informational pages if empty
    $pageCount = (int)$db->query("SELECT COUNT(*) FROM `pages`")->fetchColumn();
    if ($pageCount === 0) {
        $defaultPages = [
            [
                'title' => 'আমাদের সম্পর্কে',
                'slug' => 'about-us',
                'content' => '<h2>আমাদের পরিচিতি</h2><p>আমরা বস্তুনিষ্ঠ, সৎ ও নিরপেক্ষ সাংবাদিকতায় বিশ্বাসী একটি শীর্ষস্থানীয় বাংলা ডিজিটাল সংবাদমাধ্যম। দেশ ও বিদেশের তাৎক্ষণিক খবর, বিশ্লেষণ ও মতামত তুলে ধরাই আমাদের অঙ্গীকার।</p><h3>আমাদের লক্ষ্য ও উদ্দেশ্য</h3><p>সত্য ও সঠিক তথ্য সর্বস্তরের মানুষের কাছে দ্রুততম সময়ে পৌঁছে দেওয়া এবং সামাজিক ন্যায়বিচার ও ইতিবাচক পরিবর্তনের পক্ষে কাজ করা।</p>',
                'meta_title' => 'আমাদের সম্পর্কে - পরিচিতি ও লক্ষ্য',
                'meta_description' => 'আমাদের নিউজ পোর্টালের লক্ষ্য, উদ্দেশ্য ও সম্পাদকীয় দলের পরিচিতি।',
                'show_in_footer' => 1,
                'order_index' => 1,
            ],
            [
                'title' => 'গোপনীয়তা নীতি',
                'slug' => 'privacy-policy',
                'content' => '<h2>গোপনীয়তা নীতি (Privacy Policy)</h2><p>আমাদের ওয়েবসাইট ব্যবহারের সময় আপনার ব্যক্তিগত তথ্যের সুরক্ষা নিশ্চিত করতে আমরা প্রতিশ্রুতিবদ্ধ। এই নীতিমালায় ব্যাখ্যা করা হয়েছে কীভাবে আমরা আপনার তথ্য সংগ্রহ, সংরক্ষণ ও ব্যবহার করি।</p><h3>তথ্য সংগ্রহ</h3><p>আমাদের ওয়েবসাইটে ব্রাউজ করার সময় কুকিজ ও অ্যানালিটিক্স টুলের মাধ্যমে সাধারণ ট্রাফিক ডেটা সংগ্রহ করা হতে পারে, যা ব্যবহারকারীর অভিজ্ঞতা উন্নয়নে সহায়তা করে।</p><h3>তথ্যের নিরাপত্তা</h3><p>আমরা আপনার কোনো ব্যক্তিগত তথ্য কোনো তৃতীয় পক্ষের কাছে বিক্রয় বা হস্তান্তর করি না।</p>',
                'meta_title' => 'গোপনীয়তা নীতি (Privacy Policy)',
                'meta_description' => 'ব্যবহারকারীদের তথ্যের সুরক্ষা ও গোপনীয়তা সংক্রান্ত বিস্তারিত নীতিমালা।',
                'show_in_footer' => 1,
                'order_index' => 2,
            ],
            [
                'title' => 'ব্যবহারের শর্তাবলী',
                'slug' => 'terms',
                'content' => '<h2>ব্যবহারের শর্তাবলী (Terms of Service)</h2><p>এই ওয়েবসাইটটি ব্যবহার করার মাধ্যমে আপনি আমাদের শর্তাবলী ও নিয়মাবলী মেনে নিতে সম্মত হচ্ছেন।</p><h3>কপিরাইট ও স্বত্ব</h3><p>এই ওয়েবসাইটে প্রকাশিত সকল সংবাদ, ছবি, ভিডিও এবং অন্যান্য সামগ্রীর স্বত্ব সংরক্ষিত। কর্তৃপক্ষের লিখিত অনুমতি ব্যতীত কোনো সামগ্রী অনুলিপি বা পুনপ্রকাশ করা সম্পূর্ণ নিষিদ্ধ।</p>',
                'meta_title' => 'ব্যবহারের শর্তাবলী ও নিয়মাবলী',
                'meta_description' => 'ওয়েবসাইট ব্যবহারের নিয়মাবলী, স্বত্ব ও আইনি শর্ত।',
                'show_in_footer' => 1,
                'order_index' => 3,
            ],
            [
                'title' => 'যোগাযোগ',
                'slug' => 'contact',
                'content' => '<h2>আমাদের সাথে যোগাযোগ</h2><p>যেকোনো সংবাদ, মতামত, তথ্য বা বিজ্ঞাপনের প্রয়োজনে আমাদের প্রধান কার্যালয় অথবা নিউজরুমে সরাসরি যোগাযোগ করতে পারেন।</p><div class="p-3 bg-light rounded border my-3"><p><strong>অফিস ঠিকানা:</strong> প্রধান কার্যালয়, ঢাকা, বাংলাদেশ</p><p><strong>ইমেইল:</strong> contact@example.com</p><p><strong>ফোন:</strong> +৮৮০ ১৭১১-০০০০০০</p></div>',
                'meta_title' => 'যোগাযোগ করুন - ঠিকানা ও ফোন নম্বর',
                'meta_description' => 'নিউজ পোর্টালের অফিস ঠিকানা, ইমেইল ও যোগাযোগের নম্বর।',
                'show_in_footer' => 1,
                'order_index' => 4,
            ],
            [
                'title' => 'সম্পাদকীয় নীতিমালা',
                'slug' => 'editorial-policy',
                'content' => '<h2>সম্পাদকীয় নীতিমালা (Editorial Policy)</h2><p>আমরা হলুদ সাংবাদিকতা ও মিথ্যা প্রচারণা সম্পূর্ণ পরিহার করে সত্যতা যাচাইয়ের মাধ্যমে তথ্য পরিবেশন করি। যেকোনো প্রতিবেদনের ক্ষেত্রে বস্তুনিষ্ঠতা ও সংশ্লিষ্ট পক্ষের ভারসাম্যপূর্ণ বক্তব্য নিশ্চিত করা আমাদের মূল নীতি।</p>',
                'meta_title' => 'সম্পাদকীয় নীতিমালা ও দায়বদ্ধতা',
                'meta_description' => 'বস্তুনিষ্ঠ সাংবাদিকতা ও নৈতিক সম্পাদকীয় নীতিমালা।',
                'show_in_footer' => 1,
                'order_index' => 5,
            ],
            [
                'title' => 'বিজ্ঞাপন মূল্য ও নিয়মাবলী',
                'slug' => 'ad-rates',
                'content' => '<h2>বিজ্ঞাপনের স্থান ও মূল্যতালিকা</h2><p>আমাদের পোর্টালে হেডার ব্যানার, সাইডবার ব্যানার ও ইন-আর্টিকেল বিজ্ঞাপনের জন্য যোগাযোগ করুন। মাসিক এবং বাৎসরিক চুক্তিতে বিশেষ ছাড় প্রদান করা হয়।</p><p>বিজ্ঞাপনের জন্য যোগাযোগ: ads@example.com</p>',
                'meta_title' => 'বিজ্ঞাপন মূল্য ও বিজ্ঞাপন নীতিমালা',
                'meta_description' => 'ডিজিটাল ব্যানার বিজ্ঞাপন ও স্পন্সরড কন্টেন্টের মূল্যতালিকা।',
                'show_in_footer' => 1,
                'order_index' => 6,
            ],
        ];

        $insPage = $db->prepare("
            INSERT INTO `pages` (`title`, `slug`, `content`, `meta_title`, `meta_description`, `status`, `show_in_menu`, `show_in_footer`, `order_index`)
            VALUES (:title, :slug, :content, :mtitle, :mdesc, 'published', 0, :foot, :ord)
        ");

        foreach ($defaultPages as $p) {
            $insPage->execute([
                ':title'   => $p['title'],
                ':slug'    => $p['slug'],
                ':content' => $p['content'],
                ':mtitle'  => $p['meta_title'],
                ':mdesc'   => $p['meta_description'],
                ':foot'    => $p['show_in_footer'],
                ':ord'     => $p['order_index'],
            ]);
        }
        $logs[] = "Seeded 6 default legal & informational pages.";
    }

    return $logs;
}

// If invoked via CLI directly:
if (php_sapi_name() === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    echo "Running white-label migrations...\n";
    $results = run_template_migrations();
    foreach ($results as $res) {
        echo " - {$res}\n";
    }
    echo "Migration completed successfully!\n";
}
