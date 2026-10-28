<?php
/**
 * Site Settings & Identity Management
 * Alokito Gour Clone
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';

$pageTitle = 'সাইট পরিচিতি ও সেটিংস | আলোকিত গৌড়';
$db = get_db();
$settings = get_settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $siteTitle = trim($_POST['site_title'] ?? '');
    $siteTagline = trim($_POST['site_tagline'] ?? '');
    $editorName = trim($_POST['editor_name'] ?? '');
    $publisherName = trim($_POST['publisher_name'] ?? '');
    $officeAddress = trim($_POST['office_address'] ?? '');
    $contactPhone = trim($_POST['contact_phone'] ?? '');
    $contactEmail = trim($_POST['contact_email'] ?? '');
    $facebookUrl = trim($_POST['facebook_url'] ?? '');
    $youtubeUrl = trim($_POST['youtube_url'] ?? '');

    $logoPath = $settings['logo_path'] ?? '';
    $faviconPath = $settings['favicon_path'] ?? '';

    // Handle Logo Upload
    if (!empty($_FILES['logo']['name'])) {
        $uploaded = upload_image_webp($_FILES['logo'], 'assets/uploads/');
        if ($uploaded) {
            $logoPath = $uploaded;
        }
    } elseif (!empty($_POST['custom_logo_url'])) {
        $logoPath = trim($_POST['custom_logo_url']);
    }

    // Handle Favicon Upload
    if (!empty($_FILES['favicon']['name'])) {
        $uploaded = upload_image_webp($_FILES['favicon'], 'assets/uploads/');
        if ($uploaded) {
            $faviconPath = $uploaded;
        }
    } elseif (!empty($_POST['custom_favicon_url'])) {
        $faviconPath = trim($_POST['custom_favicon_url']);
    }

    try {
        $stmt = $db->prepare("
            UPDATE `settings` SET 
                `site_title` = :stitle,
                `site_tagline` = :stagline,
                `logo_path` = :logo,
                `favicon_path` = :fav,
                `editor_name` = :ed,
                `publisher_name` = :pub,
                `office_address` = :addr,
                `contact_phone` = :phone,
                `contact_email` = :email,
                `facebook_url` = :fb,
                `youtube_url` = :yt
            WHERE `id` = 1
        ");
        $stmt->execute([
            ':stitle'   => $siteTitle,
            ':stagline' => $siteTagline,
            ':logo'     => $logoPath,
            ':fav'      => $faviconPath,
            ':ed'       => $editorName,
            ':pub'      => $publisherName,
            ':addr'     => $officeAddress,
            ':phone'    => $contactPhone,
            ':email'    => $contactEmail,
            ':fb'       => $facebookUrl,
            ':yt'       => $youtubeUrl,
        ]);

        set_flash('success', 'সাইট সেটিংস সফলভাবে আপডেট করা হয়েছে!');
        header('Location: settings.php');
        exit;
    } catch (Exception $e) {
        set_flash('danger', 'সংরক্ষণ ত্রুটি: ' . $e->getMessage());
    }
}

// Reload updated settings
$settings = get_settings();

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">সাইট পরিচিতি ও সেটিংস</h4>
        <p class="text-muted small mb-0">পোর্টালের লোগো, স্লোগান, সম্পাদকীয় তথ্য, ঠিকানা ও সোশ্যাল মিডিয়া সংযোগ</p>
    </div>
</div>

<form method="POST" action="settings.php" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="row g-4">
        <!-- Site Branding Column -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="bi bi-globe me-2 text-warning"></i>পোর্টাল পরিচিতি ও ব্র্যান্ডিং
                </h6>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">সাইটের নাম / টাইটেল</label>
                    <input type="text" class="form-control" name="site_title" value="<?= e($settings['site_title'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">ট্যাগলাইন / স্লোগান</label>
                    <input type="text" class="form-control" name="site_tagline" value="<?= e($settings['site_tagline'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">বর্তমান হেডার লোগো</label>
                    <div class="p-2 border rounded bg-light text-center mb-2">
                        <?php if (!empty($settings['logo_path'])): ?>
                            <img src="<?= e($settings['logo_path']) ?>" alt="Logo" class="img-fluid" style="max-height: 70px;">
                        <?php else: ?>
                            <span class="text-muted small">কোনো লোগো নেই</span>
                        <?php endif; ?>
                    </div>
                    <input type="file" class="form-control form-control-sm mb-2" name="logo" accept="image/*">
                    <input type="url" class="form-control form-control-sm" name="custom_logo_url" placeholder="বা লোগোর ওয়েব লিংক" value="<?= (str_starts_with($settings['logo_path'] ?? '', 'http')) ? e($settings['logo_path']) : '' ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">বর্তমান ফ্যাভিকন (Favicon)</label>
                    <div class="p-2 border rounded bg-light text-center mb-2">
                        <?php if (!empty($settings['favicon_path'])): ?>
                            <img src="<?= e($settings['favicon_path']) ?>" alt="Favicon" width="32" height="32">
                        <?php else: ?>
                            <span class="text-muted small">কোনো ফ্যাভিকন নেই</span>
                        <?php endif; ?>
                    </div>
                    <input type="file" class="form-control form-control-sm mb-2" name="favicon" accept="image/*">
                    <input type="url" class="form-control form-control-sm" name="custom_favicon_url" placeholder="বা ফ্যাভিকনের ওয়েব লিংক" value="<?= (str_starts_with($settings['favicon_path'] ?? '', 'http')) ? e($settings['favicon_path']) : '' ?>">
                </div>
            </div>

            <!-- Social Links -->
            <div class="card border-0 shadow-sm p-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="bi bi-share me-2 text-warning"></i>সোশ্যাল মিডিয়া প্রোফাইল লিংক
                </h6>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">ফেসবুক পেজ (Facebook URL)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-primary"><i class="bi bi-facebook"></i></span>
                        <input type="url" class="form-control" name="facebook_url" value="<?= e($settings['facebook_url'] ?? '') ?>" placeholder="https://facebook.com/...">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">ইউটিউব চ্যানেল (YouTube URL)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-danger"><i class="bi bi-youtube"></i></span>
                        <input type="url" class="form-control" name="youtube_url" value="<?= e($settings['youtube_url'] ?? '') ?>" placeholder="https://youtube.com/...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Editorial & Office Column -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="bi bi-person-badge me-2 text-warning"></i>সম্পাদকীয় ও যোগাযোগ তথ্য
                </h6>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">প্রধান সম্পাদক</label>
                    <input type="text" class="form-control" name="editor_name" value="<?= e($settings['editor_name'] ?? '') ?>" placeholder="যেমন: মোহাঃ আব্দুল্লাহ">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">প্রকাশকের নাম</label>
                    <input type="text" class="form-control" name="publisher_name" value="<?= e($settings['publisher_name'] ?? '') ?>" placeholder="যেমন: মোহাঃ আব্দুল্লাহ">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">অফিসের ঠিকানা</label>
                    <textarea class="form-control" name="office_address" rows="3"><?= e($settings['office_address'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">যোগাযোগের ফোন নম্বর (Phone Numbers)</label>
                    <input type="text" class="form-control" name="contact_phone" value="<?= e($settings['contact_phone'] ?? '') ?>" placeholder="সম্পাদক, অফিস ও বিজ্ঞাপন ফোন নম্বর">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">যোগাযোগের ইমেইল (Contact Email)</label>
                    <input type="email" class="form-control" name="contact_email" value="<?= e($settings['contact_email'] ?? '') ?>" placeholder="editor@alokitogour.com">
                </div>
            </div>

            <button type="submit" class="btn btn-warning text-white fw-bold py-2 w-100 shadow-sm">
                <i class="bi bi-save me-1"></i> সমস্ত সেটিংস সংরক্ষণ করুন
            </button>
        </div>
    </div>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
