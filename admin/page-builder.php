<?php
/**
 * Elementor-Style Page Builder with Real-Time Live Preview
 * Left: widget icon library  •  Right: canvas + live content editor
 * Alokito Gour Clone
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';

$db = get_db();
$pageTitle = 'Page Builder | Alokito Gour';

/**
 * ------------------------------------------------------------------
 * AJAX: Save layout (called from the builder toolbar / Ctrl+S)
 * ------------------------------------------------------------------
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_layout') {
    header('Content-Type: application/json; charset=utf-8');

    // CSRF verification for the AJAX endpoint
    $token = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        echo json_encode([
            'success' => false,
            'message' => 'নিরাপত্তা টোকেনের মেয়াদ শেষ হয়েছে। পেজ রিফ্রেশ করে আবার চেষ্টা করুন।',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $slug = trim((string)($_POST['page_slug'] ?? 'home'));
    $rawLayout = (string)($_POST['layout_data'] ?? '[]');

    $decoded = json_decode($rawLayout, true);
    if (!is_array($decoded)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'লেআউট ডেটা সঠিক নয়।'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Whitelist widget structure so only known keys are persisted
    $allowedTypes = ['hero_lead', 'ad_banner', 'category_block', 'category_grid', 'category_split',
        'full_width_split', 'multimedia_gallery', 'custom_html', 'sidebar_tabs', 'district_filter'];
    $cleanLayout = [];
    foreach ($decoded as $widget) {
        if (!is_array($widget) || empty($widget['type'])) {
            continue;
        }
        if (!in_array($widget['type'], $allowedTypes, true)) {
            continue;
        }
        $cleanLayout[] = [
            'id'        => (string)($widget['id'] ?? ('sec_' . $widget['type'] . '_' . bin2hex(random_bytes(3)))),
            'type'      => (string)$widget['type'],
            'title'     => (string)($widget['title'] ?? ''),
            'container' => (isset($widget['container']) && $widget['container'] === 'full') ? 'full' : 'main',
            'status'    => (isset($widget['status']) && (int)$widget['status'] === 0) ? 0 : 1,
            'settings'  => is_array($widget['settings'] ?? null) ? $widget['settings'] : new stdClass(),
        ];
    }

    $stmt = $db->prepare("
        UPDATE `page_layouts`
        SET `layout_data` = :layout, `updated_at` = CURRENT_TIMESTAMP
        WHERE `page_slug` = :slug
    ");
    $stmt->execute([
        ':layout' => json_encode($cleanLayout, JSON_UNESCAPED_UNICODE),
        ':slug'   => $slug,
    ]);

    echo json_encode([
        'success'   => true,
        'message'   => 'লেআউট সফলভাবে সংরক্ষিত হয়েছে।',
        'count'     => count($cleanLayout),
        'saved_at'  => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * ------------------------------------------------------------------
 * Classic form actions: create page / delete page / reset default
 * ------------------------------------------------------------------
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $action = $_POST['form_action'] ?? '';

    if ($action === 'create_page') {
        $newTitle = trim($_POST['title'] ?? '');
        $newSlug = slugify(trim($_POST['slug'] ?? ''));

        if (empty($newTitle) || empty($newSlug)) {
            set_flash('danger', 'Page title and slug are required.');
        } else {
            $checkStmt = $db->prepare("SELECT id FROM page_layouts WHERE page_slug = ?");
            $checkStmt->execute([$newSlug]);
            if ($checkStmt->fetch()) {
                set_flash('danger', 'A page with this slug already exists.');
            } else {
                $template = $_POST['template'] ?? 'blank';
                $initData = '[]';
                if ($template === 'home_clone') {
                    $hStmt = $db->query("SELECT layout_data FROM page_layouts WHERE page_slug = 'home' LIMIT 1");
                    $hRow = $hStmt->fetch();
                    if ($hRow) {
                        $initData = $hRow['layout_data'];
                    }
                }

                $insStmt = $db->prepare("INSERT INTO page_layouts (page_slug, title, layout_data, status) VALUES (?, ?, ?, 1)");
                $insStmt->execute([$newSlug, $newTitle, $initData]);
                set_flash('success', "New page '{$newTitle}' created successfully.");
                header("Location: page-builder.php?page={$newSlug}");
                exit;
            }
        }
    } elseif ($action === 'delete_page') {
        $delSlug = trim($_POST['del_slug'] ?? '');
        if ($delSlug === 'home' || $delSlug === 'news_sidebar') {
            set_flash('danger', 'Core system layouts cannot be deleted.');
        } else {
            $delStmt = $db->prepare("DELETE FROM page_layouts WHERE page_slug = ?");
            $delStmt->execute([$delSlug]);
            set_flash('success', 'Page deleted successfully.');
            header('Location: page-builder.php?page=home');
            exit;
        }
    } elseif ($action === 'reset_default') {
        // Authentic default 11-widget homepage layout
        $defaultHomeLayout = [
            ['id' => 'sec_hero_lead', 'type' => 'hero_lead', 'title' => 'প্রধান ও শীর্ষ সংবাদ', 'container' => 'main', 'status' => 1, 'settings' => ['bottom_thumbs' => 3, 'sub_leads' => 3]],
            ['id' => 'sec_ad_after_lead', 'type' => 'ad_banner', 'title' => 'বিজ্ঞাপন ব্যানার (লিডের পর)', 'container' => 'main', 'status' => 1, 'settings' => ['position' => 'after_lead']],
            ['id' => 'sec_district_filter', 'type' => 'district_filter', 'title' => 'আমার এলাকার খবর', 'container' => 'main', 'status' => 1, 'settings' => []],
            ['id' => 'sec_cat_chapai', 'type' => 'category_block', 'title' => 'চাঁপাইনবাবগঞ্জ', 'container' => 'main', 'status' => 1, 'settings' => ['category_slug' => 'chapai-nawabganj', 'limit' => 5, 'show_more' => true]],
            ['id' => 'sec_cat_national', 'type' => 'category_grid', 'title' => 'জাতীয়', 'container' => 'main', 'status' => 1, 'settings' => ['category_slug' => 'national', 'limit' => 4, 'show_more' => true]],
            ['id' => 'sec_cat_politics_intl', 'type' => 'category_split', 'title' => 'রাজনীতি ও আন্তর্জাতিক', 'container' => 'main', 'status' => 1, 'settings' => ['left_category' => 'politics', 'left_title' => 'রাজনীতি', 'right_category' => 'international', 'right_title' => 'আন্তর্জাতিক', 'limit' => 4]],
            ['id' => 'sec_cat_economy_law', 'type' => 'category_split', 'title' => 'অর্থনীতি ও আইন-অধিকার', 'container' => 'main', 'status' => 1, 'settings' => ['left_category' => 'economy', 'left_title' => 'অর্থনীতি', 'right_category' => 'law-rights', 'right_title' => 'আইন ও অধিকার', 'limit' => 4]],
            ['id' => 'sec_cat_tech_religion', 'type' => 'category_split', 'title' => 'বিজ্ঞান ও প্রযুক্তি এবং ধর্ম', 'container' => 'main', 'status' => 1, 'settings' => ['left_category' => 'tech', 'left_title' => 'বিজ্ঞান ও প্রযুক্তি', 'right_category' => 'religion', 'right_title' => 'ধর্ম ও জীবন', 'limit' => 4]],
            ['id' => 'sec_cat_jobs_edu', 'type' => 'category_split', 'title' => 'চাকরি ও ক্যারিয়ার এবং শিক্ষা-সাহিত্য', 'container' => 'main', 'status' => 1, 'settings' => ['left_category' => 'jobs-career', 'left_title' => 'চাকরি ও ক্যারিয়ার', 'right_category' => 'education-literature', 'right_title' => 'শিক্ষা ও সাহিত্য', 'limit' => 4]],
            ['id' => 'sec_full_country_sports', 'type' => 'full_width_split', 'title' => 'সারাদেশ ও খেলাধুলা', 'container' => 'full', 'status' => 1, 'settings' => ['left_category' => 'country', 'left_title' => 'সারাদেশ', 'right_category' => 'sports', 'right_title' => 'খেলাধুলা', 'limit' => 4]],
            ['id' => 'sec_gallery', 'type' => 'multimedia_gallery', 'title' => 'ছবি ও ভিডিও গ্যালারি', 'container' => 'full', 'status' => 1, 'settings' => ['title' => 'ছবি ও ভিডিও গ্যালারি', 'limit' => 4]],
        ];

        $upStmt = $db->prepare("UPDATE page_layouts SET layout_data = ? WHERE page_slug = 'home'");
        $upStmt->execute([json_encode($defaultHomeLayout, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)]);
        set_flash('success', 'Homepage layout successfully reset to default sections.');
        header('Location: page-builder.php?page=home');
        exit;
    }
}

// Pages / current page
$allPages = $db->query("SELECT id, page_slug, title, updated_at FROM page_layouts ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

$currentSlug = trim($_GET['page'] ?? 'home');
$pageStmt = $db->prepare("SELECT * FROM page_layouts WHERE page_slug = :slug LIMIT 1");
$pageStmt->execute([':slug' => $currentSlug]);
$currentPage = $pageStmt->fetch(PDO::FETCH_ASSOC);

if (!$currentPage) {
    $currentSlug = 'home';
    $pageStmt->execute([':slug' => 'home']);
    $currentPage = $pageStmt->fetch(PDO::FETCH_ASSOC);
}

// Categories for the inspector dropdowns
$categories = $db->query("SELECT id, name, slug FROM categories ORDER BY order_index ASC")->fetchAll(PDO::FETCH_ASSOC);

// Decode current widgets
$currentWidgets = json_decode($currentPage['layout_data'] ?? '[]', true);
if (!is_array($currentWidgets)) {
    $currentWidgets = [];
}

// Normalise widget entries so the JS layer always has the same shape
$normalisedWidgets = [];
foreach ($currentWidgets as $widget) {
    if (!is_array($widget) || empty($widget['type'])) {
        continue;
    }
    $normalisedWidgets[] = [
        'id'        => (string)($widget['id'] ?? ('sec_' . $widget['type'] . '_' . substr(md5(json_encode($widget)), 0, 6))),
        'type'      => (string)$widget['type'],
        'title'     => (string)($widget['title'] ?? ''),
        'container' => (($widget['container'] ?? '') === 'full') ? 'full' : (string)($widget['container'] ?? 'main'),
        'status'    => (isset($widget['status']) && (int)$widget['status'] === 0) ? 0 : 1,
        'settings'  => is_array($widget['settings'] ?? null) ? $widget['settings'] : [],
    ];
}

$previewUrl = ($currentSlug === 'home')
    ? '/'
    : (($currentSlug === 'news_sidebar') ? '/news/1' : '/page/' . urlencode($currentSlug));

$previewFrameUrl = 'live-preview.php?page=' . urlencode($currentSlug) . '&builder=1';

$pageType = ($currentSlug === 'home') ? 'home' : (($currentSlug === 'news_sidebar') ? 'sidebar' : 'custom');

$builderConfig = [
    'pageSlug'   => $currentSlug,
    'pageTitle'  => $currentPage['title'] ?? $currentSlug,
    'pageType'   => $pageType,
    'csrfToken'  => csrf_token(),
    'widgets'    => $normalisedWidgets,
    'categories' => $categories,
    'previewUrl' => $previewFrameUrl,
    'liveUrl'    => $previewUrl,
    'saveUrl'    => 'page-builder.php',
];

$extraCss = ['assets/css/builder.css'];

include __DIR__ . '/includes/header.php';
?>

<div class="content-body">
    <div class="eb-app" id="ebApp">

        <!-- ================= TOP TOOLBAR ================= -->
        <header class="eb-topbar">
            <div class="eb-topbar-left">
                <span class="eb-brand">
                    <i class="bi bi-layout-text-window-reverse"></i>
                    <span>Page Builder</span>
                </span>

                <select class="eb-page-select" id="ebPageSelector" onchange="location.href='page-builder.php?page=' + encodeURIComponent(this.value);">
                    <?php foreach ($allPages as $p): ?>
                        <option value="<?= e($p['page_slug']) ?>" <?= $p['page_slug'] === $currentSlug ? 'selected' : '' ?>>
                            <?= e($p['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="button" class="eb-btn eb-btn-icon" data-bs-toggle="modal" data-bs-target="#createPageModal" title="নতুন পেজ তৈরি করুন">
                    <i class="bi bi-plus-lg"></i>
                </button>
            </div>

            <div class="eb-topbar-center">
                <div class="eb-segment" id="ebModeSwitch">
                    <button type="button" data-mode="split" class="active" title="এডিটর ও লাইভ প্রিভিউ একসাথে">
                        <i class="bi bi-layout-sidebar-inset"></i><span>এডিটর</span>
                    </button>
                    <button type="button" data-mode="focus" title="শুধু লাইভ প্রিভিউ (ফুল স্ক্রিন)">
                        <i class="bi bi-eye"></i><span>প্রিভিউ</span>
                    </button>
                </div>

                <div class="eb-segment" id="ebDeviceSwitch">
                    <button type="button" data-device="desktop" class="active" title="ডেস্কটপ ভিউ"><i class="bi bi-display"></i></button>
                    <button type="button" data-device="tablet" title="ট্যাবলেট ভিউ"><i class="bi bi-tablet"></i></button>
                    <button type="button" data-device="mobile" title="মোবাইল ভিউ"><i class="bi bi-phone"></i></button>
                </div>

                <div class="eb-segment">
                    <button type="button" id="ebUndo" title="আনডু (Ctrl + Z)" disabled><i class="bi bi-arrow-counterclockwise"></i></button>
                    <button type="button" id="ebRedo" title="রিডু (Ctrl + Shift + Z)" disabled><i class="bi bi-arrow-clockwise"></i></button>
                </div>
            </div>

            <div class="eb-topbar-right">
                <span class="eb-save-state" id="ebSaveState"><span class="dot"></span><span class="txt">সব পরিবর্তন সংরক্ষিত</span></span>

                <button type="button" class="eb-btn eb-btn-icon" id="ebToggleLibrary" title="উইজেট প্যানেল">
                    <i class="bi bi-grid-3x3-gap"></i>
                </button>

                <a href="<?= e($previewUrl) ?>" target="_blank" class="eb-btn eb-btn-ghost" title="লাইভ ওয়েবসাইট দেখুন">
                    <i class="bi bi-box-arrow-up-right"></i><span>লাইভ সাইট</span>
                </a>

                <?php if ($currentSlug === 'home'): ?>
                    <form method="POST" class="d-inline-flex m-0" onsubmit="return confirm('আপনি কি হোমপেজের সব সেকশন আগের ডিফল্ট লেআউটে ফিরিয়ে নিতে চান?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="form_action" value="reset_default">
                        <button type="submit" class="eb-btn eb-btn-icon" title="ডিফল্ট লেআউটে রিসেট করুন">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    </form>
                <?php elseif ($currentSlug !== 'news_sidebar'): ?>
                    <form method="POST" class="d-inline-flex m-0" onsubmit="return confirm('আপনি কি এই পেজটি মুছে ফেলতে চান?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="form_action" value="delete_page">
                        <input type="hidden" name="del_slug" value="<?= e($currentSlug) ?>">
                        <button type="submit" class="eb-btn eb-btn-icon" title="পেজ ডিলিট করুন">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </form>
                <?php endif; ?>

                <button type="button" class="eb-btn eb-btn-save" id="ebSaveBtn" title="লেআউট সেভ করুন (Ctrl + S)">
                    <i class="bi bi-check2-circle"></i><span>সেভ করুন</span>
                </button>
            </div>
        </header>

        <!-- ================= WORKSPACE ================= -->
        <div class="eb-body">

            <!-- ---------- LEFT: widget icon library + structure ---------- -->
            <aside class="eb-panel eb-panel-left" id="ebLeftPanel">
                <div class="eb-panel-head">
                    <h6 class="eb-panel-title"><i class="bi bi-puzzle-fill"></i>উইজেট লাইব্রেরি</h6>
                    <div class="d-flex align-items-center gap-1">
                        <span class="eb-count" id="ebLibraryCount">10</span>
                        <button type="button" class="eb-btn eb-btn-icon eb-panel-close" id="ebCloseLibrary" title="বন্ধ করুন">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>

                <div class="eb-tabs" id="ebTabs">
                    <button type="button" class="eb-tab active" data-tab="widgets">
                        <i class="bi bi-grid-3x3-gap-fill"></i>উইজেট
                    </button>
                    <button type="button" class="eb-tab" data-tab="navigator">
                        <i class="bi bi-list-nested"></i>স্ট্রাকচার
                        <span class="eb-count" id="ebNavCount">0</span>
                    </button>
                </div>

                <div class="eb-panel-scroll">
                    <div class="eb-tab-panel" data-panel="widgets">
                        <div class="eb-search">
                            <i class="bi bi-search"></i>
                            <input type="text" id="ebWidgetSearch" placeholder="উইজেট খুঁজুন…" autocomplete="off">
                        </div>
                        <div id="ebLibrary"><!-- widget tiles injected by JS --></div>
                    </div>

                    <div class="eb-tab-panel" data-panel="navigator" hidden>
                        <ul class="eb-nav-list" id="ebNavList"><!-- injected by JS --></ul>
                        <div class="eb-library-empty" id="ebNavEmpty" hidden>
                            <i class="bi bi-inboxes d-block fs-4 mb-1 opacity-50"></i>
                            এখনো কোনো উইজেট নেই। বাম দিকের আইকন থেকে যোগ করুন।
                        </div>
                    </div>
                </div>

                <div class="eb-panel-hint">
                    <i class="bi bi-hand-index-thumb-fill"></i>
                    আইকনে ক্লিক করুন বা ডান দিকের প্রিভিউতে টেনে ড্রপ করুন
                </div>
            </aside>

            <!-- ---------- CENTER: live preview canvas ---------- -->
            <section class="eb-canvas">
                <div class="eb-canvas-bar">
                    <div class="d-flex align-items-center gap-2">
                        <span class="eb-chip" id="ebSyncChip">
                            <span class="sync-dot"></span><span class="txt">লাইভ প্রিভিউ</span>
                        </span>
                        <span class="eb-chip">
                            <i class="bi bi-layers-half"></i><span id="ebWidgetCount">0</span> উইজেট
                        </span>
                    </div>

                    <div class="eb-canvas-actions">
                        <button type="button" class="eb-btn" id="ebOpenLibrary" title="উইজেট লাইব্রেরি খুলুন">
                            <i class="bi bi-grid-3x3-gap"></i><span>উইজেট</span>
                        </button>
                        <button type="button" class="eb-btn" id="ebRefreshPreview" title="প্রিভিউ রিফ্রেশ করুন">
                            <i class="bi bi-arrow-clockwise"></i><span>রিফ্রেশ</span>
                        </button>
                        <button type="button" class="eb-btn" id="ebToggleFocus" title="ফুল স্ক্রিন প্রিভিউ">
                            <i class="bi bi-arrows-fullscreen"></i>
                        </button>
                    </div>
                </div>

                <div class="eb-frame-wrap">
                    <div class="eb-frame-shell device-desktop" id="ebFrameShell">
                        <iframe id="ebPreviewFrame" src="<?= e($previewFrameUrl) ?>" title="রিয়েল-টাইম লাইভ প্রিভিউ"></iframe>
                        <div class="eb-frame-loading"><span><i class="bi bi-arrow-repeat"></i>প্রিভিউ আপডেট হচ্ছে…</span></div>
                    </div>
                </div>
            </section>

            <!-- ---------- RIGHT: live content editor (inspector) ---------- -->
            <aside class="eb-panel eb-panel-right" id="ebInspector">
                <div id="ebInspectorContent" class="d-flex flex-column h-100"><!-- injected by JS --></div>
            </aside>
        </div>
    </div>
</div>

<!-- ================= CREATE PAGE MODAL ================= -->
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
                        <label class="form-label fw-bold small">পেজের নাম <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="যেমন: বিশেষ সংবাদ" required id="newPageTitle" onkeyup="autoSlug(this.value);">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">URL স্লাগ <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text small">/page/</span>
                            <input type="text" name="slug" class="form-control" placeholder="special-coverage" required id="newPageSlug">
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold small">শুরুর লেআউট</label>
                        <select name="template" class="form-select">
                            <option value="blank">খালি ক্যানভাস</option>
                            <option value="home_clone">হোমপেজের লেআউট কপি করুন</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">বাতিল</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">পেজ তৈরি করুন</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= TOAST ================= -->
<div class="eb-toast" id="ebToast" role="status" aria-live="polite">
    <i class="bi bi-check-circle-fill"></i><span id="ebToastText">সংরক্ষিত হয়েছে</span>
</div>

<script>
    window.ALOKITO_BUILDER = <?= json_encode($builderConfig, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    // Slug helper for the "create page" modal
    function autoSlug(val) {
        const slug = String(val).toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .trim()
            .replace(/[\s_-]+/g, '-');
        const target = document.getElementById('newPageSlug');
        if (target) target.value = slug;
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="assets/js/page-builder.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
