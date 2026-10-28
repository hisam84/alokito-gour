<?php
/**
 * SQLite Local Database Initializer
 * Alokito Gour Clone
 */

declare(strict_types=1);

$dbDir = __DIR__;
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

$dbFile = $dbDir . '/alokito_gour.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Settings Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `settings` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `site_title` TEXT NOT NULL DEFAULT 'Alokito Gour - আলোকিত গৌড়',
            `site_tagline` TEXT NULL DEFAULT 'সত্যের সাথে, আগামীর পথে',
            `logo_path` TEXT NULL DEFAULT 'https://alokitogour.com/photos/logo-2024-09-18-02-37-58.png',
            `favicon_path` TEXT NULL DEFAULT 'https://alokitogour.com/photos/favicon-2024-12-22-17-06-58.png',
            `editor_name` TEXT NULL DEFAULT 'মোহাঃ আব্দুল্লাহ',
            `publisher_name` TEXT NULL DEFAULT 'মোহাঃ আব্দুল্লাহ',
            `office_address` TEXT NULL DEFAULT 'ক্লাব সুপার মার্কেট (৪র্থ তলা), সদর, চাঁপাইনবাবগঞ্জ।',
            `contact_phone` TEXT NULL DEFAULT '01333479192 (সম্পাদক), 01333479193 (অফিস)',
            `contact_email` TEXT NULL DEFAULT 'editor@alokitogour.com',
            `facebook_url` TEXT NULL DEFAULT 'https://facebook.com/alokitogour',
            `youtube_url` TEXT NULL DEFAULT 'https://youtube.com/@alokitogour',
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // 2. Users Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `name` TEXT NOT NULL,
            `email` TEXT NOT NULL UNIQUE,
            `password` TEXT NOT NULL,
            `role` TEXT DEFAULT 'superadmin',
            `status` INTEGER DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // 3. Categories Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `categories` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `name` TEXT NOT NULL,
            `slug` TEXT NOT NULL UNIQUE,
            `order_index` INTEGER DEFAULT 0,
            `show_in_menu` INTEGER DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // 4. Posts Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `posts` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `category_id` INTEGER NOT NULL,
            `user_id` INTEGER NOT NULL,
            `title` TEXT NOT NULL,
            `slug` TEXT NOT NULL UNIQUE,
            `excerpt` TEXT NULL,
            `content` TEXT NOT NULL,
            `thumbnail` TEXT NULL,
            `caption` TEXT NULL,
            `reporter_name` TEXT NULL DEFAULT 'নিজস্ব প্রতিবেদক',
            `is_lead` INTEGER DEFAULT 0,
            `is_breaking` INTEGER DEFAULT 0,
            `views` INTEGER DEFAULT 0,
            `status` TEXT DEFAULT 'published',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_posts_cat ON `posts`(`category_id`);
        CREATE INDEX IF NOT EXISTS idx_posts_lead ON `posts`(`is_lead`);
        CREATE INDEX IF NOT EXISTS idx_posts_breaking ON `posts`(`is_breaking`);
        CREATE INDEX IF NOT EXISTS idx_posts_views ON `posts`(`views`);
        CREATE INDEX IF NOT EXISTS idx_posts_date ON `posts`(`created_at`);
    ");

    // 5. Advertisements Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `advertisements` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `position` TEXT NOT NULL UNIQUE,
            `title` TEXT NULL,
            `image_path` TEXT NULL,
            `target_url` TEXT NULL,
            `custom_code` TEXT NULL,
            `status` INTEGER DEFAULT 1
        );
    ");

    // Check if data already seeded
    $userCount = (int)$pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
    if ($userCount === 0) {
        // Insert Superadmin (Password: admin123)
        $superAdminHash = '$2y$10$F4sLiEzdYD85R5D5iXBtJucWJlWrqXatkKPfb5mi8nAkuq/ljZFEi';
        $userStmt = $pdo->prepare("INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`) VALUES (1, 'Super Admin', 'admin@alokitogour.com', :pwd, 'superadmin', 1)");
        $userStmt->execute([':pwd' => $superAdminHash]);

        // Insert Default Settings
        $pdo->exec("
            INSERT INTO `settings` (`id`, `site_title`, `site_tagline`, `logo_path`, `favicon_path`, `editor_name`, `publisher_name`, `office_address`, `contact_phone`, `contact_email`, `facebook_url`, `youtube_url`)
            VALUES (1, 'Alokito Gour - আলোকিত গৌড়', 'সত্যের সাথে, আগামীর পথে', 'https://alokitogour.com/photos/logo-2024-09-18-02-37-58.png', 'https://alokitogour.com/photos/favicon-2024-12-22-17-06-58.png', 'মোহাঃ আব্দুল্লাহ', 'মোহাঃ আব্দুল্লাহ', 'ক্লাব সুপার মার্কেট (৪র্থ তলা), সদর, চাঁপাইনবাবগঞ্জ।', '01333479192 (সম্পাদক), 01333479193 (অফিস)', 'editor@alokitogour.com', 'https://facebook.com/alokitogour', 'https://youtube.com/@alokitogour');
        ");

        // Insert Categories
        $pdo->exec("
            INSERT INTO `categories` (`id`, `name`, `slug`, `order_index`, `show_in_menu`) VALUES
            (1, 'চাঁপাইনবাবগঞ্জ', 'chapai-nawabganj', 1, 1),
            (2, 'জাতীয়', 'national', 2, 1),
            (3, 'সারাদেশ', 'country', 3, 1),
            (4, 'রাজনীতি', 'politics', 4, 1),
            (5, 'আন্তর্জাতিক', 'international', 5, 1),
            (6, 'খেলাধুলা', 'sports', 6, 1),
            (7, 'স্বাস্থ্য', 'health', 7, 1),
            (8, 'অর্থনীতি', 'economy', 8, 1),
            (9, 'বিনোদন', 'entertainment', 9, 1),
            (10, 'বিজ্ঞান ও প্রযুক্তি', 'tech', 10, 1),
            (11, 'শিক্ষা ও সাহিত্য', 'education-literature', 11, 1),
            (12, 'ধর্ম', 'religion', 12, 1),
            (13, 'চাকরি ও ক্যারিয়ার', 'jobs-career', 13, 0);
        ");

        // Insert Ad Slots
        $pdo->exec("
            INSERT INTO `advertisements` (`position`, `title`, `image_path`, `target_url`, `custom_code`, `status`) VALUES
            ('header_right', 'Header Leaderboard Ad', '', '#', '<div class=\"text-center p-3 border bg-light text-muted small\" style=\"min-height:90px; display:flex; align-items:center; justify-content:center;\">বিজ্ঞাপন দিন (৭২৮x৯০)</div>', 1),
            ('after_lead', 'After Lead Banner', '', '#', '<div class=\"text-center p-3 border bg-light text-muted small\" style=\"min-height:90px; display:flex; align-items:center; justify-content:center;\">বিজ্ঞাপন দিন (৯৭০x৯০)</div>', 1),
            ('sidebar', 'Sidebar Rectangle Ad', '', '#', '', 1),
            ('inside_article', 'In-Article Ad', '', '#', '<div class=\"text-center p-3 border bg-light text-muted small my-3\" style=\"min-height:100px; display:flex; align-items:center; justify-content:center;\">বিজ্ঞাপন দিন (ইন-আর্টিকেল)</div>', 1);
        ");

        // Insert Real Seed Posts
        $postStmt = $pdo->prepare("
            INSERT INTO `posts` (`category_id`, `user_id`, `title`, `slug`, `excerpt`, `content`, `thumbnail`, `caption`, `reporter_name`, `is_lead`, `is_breaking`, `views`, `status`) 
            VALUES (:cat, 1, :title, :slug, :excerpt, :content, :thumb, :caption, :reporter, :is_lead, :is_breaking, :views, 'published')
        ");

        $postsData = [
            [
                'cat' => 1,
                'title' => 'চাঁপাইনবাবগঞ্জে ডেঙ্গু প্রতিরোধে নূরুল ইসলাম বুলবুলের লার্ভা নিধন কর্মসূচির উদ্বোধন',
                'slug' => 'chapai-dengue-prevention-larva-kill-campaign',
                'excerpt' => 'চাঁপাইনবাবগঞ্জে ডেঙ্গু প্রতিরোধ ও এডিস মশার বংশবিস্তার রোধে লার্ভা নিধন কর্মসূচির উদ্বোধন করা হয়েছে।',
                'content' => '<p>চাঁপাইনবাবগঞ্জে ডেঙ্গু প্রতিরোধ ও এডিস মশার বংশবিস্তার রোধে লার্ভা নিধন কর্মসূচির উদ্বোধন করা হয়েছে। রোববার বিকেল ৪টায় শহরের শান্তিমোড় এলাকায় এ কর্মসূচির আনুষ্ঠানিক উদ্বোধন করা হয়।</p><p>উদ্বোধনী অনুষ্ঠানে প্রধান অতিথি হিসেবে উপস্থিত ছিলেন বিশিষ্ট সমাজসেবক নূরুল ইসলাম বুলবুল। তিনি বলেন, ডেঙ্গু এখন আর শুধু ঢাকা শহরের সমস্যা নয়, জেলা ও উপজেলা পর্যায়েও মারাত্মক আকার ধারণ করেছে। তাই প্রতিটি নাগরিককে নিজ নিজ বাড়ি এবং আঙিনা পরিষ্কার-পরিচ্ছন্ন রাখতে হবে।</p>',
                'thumb' => 'https://images.unsplash.com/photo-1584467735871-8e85353a8413?w=800&q=80',
                'caption' => 'লার্ভা নিধন কর্মসূচির উদ্বোধন করছেন অতিথিরা',
                'reporter' => 'চাঁপাইনবাবগঞ্জ প্রতিনিধি',
                'is_lead' => 1,
                'is_breaking' => 1,
                'views' => 1420
            ],
            [
                'cat' => 1,
                'title' => 'ডেঙ্গু প্রতিরোধে চাঁপাইনবাবগঞ্জে অসহায় পরিবারের মাঝে মশারি বিতরণ',
                'slug' => 'chapai-dengue-prevention-mosquito-net-distribution',
                'excerpt' => 'চাঁপাইনবাবগঞ্জে ডেঙ্গু জ্বরের প্রাদুর্ভাব রোধ এবং ডেঙ্গু আক্রান্ত ও ঝুঁকিতে থাকা অসহায়-দুঃস্থ মানুষের সুরক্ষায় বিশেষ উদ্যোগ গ্রহণ করেছে চাঁপাইনবাবগঞ্জ জেলা পরিষদ।',
                'content' => '<p>চাঁপাইনবাবগঞ্জে ডেঙ্গু জ্বরের প্রাদুর্ভাব রোধ এবং ডেঙ্গু আক্রান্ত ও ঝুঁকিতে থাকা অসহায়-দুঃস্থ মানুষের সুরক্ষায় বিশেষ উদ্যোগ গ্রহণ করেছে চাঁপাইনবাবগঞ্জ জেলা পরিষদ। রোববার দুপুরে পাঁচ শতাধিক পরিবারের মাঝে মশারি বিতরণ করা হয়।</p>',
                'thumb' => 'https://images.unsplash.com/photo-1576765608535-5f04d1e3f289?w=800&q=80',
                'caption' => 'অসহায়দের মাঝে মশারি বিতরণ',
                'reporter' => 'নিজস্ব প্রতিবেদক',
                'is_lead' => 0,
                'is_breaking' => 1,
                'views' => 980
            ],
            [
                'cat' => 1,
                'title' => 'চাঁপাইনবাবগঞ্জে আমবাগান থেকে ৯২টি ককটেল ও গান পাউডার উদ্ধার',
                'slug' => 'chapai-mango-orchard-cocktail-gunpowder-recovery',
                'excerpt' => 'চাঁপাইনবাবগঞ্জ সদর উপজেলার মহারাজপুর ইউনিয়নের পূর্ব টিকরা গ্রামের একটি আমবাগান থেকে ৯২টি ককটেল ও গান পাউডার উদ্ধার করা হয়েছে।',
                'content' => '<p>চাঁপাইনবাবগঞ্জ সদর উপজেলার মহারাজপুর ইউনিয়নের পূর্ব টিকরা গ্রামের একটি আমবাগান থেকে ৯২টি তাজা ককটেল, ১১ দশমিক ৮ কেজি গান পাউডার এবং বিপুল পরিমাণ বিস্ফোরক তৈরির সরঞ্জাম উদ্ধার করেছে র‍্যাব-৫।</p>',
                'thumb' => 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?w=800&q=80',
                'caption' => 'উদ্ধারকৃত ককটেল ও বিস্ফোরক সরঞ্জাম',
                'reporter' => 'সদর প্রতিনিধি',
                'is_lead' => 0,
                'is_breaking' => 1,
                'views' => 2350
            ],
            [
                'cat' => 2,
                'title' => 'ঢাকার বায়ুদূষণ দেশের জন্য লজ্জার: প্রধানমন্ত্রী',
                'slug' => 'dhaka-air-pollution-shameful-pm',
                'excerpt' => 'ঢাকার অব্যাহত বায়ুদূষণকে দেশের জন্য লজ্জার বিষয় উল্লেখ করে পরিবেশ ও বন রক্ষায় সর্বস্তরের মানুষকে এগিয়ে আসার আহ্বান জানিয়েছেন প্রধানমন্ত্রী।',
                'content' => '<p>ঢাকার অব্যাহত বায়ুদূষণকে দেশের জন্য লজ্জার বিষয় উল্লেখ করে পরিবেশ ও বন রক্ষায় সর্বস্তরের মানুষকে এগিয়ে আসার আহ্বান জানিয়েছেন প্রধানমন্ত্রী। তিনি বলেন, বিশ্বের দূষিত শহরের তালিকায় রাজধানী ঢাকার নাম আসা অত্যন্ত দুঃখজনক।</p>',
                'thumb' => 'https://images.unsplash.com/photo-1611273426858-450d8e3c9fce?w=800&q=80',
                'caption' => 'ঢাকার বায়ুদূষণ পরিস্থিতি',
                'reporter' => 'জাতীয় ডেস্ক',
                'is_lead' => 0,
                'is_breaking' => 1,
                'views' => 3120
            ],
            [
                'cat' => 3,
                'title' => 'এ বছর থেকেই দেশের বিভিন্ন স্থানে শুরু হচ্ছে যাত্রাপালা: সংস্কৃতিমন্ত্রী',
                'slug' => 'jatrapala-starting-countrywide-cultural-minister',
                'excerpt' => 'এ বছর থেকেই দেশের বিভিন্ন স্থানে যাত্রাপালা আয়োজনের উদ্যোগ নেওয়া হয়েছে বলে জানিয়েছেন সংস্কৃতিবিষয়ক মন্ত্রী।',
                'content' => '<p>এ বছর থেকেই দেশের বিভিন্ন স্থানে সুস্থধারার যাত্রাপালা আয়োজনের উদ্যোগ নেওয়া হয়েছে বলে জানিয়েছেন সংস্কৃতিবিষয়ক মন্ত্রী। তিনি বলেন, নর্তকী নাচ ও অশ্লীলতা বাদ দিয়ে মানুষের জীবনের কথা বলে—এমন পরিচ্ছন্ন যাত্রাপালার আয়োজন করা হবে।</p>',
                'thumb' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=800&q=80',
                'caption' => 'যাত্রাপালা ও লোকজ উৎসব',
                'reporter' => 'বিনোদন প্রতিবেদক',
                'is_lead' => 0,
                'is_breaking' => 0,
                'views' => 840
            ],
            [
                'cat' => 1,
                'title' => 'শিবগঞ্জ উপজেলা স্বাস্থ্য কমপ্লেক্সে ড. কেরামত আলীর ৫৭৫ ডেঙ্গু পরীক্ষার কিট প্রদান',
                'slug' => 'shivganj-health-complex-dengue-test-kit-donation',
                'excerpt' => 'চাঁপাইনবাবগঞ্জের শিবগঞ্জ উপজেলা স্বাস্থ্য কমপ্লেক্সে ডেঙ্গু শনাক্তকরণ জোরদার করতে ৫৭৫টি কিট প্রদান করেছেন ড. কেরামত আলী।',
                'content' => '<p>শিবগঞ্জ উপজেলা স্বাস্থ্য কমপ্লেক্সে বিনামূল্যে ডেঙ্গু পরীক্ষার সুবিধার্থে ৫৭৫টি উচ্চমানের টেস্ট কিট প্রদান করা হয়েছে।</p>',
                'thumb' => 'https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?w=800&q=80',
                'caption' => 'কিট হস্তান্তর অনুষ্ঠান',
                'reporter' => 'শিবগঞ্জ প্রতিনিধি',
                'is_lead' => 0,
                'is_breaking' => 0,
                'views' => 610
            ],
            [
                'cat' => 8,
                'title' => 'আজ বিশ্ব খাদ্য দিবস: টেকসই ভবিষ্যতের অঙ্গীকার',
                'slug' => 'world-food-day-sustainable-future',
                'excerpt' => 'আজ ১৬ অক্টোবর, বিশ্ব খাদ্য দিবস। বিশ্বের অন্যান্য দেশের মতো বাংলাদেশেও দিবসটি পালিত হচ্ছে।',
                'content' => '<p>পুষ্টিকর ও নিরাপদ খাদ্য নিশ্চিতকরণ এবং অপচয় রোধের অঙ্গীকার নিয়ে আজ বিশ্ব খাদ্য দিবস পালিত হচ্ছে।</p>',
                'thumb' => 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=800&q=80',
                'caption' => 'বিশ্ব খাদ্য দিবসের র‍্যালি',
                'reporter' => 'অর্থনীতি ডেস্ক',
                'is_lead' => 0,
                'is_breaking' => 0,
                'views' => 520
            ],
            [
                'cat' => 4,
                'title' => 'একাত্তরের চেতনার ব্যবসা করে জাতির মধ্যে বিভাজন করে রাখা হয়েছে: শিবির সভাপতি সাদ্দাম',
                'slug' => 'shibir-president-saddam-speech-unity',
                'excerpt' => 'একাত্তরের চেতনার ব্যবসা করে রাজনৈতিক ফায়দা লোটার দিন শেষ বলে মন্তব্য করেছেন বাংলাদেশ ইসলামী ছাত্রশিবিরের কেন্দ্রীয় সভাপতি।',
                'content' => '<p>রাজশাহীতে এক কর্মী সমাবেশে ছাত্রশিবিরের সভাপতি বলেন, দেশের মানুষ এখন শান্তি, ন্যায়বিচার এবং মেধাভিত্তিক সমাজ চায়।</p>',
                'thumb' => 'https://images.unsplash.com/photo-1540910419892-4a36d2c3266c?w=800&q=80',
                'caption' => 'কর্মী সমাবেশে বক্তব্য দিচ্ছেন নেতৃবৃন্দ',
                'reporter' => 'নিজস্ব প্রতিবেদক',
                'is_lead' => 0,
                'is_breaking' => 0,
                'views' => 1890
            ],
            [
                'cat' => 5,
                'title' => 'মুসলিম দোকান থেকে পূজার সামগ্রী কিনতে না করলো হিন্দু জাগরণ মঞ্চ',
                'slug' => 'hindu-jagaran-manch-advisory-controversy',
                'excerpt' => 'ভারতে দুর্গাপূজা উপলক্ষে মুসলিম মালিকানাধীন দোকান থেকে সামগ্রী কেনা পরিহার করার বিতর্কিত আহ্বান জানিয়েছে হিন্দু জাগরণ মঞ্চ।',
                'content' => '<p>ভারতে সাম্প্রদায়িক সম্প্রীতি বিনষ্টের অপচেষ্টার বিরুদ্ধে দেশটির বিভিন্ন নাগরিক সমাজ ও রাজনৈতিক দল ক্ষোভ প্রকাশ করেছে।</p>',
                'thumb' => 'https://images.unsplash.com/photo-1532375810709-75b1da00537c?w=800&q=80',
                'caption' => 'আন্তর্জাতিক সংবাদ ডেস্ক',
                'reporter' => 'আন্তর্জাতিক ডেস্ক',
                'is_lead' => 0,
                'is_breaking' => 0,
                'views' => 1640
            ],
            [
                'cat' => 10,
                'title' => 'বিসিক উদ্যোক্তা মেলায় বিশেষ উদ্ভাবনী উদ্যোক্তা হিসেবে সম্মাননা পেলেন ঈদুল হাসান জনি',
                'slug' => 'bscic-entrepreneur-fair-innovation-award-johnny',
                'excerpt' => 'চাঁপাইনবাবগঞ্জে আয়োজিত বিসিক উদ্যোক্তা মেলায় বিশেষ উদ্ভাবনী প্রযুক্তি পণ্যের জন্য সম্মাননা পেয়েছেন ঈদুল হাসান জনি।',
                'content' => '<p>কৃষি অটোমেশন এবং সৌরবিদ্যুৎ চালিত বিশেষ সেচ যন্ত্র প্রদর্শনের জন্য মেলায় তাকে এই সম্মাননা ক্রেস্ট প্রদান করা হয়।</p>',
                'thumb' => 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=800&q=80',
                'caption' => 'সম্মাননা স্মারক গ্রহণ',
                'reporter' => 'প্রযুক্তি ডেস্ক',
                'is_lead' => 0,
                'is_breaking' => 0,
                'views' => 780
            ],
            [
                'cat' => 12,
                'title' => 'নির্ঘুম রাতে নামাজ হতে পারে মুমিনের শ্রেষ্ঠ আশ্রয়: তাহাজ্জুদের গুরুত্ব',
                'slug' => 'importance-of-tahajjud-prayer-islam',
                'excerpt' => 'দুনিয়া ও আখিরাতের শান্তির জন্য এবং মহান আল্লাহর সন্তুষ্টি অর্জনে শেষ রাতের ইবাদত অত্যন্ত বরকতময়।',
                'content' => '<p>পবিত্র কোরআন ও হাদিসে তাহাজ্জুদ নামাজের বিশেষ ফজিলত ও মর্যাদা বর্ণনা করা হয়েছে। এটি আত্মশুদ্ধি অর্জনের অন্যতম শ্রেষ্ঠ মাধ্যম।</p>',
                'thumb' => 'https://images.unsplash.com/photo-1542816417-0983c9c9ad53?w=800&q=80',
                'caption' => 'তাহাজ্জুদ নামাজ ও দোয়া',
                'reporter' => 'ধর্ম ও জীবন ডেস্ক',
                'is_lead' => 0,
                'is_breaking' => 0,
                'views' => 1250
            ]
        ];

        foreach ($postsData as $p) {
            $postStmt->execute([
                ':cat'         => $p['cat'],
                ':title'       => $p['title'],
                ':slug'        => $p['slug'],
                ':excerpt'     => $p['excerpt'],
                ':content'     => $p['content'],
                ':thumb'       => $p['thumb'],
                ':caption'     => $p['caption'],
                ':reporter'    => $p['reporter'],
                ':is_lead'     => $p['is_lead'],
                ':is_breaking' => $p['is_breaking'],
                ':views'       => $p['views'],
            ]);
        }
    }

    echo "SUCCESS: Local SQLite database successfully created and seeded at: " . $dbFile . "\n";
    return $pdo;
} catch (Exception $e) {
    echo "ERROR initializing SQLite database: " . $e->getMessage() . "\n";
    exit(1);
}
