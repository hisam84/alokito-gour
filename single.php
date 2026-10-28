<?php
/**
 * Single Article Page
 * Alokito Gour Clone
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/widget_renderer.php';

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("HTTP/1.0 404 Not Found");
    die("সংবাদটি খুঁজে পাওয়া যায়নি।");
}

// Fetch Article
$stmt = $db->prepare("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.name AS author_name 
    FROM `posts` p 
    LEFT JOIN `categories` c ON p.category_id = c.id 
    LEFT JOIN `users` u ON p.user_id = u.id 
    WHERE p.id = :id AND p.status = 'published' 
    LIMIT 1
");
$stmt->execute([':id' => $id]);
$post = $stmt->fetch();

if (!$post) {
    header("HTTP/1.0 404 Not Found");
    die("<div style='font-family:sans-serif;text-align:center;padding:50px;'><h2>৪০৪ - সংবাদটি পাওয়া যায়নি!</h2><p><a href='/'>মূল পাতায় ফিরে যান</a></p></div>");
}

// Increment View Counter
$upStmt = $db->prepare("UPDATE `posts` SET `views` = `views` + 1 WHERE `id` = :id");
$upStmt->execute([':id' => $id]);

// SEO Meta Variables
$metaTitle = $post['title'];
$metaDescription = $post['excerpt'];
$metaImage = $post['thumbnail'];

// Fetch Related News in Same Category (4 items)
$relStmt = $db->prepare("
    SELECT id, title, slug, thumbnail, created_at 
    FROM `posts` 
    WHERE `category_id` = :cat_id AND `id` != :curr_id AND `status` = 'published' 
    ORDER BY `id` DESC 
    LIMIT 4
");
$relStmt->execute([':cat_id' => $post['category_id'], ':curr_id' => $id]);
$relatedPosts = $relStmt->fetchAll();

$hostName = $_SERVER['HTTP_HOST'] ?? 'localhost';
$reqUri = $_SERVER['REQUEST_URI'] ?? ('/news/' . $id);
$articleUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$hostName}{$reqUri}";

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/ticker.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="/" class="text-muted"><i class="bi bi-house-door me-1"></i>প্রচ্ছদ</a></li>
            <?php if (!empty($post['category_name'])): ?>
                <li class="breadcrumb-item"><a href="/category/<?= e($post['category_slug']) ?>" class="text-muted"><?= e($post['category_name']) ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active text-truncate" aria-current="page" style="max-width: 300px;"><?= e($post['title']) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Main Article Column (8 Cols) -->
        <div class="col-lg-8">
            <article class="bg-white p-4 rounded-3 border shadow-sm">
                <!-- Article Headline -->
                <header class="article-header">
                    <h1 class="article-headline"><?= e($post['title']) ?></h1>

                    <!-- Article Meta & Byline -->
                    <div class="article-meta">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-fill text-warning fs-5"></i>
                            <div>
                                <span class="fw-bold text-dark d-block lh-1"><?= e($post['reporter_name'] ?: 'নিজস্ব প্রতিবেদক') ?></span>
                                <span class="small text-muted" style="font-size:11px;">প্রকাশিত: <?= bn_date($post['created_at'], true) ?></span>
                            </div>
                        </div>

                        <!-- Social Share Icons -->
                        <div class="d-flex align-items-center gap-1 social-share-btns">
                            <span class="small text-muted me-2 d-none d-sm-inline">শেয়ার:</span>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($articleUrl) ?>" target="_blank" rel="noopener" class="share-fb" title="Facebook-এ শেয়ার করুন">
                                <i class="bi bi-facebook"></i>
                            </a>
                            <a href="https://api.whatsapp.com/send?text=<?= urlencode($post['title'] . ' ' . $articleUrl) ?>" target="_blank" rel="noopener" class="share-wa" title="WhatsApp-এ পাঠান">
                                <i class="bi bi-whatsapp"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?text=<?= urlencode($post['title']) ?>&url=<?= urlencode($articleUrl) ?>" target="_blank" rel="noopener" class="share-tw" title="X (Twitter)-এ শেয়ার করুন">
                                <i class="bi bi-twitter-x"></i>
                            </a>
                            <a href="#" id="copyArticleLink" class="share-copy" title="লিংক কপি করুন">
                                <i class="bi bi-link-45deg"></i>
                            </a>
                        </div>
                    </div>
                </header>

                <!-- Featured Image -->
                <?php if (!empty($post['thumbnail'])): ?>
                    <div class="article-featured-img">
                        <img src="<?= e($post['thumbnail']) ?>" alt="<?= e($post['title']) ?>" class="img-fluid">
                        <?php if (!empty($post['caption'])): ?>
                            <div class="caption text-center">
                                <i class="bi bi-camera me-1"></i><?= e($post['caption']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Article Content Body -->
                <div class="article-body">
                    <?= $post['content'] ?>
                </div>

                <!-- Inside Article Advertisement Slot -->
                <?= render_ad('inside_article') ?>

                <!-- Bottom Meta (Views & Category) -->
                <div class="d-flex align-items-center justify-content-between pt-3 mt-4 border-top text-muted small">
                    <div>
                        <span class="badge bg-light text-dark border me-2"><?= e($post['category_name']) ?></span>
                    </div>
                    <div>
                        <i class="bi bi-eye me-1 text-secondary"></i>পঠিত: <strong><?= bn_number($post['views']) ?></strong> বার
                    </div>
                </div>
            </article>

            <!-- Related Articles Grid -->
            <?php if (!empty($relatedPosts)): ?>
                <section class="mt-4">
                    <div class="section-title">
                        <span>সম্পর্কিত আরও খবর</span>
                    </div>
                    <div class="row g-3">
                        <?php foreach ($relatedPosts as $rel): ?>
                            <div class="col-sm-6">
                                <div class="news-card">
                                    <a href="/news/<?= (int)$rel['id'] ?>/<?= e($rel['slug']) ?>" class="card-thumb d-block">
                                        <img src="<?= e($rel['thumbnail'] ?: 'https://images.unsplash.com/photo-1584467735871-8e85353a8413?w=500&q=80') ?>" alt="<?= e($rel['title']) ?>">
                                    </a>
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <a href="/news/<?= (int)$rel['id'] ?>/<?= e($rel['slug']) ?>"><?= e($rel['title']) ?></a>
                                        </h6>
                                        <span class="card-meta"><i class="bi bi-clock me-1"></i><?= get_time_ago($rel['created_at']) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>

        <!-- Sidebar Area (4 Cols) - Powered by Widget Builder & Article Ad Settings -->
        <div class="col-lg-4">
            <?php render_news_sidebar($post, $db); ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
