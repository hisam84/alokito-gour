<?php
/**
 * Search Results Page
 * Alokito Gour Clone
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

$db = get_db();
$query = trim($_GET['q'] ?? '');

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$posts = [];
$totalPosts = 0;
$totalPages = 1;

if (!empty($query)) {
    // Count Search Results
    $countStmt = $db->prepare("
        SELECT COUNT(*) 
        FROM `posts` 
        WHERE `status` = 'published' AND (`title` LIKE :q OR `excerpt` LIKE :q OR `content` LIKE :q OR `keywords` LIKE :q)
    ");
    $countStmt->execute([':q' => '%' . $query . '%']);
    $totalPosts = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalPosts / $perPage));

    // Fetch Paginated Results
    $dataStmt = $db->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug 
        FROM `posts` p 
        LEFT JOIN `categories` c ON p.category_id = c.id 
        WHERE p.status = 'published' AND (p.title LIKE :q OR p.excerpt LIKE :q OR p.content LIKE :q OR p.keywords LIKE :q)
        ORDER BY p.id DESC 
        LIMIT :offset, :limit
    ");
    $dataStmt->bindValue(':q', '%' . $query . '%');
    $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $dataStmt->execute();
    $posts = $dataStmt->fetchAll();
}

$metaTitle = !empty($query) ? 'অনুসন্ধান ফলাফল: ' . $query : 'সংবাদ অনুসন্ধান';
$metaDescription = 'আলোকিত গৌড় সংবাদ অনুসন্ধান ফলাফল';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/ticker.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="/" class="text-muted"><i class="bi bi-house-door me-1"></i>প্রচ্ছদ</a></li>
            <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">অনুসন্ধান ফলাফল</li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Main Search Area (8 Cols) -->
        <div class="col-lg-8">
            <!-- Search Input Box -->
            <div class="card border-0 shadow-sm p-3 mb-4">
                <form action="/search" method="GET" class="row g-2 align-items-center">
                    <div class="col-sm-9">
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" name="q" value="<?= e($query) ?>" placeholder="কী খুঁজতে চান লিখুন..." required>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <button type="submit" class="btn btn-warning text-white fw-bold w-100">খুঁজুন</button>
                    </div>
                </form>
            </div>

            <div class="section-title">
                <span>"<?= e($query) ?>" এর অনুসন্ধান ফলাফল</span>
                <span class="text-muted small fw-normal">পাওয়া গেছে: <?= bn_number($totalPosts) ?> টি</span>
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
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary mb-1"><?= e($post['category_name']) ?></span>
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
                                    <a class="page-link" href="/search?q=<?= urlencode($query) ?>&page=<?= $page - 1 ?>">পূর্ববর্তী</a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="/search?q=<?= urlencode($query) ?>&page=<?= $i ?>"><?= bn_number($i) ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="/search?q=<?= urlencode($query) ?>&page=<?= $page + 1 ?>">পরবর্তী</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php elseif (!empty($query)): ?>
                <div class="bg-white p-5 rounded border text-center text-muted">
                    <i class="bi bi-search fs-1 text-secondary mb-2 d-block"></i>
                    <p class="mb-0">"<?= e($query) ?>" দিয়ে কোনো সংবাদ খুঁজে পাওয়া যায়নি। অন্য কোনো শব্দ দিয়ে চেষ্টা করুন।</p>
                </div>
            <?php else: ?>
                <div class="bg-white p-5 rounded border text-center text-muted">
                    <p class="mb-0">অনুগ্রহ করে উপরে অনুসন্ধান বাক্সে কোনো শব্দ লিখে খুঁজুন।</p>
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
