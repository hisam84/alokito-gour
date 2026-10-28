<?php
/**
 * Category Management
 * Alokito Gour Clone
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';

$pageTitle = 'Category Management | Alokito Gour';
$db = get_db();
$error = '';
$editCategory = null;

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $token = $_GET['csrf_token'] ?? '';
    if (hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $delId = (int)$_GET['id'];
        $delStmt = $db->prepare("DELETE FROM `categories` WHERE `id` = :id");
        $delStmt->execute([':id' => $delId]);
        set_flash('success', 'Category deleted successfully.');
    } else {
        set_flash('danger', 'Security token is invalid!');
    }
    header('Location: categories.php');
    exit;
}

// Check for Edit Mode
if (isset($_GET['edit']) && (int)$_GET['edit'] > 0) {
    $editId = (int)$_GET['edit'];
    $eStmt = $db->prepare("SELECT * FROM `categories` WHERE `id` = :id LIMIT 1");
    $eStmt->execute([':id' => $editId]);
    $editCategory = $eStmt->fetch();
}

// Handle Form Submission (Add or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $orderIndex = (int)($_POST['order_index'] ?? 0);
    $showInMenu = isset($_POST['show_in_menu']) ? 1 : 0;
    $categoryId = (int)($_POST['category_id'] ?? 0);

    if (empty($name)) {
        $error = 'Category name is required.';
    } else {
        if (empty($slug)) {
            $slug = slugify($name);
        } else {
            $slug = slugify($slug);
        }

        try {
            if ($categoryId > 0) {
                // Update
                $upStmt = $db->prepare("
                    UPDATE `categories` 
                    SET `name` = :name, `slug` = :slug, `order_index` = :ord, `show_in_menu` = :menu 
                    WHERE `id` = :id
                ");
                $upStmt->execute([
                    ':name' => $name,
                    ':slug' => $slug,
                    ':ord'  => $orderIndex,
                    ':menu' => $showInMenu,
                    ':id'   => $categoryId
                ]);
                set_flash('success', 'Category updated successfully.');
            } else {
                // Insert
                $insStmt = $db->prepare("
                    INSERT INTO `categories` (`name`, `slug`, `order_index`, `show_in_menu`) 
                    VALUES (:name, :slug, :ord, :menu)
                ");
                $insStmt->execute([
                    ':name' => $name,
                    ':slug' => $slug,
                    ':ord'  => $orderIndex,
                    ':menu' => $showInMenu
                ]);
                set_flash('success', 'New category created successfully.');
            }
            header('Location: categories.php');
            exit;
        } catch (Exception $e) {
            $error = 'Error: Category name or slug might already exist.';
        }
    }
}

// Fetch all categories
$categories = $db->query("
    SELECT c.*, COUNT(p.id) AS post_count
    FROM `categories` c
    LEFT JOIN `posts` p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY c.order_index ASC, c.id ASC
")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Category Management</h4>
        <p class="text-muted small mb-0">Manage portal news categories and main navigation menu order</p>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 px-3 mb-3">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Category Form (Left) -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm p-3">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                <?= $editCategory ? 'Edit Category' : 'Add New Category' ?>
            </h6>

            <form method="POST" action="categories.php">
                <?= csrf_field() ?>
                <?php if ($editCategory): ?>
                    <input type="hidden" name="category_id" value="<?= (int)$editCategory['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="name" class="form-label small fw-bold text-secondary">Category Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= e($editCategory['name'] ?? '') ?>" placeholder="e.g. Politics, Sports, National" required>
                </div>

                <div class="mb-3">
                    <label for="slug" class="form-label small fw-bold text-secondary">URL Slug</label>
                    <input type="text" class="form-control" id="slug" name="slug" value="<?= e($editCategory['slug'] ?? '') ?>" placeholder="Leave empty to auto-generate">
                    <div class="form-text small" style="font-size:11px;">e.g., /category/politics</div>
                </div>

                <div class="mb-3">
                    <label for="order_index" class="form-label small fw-bold text-secondary">Display Order (Order Index)</label>
                    <input type="number" class="form-control" id="order_index" name="order_index" value="<?= (int)($editCategory['order_index'] ?? 0) ?>">
                    <div class="form-text small" style="font-size:11px;">Smaller numbers appear earlier in the menu</div>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="show_in_menu" name="show_in_menu" value="1" <?= (!$editCategory || !empty($editCategory['show_in_menu'])) ? 'checked' : '' ?>>
                    <label class="form-check-label small fw-bold text-dark" for="show_in_menu">Display in Main Menu</label>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-warning text-white fw-bold w-100">
                        <?= $editCategory ? 'Update Category' : 'Save Category' ?>
                    </button>
                    <?php if ($editCategory): ?>
                        <a href="categories.php" class="btn btn-outline-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Category Table (Right) -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">Order</th>
                            <th>Category Name</th>
                            <th>Slug</th>
                            <th>Posts Count</th>
                            <th>Menu Status</th>
                            <th class="text-end" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><span class="badge bg-light text-dark border"><?= (int)$cat['order_index'] ?></span></td>
                                    <td>
                                        <span class="fw-bold text-dark"><?= e($cat['name']) ?></span>
                                    </td>
                                    <td><code class="small text-muted">/category/<?= e($cat['slug']) ?></code></td>
                                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= (int)$cat['post_count'] ?></span></td>
                                    <td>
                                        <?php if ($cat['show_in_menu']): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Visible in Menu</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">Hidden</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="categories.php?edit=<?= (int)$cat['id'] ?>" class="btn btn-sm btn-light border text-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="categories.php?action=delete&id=<?= (int)$cat['id'] ?>&csrf_token=<?= e(csrf_token()) ?>" class="btn btn-sm btn-light border text-danger btn-confirm-delete" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No categories created yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
