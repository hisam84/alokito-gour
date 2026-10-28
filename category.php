<?php
/**
 * Category Archive Page with Pagination
 * Alokito Gour Clone
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

$db = get_db();
$slug = trim($_GET['slug'] ?? '');

if (empty($slug)) {
    header("Location: /");
    exit;
}

// Fetch Category
$catStmt = $db->prepare("SELECT * FROM `categories` WHERE `slug` = :slug LIMIT 1");
$catStmt->execute([':slug' => $slug]);
$category = $catStmt->fetch();

if (!$category) {
    header("HTTP/1.0 404 Not Found");
    die("<div style='font-family:sans-serif;text-align:center;padding:50px;'><h2>৪০৪ - ক্যাটাগরি খুঁজে পাওয়া যায়নি!</h2><p><a href='/'>মূল পাতায় ফিরে যান</a></p></div>");
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Count Total Posts in Category
$countStmt = $db->prepare("SELECT COUNT(*) FROM `posts` WHERE `category_id` = :cat_id AND `status` = 'published'");
$countStmt->execute([':cat_id' => $category['id']]);
$totalPosts = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalPosts / $perPage));

// Fetch Paginated Posts
$postsStmt = $db->prepare("
    SELECT * 
    FROM `posts` 
    WHERE `category_id` = :cat_id AND `status` = 'published' 
    ORDER BY `id` DESC 
    LIMIT :offset, :limit
");
$postsStmt->bindValue(':cat_id', $category['id'], PDO::PARAM_INT);
$postsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$postsStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$postsStmt->execute();
$posts = $postsStmt->fetchAll();

// Meta for Header
$metaTitle = $category['name'] . ' বিভাগের সকল খবর';
$metaDescription = $category['name'] . ' বিভাগের সর্বশেষ এবং ব্রেকিং নিউজ';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/ticker.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="/" class="text-muted"><i class="bi bi-house-door me-1"></i>প্রচ্ছদ</a></li>
            <li class="breadcrumb-item active text-dark fw-bold" aria-current="page"><?= e($category['name']) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Main News Archive Area (8 Cols) -->
        <div class="col-lg-8">
            <div class="section-title">
                <span><?= e($category['name']) ?></span>
                <span class="text-muted small fw-normal">মোট খবর: <?= bn_number($totalPosts) ?> টি</span>
            </div>

            <?php if (!empty($posts)): ?>
                <div class="row g-3">
                    <?php foreach ($posts as $post): ?>
                        <div class="col-md-6">
                            <article class="news-card">
                                <a href="/news/<?= (int)$post['id'] ?>/<?= e($post['slug']) ?>" class="card-thumb d-block">
                                    <img src="<?= e($post['thumbnail'] ?: 'https://images.unsplash.com/photo-1584467735871-8e85353a8413?w=500&q=80') ?>" alt="<?= e($post['title']) ?>">
                                </a>
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="/news/<?= (int)$post['id'] ?>/<?= e($post['slug']) ?>"><?= e($post['title']) ?></a>
                                    </h5>
                                    <p class="small text-muted mb-2 text-truncate-2" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                        <?= e($post['excerpt']) ?>
                                    </p>
                                    <div class="card-meta">
                                        <i class="bi bi-clock me-1"></i><?= get_time_ago($post['created_at']) ?>
                                    </div>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4" aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="/category/<?= e($slug) ?>?page=<?= $page - 1 ?>">পূর্ববর্তী</a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="/category/<?= e($slug) ?>?page=<?= $i ?>"><?= bn_number($i) ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="/category/<?= e($slug) ?>?page=<?= $page + 1 ?>">পরবর্তী</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="bg-white p-5 rounded border text-center text-muted">
                    <i class="bi bi-folder2-open fs-1 text-secondary mb-2 d-block"></i>
                    <p class="mb-0">এই ক্যাটাগরিতে এখনও কোনো সংবাদ প্রকাশিত হয়নি।</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Area (4 Cols) -->
        <div class="col-lg-4">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
