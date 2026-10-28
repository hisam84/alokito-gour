<?php
/**
 * Category Management
 * Alokito Gour Clone
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';

$pageTitle = 'ক্যাটাগরি ব্যবস্থাপনা | আলোকিত গৌড়';
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
        set_flash('success', 'ক্যাটাগরি সফলভাবে মুছে ফেলা হয়েছে।');
    } else {
        set_flash('danger', 'নিরাপত্তা টোকেন সঠিক নয়!');
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
        $error = 'ক্যাটাগরির নাম প্রদান করা আবশ্যক।';
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
                set_flash('success', 'ক্যাটাগরি সফলভাবে আপডেট করা হয়েছে।');
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
                set_flash('success', 'নতুন ক্যাটাগরি সফলভাবে তৈরি করা হয়েছে।');
            }
            header('Location: categories.php');
            exit;
        } catch (Exception $e) {
            $error = 'ত্রুটি: ক্যাটাগরি বা স্ল্যাগটি ইতোমধ্যে ব্যবহৃত হয়ে থাকতে পারে।';
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
        <h4 class="fw-bold mb-1 text-dark">ক্যাটাগরি ব্যবস্থাপনা</h4>
        <p class="text-muted small mb-0">পোর্টালের বিষয়ভিত্তিক ক্যাটাগরি এবং মেনু নেভিগেশন ক্রম নিয়ন্ত্রণ করুন</p>
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
                <?= $editCategory ? 'ক্যাটাগরি সম্পাদন করুন' : 'নতুন ক্যাটাগরি যোগ করুন' ?>
            </h6>

            <form method="POST" action="categories.php">
                <?= csrf_field() ?>
                <?php if ($editCategory): ?>
                    <input type="hidden" name="category_id" value="<?= (int)$editCategory['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="name" class="form-label small fw-bold text-secondary">ক্যাটাগরির নাম <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= e($editCategory['name'] ?? '') ?>" placeholder="যেমন: চাঁপাইনবাবগঞ্জ বা রাজনীতি" required>
                </div>

                <div class="mb-3">
                    <label for="slug" class="form-label small fw-bold text-secondary">ইউআরএল স্ল্যাগ (Slug)</label>
                    <input type="text" class="form-control" id="slug" name="slug" value="<?= e($editCategory['slug'] ?? '') ?>" placeholder="খালি রাখলে স্বয়ংক্রিয় তৈরি হবে">
                    <div class="form-text small" style="font-size:11px;">যেমন: /category/chapai-nawabganj</div>
                </div>

                <div class="mb-3">
                    <label for="order_index" class="form-label small fw-bold text-secondary">প্রদর্শন ক্রম (Order Index)</label>
                    <input type="number" class="form-control" id="order_index" name="order_index" value="<?= (int)($editCategory['order_index'] ?? 0) ?>">
                    <div class="form-text small" style="font-size:11px;">ছোট সংখ্যাগুলো মেনুতে আগে প্রদর্শিত হবে</div>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="show_in_menu" name="show_in_menu" value="1" <?= (!$editCategory || !empty($editCategory['show_in_menu'])) ? 'checked' : '' ?>>
                    <label class="form-check-label small fw-bold text-dark" for="show_in_menu">প্রধান মেনুবারে প্রদর্শন করুন</label>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-warning text-white fw-bold w-100">
                        <?= $editCategory ? 'আপডেট করুন' : 'ক্যাটাগরি সংরক্ষণ' ?>
                    </button>
                    <?php if ($editCategory): ?>
                        <a href="categories.php" class="btn btn-outline-secondary">বাতিল</a>
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
                            <th style="width: 50px;">ক্রম</th>
                            <th>ক্যাটাগরির নাম</th>
                            <th>স্ল্যাগ</th>
                            <th>সংবাদ সংখ্যা</th>
                            <th>মেনু স্ট্যাটাস</th>
                            <th class="text-end" style="width: 120px;">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><span class="badge bg-light text-dark border"><?= bn_number($cat['order_index']) ?></span></td>
                                    <td>
                                        <span class="fw-bold text-dark"><?= e($cat['name']) ?></span>
                                    </td>
                                    <td><code class="small text-muted">/category/<?= e($cat['slug']) ?></code></td>
                                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= bn_number($cat['post_count']) ?> টি</span></td>
                                    <td>
                                        <?php if ($cat['show_in_menu']): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">মেনুতে দৃশ্যমান</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">লুকায়িত</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="categories.php?edit=<?= (int)$cat['id'] ?>" class="btn btn-sm btn-light border text-primary" title="সম্পাদনা">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="categories.php?action=delete&id=<?= (int)$cat['id'] ?>&csrf_token=<?= e(csrf_token()) ?>" class="btn btn-sm btn-light border text-danger btn-confirm-delete" title="মুছে ফেলুন">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">কোনো ক্যাটাগরি তৈরি করা হয়নি।</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
