<?php
/**
 * Add New Post
 * Alokito Gour Clone
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';
require_once dirname(__DIR__) . '/includes/bangladesh_geo.php';

$pageTitle = 'Publish New Post | Alokito Gour';
$db = get_db();
$settings = get_settings();
$error = '';

$geoTree = get_bangladesh_geo();

// Fetch all categories for dropdown
$categories = $db->query("SELECT * FROM `categories` ORDER BY `order_index` ASC, `name` ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $title = trim($_POST['title'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $reporterName = trim($_POST['reporter_name'] ?? 'Staff Reporter');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $keywords = trim($_POST['keywords'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $caption = trim($_POST['caption'] ?? '');
    $isLead = isset($_POST['is_lead']) ? 1 : 0;
    $isBreaking = isset($_POST['is_breaking']) ? 1 : 0;
    $status = in_array($_POST['status'] ?? '', ['published', 'draft'], true) ? $_POST['status'] : 'published';
    $customImageUrl = trim($_POST['custom_image_url'] ?? '');

    // Geographic Location (Tree)
    $division = trim($_POST['division'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $upazila = trim($_POST['upazila'] ?? '');

    if (empty($title)) {
        $error = 'Article headline is required.';
    } elseif ($categoryId <= 0) {
        $error = 'Please select a category for this article.';
    } elseif (empty($content)) {
        $error = 'Full article content is required.';
    } else {
        // Generate Unique Slug
        $baseSlug = slugify($title);
        $slug = $baseSlug;
        $counter = 1;

        $checkStmt = $db->prepare("SELECT COUNT(*) FROM `posts` WHERE `slug` = :slug");
        do {
            $checkStmt->execute([':slug' => $slug]);
            if ((int)$checkStmt->fetchColumn() === 0) break;
            $slug = $baseSlug . '-' . $counter++;
        } while (true);

        // Process Thumbnail Image
        $thumbnailPath = null;
        if (!empty($_FILES['thumbnail']['name'])) {
            $uploaded = upload_image_webp($_FILES['thumbnail'], 'assets/uploads/news/');
            if ($uploaded) {
                $thumbnailPath = $uploaded;
            }
        }
        
        // If file not uploaded but custom URL provided
        if (!$thumbnailPath && !empty($customImageUrl)) {
            $thumbnailPath = $customImageUrl;
        }

        // Auto generate excerpt if left empty
        if (empty($excerpt)) {
            $plainText = strip_tags($content);
            $excerpt = mb_substr($plainText, 0, 180, 'UTF-8') . (mb_strlen($plainText, 'UTF-8') > 180 ? '...' : '');
        }

        // Process Sidebar Custom Ad
        $sidebarAdType = in_array($_POST['sidebar_ad_type'] ?? '', ['global', 'custom', 'code', 'none'], true) ? $_POST['sidebar_ad_type'] : 'global';
        $sidebarAdUrl = trim($_POST['sidebar_ad_url'] ?? '');
        $sidebarAdCode = trim($_POST['sidebar_ad_code'] ?? '');
        $sidebarAdImage = null;

        if (!empty($_FILES['sidebar_ad_file']['name'])) {
            $uploadedAd = upload_image_webp($_FILES['sidebar_ad_file'], 'assets/uploads/ads/');
            if ($uploadedAd) {
                $sidebarAdImage = $uploadedAd;
            }
        } elseif (!empty($_POST['sidebar_ad_image_url'])) {
            $sidebarAdImage = trim($_POST['sidebar_ad_image_url']);
        }

        $userId = $_SESSION['admin_user_id'] ?? 1;

        try {
            $stmt = $db->prepare("
                INSERT INTO `posts` 
                (`category_id`, `user_id`, `title`, `slug`, `excerpt`, `keywords`, `content`, `thumbnail`, `caption`, `reporter_name`, `is_lead`, `is_breaking`, `status`, `sidebar_ad_type`, `sidebar_ad_image`, `sidebar_ad_url`, `sidebar_ad_code`, `division`, `district`, `upazila`, `views`) 
                VALUES 
                (:cat, :uid, :title, :slug, :excerpt, :keywords, :content, :thumb, :caption, :reporter, :is_lead, :is_breaking, :status, :ad_type, :ad_img, :ad_url, :ad_code, :division, :district, :upazila, 0)
            ");
            $stmt->execute([
                ':cat'         => $categoryId,
                ':uid'         => $userId,
                ':title'       => $title,
                ':slug'        => $slug,
                ':excerpt'     => $excerpt,
                ':keywords'    => $keywords,
                ':content'     => $content,
                ':thumb'       => $thumbnailPath,
                ':caption'     => $caption,
                ':reporter'    => $reporterName,
                ':is_lead'     => $isLead,
                ':is_breaking' => $isBreaking,
                ':status'      => $status,
                ':ad_type'     => $sidebarAdType,
                ':ad_img'      => $sidebarAdImage,
                ':ad_url'      => $sidebarAdUrl,
                ':ad_code'     => $sidebarAdCode,
                ':division'    => $division ?: null,
                ':district'    => $district ?: null,
                ':upazila'     => $upazila ?: null,
            ]);

            set_flash('success', 'Article published and saved successfully!');
            header('Location: post-list.php');
            exit;
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h4 class="fw-bold mb-1 text-dark">Compose New Article</h4>
                <p class="text-muted small mb-0">Publish news with rich-text editor, geographic location, image optimization & AI auto-generator</p>
            </div>
            <a href="post-list.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to All Posts
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 px-3 mb-3">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="post-add.php" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="row g-4">
                <!-- Main Form Column (8) -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm p-4 mb-4">
                        <!-- Headline with AI Auto-Generator Button -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label for="title" class="form-label fw-bold text-dark mb-0">
                                    Article Headline <span class="text-danger">*</span>
                                </label>
                                <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-bold d-inline-flex align-items-center gap-1 shadow-sm" id="btnAutoGenerate" onclick="autoGenerateTitleAndKeywords()" title="Auto-generate catchy headline, keywords & excerpt from full content">
                                    <i class="bi bi-stars text-warning"></i>
                                    <span>AI Auto-Generate</span>
                                </button>
                            </div>
                            <input type="text" class="form-control form-control-lg fw-bold" id="title" name="title" placeholder="Enter news headline..." value="<?= e($_POST['title'] ?? '') ?>" required>
                            <div id="auto_gen_feedback_area"></div>
                        </div>

                        <!-- Keywords / Tags -->
                        <div class="mb-3">
                            <label for="keywords" class="form-label fw-bold small text-secondary">Keywords / Tags (Comma-separated)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-tags"></i></span>
                                <input type="text" class="form-control" id="keywords" name="keywords" placeholder="e.g. চাঁপাইনবাবগঞ্জ, কৃষি, আম, অর্থনীতি" value="<?= e($_POST['keywords'] ?? '') ?>">
                            </div>
                            <div class="form-text small" style="font-size:11px;">Comma-separated keywords improve searchability and SEO relevance.</div>
                        </div>

                        <!-- Short Excerpt -->
                        <div class="mb-3">
                            <label for="excerpt" class="form-label fw-bold small text-secondary">Short Excerpt / Summary</label>
                            <textarea class="form-control" id="excerpt" name="excerpt" rows="2" placeholder="Brief 1-2 sentence lead summary..."><?= e($_POST['excerpt'] ?? '') ?></textarea>
                            <div class="form-text small" style="font-size:11px;">If left empty, an excerpt will be generated automatically from the content.</div>
                        </div>

                        <!-- WYSIWYG Content Body -->
                        <div class="mb-3">
                            <label for="summernote" class="form-label fw-bold text-dark">
                                Full News Content <span class="text-danger">*</span>
                            </label>
                            <textarea id="summernote" name="content"><?= $_POST['content'] ?? '' ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar Settings Column (4) -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm p-3 mb-4">
                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Publish Settings</h6>

                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label small fw-bold text-secondary">Publication Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="published" <?= (($_POST['status'] ?? 'published') === 'published') ? 'selected' : '' ?>>Published (Live Immediately)</option>
                                <option value="draft" <?= (($_POST['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Save as Draft</option>
                            </select>
                        </div>

                        <!-- Category -->
                        <div class="mb-3">
                            <label for="category_id" class="form-label small fw-bold text-secondary">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int)$cat['id'] ?>" <?= ((int)($_POST['category_id'] ?? 0) === (int)$cat['id']) ? 'selected' : '' ?>>
                                        <?= e($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Reporter Name -->
                        <div class="mb-3">
                            <label for="reporter_name" class="form-label small fw-bold text-secondary">Reporter Name / Source</label>
                            <input type="text" class="form-control" id="reporter_name" name="reporter_name" value="<?= e($_POST['reporter_name'] ?? 'Staff Reporter') ?>" placeholder="e.g. Staff Reporter, Chapainawabganj">
                        </div>

                        <!-- Flags -->
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_lead" name="is_lead" value="1" <?= !empty($_POST['is_lead']) ? 'checked' : '' ?>>
                            <label class="form-check-label small fw-bold text-dark" for="is_lead">Lead Story (Top Headlines)</label>
                            <div class="form-text small" style="font-size:11px;">Features prominently in the hero lead box on the homepage</div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_breaking" name="is_breaking" value="1" <?= !empty($_POST['is_breaking']) ? 'checked' : '' ?>>
                            <label class="form-check-label small fw-bold text-dark" for="is_breaking">Breaking News Ticker</label>
                            <div class="form-text small" style="font-size:11px;">Appears in the live scrolling breaking headlines bar</div>
                        </div>

                        <hr class="my-3">

                        <!-- Geographic Location Selection (Tree: Division -> District -> Upazila) -->
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fw-bold text-dark mb-0">
                                <i class="bi bi-geo-alt-fill me-1 text-danger"></i>Location (এলাকা / অঞ্চল)
                            </h6>
                            <button type="button" class="btn btn-link text-decoration-none p-0 text-muted small" onclick="clearLocationSelection()" style="font-size: 11px;">Clear</button>
                        </div>
                        <p class="text-muted small mb-2" style="font-size:11px;">Select news location in hierarchical tree format.</p>

                        <!-- 1. Division Select -->
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-secondary mb-1">বিভাগ (Division)</label>
                            <select class="form-select form-select-sm" name="division" id="division_select" onchange="onDivisionChange()">
                                <option value="">-- বিভাগ নির্বাচন করুন --</option>
                                <?php foreach ($geoTree as $divName => $districts): ?>
                                    <option value="<?= e($divName) ?>" <?= (($_POST['division'] ?? '') === $divName) ? 'selected' : '' ?>><?= e($divName) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- 2. District Select -->
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-secondary mb-1">জেলা (District)</label>
                            <select class="form-select form-select-sm" name="district" id="district_select" onchange="onDistrictChange()" disabled>
                                <option value="">-- প্রথমে বিভাগ নির্বাচন করুন --</option>
                            </select>
                        </div>

                        <!-- 3. Upazila Select -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary mb-1">উপজেলা / থানা (Upazila)</label>
                            <select class="form-select form-select-sm" name="upazila" id="upazila_select" disabled>
                                <option value="">-- প্রথমে জেলা নির্বাচন করুন --</option>
                            </select>
                        </div>

                        <hr class="my-3">

                        <h6 class="fw-bold text-dark mb-2">Featured Image (Thumbnail)</h6>
                        <div class="mb-2">
                            <input type="file" class="form-control image-preview-input" id="thumbnail" name="thumbnail" accept="image/*" data-preview="thumbPreview">
                            <div class="form-text small" style="font-size:11px;">Automatically compressed and optimized to WebP format</div>
                        </div>

                        <div class="mb-2">
                            <label for="custom_image_url" class="form-label small text-secondary">Or Direct Image Web URL</label>
                            <input type="url" class="form-control form-control-sm" id="custom_image_url" name="custom_image_url" placeholder="https://..." value="<?= e($_POST['custom_image_url'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="caption" class="form-label small text-secondary">Image Caption / Credit</label>
                            <input type="text" class="form-control form-control-sm" id="caption" name="caption" placeholder="Photo caption or source credit" value="<?= e($_POST['caption'] ?? '') ?>">
                        </div>

                        <div class="mb-3 text-center">
                            <img id="thumbPreview" src="" alt="Preview" class="img-fluid rounded border" style="max-height: 140px; display: none;">
                        </div>

                        <hr class="my-3">

                        <!-- Right Sidebar Ad Widget Configuration -->
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fw-bold text-dark mb-0">
                                <i class="bi bi-badge-ad me-1 text-warning"></i>Sidebar Ad Widget
                            </h6>
                            <span class="badge bg-warning-subtle text-warning">Widget Option</span>
                        </div>
                        <p class="text-muted small mb-3" style="font-size:11px;">Customize the advertisement displayed on the right sidebar of this article.</p>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Select Ad Type</label>
                            <select class="form-select form-select-sm" name="sidebar_ad_type" id="sidebar_ad_type" onchange="toggleSidebarAdFields(this.value)">
                                <option value="global" <?= (($_POST['sidebar_ad_type'] ?? 'global') === 'global') ? 'selected' : '' ?>>Default Global Sidebar Ad</option>
                                <option value="custom" <?= (($_POST['sidebar_ad_type'] ?? '') === 'custom') ? 'selected' : '' ?>>Custom Banner for this Post (GIF / WebP / Image)</option>
                                <option value="code" <?= (($_POST['sidebar_ad_type'] ?? '') === 'code') ? 'selected' : '' ?>>Custom Ad Code / Google AdSense</option>
                                <option value="none" <?= (($_POST['sidebar_ad_type'] ?? '') === 'none') ? 'selected' : '' ?>>No Ad (Disable Ad on this Post)</option>
                            </select>
                        </div>

                        <!-- Custom Banner Upload (GIF / WebP / PNG / JPG) -->
                        <div id="sidebar_ad_custom_box" style="display: <?= (($_POST['sidebar_ad_type'] ?? '') === 'custom') ? 'block' : 'none' ?>;" class="p-2 border rounded bg-light mb-3">
                            <div class="mb-2">
                                <label class="form-label small text-secondary">Upload Banner / Animated GIF</label>
                                <input type="file" class="form-control form-control-sm" name="sidebar_ad_file" accept="image/png,image/jpeg,image/webp,image/gif">
                                <div class="form-text small" style="font-size:11px;">Supports GIF, WebP, PNG, JPG including animated banners.</div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small text-secondary">Or Direct Banner Image URL</label>
                                <input type="url" class="form-control form-control-sm" name="sidebar_ad_image_url" placeholder="https://..." value="<?= e($_POST['sidebar_ad_image_url'] ?? '') ?>">
                            </div>
                            <div class="mb-1">
                                <label class="form-label small text-secondary">Target Click URL</label>
                                <input type="url" class="form-control form-control-sm" name="sidebar_ad_url" placeholder="https://..." value="<?= e($_POST['sidebar_ad_url'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- Custom Ad Code Field -->
                        <div id="sidebar_ad_code_box" style="display: <?= (($_POST['sidebar_ad_type'] ?? '') === 'code') ? 'block' : 'none' ?>;" class="p-2 border rounded bg-light mb-3">
                            <label class="form-label small text-secondary">HTML / Script Ad Code</label>
                            <textarea class="form-control form-control-sm font-monospace" name="sidebar_ad_code" rows="3" placeholder="<script>...</script>"><?= e($_POST['sidebar_ad_code'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-warning text-white fw-bold w-100 py-2 shadow-sm">
                            <i class="bi bi-cloud-arrow-up-fill me-1"></i> Publish Article
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Bangladesh Administrative Tree Data
const GEO_TREE = <?= json_encode($geoTree, JSON_UNESCAPED_UNICODE) ?>;

function onDivisionChange(preselectedDistrict, preselectedUpazila) {
    const divSelect = document.getElementById('division_select');
    const distSelect = document.getElementById('district_select');
    const upazilaSelect = document.getElementById('upazila_select');
    const selectedDivision = divSelect.value;

    distSelect.innerHTML = '<option value="">-- জেলা নির্বাচন করুন --</option>';
    upazilaSelect.innerHTML = '<option value="">-- প্রথমে জেলা নির্বাচন করুন --</option>';
    upazilaSelect.disabled = true;

    if (!selectedDivision || !GEO_TREE[selectedDivision]) {
        distSelect.disabled = true;
        distSelect.innerHTML = '<option value="">-- প্রথমে বিভাগ নির্বাচন করুন --</option>';
        return;
    }

    distSelect.disabled = false;
    const districts = Object.keys(GEO_TREE[selectedDivision]);
    districts.forEach(function(d) {
        const opt = document.createElement('option');
        opt.value = d;
        opt.textContent = d;
        if (preselectedDistrict && preselectedDistrict === d) {
            opt.selected = true;
        }
        distSelect.appendChild(opt);
    });

    if (preselectedDistrict) {
        onDistrictChange(preselectedUpazila);
    }
}

function onDistrictChange(preselectedUpazila) {
    const divSelect = document.getElementById('division_select');
    const distSelect = document.getElementById('district_select');
    const upazilaSelect = document.getElementById('upazila_select');
    const selectedDivision = divSelect.value;
    const selectedDistrict = distSelect.value;

    upazilaSelect.innerHTML = '<option value="">-- উপজেলা / থানা নির্বাচন করুন --</option>';

    if (!selectedDistrict || !GEO_TREE[selectedDivision] || !GEO_TREE[selectedDivision][selectedDistrict]) {
        upazilaSelect.disabled = true;
        return;
    }

    upazilaSelect.disabled = false;
    const upazilas = GEO_TREE[selectedDivision][selectedDistrict];
    upazilas.forEach(function(u) {
        const opt = document.createElement('option');
        opt.value = u;
        opt.textContent = u;
        if (preselectedUpazila && preselectedUpazila === u) {
            opt.selected = true;
        }
        upazilaSelect.appendChild(opt);
    });
}

function clearLocationSelection() {
    document.getElementById('division_select').value = '';
    onDivisionChange();
}

// Check if POST values were retained on validation failure
document.addEventListener('DOMContentLoaded', function() {
    const initialDiv = <?= json_encode($_POST['division'] ?? '') ?>;
    const initialDist = <?= json_encode($_POST['district'] ?? '') ?>;
    const initialUpz = <?= json_encode($_POST['upazila'] ?? '') ?>;

    if (initialDiv) {
        document.getElementById('division_select').value = initialDiv;
        onDivisionChange(initialDist, initialUpz);
    }
});

function toggleSidebarAdFields(val) {
    document.getElementById('sidebar_ad_custom_box').style.display = (val === 'custom') ? 'block' : 'none';
    document.getElementById('sidebar_ad_code_box').style.display = (val === 'code') ? 'block' : 'none';
}

function autoGenerateTitleAndKeywords() {
    var content = '';
    if (typeof $ !== 'undefined' && $('#summernote').length && $('#summernote').summernote('isEmpty') === false) {
        content = $('#summernote').summernote('code');
    } else {
        var el = document.getElementById('summernote');
        if (el) content = el.value;
    }

    var tempDiv = document.createElement('div');
    tempDiv.innerHTML = content;
    var plain = (tempDiv.textContent || tempDiv.innerText || '').trim();

    if (plain.length < 20) {
        alert('Please write or paste the full news content into the body editor first.');
        return;
    }

    var btn = document.getElementById('btnAutoGenerate');
    var originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Analyzing & Generating...';

    var formData = new FormData();
    formData.append('action', 'generate');
    formData.append('content', content);

    var feedback = document.getElementById('auto_gen_feedback_area');
    if (feedback) feedback.innerHTML = '';

    fetch('ajax-auto-generate.php', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = originalHtml;

        if (data.success) {
            var titleInput = document.getElementById('title');
            var keywordsInput = document.getElementById('keywords');
            var excerptInput = document.getElementById('excerpt');

            if (data.title && titleInput) {
                titleInput.value = data.title;
                titleInput.classList.add('bg-warning-subtle');
                setTimeout(function() { titleInput.classList.remove('bg-warning-subtle'); }, 2500);
            }
            if (data.keywords && keywordsInput) {
                keywordsInput.value = data.keywords;
                keywordsInput.classList.add('bg-warning-subtle');
                setTimeout(function() { keywordsInput.classList.remove('bg-warning-subtle'); }, 2500);
            }
            if (data.excerpt && excerptInput && !excerptInput.value.trim()) {
                excerptInput.value = data.excerpt;
            }

            var noteHtml = data.notice ? '<div class="text-danger small mt-1">' + data.notice + '</div>' : '';
            if (feedback) {
                feedback.innerHTML = '<div class="alert alert-success alert-dismissible fade show mt-2 py-2 small shadow-sm" role="alert">' +
                    '<i class="bi bi-check-circle-fill me-1"></i>Headline & Keywords generated successfully using <strong>' + data.provider_name + '</strong>!' + noteHtml +
                    '<button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>' +
                    '</div>';
            }
        } else {
            alert(data.error || 'Auto-generation failed');
        }
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        alert('Server request error: ' + err.message);
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
