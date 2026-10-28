<?php
/**
 * Dynamic Drag-and-Drop Page Builder with Real-Time Live Preview
 * WordPress/Elementor-Style Real-time Sync & Modular Widget Engine
 * Alokito Gour Clone
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';

$db = get_db();
$pageTitle = 'পেজ বিল্ডার (Page Builder) | আলোকিত গৌড়';

// Handle AJAX Save Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_layout') {
    header('Content-Type: application/json; charset=utf-8');

    $slug = trim($_POST['page_slug'] ?? 'home');
    $rawLayout = $_POST['layout_data'] ?? '[]';

    // Verify valid JSON
    $decoded = json_decode($rawLayout, true);
    if (!is_array($decoded)) {
        echo json_encode(['success' => false, 'message' => 'অবৈধ লেআউট ডাটা']);
        exit;
    }

    $stmt = $db->prepare("
        UPDATE `page_layouts` 
        SET `layout_data` = :layout, `updated_at` = CURRENT_TIMESTAMP 
        WHERE `page_slug` = :slug
    ");
    $stmt->execute([
        ':layout' => json_encode($decoded, JSON_UNESCAPED_UNICODE),
        ':slug' => $slug,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'লেআউট সফলভাবে সংরক্ষিত হয়েছে!',
        'count' => count($decoded)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Handle Form Submissions (Create Page, Reset Default, Delete Page)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $action = $_POST['form_action'] ?? '';

    if ($action === 'create_page') {
        $newTitle = trim($_POST['title'] ?? '');
        $newSlug = slugify(trim($_POST['slug'] ?? ''));

        if (empty($newTitle) || empty($newSlug)) {
            set_flash('danger', 'পাতার নাম ও স্ল্যাগ প্রদান করা বাধ্যতামূলক।');
        } else {
            // Check uniqueness
            $checkStmt = $db->prepare("SELECT id FROM page_layouts WHERE page_slug = ?");
            $checkStmt->execute([$newSlug]);
            if ($checkStmt->fetch()) {
                set_flash('danger', 'এই স্ল্যাগে ইতোমধ্যে একটি পাতা তৈরি করা আছে।');
            } else {
                $template = $_POST['template'] ?? 'blank';
                $initData = '[]';
                if ($template === 'home_clone') {
                    $hStmt = $db->query("SELECT layout_data FROM page_layouts WHERE page_slug = 'home' LIMIT 1");
                    $hRow = $hStmt->fetch();
                    if ($hRow) $initData = $hRow['layout_data'];
                }

                $insStmt = $db->prepare("INSERT INTO page_layouts (page_slug, title, layout_data, status) VALUES (?, ?, ?, 1)");
                $insStmt->execute([$newSlug, $newTitle, $initData]);
                set_flash('success', "নতুন পাতা '{$newTitle}' সফলভাবে তৈরি হয়েছে।");
                header("Location: page-builder.php?page={$newSlug}");
                exit;
            }
        }
    } elseif ($action === 'delete_page') {
        $delSlug = trim($_POST['del_slug'] ?? '');
        if ($delSlug === 'home' || $delSlug === 'news_sidebar') {
            set_flash('danger', 'মূল সিস্টেম লেআউট মুছে ফেলা যাবে না।');
        } else {
            $delStmt = $db->prepare("DELETE FROM page_layouts WHERE page_slug = ?");
            $delStmt->execute([$delSlug]);
            set_flash('success', 'পাতাটি সফলভাবে মুছে ফেলা হয়েছে।');
            header('Location: page-builder.php?page=home');
            exit;
        }
    } elseif ($action === 'reset_default') {
        // Authentic default 10-widget homepage layout
        $defaultHomeLayout = [
            [
                'id' => 'sec_hero_lead',
                'type' => 'hero_lead',
                'title' => 'প্রধান ও শীর্ষ সংবাদ (Hero Lead Section)',
                'container' => 'main',
                'status' => 1,
                'settings' => ['bottom_thumbs' => 3, 'sub_leads' => 3]
            ],
            [
                'id' => 'sec_ad_after_lead',
                'type' => 'ad_banner',
                'title' => 'বিজ্ঞাপন ব্যানার (লিডের পর)',
                'container' => 'main',
                'status' => 1,
                'settings' => ['position' => 'after_lead']
            ],
            [
                'id' => 'sec_cat_chapai',
                'type' => 'category_block',
                'title' => 'চাঁপাইনবাবগঞ্জ',
                'container' => 'main',
                'status' => 1,
                'settings' => ['category_slug' => 'chapai-nawabganj', 'limit' => 5, 'show_more' => true]
            ],
            [
                'id' => 'sec_cat_national',
                'type' => 'category_grid',
                'title' => 'জাতীয়',
                'container' => 'main',
                'status' => 1,
                'settings' => ['category_slug' => 'national', 'limit' => 4, 'show_more' => true]
            ],
            [
                'id' => 'sec_cat_politics_intl',
                'type' => 'category_split',
                'title' => 'রাজনীতি ও আন্তর্জাতিক',
                'container' => 'main',
                'status' => 1,
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
                'container' => 'main',
                'status' => 1,
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
                'container' => 'main',
                'status' => 1,
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
                'container' => 'main',
                'status' => 1,
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
                'container' => 'full',
                'status' => 1,
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
                'container' => 'full',
                'status' => 1,
                'settings' => ['title' => 'ছবি ও ভিডিও গ্যালারি', 'limit' => 4]
            ]
        ];

        $upStmt = $db->prepare("UPDATE page_layouts SET layout_data = ? WHERE page_slug = 'home'");
        $upStmt->execute([json_encode($defaultHomeLayout, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)]);
        set_flash('success', 'হোমপেজ লেআউট ডিফল্ট সেকশনে সফলভাবে রিসেট করা হয়েছে।');
        header('Location: page-builder.php?page=home');
        exit;
    }
}

// Fetch all available pages
$allPages = $db->query("SELECT id, page_slug, title, updated_at FROM page_layouts ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Current selected page
$currentSlug = trim($_GET['page'] ?? 'home');
$pageStmt = $db->prepare("SELECT * FROM page_layouts WHERE page_slug = :slug LIMIT 1");
$pageStmt->execute([':slug' => $currentSlug]);
$currentPage = $pageStmt->fetch(PDO::FETCH_ASSOC);

if (!$currentPage) {
    // Fallback to home
    $currentSlug = 'home';
    $pageStmt->execute([':slug' => 'home']);
    $currentPage = $pageStmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all categories for select dropdowns
$categories = $db->query("SELECT id, name, slug FROM categories ORDER BY order_index ASC")->fetchAll(PDO::FETCH_ASSOC);

// Decode current widgets
$currentWidgets = json_decode($currentPage['layout_data'] ?? '[]', true);
if (!is_array($currentWidgets)) {
    $currentWidgets = [];
}

include __DIR__ . '/includes/header.php';
?>

<div class="content-body p-3 p-md-4">
    <?php $flash = get_flash(); if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show mb-3" role="alert">
            <?= e($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Top Action Toolbar -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="row align-items-center g-3">
                <!-- Page Selector -->
                <div class="col-xl-4 col-lg-5 col-md-6">
                    <div class="d-flex align-items-center gap-2">
                        <label class="form-label mb-0 fw-bold small text-nowrap"><i class="bi bi-file-earmark-code me-1 text-warning"></i>পাতা:</label>
                        <select class="form-select form-select-sm" id="pageSelector" onchange="location.href='page-builder.php?page=' + this.value;">
                            <?php foreach ($allPages as $p): ?>
                                <option value="<?= e($p['page_slug']) ?>" <?= $p['page_slug'] === $currentSlug ? 'selected' : '' ?>>
                                    <?= e($p['title']) ?> (<?= e($p['page_slug']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-outline-primary btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#createPageModal">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- View Mode Switcher & Device Controls -->
                <div class="col-xl-4 col-lg-7 col-md-6 text-center">
                    <div class="d-inline-flex align-items-center gap-1 bg-light p-1 rounded border">
                        <button type="button" class="btn btn-sm btn-white border shadow-sm active" id="btnModeSplit" onclick="setBuilderMode('split')" title="স্প্লিট মোড: বিল্ডার ও লাইভ প্রিভিউ একসাথে">
                            <i class="bi bi-layout-split me-1 text-primary"></i>স্প্লিট ভিউ
                        </button>
                        <button type="button" class="btn btn-sm btn-light border-0" id="btnModeCanvas" onclick="setBuilderMode('canvas')" title="শুধুমাত্র ক্যানভাস ড্র্যাগ-ড্রপ">
                            <i class="bi bi-card-checklist me-1 text-secondary"></i>ক্যানভাস
                        </button>
                        <button type="button" class="btn btn-sm btn-light border-0" id="btnModePreview" onclick="setBuilderMode('preview')" title="ফুলস্ক্রিন লাইভ প্রিভিউ">
                            <i class="bi bi-eye me-1 text-success"></i>ফুল প্রিভিউ
                        </button>
                    </div>
                </div>

                <!-- Actions: Reset, Live Link, Save -->
                <div class="col-xl-4 col-lg-12 text-xl-end text-center">
                    <div class="d-flex align-items-center justify-content-xl-end justify-content-center gap-2 flex-wrap">
                        <?php 
                        $previewUrl = ($currentSlug === 'home') ? '/' : (($currentSlug === 'news_sidebar') ? '/news/1' : '/page/' . urlencode($currentSlug));
                        ?>
                        <a href="<?= e($previewUrl) ?>" target="_blank" class="btn btn-outline-secondary btn-sm" title="নতুন ট্যাবে আসল সাইট দেখুন">
                            <i class="bi bi-box-arrow-up-right me-1"></i>লাইভ সাইট
                        </a>

                        <?php if ($currentSlug === 'home'): ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('আপনি কি নিশ্চিত যে হোমপেজের সকল সেকশন মূল ডিফল্ট অবস্থায় ফিরিয়ে নিতে চান?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="form_action" value="reset_default">
                                <button type="submit" class="btn btn-outline-danger btn-sm" title="ডিফল্ট লেআউটে ফিরিয়ে আনুন">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </form>
                        <?php elseif ($currentSlug !== 'news_sidebar'): ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('আপনি কি নিশ্চিত যে এই পাতাটি মুছে ফেলতে চান?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="form_action" value="delete_page">
                                <input type="hidden" name="del_slug" value="<?= e($currentSlug) ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        <?php endif; ?>

                        <!-- Big Save Layout Button -->
                        <button type="button" class="btn btn-success btn-sm px-3 fw-bold" id="btnSaveLayout" onclick="saveLayout();">
                            <i class="bi bi-check2-circle me-1"></i>সংরক্ষণ করুন
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Builder Workspace Grid (Supports Split, Canvas, and Full Preview Modes) -->
    <div class="row g-3" id="workspaceRow">
        <!-- Column 1: Builder Canvas & Widget Library -->
        <div class="col-xl-5 col-lg-6" id="builderCol">
            <!-- Canvas Card -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 fw-bold text-dark small"><i class="bi bi-ui-checks me-1 text-primary"></i>উইজেট তালিকা (ড্র্যাগ এন্ড ড্রপ)</h6>
                    </div>
                    <span class="badge bg-secondary-subtle text-secondary" id="widgetCountBadge"><?= count($currentWidgets) ?> টি</span>
                </div>

                <div class="card-body p-2 p-md-3">
                    <!-- Drop Canvas -->
                    <div class="builder-canvas" id="widgetCanvas" style="min-height: 280px;">
                        <!-- Rendered dynamically by JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Widget Drawer Palette -->
            <div class="card border-0 shadow-sm" id="widgetDrawerCard">
                <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark small"><i class="bi bi-puzzle-fill me-1 text-warning"></i>+ উইজেট যোগ করুন (লাইব্রেরি)</h6>
                    <small class="text-muted" style="font-size: 11px;">ক্লিক করলে যুক্ত হবে</small>
                </div>

                <div class="card-body p-2 p-md-3" style="max-height: 400px; overflow-y: auto;">
                    <div class="row g-2">
                        <!-- 1. Hero Lead -->
                        <div class="col-12">
                            <div class="widget-palette-item p-2" onclick="addWidget('hero_lead');">
                                <div class="widget-palette-icon bg-danger bg-opacity-10 text-danger" style="width:30px; height:30px; font-size:1rem;">
                                    <i class="bi bi-newspaper"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="fw-bold small text-dark lh-sm">প্রধান ও শীর্ষ সংবাদ (Hero Lead)</div>
                                    <div class="text-muted text-truncate" style="font-size: 10px;">১টি বড় লিড, ৩টি ছোট থাম্ব ও ৩টি পাশের লিড</div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Ad Banner -->
                        <div class="col-12">
                            <div class="widget-palette-item p-2" onclick="addWidget('ad_banner');">
                                <div class="widget-palette-icon bg-warning bg-opacity-10 text-warning" style="width:30px; height:30px; font-size:1rem;">
                                    <i class="bi bi-badge-ad"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="fw-bold small text-dark lh-sm">বিজ্ঞাপন ব্যানার (Ad Banner)</div>
                                    <div class="text-muted text-truncate" style="font-size: 10px;">লিড পরবর্তী বা সাইডবার বিজ্ঞাপন ব্যানার</div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Category Block -->
                        <div class="col-12">
                            <div class="widget-palette-item p-2" onclick="addWidget('category_block');">
                                <div class="widget-palette-icon bg-primary bg-opacity-10 text-primary" style="width:30px; height:30px; font-size:1rem;">
                                    <i class="bi bi-card-heading"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="fw-bold small text-dark lh-sm">ক্যাটাগরি ব্লক (১টি বড় + তালিকা)</div>
                                    <div class="text-muted text-truncate" style="font-size: 10px;">বামে ১টি বড় থাম্ব এবং ডানে বুলেট তালিকা (চাঁপাইনবাবগঞ্জ)</div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Category Grid -->
                        <div class="col-12">
                            <div class="widget-palette-item p-2" onclick="addWidget('category_grid');">
                                <div class="widget-palette-icon bg-info bg-opacity-10 text-info" style="width:30px; height:30px; font-size:1rem;">
                                    <i class="bi bi-grid-fill"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="fw-bold small text-dark lh-sm">ক্যাটাগরি গ্রিড (৪টি থাম্ব কার্ড)</div>
                                    <div class="text-muted text-truncate" style="font-size: 10px;">৪টি ফটোযুক্ত কার্ডের ২x২ রেসপন্সিভ গ্রিড (জাতীয়)</div>
                                </div>
                            </div>
                        </div>

                        <!-- 5. Split Category -->
                        <div class="col-12">
                            <div class="widget-palette-item p-2" onclick="addWidget('category_split');">
                                <div class="widget-palette-icon bg-purple bg-opacity-10 text-purple" style="color: #8b5cf6; width:30px; height:30px; font-size:1rem;">
                                    <i class="bi bi-layout-split"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="fw-bold small text-dark lh-sm">দ্বৈত ক্যাটাগরি (পাশাপাশি ২টি বিভাগ)</div>
                                    <div class="text-muted text-truncate" style="font-size: 10px;">পাশাপাশি ২টি ভিন্ন ক্যাটাগরির সংবাদ (যেমন রাজনীতি ও আন্তর্জাতিক)</div>
                                </div>
                            </div>
                        </div>

                        <!-- 6. Full-Width Split -->
                        <div class="col-12">
                            <div class="widget-palette-item p-2" onclick="addWidget('full_width_split');">
                                <div class="widget-palette-icon bg-success bg-opacity-10 text-success" style="width:30px; height:30px; font-size:1rem;">
                                    <i class="bi bi-distribute-horizontal"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="fw-bold small text-dark lh-sm">ফুল-উইডথ ডাবল ব্লক (সারাদেশ/খেলাধুলা)</div>
                                    <div class="text-muted text-truncate" style="font-size: 10px;">১২ কলাম জুড়ে পাশাপাশি ২টি ক্যাটাগরি</div>
                                </div>
                            </div>
                        </div>

                        <!-- 7. Multimedia Gallery -->
                        <div class="col-12">
                            <div class="widget-palette-item p-2" onclick="addWidget('multimedia_gallery');">
                                <div class="widget-palette-icon bg-dark bg-opacity-10 text-dark" style="width:30px; height:30px; font-size:1rem;">
                                    <i class="bi bi-camera-fill"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="fw-bold small text-dark lh-sm">ছবি ও ভিডিও গ্যালারি (Gallery)</div>
                                    <div class="text-muted text-truncate" style="font-size: 10px;">ডার্ক ব্যাকড্রপে ফটো ও ভিডিও কার্ড</div>
                                </div>
                            </div>
                        </div>

                        <!-- 8. Custom HTML -->
                        <div class="col-12">
                            <div class="widget-palette-item p-2" onclick="addWidget('custom_html');">
                                <div class="widget-palette-icon bg-secondary bg-opacity-10 text-secondary" style="width:30px; height:30px; font-size:1rem;">
                                    <i class="bi bi-code-slash"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="fw-bold small text-dark lh-sm">কাস্টম এইচটিএমএল / নোটিশ (Embed)</div>
                                    <div class="text-muted text-truncate" style="font-size: 10px;">ইউটিউব ভিডিও বা নিজস্ব কাস্টম কোড</div>
                                </div>
                            </div>
                        </div>

                        <!-- 9. Sidebar Tabs -->
                        <div class="col-12">
                            <div class="widget-palette-item p-2" onclick="addWidget('sidebar_tabs');">
                                <div class="widget-palette-icon bg-info bg-opacity-10 text-info" style="width:30px; height:30px; font-size:1rem;">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="fw-bold small text-dark lh-sm">সর্বশেষ ও জনপ্রিয় সংবাদ ট্যাব</div>
                                    <div class="text-muted text-truncate" style="font-size: 10px;">সাইডবারের জন্য সর্বশেষ ও সর্বাধিক পঠিত সংবাদ</div>
                                </div>
                            </div>
                        </div>

                        <!-- 10. District Filter -->
                        <div class="col-12">
                            <div class="widget-palette-item p-2" onclick="addWidget('district_filter');">
                                <div class="widget-palette-icon bg-danger bg-opacity-10 text-danger" style="width:30px; height:30px; font-size:1rem;">
                                    <i class="bi bi-geo-alt-fill"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="fw-bold small text-dark lh-sm">আমার এলাকার খবর (জেলা ফিল্টার)</div>
                                    <div class="text-muted text-truncate" style="font-size: 10px;">জেলাভিত্তিক বাটন গ্রিড ফিল্টার</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Column 2: WordPress-Style Live Real-Time Preview Pane -->
        <div class="col-xl-7 col-lg-6" id="previewCol">
            <div class="card border-0 shadow-sm sticky-top" style="top: 80px;">
                <!-- Preview Device & Control Header -->
                <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold text-dark small"><i class="bi bi-display me-1 text-success"></i>রিয়েল-টাইম লাইভ প্রিভিউ</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1" id="liveSyncStatus" style="font-size: 11px;">
                            <span class="spinner-grow spinner-grow-sm text-success" style="width: 7px; height: 7px;"></span>সিঙ্ক সক্রিয়
                        </span>
                    </div>

                    <!-- Device Switcher Buttons -->
                    <div class="d-flex align-items-center gap-1">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-light border active" id="btnDevDesktop" onclick="setPreviewSize('desktop')" title="ডেস্কটপ ভিউ (১০০%)">
                                <i class="bi bi-display"></i>
                            </button>
                            <button type="button" class="btn btn-light border" id="btnDevTablet" onclick="setPreviewSize('tablet')" title="ট্যাবলেট ভিউ (৭৬৮px)">
                                <i class="bi bi-tablet"></i>
                            </button>
                            <button type="button" class="btn btn-light border" id="btnDevMobile" onclick="setPreviewSize('mobile')" title="মোবাইল ভিউ (৩৭৫px)">
                                <i class="bi bi-phone"></i>
                            </button>
                        </div>

                        <button type="button" class="btn btn-light border btn-sm" onclick="refreshLivePreview()" title="রিফ্রেশ প্রিভিউ">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>

                <!-- Preview Frame Viewport -->
                <div class="card-body p-2 preview-container" id="previewContainer">
                    <iframe id="livePreviewFrame" class="device-desktop" src="live-preview.php?page=<?= urlencode($currentSlug) ?>" title="Live Real-time Preview"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================
     MODALS
     ======================================================== -->

<!-- 1. Widget Settings Configurator Modal -->
<div class="modal fade" id="widgetSettingsModal" tabindex="-1" aria-labelledby="widgetSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold" id="widgetSettingsModalLabel">
                    <i class="bi bi-sliders me-1 text-warning"></i>উইজেট কনফিগারেশন
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="modalFormContainer">
                <!-- Form dynamically rendered by JS based on widget type -->
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">বাতিল</button>
                <button type="button" class="btn btn-primary btn-sm px-3" onclick="applyWidgetSettings();">
                    <i class="bi bi-check2 me-1"></i>সেটিংস আপডেট করুন
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 2. Create Page Modal -->
<div class="modal fade" id="createPageModal" tabindex="-1" aria-labelledby="createPageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="create_page">
                <div class="modal-header bg-light">
                    <h6 class="modal-title fw-bold" id="createPageModalLabel">
                        <i class="bi bi-file-earmark-plus me-1 text-primary"></i>নতুন পেজ তৈরি করুন
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">পাতার শিরোনাম <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="যেমন: বিশেষ সংবাদ আয়োজন" required id="newPageTitle" onkeyup="autoSlug(this.value);">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">পাতার ইউআরএল স্ল্যাগ (Slug) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text small">/page/</span>
                            <input type="text" name="slug" class="form-control" placeholder="special-news" required id="newPageSlug">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">প্রাথমিক লেআউট টেমপ্লেট</label>
                        <select name="template" class="form-select">
                            <option value="blank">খালি পাতা (ব্ল্যাঙ্ক ক্যানভাস)</option>
                            <option value="home_clone">হোমপেজের অনুরূপ লেআউট কপি করুন</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">বাতিল</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">পাতা তৈরি করুন</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast Container for Save Notifications -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11000">
    <div id="saveToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body fs-6" id="saveToastMessage">
                <i class="bi bi-check-circle-fill me-2"></i>লেআউট সফলভাবে সংরক্ষিত হয়েছে!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- SortableJS CDN (Lightweight zero-runtime library) -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<script>
// Category data from PHP
const ALL_CATEGORIES = <?= json_encode($categories, JSON_UNESCAPED_UNICODE) ?>;
const PAGE_SLUG = <?= json_encode($currentSlug) ?>;

// In-Memory widget structure
let currentWidgets = <?= json_encode($currentWidgets, JSON_UNESCAPED_UNICODE) ?>;
let activeEditingIndex = -1;
let currentMode = 'split'; // 'split', 'canvas', 'preview'
let currentDevice = 'desktop'; // 'desktop', 'tablet', 'mobile'

// Widget Meta Registry
const WIDGET_META = {
    hero_lead: {
        label: 'প্রধান ও শীর্ষ সংবাদ',
        icon: 'bi-newspaper',
        colorClass: 'type-hero_lead',
        defaultContainer: 'main',
        defaultTitle: 'প্রধান ও শীর্ষ সংবাদ (Hero Lead Section)',
        defaultSettings: { bottom_thumbs: 3, sub_leads: 3 }
    },
    ad_banner: {
        label: 'বিজ্ঞাপন ব্যানার',
        icon: 'bi-badge-ad',
        colorClass: 'type-ad_banner',
        defaultContainer: 'main',
        defaultTitle: 'বিজ্ঞাপন ব্যানার (লিডের পর)',
        defaultSettings: { position: 'after_lead', custom_code: '' }
    },
    category_block: {
        label: 'ক্যাটাগরি ব্লক (১টি বড় + তালিকা)',
        icon: 'bi-card-heading',
        colorClass: 'type-category_block',
        defaultContainer: 'main',
        defaultTitle: 'চাঁপাইনবাবগঞ্জ',
        defaultSettings: { category_slug: 'chapai-nawabganj', limit: 5, show_more: true }
    },
    category_grid: {
        label: 'ক্যাটাগরি গ্রিড (৪টি কার্ড)',
        icon: 'bi-grid-fill',
        colorClass: 'type-category_grid',
        defaultContainer: 'main',
        defaultTitle: 'জাতীয়',
        defaultSettings: { category_slug: 'national', limit: 4, show_more: true }
    },
    category_split: {
        label: 'দ্বৈত ক্যাটাগরি (পাশাপাশি ২টি)',
        icon: 'bi-layout-split',
        colorClass: 'type-category_split',
        defaultContainer: 'main',
        defaultTitle: 'রাজনীতি ও আন্তর্জাতিক',
        defaultSettings: { left_category: 'politics', left_title: 'রাজনীতি', right_category: 'international', right_title: 'আন্তর্জাতিক', limit: 4 }
    },
    full_width_split: {
        label: 'ফুল-উইডথ ডাবল ব্লক',
        icon: 'bi-distribute-horizontal',
        colorClass: 'type-full_width_split',
        defaultContainer: 'full',
        defaultTitle: 'সারাদেশ ও খেলাধুলা',
        defaultSettings: { left_category: 'country', left_title: 'সারাদেশ', right_category: 'sports', right_title: 'খেলাধুলা', limit: 4 }
    },
    multimedia_gallery: {
        label: 'ছবি ও ভিডিও গ্যালারি',
        icon: 'bi-camera-fill',
        colorClass: 'type-multimedia_gallery',
        defaultContainer: 'full',
        defaultTitle: 'ছবি ও ভিডিও গ্যালারি',
        defaultSettings: { title: 'ছবি ও ভিডিও গ্যালারি', limit: 4 }
    },
    custom_html: {
        label: 'কাস্টম কোড / নোটিশ',
        icon: 'bi-code-slash',
        colorClass: 'type-custom_html',
        defaultContainer: 'main',
        defaultTitle: 'বিশেষ নোটিশ / কাস্টম উইজেট',
        defaultSettings: { content: '<div class="p-3 bg-light border rounded">কাস্টম নোটিশ বা এম্বেড কোড এখানে দিন...</div>' }
    },
    sidebar_tabs: {
        label: 'সর্বশেষ ও জনপ্রিয় সংবাদ ট্যাব',
        icon: 'bi-clock-history',
        colorClass: 'type-sidebar_tabs',
        defaultContainer: 'main',
        defaultTitle: 'সর্বশেষ ও জনপ্রিয় সংবাদ ট্যাব',
        defaultSettings: { limit: 7 }
    },
    district_filter: {
        label: 'আমার এলাকার খবর (জেলা ফিল্টার)',
        icon: 'bi-geo-alt-fill',
        colorClass: 'type-district_filter',
        defaultContainer: 'main',
        defaultTitle: 'আমার এলাকার খবর (জেলা ফিল্টার)',
        defaultSettings: {}
    }
};

/**
 * Render the entire canvas from currentWidgets array
 */
function renderCanvas() {
    const canvas = document.getElementById('widgetCanvas');
    const badge = document.getElementById('widgetCountBadge');
    badge.innerText = `${currentWidgets.length} টি`;

    if (currentWidgets.length === 0) {
        canvas.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="bi bi-layers fs-2 opacity-50 d-block mb-1"></i>
                <h6 class="small mb-1">বর্তমানে কোনো উইজেট নেই</h6>
                <p class="text-muted" style="font-size: 11px;">নিচের লাইব্রেরি থেকে উইজেট যোগ করুন।</p>
            </div>
        `;
        return;
    }

    let html = '';
    currentWidgets.forEach((w, idx) => {
        const meta = WIDGET_META[w.type] || {
            label: w.type,
            icon: 'bi-puzzle',
            colorClass: 'border-secondary',
            defaultContainer: 'main'
        };

        const isHidden = (w.status === 0 || w.status === '0');
        const container = w.container || meta.defaultContainer || 'main';
        const containerLabel = (container === 'full') ? 'ফুল-উইডথ' : 'মূল কলাম';
        const containerBadgeClass = (container === 'full') ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary';

        html += `
            <div class="widget-card ${meta.colorClass} ${isHidden ? 'is-hidden' : ''} p-2 mb-2" data-index="${idx}">
                <div class="d-flex align-items-center gap-2 flex-grow-1 overflow-hidden">
                    <span class="widget-handle py-1 px-1" title="টেনে সাজান"><i class="bi bi-grip-vertical"></i></span>
                    <span class="badge ${containerBadgeClass} p-1" style="font-size: 10px;">${containerLabel}</span>
                    <div class="text-truncate">
                        <div class="fw-bold text-dark small text-truncate" style="font-size: 12px;">
                            <i class="bi ${meta.icon} me-1 text-muted"></i>${escapeHtml(w.title || meta.label)}
                        </div>
                        <div class="text-muted text-truncate" style="font-size: 10px;">
                            ${escapeHtml(meta.label)} &bull; ${escapeHtml(describeWidget(w))}
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-1 ms-1 flex-shrink-0">
                    <!-- Visibility Toggle -->
                    <button type="button" class="btn btn-sm ${isHidden ? 'btn-outline-secondary' : 'btn-outline-success'} p-0 px-1" style="font-size: 11px;" title="${isHidden ? 'প্রদর্শন করুন' : 'লুকান'}" onclick="toggleVisibility(${idx});">
                        <i class="bi ${isHidden ? 'bi-eye-slash' : 'bi-eye'}"></i>
                    </button>

                    <!-- Edit Settings -->
                    <button type="button" class="btn btn-sm btn-outline-primary p-0 px-1" style="font-size: 11px;" title="উইজেট কনফিগার করুন" onclick="editWidget(${idx});">
                        <i class="bi bi-gear-fill"></i>
                    </button>

                    <!-- Duplicate -->
                    <button type="button" class="btn btn-sm btn-outline-secondary p-0 px-1" style="font-size: 11px;" title="ডুপ্লিকেট করুন" onclick="duplicateWidget(${idx});">
                        <i class="bi bi-copy"></i>
                    </button>

                    <!-- Delete -->
                    <button type="button" class="btn btn-sm btn-outline-danger p-0 px-1" style="font-size: 11px;" title="মুছে ফেলুন" onclick="deleteWidget(${idx});">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
    });

    canvas.innerHTML = html;
}

/**
 * Generate human-friendly description for widget card
 */
function describeWidget(w) {
    const s = w.settings || {};
    switch (w.type) {
        case 'category_block':
        case 'category_grid':
            return `ক্যাটাগরি: ${s.category_slug || 'জাতীয়'} (${s.limit || 4} টি)`;
        case 'category_split':
        case 'full_width_split':
            return `${s.left_title || s.left_category} &bull; ${s.right_title || s.right_category}`;
        case 'ad_banner':
            return `স্লট: ${s.position || 'after_lead'}`;
        case 'hero_lead':
            return `লিড + ৩ থাম্ব + ৩ সাইডবার`;
        case 'multimedia_gallery':
            return `গ্যালারি (${s.limit || 4} টি)`;
        case 'custom_html':
            return `কাস্টম কোড`;
        case 'sidebar_tabs':
            return `সর্বশেষ ও জনপ্রিয় (${s.limit || 7} টি)`;
        case 'district_filter':
            return `জেলা ফিল্টার গ্রিড`;
        default:
            return '';
    }
}

/**
 * Add a new widget from palette
 */
function addWidget(type) {
    const meta = WIDGET_META[type];
    if (!meta) return;

    const newWidget = {
        id: `sec_${type}_${Date.now()}`,
        type: type,
        title: meta.defaultTitle,
        container: meta.defaultContainer,
        status: 1,
        settings: JSON.parse(JSON.stringify(meta.defaultSettings))
    };

    currentWidgets.push(newWidget);
    renderCanvas();
    updateLivePreview();
    showToast(`'${meta.label}' উইজেট যুক্ত হয়েছে।`);
}

/**
 * Toggle visibility of a widget
 */
function toggleVisibility(idx) {
    if (!currentWidgets[idx]) return;
    const currentStatus = currentWidgets[idx].status;
    currentWidgets[idx].status = (currentStatus === 0 || currentStatus === '0') ? 1 : 0;
    renderCanvas();
    updateLivePreview();
}

/**
 * Duplicate a widget
 */
function duplicateWidget(idx) {
    if (!currentWidgets[idx]) return;
    const clone = JSON.parse(JSON.stringify(currentWidgets[idx]));
    clone.id = `sec_${clone.type}_${Date.now()}`;
    clone.title = `${clone.title} (কপি)`;
    currentWidgets.splice(idx + 1, 0, clone);
    renderCanvas();
    updateLivePreview();
    showToast('উইজেট ডুপ্লিকেট করা হয়েছে।');
}

/**
 * Delete a widget
 */
function deleteWidget(idx) {
    if (!currentWidgets[idx]) return;
    if (confirm(`আপনি কি নিশ্চিত যে '${currentWidgets[idx].title}' উইজেটটি মুছে ফেলতে চান?`)) {
        currentWidgets.splice(idx, 1);
        renderCanvas();
        updateLivePreview();
        showToast('উইজেট মুছে ফেলা হয়েছে।');
    }
}

/**
 * Open Settings Modal for a widget
 */
function editWidget(idx) {
    activeEditingIndex = idx;
    const w = currentWidgets[idx];
    if (!w) return;

    const modalTitle = document.getElementById('widgetSettingsModalLabel');
    const container = document.getElementById('modalFormContainer');
    modalTitle.innerHTML = `<i class="bi bi-sliders me-1 text-warning"></i>কনফিগারেশন: ${escapeHtml(w.title)}`;

    const s = w.settings || {};
    let fieldsHtml = '';

    // Common 1: Title
    fieldsHtml += `
        <div class="row g-3 mb-3">
            <div class="col-md-8">
                <label class="form-label fw-bold small">উইজেটের শিরোনাম</label>
                <input type="text" class="form-control form-control-sm" id="edit_title" value="${escapeHtml(w.title || '')}">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold small">লেআউট অবস্থান</label>
                <select class="form-select form-select-sm" id="edit_container">
                    <option value="main" ${w.container === 'main' ? 'selected' : ''}>মূল কলাম (সাইডবারের পাশে - ৮ কলাম)</option>
                    <option value="full" ${w.container === 'full' ? 'selected' : ''}>পূর্ণ প্রস্থ (ফুল উইডথ - ১২ কলাম)</option>
                </select>
            </div>
        </div>
    `;

    // Type Specific Form Controls
    if (w.type === 'category_block' || w.type === 'category_grid') {
        let catOptions = '';
        ALL_CATEGORIES.forEach(c => {
            const isSel = (s.category_slug === c.slug) ? 'selected' : '';
            catOptions += `<option value="${escapeHtml(c.slug)}" ${isSel}>${escapeHtml(c.name)} (${escapeHtml(c.slug)})</option>`;
        });

        fieldsHtml += `
            <div class="row g-3 mb-3">
                <div class="col-md-8">
                    <label class="form-label fw-bold small">ক্যাটাগরি নির্বাচন</label>
                    <select class="form-select form-select-sm" id="edit_category_slug">
                        ${catOptions}
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small">সংবাদের সংখ্যা (Limit)</label>
                    <input type="number" class="form-control form-control-sm" id="edit_limit" value="${s.limit || 4}" min="1" max="15">
                </div>
            </div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="edit_show_more" ${s.show_more !== false ? 'checked' : ''}>
                <label class="form-check-label small" for="edit_show_more">'আরও খবর' লিংক প্রদর্শন করুন</label>
            </div>
        `;
    } else if (w.type === 'category_split' || w.type === 'full_width_split') {
        let leftOptions = '';
        let rightOptions = '';
        ALL_CATEGORIES.forEach(c => {
            leftOptions += `<option value="${escapeHtml(c.slug)}" ${s.left_category === c.slug ? 'selected' : ''}>${escapeHtml(c.name)}</option>`;
            rightOptions += `<option value="${escapeHtml(c.slug)}" ${s.right_category === c.slug ? 'selected' : ''}>${escapeHtml(c.name)}</option>`;
        });

        fieldsHtml += `
            <div class="border rounded p-3 mb-3 bg-light">
                <h6 class="fw-bold small text-primary mb-2">বামপাশের কলাম</h6>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small">ক্যাটাগরি</label>
                        <select class="form-select form-select-sm" id="edit_left_category">${leftOptions}</select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">কাস্টম শিরোনাম (ঐচ্ছিক)</label>
                        <input type="text" class="form-control form-control-sm" id="edit_left_title" value="${escapeHtml(s.left_title || '')}">
                    </div>
                </div>
            </div>

            <div class="border rounded p-3 mb-3 bg-light">
                <h6 class="fw-bold small text-primary mb-2">ডানপাশের কলাম</h6>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small">ক্যাটাগরি</label>
                        <select class="form-select form-select-sm" id="edit_right_category">${rightOptions}</select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">কাস্টম শিরোনাম (ঐচ্ছিক)</label>
                        <input type="text" class="form-control form-control-sm" id="edit_right_title" value="${escapeHtml(s.right_title || '')}">
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small">প্রতি কলামে সংবাদের সংখ্যা</label>
                <input type="number" class="form-control form-control-sm" id="edit_limit" value="${s.limit || 4}" min="1" max="10" style="max-width: 150px;">
            </div>
        `;
    } else if (w.type === 'ad_banner') {
        const positions = [
            { key: 'after_lead', label: 'হোম লিড পরবর্তী ব্যানার (৯৭০x৯০ বা রেসপন্সিভ)' },
            { key: 'header_right', label: 'হেডার ব্যানার (৭২৮x৯০)' },
            { key: 'sidebar', label: 'ডান পাশের সাইডবার মূল ব্যানার (Sidebar Top)' },
            { key: 'sidebar_bottom', label: 'ডান পাশের সাইডবার নিচের ব্যানার (Sidebar Bottom)' },
            { key: 'inside_article', label: 'সংবাদের ভেতরের ব্যানার' }
        ];

        let adOptions = '';
        positions.forEach(p => {
            adOptions += `<option value="${p.key}" ${s.position === p.key ? 'selected' : ''}>${p.label}</option>`;
        });

        fieldsHtml += `
            <div class="mb-3">
                <label class="form-label fw-bold small">বিজ্ঞাপন স্লট নির্বাচন করুন</label>
                <select class="form-select form-select-sm" id="edit_ad_position">${adOptions}</select>
                <small class="text-muted">বিজ্ঞাপনের ছবি বা লিঙ্ক পরিবর্তন করতে <a href="ads-manager.php" target="_blank">বিজ্ঞাপন ব্যানার নিয়ন্ত্রণ</a> পেজে যান।</small>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">অথবা কাস্টম অ্যাড কোড (ঐচ্ছিক)</label>
                <textarea class="form-control font-monospace small" id="edit_ad_custom_code" rows="3" placeholder="<div ...> বা গুগল অ্যাডসেন্স কোড">${escapeHtml(s.custom_code || '')}</textarea>
            </div>
        `;
    } else if (w.type === 'sidebar_tabs') {
        fieldsHtml += `
            <div class="mb-3">
                <label class="form-label fw-bold small">সংবাদের সংখ্যা (প্রতি ট্যাবে)</label>
                <input type="number" class="form-control form-control-sm" id="edit_limit" value="${s.limit || 7}" min="3" max="15" style="max-width: 150px;">
            </div>
        `;
    } else if (w.type === 'hero_lead') {
        fieldsHtml += `
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold small">প্রধান লিডের নিচের থাম্ব সংখ্যা</label>
                    <input type="number" class="form-control form-control-sm" id="edit_bottom_thumbs" value="${s.bottom_thumbs || 3}" min="0" max="6">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small">ডানপাশের সাব-লিড সংখ্যা</label>
                    <input type="number" class="form-control form-control-sm" id="edit_sub_leads" value="${s.sub_leads || 3}" min="1" max="6">
                </div>
            </div>
        `;
    } else if (w.type === 'custom_html') {
        fieldsHtml += `
            <div class="mb-3">
                <label class="form-label fw-bold small">কাস্টম এইচটিএমএল / এম্বেড কোড</label>
                <textarea class="form-control font-monospace small" id="edit_html_content" rows="6" placeholder="<div>কাস্টম কোড...</div>">${escapeHtml(s.content || '')}</textarea>
            </div>
        `;
    } else if (w.type === 'multimedia_gallery') {
        fieldsHtml += `
            <div class="mb-3">
                <label class="form-label fw-bold small">গ্যালারি আইটেম সংখ্যা</label>
                <input type="number" class="form-control form-control-sm" id="edit_limit" value="${s.limit || 4}" min="2" max="8" style="max-width: 150px;">
            </div>
        `;
    }

    container.innerHTML = fieldsHtml;

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('widgetSettingsModal'));
    modal.show();
}

/**
 * Apply settings from modal back into active widget
 */
function applyWidgetSettings() {
    if (activeEditingIndex < 0 || !currentWidgets[activeEditingIndex]) return;
    const w = currentWidgets[activeEditingIndex];

    w.title = document.getElementById('edit_title').value.trim();
    w.container = document.getElementById('edit_container').value;

    if (!w.settings) w.settings = {};

    if (w.type === 'category_block' || w.type === 'category_grid') {
        w.settings.category_slug = document.getElementById('edit_category_slug').value;
        w.settings.limit = parseInt(document.getElementById('edit_limit').value, 10) || 4;
        w.settings.show_more = document.getElementById('edit_show_more').checked;
    } else if (w.type === 'category_split' || w.type === 'full_width_split') {
        w.settings.left_category = document.getElementById('edit_left_category').value;
        w.settings.left_title = document.getElementById('edit_left_title').value.trim();
        w.settings.right_category = document.getElementById('edit_right_category').value;
        w.settings.right_title = document.getElementById('edit_right_title').value.trim();
        w.settings.limit = parseInt(document.getElementById('edit_limit').value, 10) || 4;
    } else if (w.type === 'ad_banner') {
        w.settings.position = document.getElementById('edit_ad_position').value;
        w.settings.custom_code = document.getElementById('edit_ad_custom_code').value;
    } else if (w.type === 'hero_lead') {
        w.settings.bottom_thumbs = parseInt(document.getElementById('edit_bottom_thumbs').value, 10) || 3;
        w.settings.sub_leads = parseInt(document.getElementById('edit_sub_leads').value, 10) || 3;
    } else if (w.type === 'custom_html') {
        w.settings.content = document.getElementById('edit_html_content').value;
    } else if (w.type === 'sidebar_tabs') {
        w.settings.limit = parseInt(document.getElementById('edit_limit').value, 10) || 7;
    } else if (w.type === 'multimedia_gallery') {
        w.settings.limit = parseInt(document.getElementById('edit_limit').value, 10) || 4;
    }

    renderCanvas();
    updateLivePreview();

    // Close modal
    const modalEl = document.getElementById('widgetSettingsModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();

    showToast('উইজেট কনফিগারেশন আপডেট হয়েছে।');
}

/**
 * Real-Time Live Preview Sync (WordPress/Elementor-Style)
 */
let previewDebounceTimer = null;
function updateLivePreview() {
    const syncBadge = document.getElementById('liveSyncStatus');
    if (syncBadge) {
        syncBadge.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" style="width: 8px; height: 8px;"></span>সিঙ্ক হচ্ছে...`;
        syncBadge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle d-inline-flex align-items-center gap-1';
    }

    clearTimeout(previewDebounceTimer);
    previewDebounceTimer = setTimeout(() => {
        const iframe = document.getElementById('livePreviewFrame');
        if (!iframe) return;

        const formData = new FormData();
        formData.append('page', PAGE_SLUG);
        formData.append('layout_data', JSON.stringify(currentWidgets));

        fetch('live-preview.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(html => {
            iframe.srcdoc = html;
            if (syncBadge) {
                syncBadge.innerHTML = `<span class="spinner-grow spinner-grow-sm text-success" style="width: 7px; height: 7px;"></span>সিঙ্ক সক্রিয়`;
                syncBadge.className = 'badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1';
            }
        })
        .catch(err => {
            console.error('Preview sync failed:', err);
            if (syncBadge) {
                syncBadge.innerHTML = `<i class="bi bi-exclamation-triangle text-danger"></i>সিঙ্ক সমস্যা`;
                syncBadge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle d-inline-flex align-items-center gap-1';
            }
        });
    }, 120);
}

/**
 * Refresh live preview manually
 */
function refreshLivePreview() {
    updateLivePreview();
    showToast('লাইভ প্রিভিউ রিফ্রেশ করা হয়েছে।');
}

/**
 * Switch Builder View Mode: 'split', 'canvas', 'preview'
 */
function setBuilderMode(mode) {
    currentMode = mode;
    const builderCol = document.getElementById('builderCol');
    const previewCol = document.getElementById('previewCol');
    const btnSplit = document.getElementById('btnModeSplit');
    const btnCanvas = document.getElementById('btnModeCanvas');
    const btnPreview = document.getElementById('btnModePreview');

    btnSplit.classList.remove('active', 'btn-white', 'shadow-sm');
    btnCanvas.classList.remove('active', 'btn-white', 'shadow-sm');
    btnPreview.classList.remove('active', 'btn-white', 'shadow-sm');

    if (mode === 'split') {
        btnSplit.classList.add('active', 'btn-white', 'shadow-sm');
        builderCol.className = 'col-xl-5 col-lg-6 d-block';
        previewCol.className = 'col-xl-7 col-lg-6 d-block';
    } else if (mode === 'canvas') {
        btnCanvas.classList.add('active', 'btn-white', 'shadow-sm');
        builderCol.className = 'col-12 d-block';
        previewCol.className = 'd-none';
    } else if (mode === 'preview') {
        btnPreview.classList.add('active', 'btn-white', 'shadow-sm');
        builderCol.className = 'd-none';
        previewCol.className = 'col-12 d-block';
    }
}

/**
 * Switch Preview Device Viewport: 'desktop', 'tablet', 'mobile'
 */
function setPreviewSize(device) {
    currentDevice = device;
    const iframe = document.getElementById('livePreviewFrame');
    const btnDesktop = document.getElementById('btnDevDesktop');
    const btnTablet = document.getElementById('btnDevTablet');
    const btnMobile = document.getElementById('btnDevMobile');

    btnDesktop.classList.remove('active', 'btn-primary', 'text-white');
    btnTablet.classList.remove('active', 'btn-primary', 'text-white');
    btnMobile.classList.remove('active', 'btn-primary', 'text-white');

    btnDesktop.classList.add('btn-light');
    btnTablet.classList.add('btn-light');
    btnMobile.classList.add('btn-light');

    iframe.className = `device-${device}`;

    if (device === 'desktop') {
        btnDesktop.classList.add('active', 'btn-primary', 'text-white');
        btnDesktop.classList.remove('btn-light');
    } else if (device === 'tablet') {
        btnTablet.classList.add('active', 'btn-primary', 'text-white');
        btnTablet.classList.remove('btn-light');
    } else if (device === 'mobile') {
        btnMobile.classList.add('active', 'btn-primary', 'text-white');
        btnMobile.classList.remove('btn-light');
    }
}

/**
 * Save Layout via AJAX POST
 */
function saveLayout() {
    const btn = document.getElementById('btnSaveLayout');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>সংরক্ষণ হচ্ছে...`;

    const formData = new FormData();
    formData.append('action', 'save_layout');
    formData.append('page_slug', PAGE_SLUG);
    formData.append('layout_data', JSON.stringify(currentWidgets));

    fetch('page-builder.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if (data.success) {
            showToast(data.message || 'লেআউট সফলভাবে সংরক্ষিত হয়েছে!');
            updateLivePreview();
        } else {
            alert(data.message || 'লেআউট সংরক্ষণে সমস্যা হয়েছে');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('সার্ভার যোগাযোগে সমস্যা হয়েছে: ' + err.message);
    });
}

/**
 * Show toast message
 */
function showToast(msg) {
    const toastEl = document.getElementById('saveToast');
    const msgEl = document.getElementById('saveToastMessage');
    msgEl.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i>${escapeHtml(msg)}`;
    const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
    toast.show();
}

/**
 * Auto Slug Generator for Create Page Modal
 */
function autoSlug(val) {
    const slug = val.toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .trim()
        .replace(/[\s_-]+/g, '-');
    document.getElementById('newPageSlug').value = slug;
}

/**
 * HTML Escaping
 */
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', function() {
    renderCanvas();
    updateLivePreview();

    // Initialize SortableJS
    const canvas = document.getElementById('widgetCanvas');
    new Sortable(canvas, {
        handle: '.widget-handle',
        animation: 180,
        ghostClass: 'sortable-ghost',
        onEnd: function(evt) {
            const oldIndex = evt.oldIndex;
            const newIndex = evt.newIndex;
            if (oldIndex !== newIndex) {
                const movedItem = currentWidgets.splice(oldIndex, 1)[0];
                currentWidgets.splice(newIndex, 0, movedItem);
                renderCanvas();
                updateLivePreview();
                showToast('উইজেটের ক্রম পরিবর্তিত হয়েছে। লাইভ প্রিভিউ আপডেট হয়েছে।');
            }
        }
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
