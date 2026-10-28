<?php
/**
 * Single Page Renderer (Page Builder Driven)
 * Alokito Gour - 100% Authentic Clone
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/widget_renderer.php';

$db = get_db();
$slug = trim($_GET['slug'] ?? 'home');

$layout = get_page_layout($slug, $db);
if (!$layout) {
    http_response_code(404);
    $metaTitle = "পাতা পাওয়া যায়নি (404)";
    include __DIR__ . '/includes/header.php';
    include __DIR__ . '/includes/navbar.php';
    echo '<div class="container my-5 text-center"><div class="alert alert-warning py-5"><h4>অনুরোধকৃত পাতাটি পাওয়া যায়নি (৪০৪)</h4><p class="text-muted">আপনি যে পাতাটি খুঁজছেন তা মুছে ফেলা হয়েছে বা নাম পরিবর্তন করা হয়েছে।</p><a href="/" class="btn btn-primary mt-3"><i class="bi bi-house-door me-1"></i>প্রচ্ছদে ফিরে যান</a></div></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$metaTitle = $layout['title'] ?? 'পাতা';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/ticker.php';
?>

<div class="container my-3">
    <?php render_page_layout($slug, $db); ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
