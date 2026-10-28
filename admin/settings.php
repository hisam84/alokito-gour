<?php
/**
 * Site Settings & Identity Management
 * Alokito Gour Clone
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';

$pageTitle = 'Site Settings & Identity | Alokito Gour';
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
    
    // AI Settings
    $aiProvider = trim($_POST['ai_provider'] ?? 'smart');
    $geminiApiKey = trim($_POST['gemini_api_key'] ?? '');
    $geminiModel = trim($_POST['gemini_model'] ?? 'gemini-1.5-flash');

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
                `youtube_url` = :yt,
                `ai_provider` = :aiprov,
                `gemini_api_key` = :gkey,
                `gemini_model` = :gmod
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
            ':aiprov'   => $aiProvider,
            ':gkey'     => $geminiApiKey,
            ':gmod'     => $geminiModel,
        ]);

        set_flash('success', 'Site and AI settings saved successfully!');
        header('Location: settings.php');
        exit;
    } catch (Exception $e) {
        set_flash('danger', 'Save error: ' . $e->getMessage());
    }
}

// Reload updated settings
$settings = get_settings();

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Site Identity & Configuration</h4>
        <p class="text-muted small mb-0">Manage portal name, logos, social links, contact info, and AI auto-generator settings</p>
    </div>
</div>

<?php $flash = get_flash(); if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<form method="POST" action="settings.php" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="row g-4">
        <!-- Site Branding Column -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="bi bi-globe me-2 text-warning"></i>Main Branding & Identity
                </h6>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Portal Title (Site Title)</label>
                    <input type="text" class="form-control" name="site_title" value="<?= e($settings['site_title'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Tagline / Slogan</label>
                    <input type="text" class="form-control" name="site_tagline" value="<?= e($settings['site_tagline'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Header Logo</label>
                    <div class="mb-2 p-2 bg-light rounded text-center border">
                        <?php if (!empty($settings['logo_path'])): ?>
                            <img src="<?= e($settings['logo_path']) ?>" alt="Logo" class="img-fluid" style="max-height: 50px;">
                        <?php else: ?>
                            <span class="text-muted small">No logo uploaded</span>
                        <?php endif; ?>
                    </div>
                    <input type="file" class="form-control form-control-sm mb-2" name="logo" accept="image/*">
                    <input type="url" class="form-control form-control-sm" name="custom_logo_url" placeholder="Or enter direct online logo URL" value="<?= (str_starts_with($settings['logo_path'] ?? '', 'http')) ? e($settings['logo_path']) : '' ?>">
                </div>

                <div class="mb-0">
                    <label class="form-label small fw-bold text-secondary">Favicon</label>
                    <div class="mb-2 p-2 bg-light rounded text-center border" style="width: 50px; height: 50px; display: inline-flex; align-items:center; justify-content:center;">
                        <?php if (!empty($settings['favicon_path'])): ?>
                            <img src="<?= e($settings['favicon_path']) ?>" alt="Favicon" width="32" height="32">
                        <?php else: ?>
                            <span class="text-muted small">No favicon</span>
                        <?php endif; ?>
                    </div>
                    <input type="file" class="form-control form-control-sm mb-2" name="favicon" accept="image/*">
                    <input type="url" class="form-control form-control-sm" name="custom_favicon_url" placeholder="Or enter direct online favicon URL" value="<?= (str_starts_with($settings['favicon_path'] ?? '', 'http')) ? e($settings['favicon_path']) : '' ?>">
                </div>
            </div>

            <!-- Social Links -->
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="bi bi-share me-2 text-warning"></i>Social Media Links
                </h6>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Facebook Page URL</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-primary"><i class="bi bi-facebook"></i></span>
                        <input type="url" class="form-control" name="facebook_url" value="<?= e($settings['facebook_url'] ?? '') ?>" placeholder="https://facebook.com/...">
                    </div>
                </div>

                <div class="mb-0">
                    <label class="form-label small fw-bold text-secondary">YouTube Channel URL</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-danger"><i class="bi bi-youtube"></i></span>
                        <input type="url" class="form-control" name="youtube_url" value="<?= e($settings['youtube_url'] ?? '') ?>" placeholder="https://youtube.com/...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Editorial, Office & AI Settings Column -->
        <div class="col-lg-6">
            <!-- AI & Auto-Generator Settings Card -->
            <div class="card border-0 shadow-sm p-4 mb-4 border-start border-warning border-4">
                <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-stars me-2 text-warning"></i>AI & Headlines/Keywords Auto-Generator
                    </h6>
                    <span class="badge bg-warning text-dark fw-bold">Smart Feature</span>
                </div>
                <p class="text-muted small mb-3" style="font-size:12px;">
                    Select which engine to use when generating headlines, excerpts, and keywords automatically from the full news content.
                </p>

                <!-- Engine Selection -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Preferred Auto-Generator Engine</label>
                    <div class="border rounded-3 p-3 bg-light-subtle">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="ai_provider" id="provider_smart" value="smart" <?= (($settings['ai_provider'] ?? 'smart') === 'smart') ? 'checked' : '' ?> onchange="toggleAiEngine()">
                            <label class="form-check-label fw-bold text-dark" for="provider_smart" style="cursor:pointer;">
                                <i class="bi bi-cpu text-primary me-1"></i>Built-in Smart Bengali NLP
                            </label>
                            <div class="text-muted small ps-4" style="font-size:11px;">
                                <span class="badge bg-success-subtle text-success border border-success-subtle me-1">Free & Offline</span>
                                Works entirely offline with zero API key required. Fast and reliable.
                            </div>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="ai_provider" id="provider_gemini" value="gemini" <?= (($settings['ai_provider'] ?? '') === 'gemini') ? 'checked' : '' ?> onchange="toggleAiEngine()">
                            <label class="form-check-label fw-bold text-dark" for="provider_gemini" style="cursor:pointer;">
                                <i class="bi bi-google text-danger me-1"></i>Google Gemini AI
                            </label>
                            <div class="text-muted small ps-4" style="font-size:11px;">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">Advanced AI</span>
                                Uses Google's state-of-the-art Gemini models for professional journalistic titles and keyword extraction.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gemini Configuration Panel (Shown when Gemini is selected) -->
                <div id="gemini_config_panel" class="border border-warning border-opacity-50 rounded-3 p-3 bg-warning bg-opacity-10 mb-2" style="<?= (($settings['ai_provider'] ?? '') === 'gemini') ? '' : 'display:none;' ?>">
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom border-warning border-opacity-25">
                        <span class="fw-bold small text-dark"><i class="bi bi-sliders me-1 text-primary"></i>Gemini API Setup</span>
                        <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener" class="small text-primary text-decoration-none fw-bold" style="font-size:11.5px;">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Get Free API Key &raquo;
                        </a>
                    </div>

                    <!-- API Key Input -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Google Gemini API Key <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <input type="password" class="form-control font-monospace" name="gemini_api_key" id="gemini_api_key" value="<?= e($settings['gemini_api_key'] ?? '') ?>" placeholder="AIzaSy...">
                            <button class="btn btn-outline-secondary bg-white" type="button" onclick="toggleApiKeyVisibility()" title="Show/Hide">
                                <i class="bi bi-eye" id="eye_icon"></i>
                            </button>
                            <button class="btn btn-dark" type="button" onclick="testGeminiConnection()" id="btn_test_gemini">
                                <i class="bi bi-lightning-charge me-1"></i>Test Connection
                            </button>
                        </div>
                        <div class="form-text small" style="font-size:11px;">
                            Generate a free API key at <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a>.
                        </div>
                        <div id="gemini_test_status" class="mt-2" style="display:none;"></div>
                    </div>

                    <!-- Gemini Model Selection -->
                    <div class="mb-1">
                        <label class="form-label small fw-bold text-secondary">Gemini Model</label>
                        <select class="form-select form-select-sm" name="gemini_model" id="gemini_model">
                            <option value="gemini-1.5-flash" <?= (($settings['gemini_model'] ?? 'gemini-1.5-flash') === 'gemini-1.5-flash') ? 'selected' : '' ?>>
                                Gemini 1.5 Flash (Recommended — ultra-fast & free tier)
                            </option>
                            <option value="gemini-2.0-flash" <?= (($settings['gemini_model'] ?? '') === 'gemini-2.0-flash') ? 'selected' : '' ?>>
                                Gemini 2.0 Flash (Latest high-speed model)
                            </option>
                            <option value="gemini-1.5-pro" <?= (($settings['gemini_model'] ?? '') === 'gemini-1.5-pro') ? 'selected' : '' ?>>
                                Gemini 1.5 Pro (Deep contextual reasoning for complex stories)
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Editorial & Office Card -->
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="bi bi-person-badge me-2 text-warning"></i>Editorial & Contact Information
                </h6>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Chief Editor</label>
                    <input type="text" class="form-control" name="editor_name" value="<?= e($settings['editor_name'] ?? '') ?>" placeholder="e.g. Md. Abdullah">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Publisher Name</label>
                    <input type="text" class="form-control" name="publisher_name" value="<?= e($settings['publisher_name'] ?? '') ?>" placeholder="e.g. Md. Abdullah">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Office Address</label>
                    <textarea class="form-control" name="office_address" rows="3"><?= e($settings['office_address'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Contact Phone Numbers</label>
                    <input type="text" class="form-control" name="contact_phone" value="<?= e($settings['contact_phone'] ?? '') ?>" placeholder="Editor, office, and advertising phone numbers">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Contact Email</label>
                    <input type="email" class="form-control" name="contact_email" value="<?= e($settings['contact_email'] ?? '') ?>" placeholder="editor@alokitogour.com">
                </div>
            </div>

            <button type="submit" class="btn btn-warning text-white fw-bold py-2 w-100 shadow-sm">
                <i class="bi bi-save me-1"></i> Save All Settings
            </button>
        </div>
    </div>
</form>

<script>
function toggleAiEngine() {
    var isGemini = document.getElementById('provider_gemini').checked;
    var panel = document.getElementById('gemini_config_panel');
    if (isGemini) {
        panel.style.display = 'block';
    } else {
        panel.style.display = 'none';
    }
}

function toggleApiKeyVisibility() {
    var input = document.getElementById('gemini_api_key');
    var icon = document.getElementById('eye_icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

function testGeminiConnection() {
    var apiKey = document.getElementById('gemini_api_key').value.trim();
    var model = document.getElementById('gemini_model').value;
    var statusDiv = document.getElementById('gemini_test_status');
    var btn = document.getElementById('btn_test_gemini');

    if (!apiKey) {
        statusDiv.className = 'alert alert-danger py-2 small mb-0 mt-2';
        statusDiv.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>Please enter Gemini API Key first.';
        statusDiv.style.display = 'block';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Verifying...';
    statusDiv.style.display = 'none';

    var formData = new FormData();
    formData.append('action', 'test_gemini');
    formData.append('api_key', apiKey);
    formData.append('model', model);

    fetch('ajax-auto-generate.php', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-lightning-charge me-1"></i>Test Connection';
        statusDiv.style.display = 'block';
        if (data.success) {
            statusDiv.className = 'alert alert-success py-2 small mb-0 mt-2';
            statusDiv.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>' + data.message;
        } else {
            statusDiv.className = 'alert alert-danger py-2 small mb-0 mt-2';
            statusDiv.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>' + (data.error || 'Connection failed');
        }
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-lightning-charge me-1"></i>Test Connection';
        statusDiv.style.display = 'block';
        statusDiv.className = 'alert alert-danger py-2 small mb-0 mt-2';
        statusDiv.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>Network request error.';
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
