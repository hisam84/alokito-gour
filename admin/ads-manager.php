<?php
/**
 * Advertisement Manager
 * Alokito Gour Clone
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';

$pageTitle = 'বিজ্ঞাপন ব্যানার নিয়ন্ত্রণ | আলোকিত গৌড়';
$db = get_db();

// Standard Positions
$positions = [
    'header_right'   => ['label' => 'হেডার ব্যানার (Header Right)', 'size' => '৭২৮x৯০ বা ৩২০x১০০', 'desc' => 'ওয়েবসাইটের মূল লোগোর পাশে ডানে প্রদর্শিত হবে'],
    'after_lead'     => ['label' => 'হোম লিড পরবর্তী ব্যানার (After Lead)', 'size' => '৯৭০x৯০ বা ৭২৮x৯০', 'desc' => 'প্রধান লিড সংবাদের ঠিক নিচে পুরো প্রস্থ জুড়ে প্রদর্শিত হবে'],
    'sidebar'        => ['label' => 'সাইডবার ব্যানার (Sidebar Widget)', 'size' => 'প্রস্থ ফিক্সড (১০০%), উচ্চতা যে কোনো সাইজ', 'desc' => 'ডানপাশের সাইডবারে সবার উপরে প্রদর্শিত হবে। প্রস্থ স্বয়ংক্রিয়ভাবে ১০০% ফিক্সড থাকবে কিন্তু উচ্চতা যে কোনো সাইজ হতে পারবে।'],
    'inside_article' => ['label' => 'সংবাদের ভেতরের ব্যানার (Inside Article)', 'size' => '৭২৮x৯০ বা ফুল রেসপন্সিভ', 'desc' => 'একক সংবাদের প্যারাগ্রাফের মাঝে প্রদর্শিত হবে'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $posKey = $_POST['position'] ?? '';
    if (isset($positions[$posKey])) {
        $title = trim($_POST['title'] ?? '');
        $targetUrl = trim($_POST['target_url'] ?? '');
        $customCode = trim($_POST['custom_code'] ?? '');
        $status = isset($_POST['status']) ? 1 : 0;
        $imagePath = trim($_POST['existing_image'] ?? '');

        // Upload new banner if provided
        if (!empty($_FILES['image']['name'])) {
            $uploaded = upload_image_webp($_FILES['image'], 'assets/uploads/ads/');
            if ($uploaded) {
                $imagePath = $uploaded;
            }
        } elseif (!empty($_POST['custom_image_url'])) {
            $imagePath = trim($_POST['custom_image_url']);
        }

        $checkStmt = $db->prepare("SELECT `id` FROM `advertisements` WHERE `position` = :pos LIMIT 1");
        $checkStmt->execute([':pos' => $posKey]);
        $exists = $checkStmt->fetch();

        if ($exists) {
            $stmt = $db->prepare("
                UPDATE `advertisements` 
                SET `title` = :title, `image_path` = :img, `target_url` = :target, `custom_code` = :code, `status` = :status
                WHERE `position` = :pos
            ");
        } else {
            $stmt = $db->prepare("
                INSERT INTO `advertisements` (`position`, `title`, `image_path`, `target_url`, `custom_code`, `status`)
                VALUES (:pos, :title, :img, :target, :code, :status)
            ");
        }

        $stmt->execute([
            ':pos'    => $posKey,
            ':title'  => $title,
            ':img'    => $imagePath,
            ':target' => $targetUrl,
            ':code'   => $customCode,
            ':status' => $status,
        ]);

        set_flash('success', $positions[$posKey]['label'] . ' সফলভাবে সংরক্ষণ করা হয়েছে।');
        header('Location: ads-manager.php');
        exit;
    }
}

// Fetch current ads
$adsRaw = $db->query("SELECT * FROM `advertisements`")->fetchAll();
$ads = [];
foreach ($adsRaw as $row) {
    $ads[$row['position']] = $row;
}

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">বিজ্ঞাপন ব্যানার ব্যবস্থাপনা</h4>
        <p class="text-muted small mb-0">ওয়েবসাইটের বিভিন্ন গুরুত্বপূর্ণ স্থানের ব্যানার ও গুগল অ্যাডসেন্স কোড পরিচালনা করুন</p>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($positions as $key => $meta): 
        $currentAd = $ads[$key] ?? null;
    ?>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
                    <div>
                        <h6 class="fw-bold text-dark mb-0"><?= e($meta['label']) ?></h6>
                        <span class="text-muted small" style="font-size:11px;">প্রস্তাবিত সাইজ: <?= e($meta['size']) ?></span>
                    </div>
                    <?php if (!empty($currentAd['status'])): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">সক্রিয়</span>
                    <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary border">নিষ্ক্রিয়</span>
                    <?php endif; ?>
                </div>

                <div class="card-body p-4">
                    <p class="text-muted small mb-3"><?= e($meta['desc']) ?></p>

                    <!-- Preview of Current Ad -->
                    <div class="p-3 bg-light rounded border text-center mb-3">
                        <?php if (!empty($currentAd['custom_code'])): ?>
                            <div class="small text-muted mb-1">কাস্টম স্ক্রিপ্ট / কোড প্রিভিউ:</div>
                            <div class="overflow-hidden border p-2 bg-white rounded"><?= $currentAd['custom_code'] ?></div>
                        <?php elseif (!empty($currentAd['image_path'])): ?>
                            <div class="small text-muted mb-1">বর্তমান ব্যানার:</div>
                            <img src="<?= e($currentAd['image_path']) ?>" alt="Banner" class="img-fluid rounded border" style="max-height: 80px;">
                        <?php else: ?>
                            <span class="text-muted small fst-italic">কোনো বিজ্ঞাপন সক্রিয় নেই</span>
                        <?php endif; ?>
                    </div>

                    <form method="POST" action="ads-manager.php" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="position" value="<?= e($key) ?>">
                        <input type="hidden" name="existing_image" value="<?= e($currentAd['image_path'] ?? '') ?>">

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">বিজ্ঞাপনের শিরোনাম / ক্লায়েন্ট</label>
                            <input type="text" class="form-control form-control-sm" name="title" value="<?= e($currentAd['title'] ?? '') ?>" placeholder="বিজ্ঞাপনদাতার নাম">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">ব্যানার ছবি আপলোড</label>
                            <input type="file" class="form-control form-control-sm" name="image" accept="image/*">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">অথবা ইমেজ সরাসরি লিংক</label>
                            <input type="url" class="form-control form-control-sm" name="custom_image_url" placeholder="https://..." value="<?= (!empty($currentAd['image_path']) && str_starts_with($currentAd['image_path'], 'http')) ? e($currentAd['image_path']) : '' ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">ক্লিক টার্গেট লিংক (Target URL)</label>
                            <input type="url" class="form-control form-control-sm" name="target_url" value="<?= e($currentAd['target_url'] ?? '#') ?>" placeholder="https://...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">কাস্টম HTML / Google AdSense কোড</label>
                            <textarea class="form-control form-control-sm font-monospace" name="custom_code" rows="3" placeholder="<script>...</script> বা কাস্টম কোড"><?= e($currentAd['custom_code'] ?? '') ?></textarea>
                            <div class="form-text small" style="font-size:11px;">কাস্টম কোড থাকলে ইমেজ ব্যানারের পরিবর্তে এই কোড কার্যকর হবে।</div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="status_<?= e($key) ?>" name="status" value="1" <?= (!empty($currentAd['status'])) ? 'checked' : '' ?>>
                            <label class="form-check-label small fw-bold text-dark" for="status_<?= e($key) ?>">বিজ্ঞাপনটি সাইটে প্রদর্শন করুন</label>
                        </div>

                        <button type="submit" class="btn btn-warning text-white fw-bold btn-sm w-100">
                            সংরক্ষণ করুন
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
