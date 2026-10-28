<?php
/**
 * Admin Dashboard
 * Alokito Gour Clone
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';

$pageTitle = 'Dashboard Overview | Alokito Gour';
$db = get_db();

// 1. Fetch KPI metrics
$totalPosts = (int)$db->query("SELECT COUNT(*) FROM `posts`")->fetchColumn();
$totalViews = (int)$db->query("SELECT COALESCE(SUM(`views`), 0) FROM `posts`")->fetchColumn();
$totalCategories = (int)$db->query("SELECT COUNT(*) FROM `categories`")->fetchColumn();
$activeAds = (int)$db->query("SELECT COUNT(*) FROM `advertisements` WHERE `status` = 1")->fetchColumn();

// 2. Fetch Recent Posts
$stmt = $db->query("
    SELECT p.id, p.title, p.slug, p.thumbnail, p.views, p.is_lead, p.is_breaking, p.status, p.created_at, c.name AS category_name
    FROM `posts` p
    LEFT JOIN `categories` c ON p.category_id = c.id
    ORDER BY p.id DESC
    LIMIT 8
");
$recentPosts = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Welcome back, <?= e($_SESSION['admin_user_name'] ?? 'Admin') ?>!</h4>
        <p class="text-muted small mb-0">Overview and real-time statistics of Alokito Gour news portal</p>
    </div>
    <a href="post-add.php" class="btn btn-warning text-white fw-medium shadow-sm d-flex align-items-center gap-1">
        <i class="bi bi-plus-circle"></i>
        <span>Create New Post</span>
    </a>
</div>

<!-- KPI Cards Grid -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div>
                <div class="stat-title">Total Published Posts</div>
                <div class="stat-value text-primary"><?= number_format($totalPosts) ?></div>
            </div>
            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-file-earmark-text"></i>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div>
                <div class="stat-title">Total Reader Views</div>
                <div class="stat-value text-success"><?= number_format($totalViews) ?></div>
            </div>
            <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-eye"></i>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div>
                <div class="stat-title">News Categories</div>
                <div class="stat-value text-warning"><?= number_format($totalCategories) ?></div>
            </div>
            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-tags"></i>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div>
                <div class="stat-title">Active Ad Slots</div>
                <div class="stat-value text-info"><?= number_format($activeAds) ?></div>
            </div>
            <div class="stat-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-badge-ad"></i>
            </div>
        </div>
    </div>
</div>

<!-- Recent Posts Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
            <i class="bi bi-clock-history text-secondary"></i>
            <span>Recently Added Posts</span>
        </h6>
        <a href="post-list.php" class="btn btn-sm btn-outline-secondary">View All Posts</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 70px;">Thumbnail</th>
                    <th>News Title</th>
                    <th>Category</th>
                    <th>Views</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="text-end" style="width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recentPosts)): ?>
                    <?php foreach ($recentPosts as $post): ?>
                        <tr>
                            <td>
                                <?php if (!empty($post['thumbnail'])): ?>
                                    <img src="<?= e($post['thumbnail']) ?>" alt="Thumb" class="rounded object-fit-cover" width="60" height="42">
                                <?php else: ?>
                                    <div class="bg-light text-muted d-flex align-items-center justify-content-center rounded" style="width:60px; height:42px; font-size:10px;">No Image</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="post-edit.php?id=<?= (int)$post['id'] ?>" class="text-dark text-decoration-none fw-medium text-truncate d-inline-block" style="max-width: 380px;">
                                    <?= e($post['title']) ?>
                                </a>
                                <div>
                                    <?php if ($post['is_lead']): ?>
                                        <span class="badge bg-danger" style="font-size:10px;">Lead News</span>
                                    <?php endif; ?>
                                    <?php if ($post['is_breaking']): ?>
                                        <span class="badge bg-warning text-dark" style="font-size:10px;">Breaking</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= e($post['category_name'] ?? 'Uncategorized') ?></span></td>
                            <td><span class="text-muted small"><i class="bi bi-eye me-1"></i><?= number_format((int)$post['views']) ?></span></td>
                            <td>
                                <?php if ($post['status'] === 'published'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Published</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small"><?= date('M d, Y', strtotime($post['created_at'])) ?></td>
                            <td class="text-end">
                                <a href="../single.php?id=<?= (int)$post['id'] ?>" target="_blank" class="btn btn-sm btn-light border text-secondary" title="View Article">
                                    <i class="bi bi-eye"></i>
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
                        <td colspan="7" class="text-center py-4 text-muted">No news articles found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
