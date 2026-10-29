<?php
/**
 * Facebook Photo Card Generator
 * Alokito Gour News Portal
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';

$pageTitle = 'Facebook Photo Card Generator | Alokito Gour';
$db = get_db();

// Preload specific post if post_id provided in URL
$preloadedPost = null;
$preloadedPostId = (int)($_GET['post_id'] ?? 0);
if ($preloadedPostId > 0) {
    $stmt = $db->prepare("
        SELECT p.id, p.title, p.thumbnail, p.created_at, c.name AS category_name
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $preloadedPostId]);
    $preloadedPost = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch recent 25 posts for the dropdown
$recentPosts = $db->query("
    SELECT p.id, p.title, p.thumbnail, p.created_at, c.name AS category_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
    LIMIT 25
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch active advertisements
$activeAds = $db->query("
    SELECT id, title, image_path, position
    FROM advertisements
    WHERE status = 1 AND image_path IS NOT NULL AND image_path != ''
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Format Bengali date for preloaded post
function get_bn_date_str(?string $dateStr): string {
    $ts = $dateStr ? strtotime($dateStr) : time();
    if ($ts === false) $ts = time();
    $monthsEn = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $monthsBn = ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];
    $dayNum = bn_number(date('d', $ts));
    $monthName = str_replace($monthsEn, $monthsBn, date('F', $ts));
    $yearNum = bn_number(date('Y', $ts));
    return "{$dayNum} {$monthName} {$yearNum}";
}

$initialTitle = $preloadedPost['title'] ?? ($recentPosts[0]['title'] ?? 'বাংলাদেশ থেকে কর্মী নেবে ইরাক, চুক্তি সই');
$initialDate = get_bn_date_str($preloadedPost['created_at'] ?? ($recentPosts[0]['created_at'] ?? null));
$initialRawDate = date('Y-m-d', strtotime($preloadedPost['created_at'] ?? ($recentPosts[0]['created_at'] ?? 'now')));
$initialThumb = $preloadedPost['thumbnail'] ?? ($recentPosts[0]['thumbnail'] ?? '');
$initialPostId = $preloadedPost['id'] ?? ($recentPosts[0]['id'] ?? 0);

include __DIR__ . '/includes/header.php';
?>

<!-- Google Bengali Fonts for high-fidelity card typography -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anek+Bangla:wght@500;600;700;800&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
/* Main Page Layout */
.photocard-preview-wrapper {
    position: sticky;
    top: 20px;
    background: #0f172a;
    border-radius: 14px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
}

.canvas-container {
    width: 100%;
    max-width: 480px;
    position: relative;
    box-shadow: 0 20px 40px rgba(0,0,0,0.5);
    border-radius: 8px;
    overflow: hidden;
    background: #ffffff;
    cursor: grab;
    transition: box-shadow 0.2s ease;
}

.canvas-container:active {
    cursor: grabbing;
}

#photoCardCanvas {
    display: block;
    width: 100%;
    height: auto;
    user-select: none;
}

.control-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.control-card-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    font-size: 0.95rem;
    color: #1e293b;
    margin-bottom: 16px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
}

.form-label-sm {
    font-size: 0.82rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 4px;
}

.color-chip-btn {
    width: 26px;
    height: 26px;
    border-radius: 6px;
    border: 2px solid #cbd5e1;
    cursor: pointer;
    display: inline-block;
    padding: 0;
    transition: transform 0.1s, border-color 0.1s;
}

.color-chip-btn:hover {
    transform: scale(1.15);
    border-color: #0284c7;
}

.color-chip-btn.active {
    border-color: #0284c7;
    box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.35);
}

/* PicsArt Style Mobile Bottom Navigation */
.mobile-bottom-nav {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(15, 23, 42, 0.95);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    z-index: 1050;
    padding: 6px 10px 8px;
}

.mobile-nav-items {
    display: flex;
    justify-content: space-around;
    align-items: center;
    list-style: none;
    margin: 0;
    padding: 0;
}

.mobile-nav-btn {
    background: transparent;
    border: none;
    color: #94a3b8;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    font-size: 0.7rem;
    font-weight: 500;
    padding: 4px 8px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.mobile-nav-btn i {
    font-size: 1.15rem;
}

.mobile-nav-btn:hover,
.mobile-nav-btn.active {
    color: #38bdf8;
    background: rgba(56, 189, 248, 0.12);
}

/* Mobile Sliding Sheet Modal */
.mobile-sheet-backdrop {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.55);
    z-index: 1060;
}

.mobile-bottom-sheet {
    display: none;
    position: fixed;
    bottom: 60px;
    left: 0;
    right: 0;
    max-height: 75vh;
    background: #ffffff;
    border-top-left-radius: 20px;
    border-top-right-radius: 20px;
    z-index: 1070;
    box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.3);
    overflow-y: auto;
    padding: 16px 20px 24px;
    transform: translateY(100%);
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.mobile-bottom-sheet.show {
    transform: translateY(0);
}

.sheet-drag-handle {
    width: 44px;
    height: 4px;
    background: #cbd5e1;
    border-radius: 2px;
    margin: 0 auto 12px;
}

/* Responsive Rules */
@media (max-width: 991.98px) {
    .mobile-bottom-nav {
        display: block;
    }
    .desktop-controls-col {
        display: none;
    }
    .preview-col {
        width: 100%;
        margin-bottom: 70px;
    }
    .photocard-preview-wrapper {
        position: static;
        padding: 12px;
    }
    .canvas-container {
        max-width: 100%;
    }
}

.toast-container {
    position: fixed;
    bottom: 74px;
    right: 24px;
    z-index: 9999;
}
</style>

<div class="container-fluid px-3 px-md-4 py-3">
    <!-- Top Action Breadcrumbs -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-card-image text-primary"></i> Facebook Photo Card Generator
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 fw-normal">1000 × 1170 px</span>
            </h4>
            <p class="text-muted small mb-0">Create, customize, and export professional social media photo cards with dynamic headline, date, and advertisement banner</p>
        </div>
        <div class="d-flex gap-2">
            <a href="post-list.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Posts List
            </a>
            <?php if ($preloadedPostId > 0): ?>
                <a href="post-edit.php?id=<?= $preloadedPostId ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit Article
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column: Controls (Desktop) -->
        <div class="col-lg-7 col-xl-6 desktop-controls-col" id="desktopControlsContainer">
            
            <!-- 1. News Selection Card -->
            <div class="control-card">
                <div class="control-card-header">
                    <i class="bi bi-newspaper text-primary"></i> 1. Select News Article
                </div>
                
                <div class="mb-3">
                    <label class="form-label-sm">Search by Title or Post ID</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="newsSearchInput" placeholder="Type headline or post ID..." autocomplete="off">
                    </div>
                </div>

                <div class="mb-2">
                    <select class="form-select" id="newsSelectDropdown" size="4" style="font-size:0.875rem;">
                        <?php foreach ($recentPosts as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" 
                                    data-title="<?= e($p['title']) ?>"
                                    data-thumb="<?= e($p['thumbnail'] ?? '') ?>"
                                    data-date="<?= e(get_bn_date_str($p['created_at'])) ?>"
                                    data-raw-date="<?= e(date('Y-m-d', strtotime($p['created_at']))) ?>"
                                    <?= ($p['id'] == $initialPostId) ? 'selected' : '' ?>>
                                #<?= (int)$p['id'] ?> &bull; <?= e(mb_substr($p['title'], 0, 65)) ?><?= mb_strlen($p['title']) > 65 ? '...' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="text-muted small" style="font-size:0.75rem;">
                    Selecting an article automatically updates the headline, publication date, and featured photo.
                </div>
            </div>

            <!-- 2. Headline & Typography Controls -->
            <div class="control-card">
                <div class="control-card-header">
                    <i class="bi bi-fonts text-success"></i> 2. Headline & Typography
                </div>

                <!-- Headline Text -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="cardTitleInput" class="form-label-sm">Card Headline (Bengali Title)</label>
                        <span class="badge bg-light text-secondary border" id="titleCharCount">0 characters</span>
                    </div>
                    <textarea class="form-control" id="cardTitleInput" rows="3" style="font-family:'Hind Siliguri', sans-serif; font-size:1.05rem; font-weight:600; line-height:1.4;"><?= e($initialTitle) ?></textarea>
                    <div class="form-text" style="font-size:0.75rem;">Press Enter inside the text to force a custom line break.</div>
                </div>

                <!-- Dual Line Color Controls -->
                <div class="row g-3 mb-3 p-3 bg-light rounded-3 border">
                    <div class="col-sm-6 border-end">
                        <label class="form-label-sm d-flex align-items-center justify-content-between">
                            <span>Line 1 Color</span>
                            <input type="color" id="line1ColorPicker" value="#FFE600" class="form-control form-control-color p-0 border-0" style="width:24px; height:24px;">
                        </label>
                        <div class="d-flex gap-2 align-items-center mt-1">
                            <button type="button" class="color-chip-btn active" style="background:#FFE600;" data-color="#FFE600" data-target="line1" title="Vibrant Yellow"></button>
                            <button type="button" class="color-chip-btn" style="background:#FFFFFF;" data-color="#FFFFFF" data-target="line1" title="Pure White"></button>
                            <button type="button" class="color-chip-btn" style="background:#38BDF8;" data-color="#38BDF8" data-target="line1" title="Sky Cyan"></button>
                            <button type="button" class="color-chip-btn" style="background:#F97316;" data-color="#F97316" data-target="line1" title="Brand Orange"></button>
                            <span class="small text-muted ms-auto" id="line1ColorHex">#FFE600</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label-sm d-flex align-items-center justify-content-between">
                            <span>Line 2+ Color</span>
                            <input type="color" id="line2ColorPicker" value="#FFFFFF" class="form-control form-control-color p-0 border-0" style="width:24px; height:24px;">
                        </label>
                        <div class="d-flex gap-2 align-items-center mt-1">
                            <button type="button" class="color-chip-btn active" style="background:#FFFFFF;" data-color="#FFFFFF" data-target="line2" title="Pure White"></button>
                            <button type="button" class="color-chip-btn" style="background:#FFE600;" data-color="#FFE600" data-target="line2" title="Vibrant Yellow"></button>
                            <button type="button" class="color-chip-btn" style="background:#38BDF8;" data-color="#38BDF8" data-target="line2" title="Sky Cyan"></button>
                            <button type="button" class="color-chip-btn" style="background:#F43F5E;" data-color="#F43F5E" data-target="line2" title="Rose Red"></button>
                            <span class="small text-muted ms-auto" id="line2ColorHex">#FFFFFF</span>
                        </div>
                    </div>
                </div>

                <!-- Font Size & Line Height (with direct numeric input boxes) -->
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="fontSizeRange" class="form-label-sm">Font Size (px)</label>
                            <div class="input-group input-group-sm" style="width: 82px;">
                                <input type="number" class="form-control form-control-sm text-center px-1" id="fontSizeNumber" min="20" max="70" value="40">
                                <span class="input-group-text px-1">px</span>
                            </div>
                        </div>
                        <input type="range" class="form-range" id="fontSizeRange" min="20" max="70" value="40" step="1">
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="lineHeightRange" class="form-label-sm">Line Spacing</label>
                            <input type="number" class="form-control form-control-sm text-center" id="lineHeightNumber" min="1.0" max="1.8" value="1.3" step="0.05" style="width:65px;">
                        </div>
                        <input type="range" class="form-range" id="lineHeightRange" min="1.0" max="1.8" value="1.3" step="0.05">
                    </div>
                </div>

                <!-- Date Text & Settings -->
                <div class="row g-3">
                    <div class="col-sm-8">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="cardDateInput" class="form-label-sm mb-0">Date Text (Ribbon)</label>
                            <span class="text-muted" style="font-size:0.75rem;"><i class="bi bi-info-circle me-1"></i>ক্যালেন্ডারে ক্লিক করে তারিখ বাছুন</span>
                        </div>
                        <div class="input-group">
                            <button class="btn btn-outline-secondary bg-light border" type="button" id="btnOpenCalendar" title="ক্যালেন্ডার থেকে তারিখ নির্বাচন করুন" style="cursor: pointer;">
                                <i class="bi bi-calendar-event text-primary fs-6"></i>
                            </button>
                            <input type="text" class="form-control" id="cardDateInput" value="<?= e($initialDate) ?>" placeholder="যেমন: ২৮ অক্টোবর ২০২৬" style="font-family:'Hind Siliguri', sans-serif; font-weight:600;">
                            <input type="date" id="nativeDatePicker" class="d-none" value="<?= e($initialRawDate) ?>">
                            <button class="btn btn-outline-secondary bg-light border" type="button" id="btnResetNewsDate" title="মূল নিউজের তারিখ ফিরিয়ে আনুন">
                                <i class="bi bi-arrow-clockwise text-secondary"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="dateSizeRange" class="form-label-sm">Date Size</label>
                            <input type="number" class="form-control form-control-sm text-center" id="dateSizeNumber" min="15" max="30" value="21" style="width:60px;">
                        </div>
                        <input type="range" class="form-range" id="dateSizeRange" min="15" max="30" value="21" step="1">
                    </div>
                </div>
            </div>

            <!-- 3. News Image & Framing Controls -->
            <div class="control-card">
                <div class="control-card-header">
                    <i class="bi bi-image text-warning"></i> 3. Photo Framing & Position
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="imgZoomRange" class="form-label-sm">Zoom / Scale</label>
                            <input type="number" class="form-control form-control-sm text-center" id="imgZoomNumber" min="50" max="250" value="100" style="width:65px;">
                        </div>
                        <input type="range" class="form-range" id="imgZoomRange" min="0.5" max="2.5" value="1.0" step="0.05">
                    </div>
                    <div class="col-sm-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="imgOffsetYRange" class="form-label-sm">Vertical (Y)</label>
                            <input type="number" class="form-control form-control-sm text-center" id="imgOffsetYNumber" min="-300" max="300" value="0" style="width:65px;">
                        </div>
                        <input type="range" class="form-range" id="imgOffsetYRange" min="-300" max="300" value="0" step="2">
                    </div>
                    <div class="col-sm-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="imgOffsetXRange" class="form-label-sm">Horizontal (X)</label>
                            <input type="number" class="form-control form-control-sm text-center" id="imgOffsetXNumber" min="-300" max="300" value="0" style="width:65px;">
                        </div>
                        <input type="range" class="form-range" id="imgOffsetXRange" min="-300" max="300" value="0" step="2">
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 border-top">
                    <div>
                        <label for="customPhotoInput" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-upload me-1"></i> Upload Replacement Photo
                        </label>
                        <input type="file" id="customPhotoInput" accept="image/*" class="d-none">
                    </div>
                    <button type="button" class="btn btn-sm btn-light border text-muted" id="btnResetPhotoPosition">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Framing
                    </button>
                </div>
            </div>

            <!-- 4. Organization Branding & Credit -->
            <div class="control-card">
                <div class="control-card-header d-flex justify-content-between align-items-center mb-0 pb-0 border-0">
                    <span><i class="bi bi-award text-info"></i> 4. Organization Branding & Credit</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2">
                        <i class="bi bi-lock-fill me-1"></i> Fixed (Size: 95%, Y: 800px)
                    </span>
                </div>
                <div class="form-text mt-2" style="font-size:0.75rem;">
                    Official Alokito Gour logo, social media handles, and <code>www.alokitogour.com</code> are permanently locked at standard size and position.
                </div>
            </div>

            <!-- 5. Advertisement Slot Controls (1000x170 px) -->
            <div class="control-card">
                <div class="control-card-header">
                    <i class="bi bi-badge-ad text-danger"></i> 5. Bottom Advertisement Slot (1000 × 170 px)
                </div>

                <div class="mb-3">
                    <label class="form-label-sm">Select Active Advertisement</label>
                    <select class="form-select" id="adSelectDropdown">
                        <option value="none">-- No Advertisement (Clean White) --</option>
                        <?php foreach ($activeAds as $ad): ?>
                            <option value="<?= e($ad['image_path']) ?>" data-title="<?= e($ad['title']) ?>">
                                <?= e($ad['title']) ?> [<?= e($ad['position'] ?? 'Custom') ?>]
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="d-flex gap-2">
                        <label for="customAdInput" class="btn btn-sm btn-outline-secondary" title="Use banner only for this card">
                            <i class="bi bi-file-earmark-arrow-up me-1"></i> Quick Banner Upload
                        </label>
                        <input type="file" id="customAdInput" accept="image/*" class="d-none">

                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadAdModal">
                            <i class="bi bi-cloud-arrow-up me-1"></i> Save to Ads Library
                        </button>
                    </div>
                    <span class="text-muted small">Target Size: 1000 × 170 px</span>
                </div>
            </div>

        </div>

        <!-- Right Column: Live Sticky Preview Canvas & Download Buttons -->
        <div class="col-lg-5 col-xl-6 preview-col">
            <div class="photocard-preview-wrapper">
                <div class="d-flex align-items-center justify-content-between w-100 mb-2 text-white">
                    <span class="small fw-semibold d-flex align-items-center gap-2">
                        <span class="spinner-grow spinner-grow-sm text-success" id="renderSpinner" style="display:none;" role="status"></span>
                        <i class="bi bi-eye"></i> Live Photo Card Preview
                    </span>
                    <span class="badge bg-secondary" style="font-size:0.75rem;">1000 × 1170 px</span>
                </div>

                <!-- Canvas Preview Container -->
                <div class="canvas-container" id="canvasContainer" title="Drag with mouse or scroll wheel to adjust photo position & zoom">
                    <canvas id="photoCardCanvas" width="1000" height="1170"></canvas>
                </div>

                <p class="text-muted small mt-2 mb-3 text-center" style="font-size:0.75rem;">
                    💡 Pro Tip: Click & drag on the preview image to reposition. Scroll wheel to zoom in/out.
                </p>

                <!-- Export Action Buttons -->
                <div class="d-flex flex-column gap-2 w-100" style="max-width: 480px;">
                    <button type="button" class="btn btn-primary btn-lg fw-bold d-flex align-items-center justify-content-center gap-2 shadow" id="btnDownloadPng">
                        <i class="bi bi-download"></i> Download PNG (High-Resolution)
                    </button>
                    
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-light flex-fill d-flex align-items-center justify-content-center gap-1" id="btnDownloadJpg">
                            <i class="bi bi-file-earmark-image"></i> Download JPG
                        </button>
                        <button type="button" class="btn btn-outline-info flex-fill d-flex align-items-center justify-content-center gap-1" id="btnCopyClipboard">
                            <i class="bi bi-clipboard-check"></i> Copy to Clipboard
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- PicsArt Style Mobile Bottom Navigation Bar -->
<div class="mobile-bottom-nav">
    <ul class="mobile-nav-items">
        <li>
            <button type="button" class="mobile-nav-btn" data-sheet="sheet-article">
                <i class="bi bi-newspaper"></i>
                <span>Article</span>
            </button>
        </li>
        <li>
            <button type="button" class="mobile-nav-btn" data-sheet="sheet-headline">
                <i class="bi bi-fonts"></i>
                <span>Headline</span>
            </button>
        </li>
        <li>
            <button type="button" class="mobile-nav-btn" data-sheet="sheet-date">
                <i class="bi bi-calendar3"></i>
                <span>Date</span>
            </button>
        </li>
        <li>
            <button type="button" class="mobile-nav-btn" data-sheet="sheet-photo">
                <i class="bi bi-image"></i>
                <span>Photo</span>
            </button>
        </li>
        <li>
            <button type="button" class="mobile-nav-btn" data-sheet="sheet-ad">
                <i class="bi bi-badge-ad"></i>
                <span>Ad Banner</span>
            </button>
        </li>
        <li>
            <button type="button" class="mobile-nav-btn" data-sheet="sheet-export">
                <i class="bi bi-download text-primary"></i>
                <span class="text-primary fw-bold">Export</span>
            </button>
        </li>
    </ul>
</div>

<!-- Mobile Sliding Bottom Sheet Backdrop & Modal -->
<div class="mobile-sheet-backdrop" id="sheetBackdrop"></div>

<div class="mobile-bottom-sheet" id="mobileBottomSheet">
    <div class="sheet-drag-handle"></div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold m-0" id="sheetTitle">Settings</h6>
        <button type="button" class="btn-close" id="sheetCloseBtn" aria-label="Close"></button>
    </div>
    <div id="sheetBodyContent">
        <!-- Injected dynamically on tab tap -->
    </div>
</div>

<!-- Modal: Upload & Save New Advertisement -->
<div class="modal fade" id="uploadAdModal" tabindex="-1" aria-labelledby="uploadAdModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold" id="uploadAdModalLabel">
                    <i class="bi bi-badge-ad text-danger me-1"></i> Save Advertisement to Library
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="saveAdForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label-sm">Advertisement Title / Client Name</label>
                        <input type="text" class="form-control" name="title" id="newAdTitleInput" placeholder="e.g. City Bank 2026 Promo" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-sm">Target Website URL (Optional)</label>
                        <input type="url" class="form-control" name="target_url" placeholder="https://example.com" value="#">
                    </div>
                    <div class="mb-3">
                        <label class="form-label-sm">Ad Banner Image (1000 × 170 px recommended)</label>
                        <input type="file" class="form-control" name="image" id="newAdFileInput" accept="image/*" required>
                    </div>
                    <div id="newAdPreviewContainer" class="d-none text-center p-2 bg-light rounded border">
                        <img id="newAdPreviewImg" src="" alt="Ad Preview" class="img-fluid rounded" style="max-height: 80px;">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary" id="btnSaveAdSubmit">
                        <i class="bi bi-cloud-arrow-up me-1"></i> Upload & Apply
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Notification Toast -->
<div class="toast-container">
    <div id="actionToast" class="toast align-items-center text-white bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill text-success fs-5" id="toastIcon"></i>
                <span id="toastMessage">Action completed successfully</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('photoCardCanvas');
    const ctx = canvas.getContext('2d');
    const container = document.getElementById('canvasContainer');

    // Controls
    const newsSelect = document.getElementById('newsSelectDropdown');
    const newsSearch = document.getElementById('newsSearchInput');
    const titleInput = document.getElementById('cardTitleInput');
    const dateInput = document.getElementById('cardDateInput');
    const btnOpenCalendar = document.getElementById('btnOpenCalendar');
    const nativeDatePicker = document.getElementById('nativeDatePicker');
    const btnResetNewsDate = document.getElementById('btnResetNewsDate');
    
    // Custom number inputs + Sliders
    const fontSizeRange = document.getElementById('fontSizeRange');
    const fontSizeNumber = document.getElementById('fontSizeNumber');
    const lineHeightRange = document.getElementById('lineHeightRange');
    const lineHeightNumber = document.getElementById('lineHeightNumber');
    const dateSizeRange = document.getElementById('dateSizeRange');
    const dateSizeNumber = document.getElementById('dateSizeNumber');
    
    const imgZoomRange = document.getElementById('imgZoomRange');
    const imgZoomNumber = document.getElementById('imgZoomNumber');
    const imgOffsetYRange = document.getElementById('imgOffsetYRange');
    const imgOffsetYNumber = document.getElementById('imgOffsetYNumber');
    const imgOffsetXRange = document.getElementById('imgOffsetXRange');
    const imgOffsetXNumber = document.getElementById('imgOffsetXNumber');

    // Color Pickers & Chips
    const line1ColorPicker = document.getElementById('line1ColorPicker');
    const line2ColorPicker = document.getElementById('line2ColorPicker');
    const line1ColorHex = document.getElementById('line1ColorHex');
    const line2ColorHex = document.getElementById('line2ColorHex');

    // Photo & Ad Inputs
    const customPhotoInput = document.getElementById('customPhotoInput');
    const adSelect = document.getElementById('adSelectDropdown');
    const customAdInput = document.getElementById('customAdInput');
    const btnResetPhoto = document.getElementById('btnResetPhotoPosition');

    // Export Buttons
    const btnDownloadPng = document.getElementById('btnDownloadPng');
    const btnDownloadJpg = document.getElementById('btnDownloadJpg');
    const btnCopyClipboard = document.getElementById('btnCopyClipboard');
    const renderSpinner = document.getElementById('renderSpinner');
    const titleCharCount = document.getElementById('titleCharCount');

    // State
    let currentNewsId = <?= $initialPostId ?>;
    let currentNewsOriginalDateBn = <?= json_encode($initialDate) ?>;
    let currentNewsOriginalRawDate = <?= json_encode($initialRawDate) ?>;
    let line1Color = '#FFE600';
    let line2Color = '#FFFFFF';

    // Bengali Digits & Months for Date Formatting
    const bnDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    const bnToEnMap = { '০':'0', '১':'1', '২':'2', '৩':'3', '৪':'4', '৫':'5', '৬':'6', '৭':'7', '৮':'8', '৯':'9' };
    const monthsBnList = ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];
    const monthsBnMap = {
        'জানুয়ারি': '01', 'জানুয়ারি': '01',
        'ফেব্রুয়ারি': '02', 'ফেব্রুয়ারি': '02',
        'মার্চ': '03',
        'এপ্রিল': '04',
        'মে': '05',
        'জুন': '06',
        'জুলাই': '07',
        'আগস্ট': '08',
        'সেপ্টেম্বর': '09',
        'অক্টোবর': '10',
        'নভেম্বর': '11',
        'ডিসেম্বর': '12'
    };

    function toBengaliDigits(num) {
        return String(num).replace(/[0-9]/g, d => bnDigits[d]);
    }

    // Convert ISO YYYY-MM-DD to Bengali string (e.g. 2026-10-28 -> ২৮ অক্টোবর ২০২৬)
    function formatToBengaliDate(isoDateStr) {
        if (!isoDateStr) return '';
        const parts = isoDateStr.split('-');
        if (parts.length < 3) return '';
        const year = parts[0];
        const monthIdx = parseInt(parts[1], 10) - 1;
        const day = parseInt(parts[2], 10);
        const bnDay = toBengaliDigits(day);
        const bnMonth = monthsBnList[monthIdx] || '';
        const bnYear = toBengaliDigits(year);
        return `${bnDay} ${bnMonth} ${bnYear}`;
    }

    // Convert Bengali string back to YYYY-MM-DD for opening the calendar on correct date
    function parseBengaliDateToIso(bnStr) {
        if (!bnStr) return '';
        const enStr = bnStr.replace(/[০-৯]/g, d => bnToEnMap[d] || d);
        const parts = enStr.trim().split(/\s+/);
        if (parts.length >= 3) {
            const day = String(parts[0]).padStart(2, '0');
            const month = monthsBnMap[parts[1]] || '01';
            const year = parts[2];
            if (year && year.length === 4) {
                return `${year}-${month}-${day}`;
            }
        }
        return '';
    }

    let newsImage = new Image();
    newsImage.crossOrigin = 'anonymous';
    let isNewsImageLoaded = false;

    let frameImage = new Image();
    frameImage.crossOrigin = 'anonymous';
    let isFrameLoaded = false;

    let creditImage = new Image();
    creditImage.crossOrigin = 'anonymous';
    let isCreditLoaded = false;

    let adImage = new Image();
    adImage.crossOrigin = 'anonymous';
    let isAdLoaded = false;
    let hasAd = false;

    // Load Frame Template
    frameImage.onload = () => {
        isFrameLoaded = true;
        renderCard();
    };
    frameImage.onerror = () => {
        console.error('Could not load frame template');
    };
    frameImage.src = '../assets/images/photocard-frame.svg';

    // Load Organization Credit Image
    creditImage.onload = () => {
        isCreditLoaded = true;
        renderCard();
    };
    creditImage.onerror = () => {
        console.warn('Could not load credit image');
    };
    creditImage.src = '../assets/images/alokito-gour-credit.png';

    // Load initial news thumbnail if available
    const initialThumbUrl = '<?= !empty($initialThumb) ? e($initialThumb) : "" ?>';
    if (initialThumbUrl) {
        setNewsImageSrc(initialThumbUrl);
    } else {
        renderCard();
    }

    function setNewsImageSrc(src) {
        isNewsImageLoaded = false;
        let fullSrc = src;
        if (!src.startsWith('http') && !src.startsWith('/') && !src.startsWith('data:')) {
            fullSrc = '../' + src;
        }
        newsImage.onload = () => {
            isNewsImageLoaded = true;
            renderCard();
        };
        newsImage.onerror = () => {
            console.warn('Failed to load image:', fullSrc);
            isNewsImageLoaded = false;
            renderCard();
        };
        newsImage.src = fullSrc;
    }

    function setAdImageSrc(src) {
        if (!src || src === 'none') {
            hasAd = false;
            isAdLoaded = false;
            renderCard();
            return;
        }
        hasAd = true;
        isAdLoaded = false;
        let fullSrc = src;
        if (!src.startsWith('http') && !src.startsWith('/') && !src.startsWith('data:')) {
            fullSrc = '../' + src;
        }
        adImage.onload = () => {
            isAdLoaded = true;
            renderCard();
        };
        adImage.onerror = () => {
            console.warn('Failed to load ad image:', fullSrc);
            isAdLoaded = false;
            renderCard();
        };
        adImage.src = fullSrc;
    }

    function updateTitleCount() {
        const count = titleInput.value.length;
        titleCharCount.textContent = count + ' characters';
    }
    updateTitleCount();

    // -------------------------------------------------------------
    // Main Render Function
    // -------------------------------------------------------------
    function renderCard() {
        if (renderSpinner) renderSpinner.style.display = 'inline-block';

        // 1. Clear Canvas with Clean White Background
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(0, 0, 1000, 1170);

        // 2. Draw News Photo (Top area: Y = 0 to 650)
        if (isNewsImageLoaded && newsImage.naturalWidth > 0) {
            ctx.save();
            ctx.beginPath();
            ctx.rect(0, 0, 1000, 650);
            ctx.clip();

            const zoom = parseFloat(imgZoomRange.value);
            const offsetX = parseFloat(imgOffsetXRange.value);
            const offsetY = parseFloat(imgOffsetYRange.value);

            const targetW = 1000;
            const targetH = 550;
            const imgRatio = newsImage.naturalWidth / newsImage.naturalHeight;
            const targetRatio = targetW / targetH;

            let drawW, drawH;
            if (imgRatio > targetRatio) {
                drawH = targetH * zoom;
                drawW = drawH * imgRatio;
            } else {
                drawW = targetW * zoom;
                drawH = drawW / imgRatio;
            }

            const drawX = (targetW - drawW) / 2 + offsetX;
            const drawY = (targetH - drawH) / 2 + offsetY;

            ctx.drawImage(newsImage, drawX, drawY, drawW, drawH);
            ctx.restore();
        } else {
            ctx.fillStyle = '#0f2744';
            ctx.fillRect(0, 0, 1000, 550);
            ctx.fillStyle = 'rgba(255,255,255,0.2)';
            ctx.font = '500 24px "Hind Siliguri", sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('No image selected or uploaded', 500, 275);
        }

        // 3. Draw Template Frame (Gradient, Watermark, Ribbons)
        if (isFrameLoaded) {
            ctx.drawImage(frameImage, 0, 0, 1000, 1170);
        }

        // 4. Draw Bengali Publication Date
        const dateText = dateInput.value.trim();
        if (dateText) {
            ctx.save();
            const dateFontSize = parseInt(dateSizeRange.value, 10);
            ctx.font = `700 ${dateFontSize}px "Hind Siliguri", "Anek Bangla", "SolaimanLipi", sans-serif`;
            ctx.fillStyle = '#FFFFFF';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            
            // Date ribbon bounds center: X: 143.5, Y: 467.5
            ctx.shadowColor = 'rgba(0, 0, 0, 0.4)';
            ctx.shadowBlur = 4;
            ctx.shadowOffsetY = 1;

            ctx.fillText(dateText, 143.5, 467.5);
            ctx.restore();
        }

        // 5. Draw Dynamic Headline with Separate Line 1 & Line 2 Colors
        const titleText = titleInput.value.trim();
        if (titleText) {
            ctx.save();
            const fontSize = parseInt(fontSizeRange.value, 10);
            const lineHeightFactor = parseFloat(lineHeightRange.value);
            const lineHeight = fontSize * lineHeightFactor;

            ctx.font = `700 ${fontSize}px "Hind Siliguri", "Anek Bangla", "SolaimanLipi", sans-serif`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            // High-contrast drop shadow
            ctx.shadowColor = 'rgba(0, 0, 0, 0.8)';
            ctx.shadowBlur = 10;
            ctx.shadowOffsetX = 0;
            ctx.shadowOffsetY = 2;

            // Title bounding box: X = 46.85, Y = 605.8, W = 906.31, H = 200.38
            const titleBoxX = 46.85;
            const titleBoxY = 605.8;
            const titleBoxW = 906.31;
            const titleBoxH = 200.38;
            const maxTextWidth = titleBoxW - 40;

            const paragraphs = titleText.split('\n');
            let lines = [];

            paragraphs.forEach(para => {
                const words = para.split(/\s+/);
                let currentLine = '';

                words.forEach(word => {
                    const testLine = currentLine ? (currentLine + ' ' + word) : word;
                    const testWidth = ctx.measureText(testLine).width;
                    if (testWidth > maxTextWidth && currentLine) {
                        lines.push(currentLine);
                        currentLine = word;
                    } else {
                        currentLine = testLine;
                    }
                });
                if (currentLine) {
                    lines.push(currentLine);
                }
            });

            const totalTextHeight = lines.length * lineHeight;
            let startY = titleBoxY + (titleBoxH - totalTextHeight) / 2 + (lineHeight / 2);
            const centerX = titleBoxX + (titleBoxW / 2);

            lines.forEach((line, index) => {
                // Line 1 uses Line 1 Color, subsequent lines use Line 2 Color!
                ctx.fillStyle = (index === 0) ? line1Color : line2Color;
                ctx.fillText(line, centerX, startY + (index * lineHeight));
            });

            ctx.restore();
        }

        // 6. Draw Organization Credit / Logo Info (Fixed Size: 95%, Position Y: 800px)
        if (isCreditLoaded && creditImage.naturalWidth > 0) {
            ctx.save();
            const scale = 0.95; // Fixed scale (95%)
            const creditW = creditImage.naturalWidth * scale;
            const creditH = creditImage.naturalHeight * scale;
            const creditX = (1000 - creditW) / 2;
            const creditY = 800; // Fixed position Y = 800px

            ctx.drawImage(creditImage, creditX, creditY, creditW, creditH);
            ctx.restore();
        }

        // 7. Draw Bottom Advertisement Slot (1000 x 170 px at Y = 1000 to 1170)
        if (hasAd && isAdLoaded && adImage.naturalWidth > 0) {
            ctx.save();
            ctx.beginPath();
            ctx.rect(0, 1000, 1000, 170);
            ctx.clip();

            const adSlotW = 1000;
            const adSlotH = 170;
            const adRatio = adImage.naturalWidth / adImage.naturalHeight;
            const slotRatio = adSlotW / adSlotH;

            let dw, dh, dx, dy;
            if (adRatio > slotRatio) {
                dw = adSlotW;
                dh = dw / adRatio;
                dx = 0;
                dy = 1000 + (adSlotH - dh) / 2;
            } else {
                dh = adSlotH;
                dw = dh * adRatio;
                dx = (adSlotW - dw) / 2;
                dy = 1000;
            }

            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 1000, 1000, 170);
            ctx.drawImage(adImage, dx, dy, dw, dh);
            ctx.restore();
        } else {
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 1000, 1000, 170);
        }

        if (renderSpinner) renderSpinner.style.display = 'none';
    }

    document.fonts.ready.then(() => {
        renderCard();
    });

    // -------------------------------------------------------------
    // Bi-directional Synchronization: Range Sliders & Number Inputs
    // -------------------------------------------------------------
    function syncInputs(rangeEl, numberEl, onUpdate) {
        rangeEl.addEventListener('input', () => {
            numberEl.value = rangeEl.value;
            if (onUpdate) onUpdate();
            renderCard();
        });
        numberEl.addEventListener('input', () => {
            rangeEl.value = numberEl.value;
            if (onUpdate) onUpdate();
            renderCard();
        });
    }

    syncInputs(fontSizeRange, fontSizeNumber);
    syncInputs(lineHeightRange, lineHeightNumber);
    syncInputs(dateSizeRange, dateSizeNumber);
    syncInputs(imgZoomRange, imgZoomNumber, () => {
        imgZoomNumber.value = Math.round(imgZoomRange.value * 100);
    });
    imgZoomNumber.addEventListener('input', () => {
        imgZoomRange.value = (parseFloat(imgZoomNumber.value) / 100).toFixed(2);
        renderCard();
    });

    syncInputs(imgOffsetYRange, imgOffsetYNumber);
    syncInputs(imgOffsetXRange, imgOffsetXNumber);
    // Title & Date Listeners
    titleInput.addEventListener('input', () => {
        updateTitleCount();
        renderCard();
    });
    dateInput.addEventListener('input', renderCard);

    // Native Calendar Picker Integration
    if (btnOpenCalendar && nativeDatePicker) {
        btnOpenCalendar.addEventListener('click', () => {
            const currentIso = parseBengaliDateToIso(dateInput.value);
            if (currentIso) {
                nativeDatePicker.value = currentIso;
            }
            if (typeof nativeDatePicker.showPicker === 'function') {
                try {
                    nativeDatePicker.showPicker();
                    return;
                } catch (e) {
                    // Fallback to click
                }
            }
            nativeDatePicker.click();
        });

        nativeDatePicker.addEventListener('change', function() {
            if (this.value) {
                const bnFormatted = formatToBengaliDate(this.value);
                if (bnFormatted) {
                    dateInput.value = bnFormatted;
                    renderCard();
                    showToast('ক্যালেন্ডার থেকে তারিখ নির্বাচিত হয়েছে: ' + bnFormatted, 'success');
                }
            }
        });
    }

    // Reset Date to Selected News Original Publication Date
    if (btnResetNewsDate) {
        btnResetNewsDate.addEventListener('click', () => {
            if (currentNewsOriginalDateBn) {
                dateInput.value = currentNewsOriginalDateBn;
                if (nativeDatePicker && currentNewsOriginalRawDate) {
                    nativeDatePicker.value = currentNewsOriginalRawDate.split(' ')[0];
                }
                renderCard();
                showToast('মূল নিউজের প্রকাশ তারিখ ফিরিয়ে আনা হয়েছে', 'info');
            }
        });
    }

    // Color Pickers & Chips
    line1ColorPicker.addEventListener('input', (e) => {
        line1Color = e.target.value;
        line1ColorHex.textContent = line1Color.toUpperCase();
        document.querySelectorAll('.color-chip-btn[data-target="line1"]').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-color').toUpperCase() === line1Color.toUpperCase());
        });
        renderCard();
    });

    line2ColorPicker.addEventListener('input', (e) => {
        line2Color = e.target.value;
        line2ColorHex.textContent = line2Color.toUpperCase();
        document.querySelectorAll('.color-chip-btn[data-target="line2"]').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-color').toUpperCase() === line2Color.toUpperCase());
        });
        renderCard();
    });

    document.querySelectorAll('.color-chip-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const target = this.getAttribute('data-target');
            const color = this.getAttribute('data-color');
            if (target === 'line1') {
                line1Color = color;
                line1ColorPicker.value = color;
                line1ColorHex.textContent = color.toUpperCase();
                document.querySelectorAll('.color-chip-btn[data-target="line1"]').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            } else if (target === 'line2') {
                line2Color = color;
                line2ColorPicker.value = color;
                line2ColorHex.textContent = color.toUpperCase();
                document.querySelectorAll('.color-chip-btn[data-target="line2"]').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            }
            renderCard();
        });
    });

    // Reset Framing
    btnResetPhoto.addEventListener('click', () => {
        imgZoomRange.value = 1.0;
        imgZoomNumber.value = 100;
        imgOffsetYRange.value = 0;
        imgOffsetYNumber.value = 0;
        imgOffsetXRange.value = 0;
        imgOffsetXNumber.value = 0;
        renderCard();
    });

    // Custom Photo Upload Override
    customPhotoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                setNewsImageSrc(event.target.result);
                showToast('Custom photo applied successfully!', 'success');
            };
            reader.readAsDataURL(file);
        }
    });

    // Advertisement Dropdown Selection
    adSelect.addEventListener('change', function() {
        setAdImageSrc(this.value);
    });

    // Quick Ad Upload Override
    customAdInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                setAdImageSrc(event.target.result);
                showToast('Ad banner applied to card!', 'success');
            };
            reader.readAsDataURL(file);
        }
    });

    // News Dropdown Selection
    newsSelect.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if (opt) {
            currentNewsId = opt.value;
            titleInput.value = opt.getAttribute('data-title') || '';

            // Automatically set publish date of the selected news
            const postDateBn = opt.getAttribute('data-date') || '';
            const postRawDate = opt.getAttribute('data-raw-date') || '';
            dateInput.value = postDateBn;
            currentNewsOriginalDateBn = postDateBn;
            currentNewsOriginalRawDate = postRawDate;
            if (nativeDatePicker && postRawDate) {
                nativeDatePicker.value = postRawDate.split(' ')[0];
            }

            const thumb = opt.getAttribute('data-thumb');
            if (thumb) {
                setNewsImageSrc(thumb);
            } else {
                isNewsImageLoaded = false;
                renderCard();
            }
            updateTitleCount();
            renderCard();
        }
    });

    // Instant Search for News
    let searchDebounce = null;
    newsSearch.addEventListener('input', function() {
        clearTimeout(searchDebounce);
        const query = this.value.trim();
        searchDebounce = setTimeout(() => {
            fetch(`ajax-photocard-data.php?action=search_posts&q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.posts) {
                        newsSelect.innerHTML = '';
                        if (data.posts.length === 0) {
                            const opt = document.createElement('option');
                            opt.disabled = true;
                            opt.textContent = 'No articles found';
                            newsSelect.appendChild(opt);
                        } else {
                            data.posts.forEach((p, idx) => {
                                const opt = document.createElement('option');
                                opt.value = p.id;
                                opt.setAttribute('data-title', p.title);
                                opt.setAttribute('data-thumb', p.thumbnail);
                                opt.setAttribute('data-date', p.date_bn);
                                opt.setAttribute('data-raw-date', p.created_at ? p.created_at.split(' ')[0] : '');
                                opt.textContent = `#${p.id} • ${p.title.substring(0, 65)}${p.title.length > 65 ? '...' : ''}`;
                                if (idx === 0) opt.selected = true;
                                newsSelect.appendChild(opt);
                            });
                            // Automatically select first matching news and update headline + date
                            newsSelect.dispatchEvent(new Event('change'));
                        }
                    }
                })
                .catch(err => console.error(err));
        }, 300);
    });

    // -------------------------------------------------------------
    // Interactive Drag to Pan & Mouse Wheel Zoom
    // -------------------------------------------------------------
    let isDragging = false;
    let startDragX = 0;
    let startDragY = 0;
    let initialOffsetX = 0;
    let initialOffsetY = 0;

    container.addEventListener('mousedown', function(e) {
        isDragging = true;
        startDragX = e.clientX;
        startDragY = e.clientY;
        initialOffsetX = parseFloat(imgOffsetXRange.value);
        initialOffsetY = parseFloat(imgOffsetYRange.value);
    });

    window.addEventListener('mousemove', function(e) {
        if (!isDragging) return;
        const rect = canvas.getBoundingClientRect();
        const scaleFactor = 1000 / rect.width;
        
        const deltaX = (e.clientX - startDragX) * scaleFactor;
        const deltaY = (e.clientY - startDragY) * scaleFactor;

        let newOffsetX = Math.round(initialOffsetX + deltaX);
        let newOffsetY = Math.round(initialOffsetY + deltaY);

        newOffsetX = Math.max(-300, Math.min(300, newOffsetX));
        newOffsetY = Math.max(-300, Math.min(300, newOffsetY));

        imgOffsetXRange.value = newOffsetX;
        imgOffsetXNumber.value = newOffsetX;

        imgOffsetYRange.value = newOffsetY;
        imgOffsetYNumber.value = newOffsetY;

        renderCard();
    });

    window.addEventListener('mouseup', function() {
        isDragging = false;
    });

    container.addEventListener('wheel', function(e) {
        e.preventDefault();
        let zoom = parseFloat(imgZoomRange.value);
        if (e.deltaY < 0) {
            zoom = Math.min(2.5, zoom + 0.05);
        } else {
            zoom = Math.max(0.5, zoom - 0.05);
        }
        imgZoomRange.value = zoom.toFixed(2);
        imgZoomNumber.value = Math.round(zoom * 100);
        renderCard();
    }, { passive: false });

    // -------------------------------------------------------------
    // Export Handlers
    // -------------------------------------------------------------
    function triggerDownload(dataUrl, filename) {
        const a = document.createElement('a');
        a.href = dataUrl;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    btnDownloadPng.addEventListener('click', function() {
        try {
            const dataUrl = canvas.toDataURL('image/png', 1.0);
            const filename = `alokito-gour-card-${currentNewsId || 'news'}.png`;
            triggerDownload(dataUrl, filename);
            showToast('High-resolution PNG downloaded successfully!', 'success');
        } catch(err) {
            console.error('Download error:', err);
            showToast('Download error: ' + err.message, 'danger');
        }
    });

    btnDownloadJpg.addEventListener('click', function() {
        try {
            const dataUrl = canvas.toDataURL('image/jpeg', 0.95);
            const filename = `alokito-gour-card-${currentNewsId || 'news'}.jpg`;
            triggerDownload(dataUrl, filename);
            showToast('JPG photo card downloaded successfully!', 'success');
        } catch(err) {
            console.error('Download error:', err);
            showToast('Download error: ' + err.message, 'danger');
        }
    });

    btnCopyClipboard.addEventListener('click', function() {
        if (!navigator.clipboard || !window.ClipboardItem) {
            showToast('Direct clipboard copy is not supported in this browser. Please use Download button.', 'warning');
            return;
        }

        canvas.toBlob(function(blob) {
            if (!blob) {
                showToast('Failed to create image blob', 'danger');
                return;
            }
            const item = new ClipboardItem({ 'image/png': blob });
            navigator.clipboard.write([item])
                .then(() => {
                    showToast('Copied to clipboard! Ready to paste directly into Facebook (Ctrl+V).', 'success');
                })
                .catch(err => {
                    console.error('Clipboard copy error:', err);
                    showToast('Copy failed: ' + err.message, 'danger');
                });
        }, 'image/png', 1.0);
    });

    // -------------------------------------------------------------
    // Modal: Save & Upload Ad to Library
    // -------------------------------------------------------------
    const saveAdForm = document.getElementById('saveAdForm');
    const newAdFileInput = document.getElementById('newAdFileInput');
    const newAdPreviewContainer = document.getElementById('newAdPreviewContainer');
    const newAdPreviewImg = document.getElementById('newAdPreviewImg');

    newAdFileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                newAdPreviewImg.src = event.target.result;
                newAdPreviewContainer.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        }
    });

    saveAdForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'upload_ad');

        const submitBtn = document.getElementById('btnSaveAdSubmit');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Uploading...';

        fetch('ajax-photocard-data.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-cloud-arrow-up me-1"></i> Upload & Apply';

            if (data.success && data.ad) {
                // Add to dropdown
                const opt = document.createElement('option');
                opt.value = data.ad.image_path;
                opt.textContent = `${data.ad.title} [Photocard]`;
                opt.selected = true;
                adSelect.appendChild(opt);

                // Apply to canvas
                setAdImageSrc(data.ad.image_path);

                // Close modal
                const modalEl = document.getElementById('uploadAdModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                saveAdForm.reset();
                newAdPreviewContainer.classList.add('d-none');

                showToast('Advertisement saved to library and applied!', 'success');
            } else {
                showToast(data.message || 'Failed to upload ad.', 'danger');
            }
        })
        .catch(err => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-cloud-arrow-up me-1"></i> Upload & Apply';
            showToast('Error uploading ad: ' + err.message, 'danger');
        });
    });

    // -------------------------------------------------------------
    // PicsArt Style Mobile Bottom Navigation & Sliding Sheets
    // -------------------------------------------------------------
    const mobileNavBtns = document.querySelectorAll('.mobile-nav-btn');
    const sheetBackdrop = document.getElementById('sheetBackdrop');
    const mobileSheet = document.getElementById('mobileBottomSheet');
    const sheetTitle = document.getElementById('sheetTitle');
    const sheetBody = document.getElementById('sheetBodyContent');
    const sheetCloseBtn = document.getElementById('sheetCloseBtn');

    const sheetTemplates = {
        'sheet-article': {
            title: 'Select News Article',
            render: () => `
                <div class="mb-3">
                    <label class="form-label-sm">Search Headline</label>
                    <input type="text" class="form-control" id="mNewsSearchInput" placeholder="Search news...">
                </div>
                <div class="mb-2">
                    <select class="form-select" id="mNewsSelectDropdown" size="5">
                        ${Array.from(newsSelect.options).map(o => `<option value="${o.value}" ${o.selected ? 'selected' : ''}>${o.textContent}</option>`).join('')}
                    </select>
                </div>
            `,
            bind: () => {
                const mSearch = document.getElementById('mNewsSearchInput');
                const mSelect = document.getElementById('mNewsSelectDropdown');
                mSelect.addEventListener('change', () => {
                    newsSelect.value = mSelect.value;
                    newsSelect.dispatchEvent(new Event('change'));
                });
                mSearch.addEventListener('input', () => {
                    newsSearch.value = mSearch.value;
                    newsSearch.dispatchEvent(new Event('input'));
                    setTimeout(() => {
                        mSelect.innerHTML = newsSelect.innerHTML;
                    }, 400);
                });
            }
        },
        'sheet-headline': {
            title: 'Headline & Typography',
            render: () => `
                <div class="mb-3">
                    <label class="form-label-sm">Headline Text</label>
                    <textarea class="form-control" id="mTitleInput" rows="3" style="font-family:'Hind Siliguri', sans-serif;">${titleInput.value}</textarea>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label-sm">Line 1 Color</label>
                        <div class="d-flex gap-2">
                            <input type="color" id="mLine1ColorPicker" value="${line1Color}" class="form-control form-control-color p-0 border-0" style="width:32px; height:32px;">
                            <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" id="mLine1Yellow">Yellow</button>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label-sm">Line 2+ Color</label>
                        <div class="d-flex gap-2">
                            <input type="color" id="mLine2ColorPicker" value="${line2Color}" class="form-control form-control-color p-0 border-0" style="width:32px; height:32px;">
                            <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" id="mLine2White">White</button>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <label class="form-label-sm">Font Size: <span id="mFontSizeVal">${fontSizeNumber.value}px</span></label>
                    </div>
                    <input type="range" class="form-range" id="mFontSizeRange" min="20" max="70" value="${fontSizeNumber.value}">
                </div>
            `,
            bind: () => {
                const mTitle = document.getElementById('mTitleInput');
                const mFontSize = document.getElementById('mFontSizeRange');
                const mFontSizeVal = document.getElementById('mFontSizeVal');
                const mLine1 = document.getElementById('mLine1ColorPicker');
                const mLine2 = document.getElementById('mLine2ColorPicker');

                mTitle.addEventListener('input', () => {
                    titleInput.value = mTitle.value;
                    titleInput.dispatchEvent(new Event('input'));
                });
                mFontSize.addEventListener('input', () => {
                    fontSizeRange.value = mFontSize.value;
                    fontSizeNumber.value = mFontSize.value;
                    mFontSizeVal.textContent = mFontSize.value + 'px';
                    fontSizeRange.dispatchEvent(new Event('input'));
                });
                mLine1.addEventListener('input', (e) => {
                    line1ColorPicker.value = e.target.value;
                    line1ColorPicker.dispatchEvent(new Event('input'));
                });
                mLine2.addEventListener('input', (e) => {
                    line2ColorPicker.value = e.target.value;
                    line2ColorPicker.dispatchEvent(new Event('input'));
                });
                document.getElementById('mLine1Yellow').addEventListener('click', () => {
                    line1ColorPicker.value = '#FFE600';
                    mLine1.value = '#FFE600';
                    line1ColorPicker.dispatchEvent(new Event('input'));
                });
                document.getElementById('mLine2White').addEventListener('click', () => {
                    line2ColorPicker.value = '#FFFFFF';
                    mLine2.value = '#FFFFFF';
                    line2ColorPicker.dispatchEvent(new Event('input'));
                });
            }
        },
        'sheet-date': {
            title: 'Publication Date',
            render: () => `
                <div class="mb-3">
                    <label class="form-label-sm mb-1">Date Text (Ribbon)</label>
                    <div class="input-group">
                        <button type="button" class="btn btn-outline-primary bg-light border" id="mBtnOpenCalendar" title="ক্যালেন্ডার খুলুন">
                            <i class="bi bi-calendar-event text-primary"></i>
                        </button>
                        <input type="text" class="form-control" id="mDateInput" value="${dateInput.value}" placeholder="যেমন: ২৮ অক্টোবর ২০২৬">
                        <input type="date" id="mNativeDatePicker" class="d-none" value="${nativeDatePicker ? nativeDatePicker.value : ''}">
                    </div>
                    <div class="form-text small text-muted">ক্যালেন্ডার আইকনে চাপ দিয়ে নতুন তারিখ নির্বাচন করতে পারবেন।</div>
                </div>
                <div class="mb-3">
                    <label class="form-label-sm">Date Size: <span id="mDateSizeVal">${dateSizeNumber.value}px</span></label>
                    <input type="range" class="form-range" id="mDateSizeRange" min="15" max="30" value="${dateSizeNumber.value}">
                </div>
            `,
            bind: () => {
                const mDate = document.getElementById('mDateInput');
                const mBtnCal = document.getElementById('mBtnOpenCalendar');
                const mNative = document.getElementById('mNativeDatePicker');
                const mDateSize = document.getElementById('mDateSizeRange');
                const mDateSizeVal = document.getElementById('mDateSizeVal');

                mDate.addEventListener('input', () => {
                    dateInput.value = mDate.value;
                    dateInput.dispatchEvent(new Event('input'));
                });

                if (mBtnCal && mNative) {
                    mBtnCal.addEventListener('click', () => {
                        const currentIso = parseBengaliDateToIso(mDate.value);
                        if (currentIso) mNative.value = currentIso;
                        if (typeof mNative.showPicker === 'function') {
                            try { mNative.showPicker(); return; } catch(e){}
                        }
                        mNative.click();
                    });

                    mNative.addEventListener('change', () => {
                        if (mNative.value) {
                            const bn = formatToBengaliDate(mNative.value);
                            if (bn) {
                                mDate.value = bn;
                                dateInput.value = bn;
                                if (nativeDatePicker) nativeDatePicker.value = mNative.value;
                                dateInput.dispatchEvent(new Event('input'));
                                showToast('তারিখ পরিবর্তন হয়েছে: ' + bn, 'success');
                            }
                        }
                    });
                }

                mDateSize.addEventListener('input', () => {
                    dateSizeRange.value = mDateSize.value;
                    dateSizeNumber.value = mDateSize.value;
                    mDateSizeVal.textContent = mDateSize.value + 'px';
                    dateSizeRange.dispatchEvent(new Event('input'));
                });
            }
        },
        'sheet-photo': {
            title: 'Photo Framing & Position',
            render: () => `
                <div class="mb-3">
                    <label class="form-label-sm">Zoom: <span id="mZoomVal">${imgZoomNumber.value}%</span></label>
                    <input type="range" class="form-range" id="mZoomRange" min="50" max="250" value="${imgZoomNumber.value}">
                </div>
                <div class="mb-3">
                    <label class="form-label-sm">Vertical Position (Y): <span id="mOffsetYVal">${imgOffsetYNumber.value}px</span></label>
                    <input type="range" class="form-range" id="mOffsetYRange" min="-300" max="300" value="${imgOffsetYNumber.value}">
                </div>
                <div class="d-flex gap-2">
                    <label for="mCustomPhotoInput" class="btn btn-outline-primary btn-sm flex-fill">
                        <i class="bi bi-upload me-1"></i> Upload Photo
                    </label>
                    <input type="file" id="mCustomPhotoInput" accept="image/*" class="d-none">
                    <button type="button" class="btn btn-light btn-sm border" id="mResetBtn">Reset</button>
                </div>
            `,
            bind: () => {
                const mZoom = document.getElementById('mZoomRange');
                const mZoomVal = document.getElementById('mZoomVal');
                const mOffsetY = document.getElementById('mOffsetYRange');
                const mOffsetYVal = document.getElementById('mOffsetYVal');
                const mPhotoInput = document.getElementById('mCustomPhotoInput');

                mZoom.addEventListener('input', () => {
                    imgZoomNumber.value = mZoom.value;
                    imgZoomRange.value = (parseFloat(mZoom.value) / 100).toFixed(2);
                    mZoomVal.textContent = mZoom.value + '%';
                    renderCard();
                });
                mOffsetY.addEventListener('input', () => {
                    imgOffsetYNumber.value = mOffsetY.value;
                    imgOffsetYRange.value = mOffsetY.value;
                    mOffsetYVal.textContent = mOffsetY.value + 'px';
                    renderCard();
                });
                mPhotoInput.addEventListener('change', (e) => {
                    customPhotoInput.files = e.target.files;
                    customPhotoInput.dispatchEvent(new Event('change'));
                });
                document.getElementById('mResetBtn').addEventListener('click', () => {
                    btnResetPhoto.click();
                    mZoom.value = 100;
                    mZoomVal.textContent = '100%';
                    mOffsetY.value = 0;
                    mOffsetYVal.textContent = '0px';
                });
            }
        },
        'sheet-ad': {
            title: 'Bottom Advertisement',
            render: () => `
                <div class="mb-3">
                    <label class="form-label-sm">Select Active Ad</label>
                    <select class="form-select" id="mAdSelect">
                        ${Array.from(adSelect.options).map(o => `<option value="${o.value}" ${o.selected ? 'selected' : ''}>${o.textContent}</option>`).join('')}
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <label for="mQuickAdInput" class="btn btn-outline-secondary btn-sm flex-fill">
                        <i class="bi bi-upload me-1"></i> Quick Upload
                    </label>
                    <input type="file" id="mQuickAdInput" accept="image/*" class="d-none">
                    <button type="button" class="btn btn-primary btn-sm flex-fill" data-bs-toggle="modal" data-bs-target="#uploadAdModal">
                        Save to Library
                    </button>
                </div>
            `,
            bind: () => {
                const mAd = document.getElementById('mAdSelect');
                const mQuickAd = document.getElementById('mQuickAdInput');
                mAd.addEventListener('change', () => {
                    adSelect.value = mAd.value;
                    adSelect.dispatchEvent(new Event('change'));
                });
                mQuickAd.addEventListener('change', (e) => {
                    customAdInput.files = e.target.files;
                    customAdInput.dispatchEvent(new Event('change'));
                });
            }
        },
        'sheet-export': {
            title: 'Export Photo Card',
            render: () => `
                <div class="d-flex flex-column gap-2 py-2">
                    <button type="button" class="btn btn-primary btn-lg fw-bold" id="mBtnPng">
                        <i class="bi bi-download me-1"></i> Download High-Res PNG
                    </button>
                    <button type="button" class="btn btn-outline-dark" id="mBtnJpg">
                        <i class="bi bi-file-earmark-image me-1"></i> Download JPG
                    </button>
                    <button type="button" class="btn btn-outline-info" id="mBtnCopy">
                        <i class="bi bi-clipboard-check me-1"></i> Copy to Clipboard
                    </button>
                </div>
            `,
            bind: () => {
                document.getElementById('mBtnPng').addEventListener('click', () => btnDownloadPng.click());
                document.getElementById('mBtnJpg').addEventListener('click', () => btnDownloadJpg.click());
                document.getElementById('mBtnCopy').addEventListener('click', () => btnCopyClipboard.click());
            }
        }
    };

    function openBottomSheet(sheetKey) {
        const config = sheetTemplates[sheetKey];
        if (!config) return;

        sheetTitle.textContent = config.title;
        sheetBody.innerHTML = config.render();
        if (config.bind) config.bind();

        sheetBackdrop.style.display = 'block';
        mobileSheet.style.display = 'block';
        setTimeout(() => mobileSheet.classList.add('show'), 10);
    }

    function closeBottomSheet() {
        mobileSheet.classList.remove('show');
        setTimeout(() => {
            mobileSheet.style.display = 'none';
            sheetBackdrop.style.display = 'none';
        }, 250);
        mobileNavBtns.forEach(b => b.classList.remove('active'));
    }

    mobileNavBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            mobileNavBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const sheetKey = this.getAttribute('data-sheet');
            openBottomSheet(sheetKey);
        });
    });

    sheetCloseBtn.addEventListener('click', closeBottomSheet);
    sheetBackdrop.addEventListener('click', closeBottomSheet);

    // Toast Notification Helper
    function showToast(message, type = 'success') {
        const toastEl = document.getElementById('actionToast');
        const msgEl = document.getElementById('toastMessage');
        const iconEl = document.getElementById('toastIcon');

        msgEl.textContent = message;
        if (type === 'success') {
            iconEl.className = 'bi bi-check-circle-fill text-success fs-5';
        } else if (type === 'danger') {
            iconEl.className = 'bi bi-exclamation-triangle-fill text-danger fs-5';
        } else {
            iconEl.className = 'bi bi-info-circle-fill text-warning fs-5';
        }

        const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
        toast.show();
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
