<?php
/**
 * Single Page Renderer (Supports both Page Builder Layouts & Standard Rich-Text CMS Pages)
 * 100% White-Label & Commercial Ready
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/widget_renderer.php';

$db = get_db();
$slug = trim($_GET['slug'] ?? 'home');

// 1. Check if a drag-and-drop page builder layout exists
$layout = get_page_layout($slug, $db);

// 2. Check if a standard rich-text CMS page exists in `pages` table
$cmsPage = null;
if (!$layout) {
    try {
        $stmt = $db->prepare("SELECT * FROM `pages` WHERE `slug` = :slug AND `status` = 'published' LIMIT 1");
        $stmt->execute([':slug' => $slug]);
        $cmsPage = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $cmsPage = null;
    }
}

// If neither exists, return 404
if (!$layout && !$cmsPage) {
    http_response_code(404);
    $metaTitle = "পাতা পাওয়া যায়নি (404)";
    include __DIR__ . '/includes/header.php';
    include __DIR__ . '/includes/navbar.php';
    echo '<div class="container my-5 text-center">
        <div class="alert alert-warning py-5 shadow-sm rounded-3">
            <i class="bi bi-exclamation-triangle fs-1 text-warning mb-3 d-block"></i>
            <h4 class="fw-bold">অনুরোধকৃত পাতাটি পাওয়া যায়নি (৪০৪)</h4>
            <p class="text-muted">আপনি যে পাতাটি খুঁজছেন তা মুছে ফেলা হয়েছে বা নাম পরিবর্তন করা হয়েছে।</p>
            <a href="/" class="btn btn-warning text-white mt-3 px-4 fw-bold"><i class="bi bi-house-door me-1"></i>প্রচ্ছদে ফিরে যান</a>
        </div>
    </div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

// Set SEO meta titles and descriptions
if ($layout) {
    $metaTitle = $layout['title'] ?? 'পাতা';
} else {
    $metaTitle = !empty($cmsPage['meta_title']) ? $cmsPage['meta_title'] : $cmsPage['title'];
    if (!empty($cmsPage['meta_description'])) {
        $metaDescription = $cmsPage['meta_description'];
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/ticker.php';
?>

<div class="container my-4">
    <?php if ($layout): ?>
        <!-- Render Modular Page Builder Layout -->
        <?php render_page_layout($slug, $db); ?>
    <?php else: ?>
        <!-- Render Standard Rich-Text CMS Page -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="/" class="text-muted"><i class="bi bi-house-door me-1"></i>প্রচ্ছদ</a></li>
                <li class="breadcrumb-item active text-dark fw-bold" aria-current="page"><?= e($cmsPage['title']) ?></li>
            </ol>
        </nav>

        <div class="row g-4">
            <div class="col-lg-8">
                <article class="bg-white p-3 p-sm-4 p-md-5 rounded-3 border shadow-sm">
                    <h1 class="fw-bold text-dark border-bottom pb-3 mb-4 fs-2"><?= e($cmsPage['title']) ?></h1>
                    
                    <div class="page-content-body lh-lg" style="font-size: 1.05rem; color: #334155;">
                        <?= $cmsPage['content'] ?>
                    </div>

                    <?= render_ad('inside_article') ?>
                </article>
            </div>

            <div class="col-lg-4">
                <?php include __DIR__ . '/includes/sidebar.php'; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
