<?php
/**
 * Edit Post
 * Alokito Gour Clone
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';

$pageTitle = 'সংবাদ সম্পাদনা | আলোকিত গৌড়';
$db = get_db();
$error = '';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: post-list.php');
    exit;
}

// Fetch existing post
$stmt = $db->prepare("SELECT * FROM `posts` WHERE `id` = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$post = $stmt->fetch();

if (!$post) {
    set_flash('danger', 'অনুরোধকৃত সংবাদটি পাওয়া যায়নি!');
    header('Location: post-list.php');
    exit;
}

// Fetch categories for dropdown
$categories = $db->query("SELECT * FROM `categories` ORDER BY `order_index` ASC, `name` ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $title = trim($_POST['title'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $reporterName = trim($_POST['reporter_name'] ?? 'নিজস্ব প্রতিবেদক');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $caption = trim($_POST['caption'] ?? '');
    $isLead = isset($_POST['is_lead']) ? 1 : 0;
    $isBreaking = isset($_POST['is_breaking']) ? 1 : 0;
    $status = in_array($_POST['status'] ?? '', ['published', 'draft'], true) ? $_POST['status'] : 'published';
    $customImageUrl = trim($_POST['custom_image_url'] ?? '');

    if (empty($title)) {
        $error = 'সংবাদের শিরোনাম প্রদান করা বাধ্যতামূলক।';
    } elseif ($categoryId <= 0) {
        $error = 'অনুগ্রহ করে সংবাদের একটি ক্যাটাগরি নির্বাচন করুন।';
    } elseif (empty($content)) {
        $error = 'সংবাদের বিস্তারিত বিবরণ লেখা বাধ্যতামূলক।';
    } else {
        $thumbnailPath = $post['thumbnail'];

        // If new image file uploaded
        if (!empty($_FILES['thumbnail']['name'])) {
            $uploaded = upload_image_webp($_FILES['thumbnail'], 'assets/uploads/news/');
            if ($uploaded) {
                $thumbnailPath = $uploaded;
            }
        } elseif (!empty($customImageUrl)) {
            $thumbnailPath = $customImageUrl;
        }

        // Auto excerpt if empty
        if (empty($excerpt)) {
            $plainText = strip_tags($content);
            $excerpt = mb_substr($plainText, 0, 180, 'UTF-8') . (mb_strlen($plainText, 'UTF-8') > 180 ? '...' : '');
        }

        try {
            $upStmt = $db->prepare("
                UPDATE `posts` SET 
                    `category_id` = :cat,
                    `title` = :title,
                    `excerpt` = :excerpt,
                    `content` = :content,
                    `thumbnail` = :thumb,
                    `caption` = :caption,
                    `reporter_name` = :reporter,
                    `is_lead` = :is_lead,
                    `is_breaking` = :is_breaking,
                    `status` = :status
                WHERE `id` = :id
            ");
            $upStmt->execute([
                ':cat'         => $categoryId,
                ':title'       => $title,
                ':excerpt'     => $excerpt,
                ':content'     => $content,
                ':thumb'       => $thumbnailPath,
                ':caption'     => $caption,
                ':reporter'    => $reporterName,
                ':is_lead'     => $isLead,
                ':is_breaking' => $isBreaking,
                ':status'      => $status,
                ':id'          => $id,
            ]);

            set_flash('success', 'সংবাদটি সফলভাবে হালনাগাদ করা হয়েছে!');
            header('Location: post-list.php');
            exit;
        } catch (Exception $e) {
            $error = 'ডাটাবেস সংরক্ষণ ত্রুটি: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h4 class="fw-bold mb-1 text-dark">সংবাদ সম্পাদনা</h4>
                <p class="text-muted small mb-0">আইডি: #<?= bn_number($post['id']) ?> &bull; <?= e($post['title']) ?></p>
            </div>
            <a href="post-list.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> তালিকায় ফিরে যান
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 px-3 mb-3">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="post-edit.php?id=<?= (int)$post['id'] ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="row g-3">
                <!-- Main Content Column -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm p-4 mb-3">
                        <div class="mb-3">
                            <label for="title" class="form-label fw-bold text-dark">সংবাদের প্রধান শিরোনাম <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg fs-6" id="title" name="title" value="<?= e($post['title']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="excerpt" class="form-label fw-bold text-dark">সংক্ষিপ্ত সারসংক্ষেপ (Excerpt)</label>
                            <textarea class="form-control" id="excerpt" name="excerpt" rows="3"><?= e($post['excerpt'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-0">
                            <label for="summernote" class="form-label fw-bold text-dark">সংবাদের পূর্ণ বিবরণ <span class="text-danger">*</span></label>
                            <textarea id="summernote" name="content"><?= $post['content'] ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Meta & Attributes Sidebar -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm p-3 mb-3">
                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">প্রকাশনা তথ্য</h6>

                        <div class="mb-3">
                            <label for="category_id" class="form-label small fw-bold text-secondary">ক্যাটাগরি <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int)$cat['id'] ?>" <?= ((int)$post['category_id'] === (int)$cat['id']) ? 'selected' : '' ?>>
                                        <?= e($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="reporter_name" class="form-label small fw-bold text-secondary">প্রতিবেদকের নাম / বাইলাইন</label>
                            <input type="text" class="form-control" id="reporter_name" name="reporter_name" value="<?= e($post['reporter_name'] ?? 'নিজস্ব প্রতিবেদক') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label small fw-bold text-secondary">প্রকাশনা স্ট্যাটাস</label>
                            <select class="form-select" id="status" name="status">
                                <option value="published" <?= ($post['status'] === 'published') ? 'selected' : '' ?>>প্রকাশিত (Published)</option>
                                <option value="draft" <?= ($post['status'] === 'draft') ? 'selected' : '' ?>>খসড়া (Draft)</option>
                            </select>
                        </div>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_lead" name="is_lead" value="1" <?= $post['is_lead'] ? 'checked' : '' ?>>
                            <label class="form-check-label small fw-bold text-dark" for="is_lead">প্রধান লিড নিউজ</label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_breaking" name="is_breaking" value="1" <?= $post['is_breaking'] ? 'checked' : '' ?>>
                            <label class="form-check-label small fw-bold text-dark" for="is_breaking">ব্রেকিং নিউজ টিকার</label>
                        </div>

                        <hr class="my-3">

                        <h6 class="fw-bold text-dark mb-2">ফিচার্ড ছবি (থাম্বনেইল)</h6>
                        
                        <?php if (!empty($post['thumbnail'])): ?>
                            <div class="mb-2 text-center">
                                <img src="<?= e($post['thumbnail']) ?>" alt="Current Thumb" class="img-fluid rounded border" style="max-height: 120px;">
                                <div class="small text-muted mt-1">বর্তমান ছবি</div>
                            </div>
                        <?php endif; ?>

                        <div class="mb-2">
                            <label for="thumbnail" class="form-label small text-secondary">নতুন ছবি আপলোড করুন</label>
                            <input type="file" class="form-control image-preview-input" id="thumbnail" name="thumbnail" accept="image/*" data-preview="thumbPreview">
                        </div>

                        <div class="mb-2">
                            <label for="custom_image_url" class="form-label small text-secondary">অথবা ছবির ওয়েব লিংক</label>
                            <input type="url" class="form-control form-control-sm" id="custom_image_url" name="custom_image_url" placeholder="https://...">
                        </div>

                        <div class="mb-3">
                            <label for="caption" class="form-label small text-secondary">ছবির ক্যাপশন</label>
                            <input type="text" class="form-control form-control-sm" id="caption" name="caption" value="<?= e($post['caption'] ?? '') ?>">
                        </div>

                        <div class="mb-3 text-center">
                            <img id="thumbPreview" src="" alt="Preview" class="img-fluid rounded border" style="max-height: 140px; display: none;">
                        </div>

                        <button type="submit" class="btn btn-warning text-white fw-bold w-100 py-2 shadow-sm">
                            <i class="bi bi-save me-1"></i> পরিবর্তন সংরক্ষণ করুন
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
