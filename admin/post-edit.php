<?php
/**
 * Edit Post
 * Alokito Gour Clone
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';
require_once dirname(__DIR__) . '/includes/bangladesh_geo.php';

$pageTitle = 'Edit Post | Alokito Gour';
$db = get_db();
$settings = get_settings();
$error = '';

$geoTree = get_bangladesh_geo();

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
    set_flash('danger', 'Requested post was not found!');
    header('Location: post-list.php');
    exit;
}

// Fetch categories for dropdown
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

        // Process Sidebar Custom Ad
        $sidebarAdType = in_array($_POST['sidebar_ad_type'] ?? '', ['global', 'custom', 'code', 'none'], true) ? $_POST['sidebar_ad_type'] : 'global';
        $sidebarAdUrl = trim($_POST['sidebar_ad_url'] ?? '');
        $sidebarAdCode = trim($_POST['sidebar_ad_code'] ?? '');
        $sidebarAdImage = $post['sidebar_ad_image'] ?? null;

        if (!empty($_FILES['sidebar_ad_file']['name'])) {
            $uploadedAd = upload_image_webp($_FILES['sidebar_ad_file'], 'assets/uploads/ads/');
            if ($uploadedAd) {
                $sidebarAdImage = $uploadedAd;
            }
        } elseif (!empty($_POST['sidebar_ad_image_url'])) {
            $sidebarAdImage = trim($_POST['sidebar_ad_image_url']);
        }

        try {
            $upStmt = $db->prepare("
                UPDATE `posts` SET 
                    `category_id` = :cat,
                    `title` = :title,
                    `excerpt` = :excerpt,
                    `keywords` = :keywords,
                    `content` = :content,
                    `thumbnail` = :thumb,
                    `caption` = :caption,
                    `reporter_name` = :reporter,
                    `is_lead` = :is_lead,
                    `is_breaking` = :is_breaking,
                    `status` = :status,
                    `division` = :division,
                    `district` = :district,
                    `upazila` = :upazila,
                    `sidebar_ad_type` = :ad_type,
                    `sidebar_ad_image` = :ad_img,
                    `sidebar_ad_url` = :ad_url,
                    `sidebar_ad_code` = :ad_code
                WHERE `id` = :id
            ");
            $upStmt->execute([
                ':cat'         => $categoryId,
                ':title'       => $title,
                ':excerpt'     => $excerpt,
                ':keywords'    => $keywords,
                ':content'     => $content,
                ':thumb'       => $thumbnailPath,
                ':caption'     => $caption,
                ':reporter'    => $reporterName,
                ':is_lead'     => $isLead,
                ':is_breaking' => $isBreaking,
                ':status'      => $status,
                ':division'    => !empty($division) ? $division : null,
                ':district'    => !empty($district) ? $district : null,
                ':upazila'     => !empty($upazila) ? $upazila : null,
                ':ad_type'     => $sidebarAdType,
                ':ad_img'      => $sidebarAdImage,
                ':ad_url'      => $sidebarAdUrl,
                ':ad_code'     => $sidebarAdCode,
                ':id'          => $id,
            ]);

            set_flash('success', 'Article updated and saved successfully!');
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
                <h4 class="fw-bold mb-1 text-dark">Edit Article</h4>
                <p class="text-muted small mb-0">ID: #<?= (int)$post['id'] ?> &bull; <?= e($post['title']) ?></p>
            </div>
            <a href="post-list.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Posts List
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
                            <label for="title" class="form-label fw-bold text-dark">Article Headline <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg fs-6" id="title" name="title" value="<?= e($post['title']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="excerpt" class="form-label fw-bold text-dark">Short Excerpt / Summary</label>
                            <textarea class="form-control" id="excerpt" name="excerpt" rows="3"><?= e($post['excerpt'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="keywords" class="form-label fw-bold text-dark d-flex align-items-center justify-content-between">
                                <span><i class="bi bi-tags text-warning me-1"></i>Keywords & Tags</span>
                                <span class="badge bg-light text-secondary border fw-normal" style="font-size:11px;">SEO Friendly</span>
                            </label>
                            <input type="text" class="form-control" id="keywords" name="keywords" value="<?= e($post['keywords'] ?? '') ?>" placeholder="e.g. Chapainawabganj, Agriculture, Mango, Economy">
                            <div class="form-text small" style="font-size:11px;">Separate multiple keywords with commas (,). e.g. <code>Chapainawabganj, Economy, Agriculture</code></div>
                        </div>

                        <div class="mb-0">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <label for="summernote" class="form-label fw-bold text-dark mb-0">
                                    Full News Content <span class="text-danger">*</span>
                                </label>
                                <button type="button" class="btn btn-warning text-white btn-sm fw-bold shadow-sm d-inline-flex align-items-center gap-1" id="btnAutoGenerate" onclick="autoGenerateTitleAndKeywords()">
                                    <i class="bi bi-magic"></i>
                                    <span>Auto-Generate Title & Keywords</span>
                                    <span class="badge bg-dark text-warning ms-1" style="font-size:10px;">
                                        <?= (($settings['ai_provider'] ?? 'smart') === 'gemini') ? 'Gemini AI' : 'Smart Mode' ?>
                                    </span>
                                </button>
                            </div>
                            <div id="auto_gen_feedback_area"></div>
                            <textarea id="summernote" name="content"><?= $post['content'] ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Meta & Attributes Sidebar -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm p-3 mb-3">
                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Publication Details</h6>

                        <div class="mb-3">
                            <label for="category_id" class="form-label small fw-bold text-secondary">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int)$cat['id'] ?>" <?= ((int)$post['category_id'] === (int)$cat['id']) ? 'selected' : '' ?>>
                                        <?= e($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="reporter_name" class="form-label small fw-bold text-secondary">Reporter Name / Byline</label>
                            <input type="text" class="form-control" id="reporter_name" name="reporter_name" value="<?= e($post['reporter_name'] ?? 'Staff Reporter') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label small fw-bold text-secondary">Publication Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="published" <?= ($post['status'] === 'published') ? 'selected' : '' ?>>Published</option>
                                <option value="draft" <?= ($post['status'] === 'draft') ? 'selected' : '' ?>>Draft</option>
                            </select>
                        </div>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_lead" name="is_lead" value="1" <?= $post['is_lead'] ? 'checked' : '' ?>>
                            <label class="form-check-label small fw-bold text-dark" for="is_lead">Lead News (Hero Feature)</label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_breaking" name="is_breaking" value="1" <?= $post['is_breaking'] ? 'checked' : '' ?>>
                            <label class="form-check-label small fw-bold text-dark" for="is_breaking">Breaking News Ticker</label>
                        </div>

                        <hr class="my-3">

                        <!-- Geographic Location Selection (Tree: Division -> District -> Upazila) -->
                        <?php 
                        $currDivision = $_POST['division'] ?? $post['division'] ?? '';
                        $currDistrict = $_POST['district'] ?? $post['district'] ?? '';
                        $currUpazila = $_POST['upazila'] ?? $post['upazila'] ?? '';
                        ?>
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
                                    <option value="<?= e($divName) ?>" <?= ($currDivision === $divName) ? 'selected' : '' ?>><?= e($divName) ?></option>
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
                        
                        <?php if (!empty($post['thumbnail'])): ?>
                            <div class="mb-2 text-center">
                                <img src="<?= e($post['thumbnail']) ?>" alt="Current Thumb" class="img-fluid rounded border" style="max-height: 120px;">
                                <div class="small text-muted mt-1">Current Thumbnail</div>
                            </div>
                        <?php endif; ?>

                        <div class="mb-2">
                            <label for="thumbnail" class="form-label small text-secondary">Upload New Image</label>
                            <input type="file" class="form-control image-preview-input" id="thumbnail" name="thumbnail" accept="image/*" data-preview="thumbPreview">
                        </div>

                        <div class="mb-2">
                            <label for="custom_image_url" class="form-label small text-secondary">Or Direct Image URL</label>
                            <input type="url" class="form-control form-control-sm" id="custom_image_url" name="custom_image_url" placeholder="https://...">
                        </div>

                        <div class="mb-3">
                            <label for="caption" class="form-label small text-secondary">Image Caption / Credit</label>
                            <input type="text" class="form-control form-control-sm" id="caption" name="caption" value="<?= e($post['caption'] ?? '') ?>">
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

                        <?php $currAdType = $post['sidebar_ad_type'] ?? 'global'; ?>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Select Ad Type</label>
                            <select class="form-select form-select-sm" name="sidebar_ad_type" id="sidebar_ad_type" onchange="toggleSidebarAdFields(this.value)">
                                <option value="global" <?= ($currAdType === 'global') ? 'selected' : '' ?>>Default Global Sidebar Ad</option>
                                <option value="custom" <?= ($currAdType === 'custom') ? 'selected' : '' ?>>Custom Banner for this Post (GIF / WebP / Image)</option>
                                <option value="code" <?= ($currAdType === 'code') ? 'selected' : '' ?>>Custom Ad Code / Google AdSense</option>
                                <option value="none" <?= ($currAdType === 'none') ? 'selected' : '' ?>>No Ad (Disable Ad on this Post)</option>
                            </select>
                        </div>

                        <!-- Custom Banner Upload (GIF / WebP / PNG / JPG) -->
                        <div id="sidebar_ad_custom_box" style="display: <?= ($currAdType === 'custom') ? 'block' : 'none' ?>;" class="p-2 border rounded bg-light mb-3">
                            <div class="mb-2">
                                <label class="form-label small text-secondary">Upload Banner / Animated GIF</label>
                                <input type="file" class="form-control form-control-sm" name="sidebar_ad_file" accept="image/png,image/jpeg,image/webp,image/gif">
                                <div class="form-text small" style="font-size:11px;">Supports GIF, WebP, PNG, JPG including animated banners.</div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small text-secondary">Or Direct Banner Image URL</label>
                                <input type="url" class="form-control form-control-sm" name="sidebar_ad_image_url" placeholder="https://..." value="<?= e($post['sidebar_ad_image'] ?? '') ?>">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small text-secondary">Target Click URL</label>
                                <input type="url" class="form-control form-control-sm" name="sidebar_ad_url" placeholder="https://..." value="<?= e($post['sidebar_ad_url'] ?? '') ?>">
                            </div>
                            <?php if (!empty($post['sidebar_ad_image'])): ?>
                                <div class="mt-2 text-center p-2 bg-white rounded border">
                                    <div class="small text-muted mb-1">Current Banner:</div>
                                    <img src="<?= e($post['sidebar_ad_image']) ?>" alt="Banner" class="img-fluid rounded border" style="max-height: 80px;">
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Custom Ad Code Field -->
                        <div id="sidebar_ad_code_box" style="display: <?= ($currAdType === 'code') ? 'block' : 'none' ?>;" class="p-2 border rounded bg-light mb-3">
                            <label class="form-label small text-secondary">HTML / Script Ad Code</label>
                            <textarea class="form-control form-control-sm font-monospace" name="sidebar_ad_code" rows="3" placeholder="<script>...</script>"><?= e($post['sidebar_ad_code'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-warning text-white fw-bold w-100 py-2 shadow-sm">
                            <i class="bi bi-save me-1"></i> Save Changes
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

// Pre-populate saved or posted location
document.addEventListener('DOMContentLoaded', function() {
    const initialDiv = <?= json_encode($currDivision) ?>;
    const initialDist = <?= json_encode($currDistrict) ?>;
    const initialUpz = <?= json_encode($currUpazila) ?>;

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
