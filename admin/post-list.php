<?php
/**
 * Post List Management
 * Alokito Gour Clone
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';

$pageTitle = 'All Posts List | Alokito Gour';
$db = get_db();

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $token = $_GET['csrf_token'] ?? '';
    if (hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $deleteId = (int)$_GET['id'];
        $delStmt = $db->prepare("DELETE FROM `posts` WHERE `id` = :id");
        $delStmt->execute([':id' => $deleteId]);
        set_flash('success', 'Article deleted successfully.');
    } else {
        set_flash('danger', 'Security token mismatch. Please try again.');
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
    $whereClauses[] = "(p.title LIKE :search OR p.excerpt LIKE :search OR p.reporter_name LIKE :search OR p.keywords LIKE :search OR p.district LIKE :search OR p.upazila LIKE :search)";
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
        <h4 class="fw-bold mb-1 text-dark">All Posts List</h4>
        <p class="text-muted small mb-0">Total Articles: <?= number_format($totalRows) ?></p>
    </div>
    <a href="post-add.php" class="btn btn-warning text-white fw-medium shadow-sm d-flex align-items-center gap-1">
        <i class="bi bi-pencil-square"></i>
        <span>Add New Article</span>
    </a>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="post-list.php" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0" name="q" placeholder="Search by title, reporter, or keywords..." value="<?= e($search) ?>">
                </div>
            </div>

            <div class="col-md-4">
                <select class="form-select" name="category_id">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= ($categoryId === (int)$cat['id']) ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100">Filter</button>
                <?php if ($categoryId > 0 || !empty($search)): ?>
                    <a href="post-list.php" class="btn btn-outline-danger" title="Reset Filters"><i class="bi bi-x-circle"></i></a>
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
                    <th style="width: 70px;">Thumb</th>
                    <th>Headline & Details</th>
                    <th>Category</th>
                    <th>Reporter</th>
                    <th>Views</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="text-end" style="width: 140px;">Actions</th>
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
                                    <div class="bg-light text-muted d-flex align-items-center justify-content-center rounded border" style="width:60px; height:42px; font-size:10px;">No Image</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="post-edit.php?id=<?= (int)$post['id'] ?>" class="text-dark text-decoration-none fw-medium d-block" style="max-width: 380px;">
                                    <?= e($post['title']) ?>
                                </a>
                                <div class="mt-1 d-flex flex-wrap gap-1 align-items-center">
                                    <?php if ($post['is_lead']): ?>
                                        <span class="badge bg-danger" style="font-size:10px;">Lead News</span>
                                    <?php endif; ?>
                                    <?php if ($post['is_breaking']): ?>
                                        <span class="badge bg-warning text-dark" style="font-size:10px;">Breaking</span>
                                    <?php endif; ?>
                                    <?php if (!empty($post['keywords'])): ?>
                                        <span class="badge bg-light text-muted border" style="font-size:10px;" title="Keywords: <?= e($post['keywords']) ?>"><i class="bi bi-tags me-1 text-secondary"></i><?= e(mb_substr($post['keywords'], 0, 30)) ?><?= mb_strlen($post['keywords']) > 30 ? '...' : '' ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary"><?= e($post['category_name'] ?? 'Uncategorized') ?></span>
                                <?php if (!empty($post['district'])): ?>
                                    <div class="mt-1">
                                        <span class="badge bg-light text-secondary border fw-normal" style="font-size:10px;" title="<?= e(implode(' › ', array_filter([$post['division'] ?? '', $post['district'] ?? '', $post['upazila'] ?? '']))) ?>">
                                            <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= e($post['district']) ?><?= !empty($post['upazila']) ? ' › ' . e($post['upazila']) : '' ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><span class="small text-muted"><?= e($post['reporter_name'] ?? '') ?></span></td>
                            <td><span class="small text-muted"><i class="bi bi-eye me-1"></i><?= number_format((int)$post['views']) ?></span></td>
                            <td>
                                <?php if ($post['status'] === 'published'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Published</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= date('M d, Y', strtotime($post['created_at'])) ?></td>
                            <td class="text-end">
                                <a href="../single.php?id=<?= (int)$post['id'] ?>" target="_blank" class="btn btn-sm btn-light border text-secondary" title="View Article">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="photo-card.php?post_id=<?= (int)$post['id'] ?>" class="btn btn-sm btn-light border text-info" title="Generate Facebook Photo Card">
                                    <i class="bi bi-card-image"></i>
                                </a>
                                <a href="post-edit.php?id=<?= (int)$post['id'] ?>" class="btn btn-sm btn-light border text-primary" title="Edit Article">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="post-list.php?action=delete&id=<?= (int)$post['id'] ?>&csrf_token=<?= e(csrf_token()) ?>" class="btn btn-sm btn-light border text-danger" title="Delete Article" onclick="return confirm('Are you sure you want to delete this article?');">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block text-secondary mb-2"></i>
                            No articles found matching your criteria.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
            <span class="small text-muted">Page <?= number_format($page) ?> of <?= number_format($totalPages) ?></span>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="post-list.php?page=<?= $page - 1 ?>&category_id=<?= $categoryId ?>&q=<?= urlencode($search) ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                            <a class="page-link" href="post-list.php?page=<?= $i ?>&category_id=<?= $categoryId ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="post-list.php?page=<?= $page + 1 ?>&category_id=<?= $categoryId ?>&q=<?= urlencode($search) ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
