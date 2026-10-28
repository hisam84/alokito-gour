<?php
/**
 * Real-Time Live Preview Renderer for Page Builder
 * WordPress/Elementor-Style Real-time Sync
 * Alokito Gour News Portal
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';
require_once dirname(__DIR__) . '/includes/widget_renderer.php';

header('X-Frame-Options: SAMEORIGIN');

$db = get_db();
$settings = get_settings();

$slug = trim($_GET['page'] ?? ($_POST['page'] ?? 'home'));

// Read in-memory draft widgets if passed via POST
$customWidgets = null;
if (!empty($_POST['layout_data'])) {
    $decoded = json_decode($_POST['layout_data'], true);
    if (is_array($decoded)) {
        $customWidgets = $decoded;
    }
}

// Prepare sample post for news_sidebar preview
$samplePost = null;
if ($slug === 'news_sidebar') {
    $stmt = $db->query("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug 
        FROM `posts` p 
        LEFT JOIN `categories` c ON p.category_id = c.id 
        WHERE p.status = 'published' 
        ORDER BY p.id DESC 
        LIMIT 1
    ");
    $samplePost = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>রিয়েল-টাইম প্রিভিউ - <?= e($slug) ?> | আলোকিত গৌড়</title>

    <!-- Bengali Font: SolaimanLipi -->
    <link href="https://fonts.maateen.me/solaiman-lipi/font.css" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Public Theme Stylesheet -->
    <link href="/assets/css/style.css" rel="stylesheet">

    <style>
        /* Real-time preview visual enhancements */
        body {
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* Subtle interactive outline on widgets */
        [data-widget-id] {
            position: relative;
            transition: outline 0.15s ease, box-shadow 0.15s ease;
        }

        [data-widget-id]:hover {
            outline: 2px dashed #f3730d !important;
            outline-offset: 4px;
        }

        [data-widget-id]:hover::before {
            content: attr(data-widget-id);
            position: absolute;
            top: -12px;
            left: 10px;
            background: #f3730d;
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 4px;
            z-index: 9999;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            pointer-events: none;
            font-family: sans-serif;
        }

        .preview-watermark {
            background: linear-gradient(90deg, #1e293b, #0f172a);
            color: #fbbf24;
            font-size: 11px;
            text-align: center;
            padding: 4px 10px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
    </style>
</head>
<body>

<!-- Discreet Preview Status Ribbon -->
<div class="preview-watermark">
    <i class="bi bi-eye-fill"></i>
    <span>ওয়ার্ডপ্রেস-স্টাইল লাইভ প্রিভিউ &bull; পেজ: <?= e($slug) ?></span>
    <span class="badge bg-warning text-dark ms-2" style="font-size: 9px;">রিয়েল-টাইম সিঙ্ক</span>
</div>

<?php 
// Include Top Bar, Branding Header, Navbar, Ticker
include dirname(__DIR__) . '/includes/header.php';
include dirname(__DIR__) . '/includes/navbar.php';
include dirname(__DIR__) . '/includes/ticker.php';
?>

<div class="container my-3">
    <?php if ($slug === 'news_sidebar'): ?>
        <!-- News Sidebar In-Context Preview -->
        <div class="row g-4">
            <!-- Simulated Article (8 Cols) -->
            <div class="col-lg-8">
                <article class="bg-white p-4 rounded-3 border shadow-sm mb-4">
                    <span class="badge bg-primary-subtle text-primary mb-2"><?= e($samplePost['category_name'] ?? 'জাতীয়') ?></span>
                    <h2 class="fw-bold mb-3"><?= e($samplePost['title'] ?? 'নমুনা সংবাদ শিরোনাম') ?></h2>
                    <div class="d-flex align-items-center gap-2 text-muted small mb-3 border-bottom pb-2">
                        <i class="bi bi-person-fill text-warning"></i>
                        <span><?= e($samplePost['reporter_name'] ?? 'নিজস্ব প্রতিবেদক') ?></span>
                        <span>&bull;</span>
                        <span><?= bn_date($samplePost['created_at'] ?? 'now', true) ?></span>
                    </div>

                    <?php if (!empty($samplePost['thumbnail'])): ?>
                        <div class="mb-3 rounded overflow-hidden">
                            <img src="<?= e($samplePost['thumbnail']) ?>" alt="Post Thumbnail" class="img-fluid w-100 object-fit-cover" style="max-height: 400px;">
                        </div>
                    <?php endif; ?>

                    <div class="article-body">
                        <?= $samplePost['content'] ?? '<p>এটি একটি ডেমো নিউজ আর্টিকেল ভিউ। ডানপাশের সাইডবারে পেজ বিল্ডার দিয়ে সাজানো ব্লকগুলো লাইভ প্রদর্শিত হচ্ছে।</p>' ?>
                    </div>
                </article>
            </div>

            <!-- Live Sidebar Being Customized (4 Cols) -->
            <div class="col-lg-4">
                <div class="p-2 border border-warning rounded bg-light mb-2 text-center small text-warning fw-bold">
                    <i class="bi bi-arrow-down-circle me-1"></i>লাইভ সাইডবার ব্লক
                </div>
                <?php render_news_sidebar($samplePost, $db, $customWidgets); ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Standard Homepage or Custom Page Layout -->
        <?php render_page_layout($slug, $db, ['custom_widgets' => $customWidgets]); ?>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

<!-- Bootstrap 5 Bundle JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
