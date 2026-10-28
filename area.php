<?php
/**
 * Area / Local News Filter Page
 * Alokito Gour Clone
 * Filters news by Division, District, and Upazila
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/bangladesh_geo.php';
require_once __DIR__ . '/includes/widget_renderer.php';

$db = get_db();

$division = trim($_GET['division'] ?? '');
$district = trim($_GET['district'] ?? '');
$upazila = trim($_GET['upazila'] ?? '');

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Build Dynamic SQL Query
$where = ["p.status = 'published'"];
$params = [];

if (!empty($upazila)) {
    $where[] = "p.upazila = :upz";
    $params[':upz'] = $upazila;
    if (!empty($district)) {
        $where[] = "p.district = :dist";
        $params[':dist'] = $district;
    }
    $locationLabel = $upazila . ($district ? ', ' . $district : '');
} elseif (!empty($district)) {
    $where[] = "p.district = :dist";
    $params[':dist'] = $district;
    if (!empty($division)) {
        $where[] = "p.division = :div";
        $params[':div'] = $division;
    }
    $locationLabel = $district . ' জেলা';
} elseif (!empty($division)) {
    $where[] = "p.division = :div";
    $params[':div'] = $division;
    $locationLabel = $division . ' বিভাগ';
} else {
    $locationLabel = 'সারাদেশ';
}

$whereSql = implode(' AND ', $where);

// Count Total Matching Posts
$countStmt = $db->prepare("SELECT COUNT(*) FROM `posts` p WHERE $whereSql");
$countStmt->execute($params);
$totalPosts = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalPosts / $perPage));

// Fetch Paginated Posts
$dataStmt = $db->prepare("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug 
    FROM `posts` p 
    LEFT JOIN `categories` c ON p.category_id = c.id 
    WHERE $whereSql 
    ORDER BY p.id DESC 
    LIMIT :offset, :limit
");
foreach ($params as $k => $v) {
    $dataStmt->bindValue($k, $v);
}
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$dataStmt->execute();
$posts = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

// SEO Meta Variables
$metaTitle = $locationLabel . ' এর সকল খবর - আলোকিত গৌড়';
$metaDescription = $locationLabel . ' এলাকার সর্বশেষ খবর, তাজা সংবাদ ও আপডেট। আলোকিত গৌড়';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/ticker.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="/" class="text-muted"><i class="bi bi-house-door me-1"></i>প্রচ্ছদ</a></li>
            <li class="breadcrumb-item"><a href="/area" class="text-muted">এলাকার খবর</a></li>
            <?php if (!empty($division)): ?>
                <li class="breadcrumb-item"><a href="/area?division=<?= urlencode($division) ?>" class="text-muted"><?= e($division) ?></a></li>
            <?php endif; ?>
            <?php if (!empty($district)): ?>
                <li class="breadcrumb-item"><a href="/area?division=<?= urlencode($division) ?>&district=<?= urlencode($district) ?>" class="text-muted"><?= e($district) ?></a></li>
            <?php endif; ?>
            <?php if (!empty($upazila)): ?>
                <li class="breadcrumb-item active text-dark fw-bold" aria-current="page"><?= e($upazila) ?></li>
            <?php else: ?>
                <li class="breadcrumb-item active text-dark fw-bold" aria-current="page"><?= e($locationLabel) ?></li>
            <?php endif; ?>
        </ol>
    </nav>

    <!-- Interactive Cascading Area Filter Bar -->
    <?= render_area_filter_bar(['division' => $division, 'district' => $district, 'upazila' => $upazila], false) ?>

    <div class="row g-4 mt-1">
        <!-- Main Area News Content Column (8 Cols) -->
        <div class="col-lg-8">
            <!-- Section Header -->
            <div class="section-title d-flex justify-content-between align-items-center mb-3">
                <span class="fs-5">
                    <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= e($locationLabel) ?> এর খবর
                </span>
                <span class="text-muted small fw-normal">
                    মোট সংবাদ: <strong><?= bn_number($totalPosts) ?></strong> টি
                </span>
            </div>

            <?php if (!empty($posts)): ?>
                <div class="row g-3">
                    <?php foreach ($posts as $post): ?>
                        <div class="col-md-6">
                            <article class="news-card h-100 bg-white rounded border shadow-sm overflow-hidden d-flex flex-column">
                                <a href="/news/<?= (int)$post['id'] ?>/<?= e($post['slug']) ?>" class="card-thumb d-block position-relative">
                                    <img src="<?= e($post['thumbnail'] ?: 'https://images.unsplash.com/photo-1584467735871-8e85353a8413?w=500&q=80') ?>" alt="<?= e($post['title']) ?>" class="w-100 object-fit-cover" style="height: 190px;">
                                </a>
                                
                                <div class="card-body p-3 d-flex flex-column flex-grow-1">
                                    <div class="d-flex align-items-center justify-content-between gap-1 mb-2">
                                        <?php if (!empty($post['category_name'])): ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 11px;">
                                                <?= e($post['category_name']) ?>
                                            </span>
                                        <?php endif; ?>

                                        <?php if (!empty($post['district'])): ?>
                                            <span class="badge bg-light text-secondary border fw-normal" style="font-size: 10.5px;">
                                                <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= e($post['district']) ?><?= !empty($post['upazila']) ? ' › ' . e($post['upazila']) : '' ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <h5 class="card-title fs-6 fw-bold mb-2 lh-base">
                                        <a href="/news/<?= (int)$post['id'] ?>/<?= e($post['slug']) ?>" class="text-dark text-decoration-none">
                                            <?= e($post['title']) ?>
                                        </a>
                                    </h5>

                                    <p class="small text-muted mb-3 flex-grow-1" style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;line-height:1.5;">
                                        <?= e($post['excerpt']) ?>
                                    </p>

                                    <div class="card-meta text-muted pt-2 border-top d-flex align-items-center justify-content-between mt-auto" style="font-size: 11px;">
                                        <span><i class="bi bi-person me-1"></i><?= e($post['reporter_name'] ?: 'নিজস্ব প্রতিবেদক') ?></span>
                                        <span><i class="bi bi-clock me-1"></i><?= get_time_ago($post['created_at']) ?></span>
                                    </div>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php 
                            $queryBase = [
                                'division' => $division,
                                'district' => $district,
                                'upazila'  => $upazila
                            ];
                            ?>

                            <!-- Previous Button -->
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="/area?<?= http_build_query(array_merge($queryBase, ['page' => $page - 1])) ?>">পূর্ববর্তী</a>
                                </li>
                            <?php endif; ?>

                            <!-- Page Numbers -->
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <?php if ($p === 1 || $p === $totalPages || ($p >= $page - 2 && $p <= $page + 2)): ?>
                                    <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                                        <a class="page-link" href="/area?<?= http_build_query(array_merge($queryBase, ['page' => $p])) ?>"><?= bn_number($p) ?></a>
                                    </li>
                                <?php elseif ($p === $page - 3 || $p === $page + 3): ?>
                                    <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <!-- Next Button -->
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="/area?<?= http_build_query(array_merge($queryBase, ['page' => $page + 1])) ?>">পরবর্তী</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <!-- No News Found Alert -->
                <div class="card border-0 shadow-sm text-center py-5 px-3 my-3">
                    <div class="text-danger mb-3">
                        <i class="bi bi-geo-alt fs-1"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">এই এলাকার কোনো সংবাদ পাওয়া যায়নি!</h5>
                    <p class="text-muted small mb-3">
                        নির্বাচিত এলাকা "<strong><?= e($locationLabel) ?></strong>"-তে এই মুহূর্তে কোনো সংবাদ প্রকাশিত হয়নি।<br>
                        উপরের ড্রপডাউন থেকে অন্য কোনো বিভাগ বা জেলা নির্বাচন করে পুনরায় খুঁজুন।
                    </p>
                    <div>
                        <a href="/area" class="btn btn-outline-danger btn-sm px-4">
                            <i class="bi bi-arrow-repeat me-1"></i>সকল এলাকার সংবাদ দেখুন
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Column (4 Cols) -->
        <div class="col-lg-4">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
