<?php
/**
 * Dynamic Drag-and-Drop Page Builder with Real-Time Live Preview
 * WordPress/Elementor-Style Real-time Sync & Modular Widget Engine
 * Alokito Gour Clone
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';

$db = get_db();
$pageTitle = 'Page Builder | Alokito Gour';

// Handle AJAX Save Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_layout') {
    header('Content-Type: application/json; charset=utf-8');

    $slug = trim($_POST['page_slug'] ?? 'home');
    $rawLayout = $_POST['layout_data'] ?? '[]';

    // Verify valid JSON
    $decoded = json_decode($rawLayout, true);
    if (!is_array($decoded)) {
        echo json_encode(['success' => false, 'message' => 'Invalid layout data']);
        exit;
    }

    // Video galleries accept an unlimited list of valid YouTube video URLs only.
    foreach ($decoded as &$layoutWidget) {
        if (!is_array($layoutWidget) || ($layoutWidget['type'] ?? '') !== 'multimedia_gallery') {
            continue;
        }

        $gallerySettings = isset($layoutWidget['settings']) && is_array($layoutWidget['settings']) ? $layoutWidget['settings'] : [];
        $videoLinks = $gallerySettings['youtube_videos'] ?? [];
        if (!is_array($videoLinks)) {
            echo json_encode(['success' => false, 'message' => 'YouTube video links must be provided as a list.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        foreach ($videoLinks as $videoLink) {
            if (!is_string($videoLink) || (trim($videoLink) !== '' && youtube_video_id_from_url($videoLink) === null)) {
                echo json_encode(['success' => false, 'message' => 'Only valid YouTube video links are allowed in the Video Gallery.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        $layoutWidget = normalize_video_gallery_widget($layoutWidget);
    }
    unset($layoutWidget);

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
        'message' => 'Layout saved successfully!',
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
            set_flash('danger', 'Page title and slug are required.');
        } else {
            // Check uniqueness
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
                    if ($hRow) $initData = $hRow['layout_data'];
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
        // Authentic default 10-widget homepage layout
        $defaultHomeLayout = [
            [
                'id' => 'sec_hero_lead',
                'type' => 'hero_lead',
                'title' => 'Top & Lead Stories (Hero Lead Section)',
                'container' => 'main',
                'status' => 1,
                'settings' => ['bottom_thumbs' => 3, 'sub_leads' => 3]
            ],
            [
                'id' => 'sec_ad_after_lead',
                'type' => 'ad_banner',
                'title' => 'Ad Banner (After Lead)',
                'container' => 'main',
                'status' => 1,
                'settings' => ['position' => 'after_lead']
            ],
            [
                'id' => 'sec_cat_chapai',
                'type' => 'category_block',
                'title' => 'Chapainawabganj',
                'container' => 'main',
                'status' => 1,
                'settings' => ['category_slug' => 'chapai-nawabganj', 'limit' => 5, 'show_more' => true]
            ],
            [
                'id' => 'sec_cat_national',
                'type' => 'category_grid',
                'title' => 'National',
                'container' => 'main',
                'status' => 1,
                'settings' => ['category_slug' => 'national', 'limit' => 4, 'show_more' => true]
            ],
            [
                'id' => 'sec_cat_politics_intl',
                'type' => 'category_split',
                'title' => 'Politics & International',
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
                'title' => 'Economy & Law-Rights',
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
                'title' => 'Tech & Religion',
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
                'title' => 'Jobs & Education',
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
                'title' => 'Countrywide & Sports',
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
                'title' => 'ভিডিও গ্যালারি',
                'container' => 'full',
                'status' => 1,
                'settings' => ['title' => 'ভিডিও গ্যালারি', 'youtube_videos' => []]
            ]
        ];

        $upStmt = $db->prepare("UPDATE page_layouts SET layout_data = ? WHERE page_slug = 'home'");
        $upStmt->execute([json_encode($defaultHomeLayout, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)]);
        set_flash('success', 'Homepage layout successfully reset to default sections.');
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

// Upgrade existing multimedia widgets to the YouTube-only video gallery editor.
foreach ($currentWidgets as $widgetIndex => $currentWidget) {
    if (is_array($currentWidget) && ($currentWidget['type'] ?? '') === 'multimedia_gallery') {
        $currentWidgets[$widgetIndex] = normalize_video_gallery_widget($currentWidget);
    }
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
                        <label class="form-label mb-0 fw-bold small text-nowrap"><i class="bi bi-file-earmark-code me-1 text-warning"></i>Page:</label>
                        <select class="form-select form-select-sm" id="pageSelector" onchange="location.href='page-builder.php?page=' + this.value;">
                            <?php foreach ($allPages as $p): ?>
                                <option value="<?= e($p['page_slug']) ?>" <?= $p['page_slug'] === $currentSlug ? 'selected' : '' ?>>
                                    <?= e($p['title']) ?> (<?= e($p['page_slug']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-outline-primary btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#createPageModal" title="Create New Page">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- View Mode Switcher & Device Controls -->
                <div class="col-xl-4 col-lg-7 col-md-6 text-center">
                    <div class="d-inline-flex align-items-center gap-1 bg-light p-1 rounded border">
                        <button type="button" class="btn btn-sm btn-white border shadow-sm active" id="btnModeSplit" onclick="setBuilderMode('split')" title="Split Mode: Canvas and Live Preview together">
                            <i class="bi bi-layout-split me-1 text-primary"></i>Split View
                        </button>
                        <button type="button" class="btn btn-sm btn-light border-0" id="btnModeCanvas" onclick="setBuilderMode('canvas')" title="Canvas Drag-and-Drop Only">
                            <i class="bi bi-card-checklist me-1 text-secondary"></i>Canvas
                        </button>
                        <button type="button" class="btn btn-sm btn-light border-0" id="btnModePreview" onclick="setBuilderMode('preview')" title="Fullscreen Live Preview">
                            <i class="bi bi-eye me-1 text-success"></i>Full Preview
                        </button>
                    </div>
                </div>

                <!-- Actions: Reset, Live Link, Save -->
                <div class="col-xl-4 col-lg-12 text-xl-end text-center">
                    <div class="d-flex align-items-center justify-content-xl-end justify-content-center gap-2 flex-wrap">
                        <?php 
                        $previewUrl = ($currentSlug === 'home') ? '/' : (($currentSlug === 'news_sidebar') ? '/news/1' : '/page/' . urlencode($currentSlug));
                        ?>
                        <a href="<?= e($previewUrl) ?>" target="_blank" class="btn btn-outline-secondary btn-sm" title="View live website in new tab">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Live Site
                        </a>

                        <?php if ($currentSlug === 'home'): ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reset all homepage sections to original default layout?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="form_action" value="reset_default">
                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Reset to Default Layout">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </form>
                        <?php elseif ($currentSlug !== 'news_sidebar'): ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this page?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="form_action" value="delete_page">
                                <input type="hidden" name="del_slug" value="<?= e($currentSlug) ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete Page">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        <?php endif; ?>

                        <!-- Save Layout Button -->
                        <button type="button" class="btn btn-success btn-sm px-3 fw-bold" id="btnSaveLayout" onclick="saveLayout();">
                            <i class="bi bi-check2-circle me-1"></i>Save Layout
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Builder Workspace Grid -->
    <div class="row g-3" id="workspaceRow">
        <!-- Column 1: Builder Canvas & Widget Library -->
        <div class="col-xl-5 col-lg-6" id="builderCol">
            <!-- Canvas Card -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 fw-bold text-dark small"><i class="bi bi-ui-checks me-1 text-primary"></i>Canvas Widgets (Drag & Drop)</h6>
                    </div>
                    <span class="badge bg-secondary-subtle text-secondary" id="widgetCountBadge"><?= count($currentWidgets) ?></span>
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
                    <h6 class="mb-0 fw-bold text-dark small"><i class="bi bi-puzzle-fill me-1 text-warning"></i>+ Add Widget (Library)</h6>
                    <small class="text-muted" style="font-size: 11px;">Click to append to canvas</small>
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
                                    <div class="fw-bold small text-dark lh-sm">Hero Lead Stories</div>
                                    <div class="text-muted text-truncate" style="font-size: 10px;">1 main lead story, 3 bottom thumbnails, and 3 sub-leads</div>
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
                                    <div class="fw-bold small text-dark lh-sm">Advertisement Banner</div>
                                    <div class="text-muted text-truncate" style="font-size: 10px;">After-lead or sidebar advertisement banner slot</div>
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
                                    <div class="fw-bold small text-dark lh-sm">Category Block (1 Big + List)</div>
                                    <div class="text-muted text-truncate" style="font-size: 10px;">Featured thumbnail on left and bullet news list on right</div>
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
                                    <div class="fw-bold small text-dark lh-sm">Category Grid (4 Thumb Cards)</div>
                                    <div class="text-muted text-truncate" style="font-size: 10px;">2x2 responsive photo grid of news cards</div>
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
                                    <div class="fw-bold small text-dark lh-sm">Dual Category (Side-by-side)</div>
                                    <div class="text-muted text-truncate" style="font-size: 10px;">Side-by-side 2 different news categories</div>
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
                                    <div class="fw-bold small text-dark lh-sm">Full-Width Double Block</div>
                                    <div class="text-muted text-truncate" style="font-size: 10px;">Full 12-column row with two side-by-side categories</div>
                                </div>
                            </div>
                        </div>

                        <!-- 7. YouTube Video Gallery -->
                        <div class="col-12">
                            <div class="widget-palette-item p-2" onclick="addWidget('multimedia_gallery');">
                                <div class="widget-palette-icon bg-dark bg-opacity-10 text-dark" style="width:30px; height:30px; font-size:1rem;">
                                    <i class="bi bi-camera-video-fill"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="fw-bold small text-dark lh-sm">Video Gallery</div>
                                    <div class="text-muted text-truncate" style="font-size: 10px;">Unlimited YouTube videos in an automatic carousel</div>
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
                                    <div class="fw-bold small text-dark lh-sm">Custom HTML / Notice (Embed)</div>
                                    <div class="text-muted text-truncate" style="font-size: 10px;">Embed YouTube video, iframe, or custom HTML/CSS</div>
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
                                    <div class="fw-bold small text-dark lh-sm">Latest & Popular News Tabs</div>
                                    <div class="text-muted text-truncate" style="font-size: 10px;">Tabbed news widget for latest and most-read stories</div>
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
                                    <div class="fw-bold small text-dark lh-sm">Local News (District Filter)</div>
                                    <div class="text-muted text-truncate" style="font-size: 10px;">Interactive district button grid filter</div>
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
                        <span class="fw-bold text-dark small"><i class="bi bi-display me-1 text-success"></i>Real-Time Live Preview</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1" id="liveSyncStatus" style="font-size: 11px;">
                            <span class="spinner-grow spinner-grow-sm text-success" style="width: 7px; height: 7px;"></span>Sync Active
                        </span>
                    </div>

                    <!-- Device Switcher Buttons -->
                    <div class="d-flex align-items-center gap-1">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-light border active" id="btnDevDesktop" onclick="setPreviewSize('desktop')" title="Desktop View (100%)">
                                <i class="bi bi-display"></i>
                            </button>
                            <button type="button" class="btn btn-light border" id="btnDevTablet" onclick="setPreviewSize('tablet')" title="Tablet View (768px)">
                                <i class="bi bi-tablet"></i>
                            </button>
                            <button type="button" class="btn btn-light border" id="btnDevMobile" onclick="setPreviewSize('mobile')" title="Mobile View (375px)">
                                <i class="bi bi-phone"></i>
                            </button>
                        </div>

                        <button type="button" class="btn btn-light border btn-sm" onclick="refreshLivePreview()" title="Refresh Preview">
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
                    <i class="bi bi-sliders me-1 text-warning"></i>Widget Configuration
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="modalFormContainer">
                <!-- Form dynamically rendered by JS based on widget type -->
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm px-3" onclick="applyWidgetSettings();">
                    <i class="bi bi-check2 me-1"></i>Apply Settings
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
                        <i class="bi bi-file-earmark-plus me-1 text-primary"></i>Create New Page
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Page Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Special News Coverage" required id="newPageTitle" onkeyup="autoSlug(this.value);">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Page URL Slug <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text small">/page/</span>
                            <input type="text" name="slug" class="form-control" placeholder="special-coverage" required id="newPageSlug">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Initial Layout Template</label>
                        <select name="template" class="form-select">
                            <option value="blank">Blank Canvas</option>
                            <option value="home_clone">Clone Homepage Layout</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Create Page</button>
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
                <i class="bi bi-check-circle-fill me-2"></i>Layout saved successfully!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- SortableJS CDN -->
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
        label: 'Hero Lead Stories',
        icon: 'bi-newspaper',
        colorClass: 'type-hero_lead',
        defaultContainer: 'main',
        defaultTitle: 'Top & Lead Stories (Hero Lead Section)',
        defaultSettings: { bottom_thumbs: 3, sub_leads: 3 }
    },
    ad_banner: {
        label: 'Advertisement Banner',
        icon: 'bi-badge-ad',
        colorClass: 'type-ad_banner',
        defaultContainer: 'main',
        defaultTitle: 'Ad Banner (After Lead)',
        defaultSettings: { position: 'after_lead', custom_code: '' }
    },
    category_block: {
        label: 'Category Block (1 Big + List)',
        icon: 'bi-card-heading',
        colorClass: 'type-category_block',
        defaultContainer: 'main',
        defaultTitle: 'Chapainawabganj',
        defaultSettings: { category_slug: 'chapai-nawabganj', limit: 5, show_more: true }
    },
    category_grid: {
        label: 'Category Grid (4 Thumb Cards)',
        icon: 'bi-grid-fill',
        colorClass: 'type-category_grid',
        defaultContainer: 'main',
        defaultTitle: 'National',
        defaultSettings: { category_slug: 'national', limit: 4, show_more: true }
    },
    category_split: {
        label: 'Dual Category (Side-by-side)',
        icon: 'bi-layout-split',
        colorClass: 'type-category_split',
        defaultContainer: 'main',
        defaultTitle: 'Politics & International',
        defaultSettings: { left_category: 'politics', left_title: 'রাজনীতি', right_category: 'international', right_title: 'আন্তর্জাতিক', limit: 4 }
    },
    full_width_split: {
        label: 'Full-Width Double Block',
        icon: 'bi-distribute-horizontal',
        colorClass: 'type-full_width_split',
        defaultContainer: 'full',
        defaultTitle: 'Countrywide & Sports',
        defaultSettings: { left_category: 'country', left_title: 'সারাদেশ', right_category: 'sports', right_title: 'খেলাধুলা', limit: 4 }
    },
    multimedia_gallery: {
        label: 'Video Gallery',
        icon: 'bi-camera-video-fill',
        colorClass: 'type-multimedia_gallery',
        defaultContainer: 'full',
        defaultTitle: 'ভিডিও গ্যালারি',
        defaultSettings: { title: 'ভিডিও গ্যালারি', youtube_videos: [] }
    },
    custom_html: {
        label: 'Custom HTML / Embed',
        icon: 'bi-code-slash',
        colorClass: 'type-custom_html',
        defaultContainer: 'main',
        defaultTitle: 'Custom Notice / Embed Code',
        defaultSettings: { content: '<div class="p-3 bg-light border rounded">Custom notice or embed code here...</div>' }
    },
    sidebar_tabs: {
        label: 'Latest & Popular News Tabs',
        icon: 'bi-clock-history',
        colorClass: 'type-sidebar_tabs',
        defaultContainer: 'main',
        defaultTitle: 'Latest & Popular Stories',
        defaultSettings: { limit: 7 }
    },
    district_filter: {
        label: 'Local News (District Filter)',
        icon: 'bi-geo-alt-fill',
        colorClass: 'type-district_filter',
        defaultContainer: 'main',
        defaultTitle: 'Regional News (District Filter)',
        defaultSettings: {}
    }
};

/**
 * Render the entire canvas from currentWidgets array
 */
function renderCanvas() {
    const canvas = document.getElementById('widgetCanvas');
    const badge = document.getElementById('widgetCountBadge');
    badge.innerText = `${currentWidgets.length}`;

    if (currentWidgets.length === 0) {
        canvas.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="bi bi-layers fs-2 opacity-50 d-block mb-1"></i>
                <h6 class="small mb-1">No widgets on canvas</h6>
                <p class="text-muted" style="font-size: 11px;">Click any widget from the library below to add it.</p>
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
        const containerLabel = (container === 'full') ? 'Full Width' : 'Main Column';
        const containerBadgeClass = (container === 'full') ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary';

        html += `
            <div class="widget-card ${meta.colorClass} ${isHidden ? 'is-hidden' : ''} p-2 mb-2" data-index="${idx}">
                <div class="d-flex align-items-center gap-2 flex-grow-1 overflow-hidden">
                    <span class="widget-handle py-1 px-1" title="Drag to reorder"><i class="bi bi-grip-vertical"></i></span>
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
                    <button type="button" class="btn btn-sm ${isHidden ? 'btn-outline-secondary' : 'btn-outline-success'} p-0 px-1" style="font-size: 11px;" title="${isHidden ? 'Show' : 'Hide'}" onclick="toggleVisibility(${idx});">
                        <i class="bi ${isHidden ? 'bi-eye-slash' : 'bi-eye'}"></i>
                    </button>

                    <!-- Edit Settings -->
                    <button type="button" class="btn btn-sm btn-outline-primary p-0 px-1" style="font-size: 11px;" title="Configure Widget" onclick="editWidget(${idx});">
                        <i class="bi bi-gear-fill"></i>
                    </button>

                    <!-- Duplicate -->
                    <button type="button" class="btn btn-sm btn-outline-secondary p-0 px-1" style="font-size: 11px;" title="Duplicate" onclick="duplicateWidget(${idx});">
                        <i class="bi bi-copy"></i>
                    </button>

                    <!-- Delete -->
                    <button type="button" class="btn btn-sm btn-outline-danger p-0 px-1" style="font-size: 11px;" title="Delete" onclick="deleteWidget(${idx});">
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
            return `Category: ${s.category_slug || 'national'} (${s.limit || 4} posts)`;
        case 'category_split':
        case 'full_width_split':
            return `${s.left_title || s.left_category} &bull; ${s.right_title || s.right_category}`;
        case 'ad_banner':
            return `Slot: ${s.position || 'after_lead'}`;
        case 'hero_lead':
            return `Lead + 3 Thumbs + 3 Sidebar`;
        case 'multimedia_gallery': {
            const videoCount = Array.isArray(s.youtube_videos) ? s.youtube_videos.filter(Boolean).length : 0;
            return `${videoCount} YouTube video${videoCount === 1 ? '' : 's'}`;
        }
        case 'custom_html':
            return `Custom HTML`;
        case 'sidebar_tabs':
            return `Latest & Popular (${s.limit || 7} posts)`;
        case 'district_filter':
            return `District Filter Grid`;
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
    showToast(`'${meta.label}' widget added.`);
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
    clone.title = `${clone.title} (Copy)`;
    currentWidgets.splice(idx + 1, 0, clone);
    renderCanvas();
    updateLivePreview();
    showToast('Widget duplicated.');
}

/**
 * Delete a widget
 */
function deleteWidget(idx) {
    if (!currentWidgets[idx]) return;
    if (confirm(`Are you sure you want to delete '${currentWidgets[idx].title}' widget?`)) {
        currentWidgets.splice(idx, 1);
        renderCanvas();
        updateLivePreview();
        showToast('Widget deleted.');
    }
}

/**
 * Validate a YouTube URL and return its 11-character video ID.
 */
function getYoutubeVideoId(value) {
    try {
        const url = new URL(value.trim());
        if (!['http:', 'https:'].includes(url.protocol)) return null;

        const host = url.hostname.toLowerCase().replace(/\.$/, '');
        let videoId = null;

        if (['youtu.be', 'www.youtu.be'].includes(host)) {
            const match = url.pathname.match(/^\/([A-Za-z0-9_-]{11})(?:\/.*)?$/);
            if (match) videoId = match[1];
        } else if (['youtube.com', 'www.youtube.com', 'm.youtube.com'].includes(host)) {
            if (url.pathname.replace(/^\/+|\/+$/g, '') === 'watch') {
                videoId = url.searchParams.get('v');
            } else {
                const match = url.pathname.match(/^\/(?:embed|shorts|live|v)\/([A-Za-z0-9_-]{11})(?:\/.*)?$/);
                if (match) videoId = match[1];
            }
        }

        return videoId && /^[A-Za-z0-9_-]{11}$/.test(videoId) ? videoId : null;
    } catch (error) {
        return null;
    }
}

/**
 * Build one repeatable YouTube URL field. There is intentionally no item limit.
 */
function youtubeVideoFieldHtml(videoUrl = '') {
    return `
        <div class="input-group input-group-sm youtube-video-row mb-2">
            <span class="input-group-text bg-white"><i class="bi bi-youtube text-danger"></i></span>
            <input type="url" class="form-control youtube-video-url" value="${escapeHtml(videoUrl)}" placeholder="https://www.youtube.com/watch?v=..." aria-label="YouTube video URL">
            <button type="button" class="btn btn-outline-danger" onclick="removeYoutubeVideoField(this);" title="Remove video" aria-label="Remove video">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
}

function addYoutubeVideoField() {
    const list = document.getElementById('youtubeVideoList');
    if (!list) return;
    list.insertAdjacentHTML('beforeend', youtubeVideoFieldHtml());
    const inputs = list.querySelectorAll('.youtube-video-url');
    if (inputs.length) inputs[inputs.length - 1].focus();
}

function removeYoutubeVideoField(button) {
    const row = button.closest('.youtube-video-row');
    if (row) row.remove();
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
    modalTitle.innerHTML = `<i class="bi bi-sliders me-1 text-warning"></i>Configuration: ${escapeHtml(w.title)}`;

    const s = w.settings || {};
    let fieldsHtml = '';

    // Common 1: Title
    fieldsHtml += `
        <div class="row g-3 mb-3">
            <div class="col-md-8">
                <label class="form-label fw-bold small">Widget Title</label>
                <input type="text" class="form-control form-control-sm" id="edit_title" value="${escapeHtml(w.title || '')}">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold small">Layout Position</label>
                <select class="form-select form-select-sm" id="edit_container">
                    <option value="main" ${w.container === 'main' ? 'selected' : ''}>Main Column (8 Columns)</option>
                    <option value="full" ${w.container === 'full' ? 'selected' : ''}>Full Width (12 Columns)</option>
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
                    <label class="form-label fw-bold small">Select Category</label>
                    <select class="form-select form-select-sm" id="edit_category_slug">
                        ${catOptions}
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small">Posts Limit</label>
                    <input type="number" class="form-control form-control-sm" id="edit_limit" value="${s.limit || 4}" min="1" max="15">
                </div>
            </div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="edit_show_more" ${s.show_more !== false ? 'checked' : ''}>
                <label class="form-check-label small" for="edit_show_more">Display 'More News' Link</label>
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
                <h6 class="fw-bold small text-primary mb-2">Left Column</h6>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small">Category</label>
                        <select class="form-select form-select-sm" id="edit_left_category">${leftOptions}</select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Custom Title (Optional)</label>
                        <input type="text" class="form-control form-control-sm" id="edit_left_title" value="${escapeHtml(s.left_title || '')}">
                    </div>
                </div>
            </div>

            <div class="border rounded p-3 mb-3 bg-light">
                <h6 class="fw-bold small text-primary mb-2">Right Column</h6>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small">Category</label>
                        <select class="form-select form-select-sm" id="edit_right_category">${rightOptions}</select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Custom Title (Optional)</label>
                        <input type="text" class="form-control form-control-sm" id="edit_right_title" value="${escapeHtml(s.right_title || '')}">
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small">Posts Count per Column</label>
                <input type="number" class="form-control form-control-sm" id="edit_limit" value="${s.limit || 4}" min="1" max="10" style="max-width: 150px;">
            </div>
        `;
    } else if (w.type === 'ad_banner') {
        const positions = [
            { key: 'after_lead', label: 'After Lead Banner (970x90 or responsive)' },
            { key: 'header_right', label: 'Header Right Banner (728x90)' },
            { key: 'sidebar', label: 'Sidebar Top Banner' },
            { key: 'sidebar_bottom', label: 'Sidebar Bottom Banner' },
            { key: 'inside_article', label: 'Inside Article Banner' }
        ];

        let adOptions = '';
        positions.forEach(p => {
            adOptions += `<option value="${p.key}" ${s.position === p.key ? 'selected' : ''}>${p.label}</option>`;
        });

        fieldsHtml += `
            <div class="mb-3">
                <label class="form-label fw-bold small">Select Advertisement Slot</label>
                <select class="form-select form-select-sm" id="edit_ad_position">${adOptions}</select>
                <small class="text-muted">To manage banner images or target URLs, visit <a href="ads-manager.php" target="_blank">Advertisement Banners</a>.</small>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">Or Custom Ad Code (Optional)</label>
                <textarea class="form-control font-monospace small" id="edit_ad_custom_code" rows="3" placeholder="<div ...> or Google AdSense code">${escapeHtml(s.custom_code || '')}</textarea>
            </div>
        `;
    } else if (w.type === 'sidebar_tabs') {
        fieldsHtml += `
            <div class="mb-3">
                <label class="form-label fw-bold small">Posts Limit (per tab)</label>
                <input type="number" class="form-control form-control-sm" id="edit_limit" value="${s.limit || 7}" min="3" max="15" style="max-width: 150px;">
            </div>
        `;
    } else if (w.type === 'hero_lead') {
        fieldsHtml += `
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Bottom Thumbnails Count</label>
                    <input type="number" class="form-control form-control-sm" id="edit_bottom_thumbs" value="${s.bottom_thumbs || 3}" min="0" max="6">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Right Sub-leads Count</label>
                    <input type="number" class="form-control form-control-sm" id="edit_sub_leads" value="${s.sub_leads || 3}" min="1" max="6">
                </div>
            </div>
        `;
    } else if (w.type === 'custom_html') {
        fieldsHtml += `
            <div class="mb-3">
                <label class="form-label fw-bold small">Custom HTML / Embed Code</label>
                <textarea class="form-control font-monospace small" id="edit_html_content" rows="6" placeholder="<div>Custom HTML...</div>">${escapeHtml(s.content || '')}</textarea>
            </div>
        `;
    } else if (w.type === 'multimedia_gallery') {
        const savedVideos = Array.isArray(s.youtube_videos) ? s.youtube_videos : [];
        const editableVideos = savedVideos.length ? savedVideos : [''];
        const videoFields = editableVideos.map(videoUrl => youtubeVideoFieldHtml(videoUrl)).join('');

        fieldsHtml += `
            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                <label class="form-label fw-bold small mb-0">YouTube Video Links</label>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addYoutubeVideoField();">
                    <i class="bi bi-plus-lg me-1"></i>Add Video
                </button>
            </div>
            <div id="youtubeVideoList" class="overflow-y-auto pe-1" style="max-height: 45vh;">${videoFields}</div>
            <small class="text-muted d-block mb-3">
                Only YouTube video links are allowed. Add unlimited links; each valid link becomes an embedded video in the carousel.
            </small>
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
        const inputs = Array.from(document.querySelectorAll('#youtubeVideoList .youtube-video-url'));
        const videoLinks = [];

        for (const input of inputs) {
            const videoUrl = input.value.trim();
            if (!videoUrl) continue;
            if (!getYoutubeVideoId(videoUrl)) {
                alert('Please enter a valid YouTube video link. Non-YouTube links are not allowed.');
                input.focus();
                return;
            }
            videoLinks.push(videoUrl);
        }

        w.settings.youtube_videos = videoLinks;
        w.settings.title = w.title;
        delete w.settings.limit;
    }

    renderCanvas();
    updateLivePreview();

    // Close modal
    const modalEl = document.getElementById('widgetSettingsModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();

    showToast('Widget settings updated.');
}

/**
 * Real-Time Live Preview Sync (WordPress/Elementor-Style)
 */
let previewDebounceTimer = null;
function updateLivePreview() {
    const syncBadge = document.getElementById('liveSyncStatus');
    if (syncBadge) {
        syncBadge.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" style="width: 8px; height: 8px;"></span>Syncing...`;
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
                syncBadge.innerHTML = `<span class="spinner-grow spinner-grow-sm text-success" style="width: 7px; height: 7px;"></span>Sync Active`;
                syncBadge.className = 'badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1';
            }
        })
        .catch(err => {
            console.error('Preview sync failed:', err);
            if (syncBadge) {
                syncBadge.innerHTML = `<i class="bi bi-exclamation-triangle text-danger"></i>Sync Issue`;
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
    showToast('Live preview refreshed.');
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
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Saving...`;

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
            showToast(data.message || 'Layout saved successfully!');
            updateLivePreview();
        } else {
            alert(data.message || 'Error saving layout');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('Server communication error: ' + err.message);
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
                showToast('Widget order updated. Live preview refreshed.');
            }
        }
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
