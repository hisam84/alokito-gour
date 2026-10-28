<?php
/**
 * Post List Management
 * Alokito Gour Clone
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';

$pageTitle = 'সকল সংবাদ তালিকা | আলোকিত গৌড়';
$db = get_db();

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $token = $_GET['csrf_token'] ?? '';
    if (hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $deleteId = (int)$_GET['id'];
        $delStmt = $db->prepare("DELETE FROM `posts` WHERE `id` = :id");
        $delStmt->execute([':id' => $deleteId]);
        set_flash('success', 'সংবাদটি সফলভাবে মুছে ফেলা হয়েছে।');
    } else {
        set_flash('danger', 'নিরাপত্তা টোকেন সঠিক নয়!');
    }
    header('Location: post-list.php');
    exit;
}

// Filters & Search
$categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$whereClauses = [];
$params = [];

if ($categoryId > 0) {
    $whereClauses[] = "p.category_id = :cat_id";
    $params[':cat_id'] = $categoryId;
}

if (!empty($search)) {
    $whereClauses[] = "(p.title LIKE :search OR p.excerpt LIKE :search OR p.reporter_name LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Count total posts
$countStmt = $db->prepare("SELECT COUNT(*) FROM `posts` p $whereSql");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

// Fetch paginated posts
$dataSql = "
    SELECT p.*, c.name AS category_name
    FROM `posts` p
    LEFT JOIN `categories` c ON p.category_id = c.id
    $whereSql
    ORDER BY p.id DESC
    LIMIT :offset, :limit
";
$stmt = $db->prepare($dataSql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

// Categories for filter dropdown
$categories = $db->query("SELECT id, name FROM `categories` ORDER BY `order_index` ASC, `name` ASC")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">সকল সংবাদ তালিকা</h4>
        <p class="text-muted small mb-0">মোট সংবাদ: <?= bn_number($totalRows) ?> টি</p>
    </div>
    <a href="post-add.php" class="btn btn-warning text-white fw-medium shadow-sm d-flex align-items-center gap-1">
        <i class="bi bi-pencil-square"></i>
        <span>নতুন সংবাদ যোগ করুন</span>
    </a>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="post-list.php" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0" name="q" placeholder="শিরোনাম বা প্রতিবেদকের নাম দিয়ে খুঁজুন..." value="<?= e($search) ?>">
                </div>
            </div>

            <div class="col-md-4">
                <select class="form-select" name="category_id">
                    <option value="0">সকল ক্যাটাগরি</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= ($categoryId === (int)$cat['id']) ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100">ফিল্টার করুন</button>
                <?php if ($categoryId > 0 || !empty($search)): ?>
                    <a href="post-list.php" class="btn btn-outline-danger" title="রিসেট"><i class="bi bi-x-circle"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- News Table -->
<div class="card border-0 shadow-sm rounded-3 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 70px;">ছবি</th>
                    <th>শিরোনাম</th>
                    <th>ক্যাটাগরি</th>
                    <th>প্রতিবেদক</th>
                    <th>ভিউ</th>
                    <th>স্ট্যাটাস</th>
                    <th>প্রকাশনার তারিখ</th>
                    <th class="text-end" style="width: 140px;">অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($posts)): ?>
                    <?php foreach ($posts as $post): ?>
                        <tr>
                            <td>
                                <?php if (!empty($post['thumbnail'])): ?>
                                    <img src="<?= e($post['thumbnail']) ?>" alt="Thumb" class="rounded object-fit-cover border" width="60" height="42">
                                <?php else: ?>
                                    <div class="bg-light text-muted d-flex align-items-center justify-content-center rounded border" style="width:60px; height:42px; font-size:10px;">ছবি নেই</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="post-edit.php?id=<?= (int)$post['id'] ?>" class="text-dark text-decoration-none fw-medium d-block" style="max-width: 380px;">
                                    <?= e($post['title']) ?>
                                </a>
                                <div class="mt-1">
                                    <?php if ($post['is_lead']): ?>
                                        <span class="badge bg-danger" style="font-size:10px;">লিড নিউজ</span>
                                    <?php endif; ?>
                                    <?php if ($post['is_breaking']): ?>
                                        <span class="badge bg-warning text-dark" style="font-size:10px;">ব্রেকিং</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= e($post['category_name'] ?? 'অনির্দিষ্ট') ?></span></td>
                            <td><span class="small text-muted"><?= e($post['reporter_name'] ?? '') ?></span></td>
                            <td><span class="small text-muted"><i class="bi bi-eye me-1"></i><?= bn_number($post['views']) ?></span></td>
                            <td>
                                <?php if ($post['status'] === 'published'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">প্রকাশিত</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">খসড়া</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= bn_date($post['created_at'], false) ?></td>
                            <td class="text-end">
                                <a href="../single.php?id=<?= (int)$post['id'] ?>" target="_blank" class="btn btn-sm btn-light border text-secondary" title="দেখুন">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="post-edit.php?id=<?= (int)$post['id'] ?>" class="btn btn-sm btn-light border text-primary" title="সম্পাদনা">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="post-list.php?action=delete&id=<?= (int)$post['id'] ?>&csrf_token=<?= e(csrf_token()) ?>" class="btn btn-sm btn-light border text-danger btn-confirm-delete" title="মুছে ফেলুন">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block text-secondary mb-2"></i>
                            কোনো সংবাদ পাওয়া যায়নি।
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
            <span class="small text-muted">পৃষ্ঠা <?= bn_number($page) ?> / <?= bn_number($totalPages) ?></span>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="post-list.php?page=<?= $page - 1 ?>&category_id=<?= $categoryId ?>&q=<?= urlencode($search) ?>">পূর্ববর্তী</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                            <a class="page-link" href="post-list.php?page=<?= $i ?>&category_id=<?= $categoryId ?>&q=<?= urlencode($search) ?>"><?= bn_number($i) ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="post-list.php?page=<?= $page + 1 ?>&category_id=<?= $categoryId ?>&q=<?= urlencode($search) ?>">পরবর্তী</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
