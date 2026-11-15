<?php
/**
 * Site Customizer & Settings Control Center
 * 100% White-Label & Commercial Template Engine
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';

$db = get_db();
$settings = get_settings();
$pageTitle = 'Site Customizer & Control Center | ' . e($settings['site_title'] ?? 'Admin Panel');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    // 1. Branding & Identity
    $siteTitle = trim($_POST['site_title'] ?? '');
    $siteTagline = trim($_POST['site_tagline'] ?? '');

    // Handle File / URL Uploads
    $logoPath = $settings['logo_path'] ?? '';
    if (!empty($_FILES['logo']['name'])) {
        $uploaded = upload_image_webp($_FILES['logo'], 'assets/uploads/');
        if ($uploaded) $logoPath = $uploaded;
    } elseif (!empty($_POST['custom_logo_url'])) {
        $logoPath = trim($_POST['custom_logo_url']);
    }

    $logoFooter = $settings['logo_footer'] ?? '';
    if (!empty($_FILES['logo_footer']['name'])) {
        $uploaded = upload_image_webp($_FILES['logo_footer'], 'assets/uploads/');
        if ($uploaded) $logoFooter = $uploaded;
    } elseif (isset($_POST['custom_logo_footer_url'])) {
        $logoFooter = trim($_POST['custom_logo_footer_url']);
    }

    $logoMobile = $settings['logo_mobile'] ?? '';
    if (!empty($_FILES['logo_mobile']['name'])) {
        $uploaded = upload_image_webp($_FILES['logo_mobile'], 'assets/uploads/');
        if ($uploaded) $logoMobile = $uploaded;
    } elseif (isset($_POST['custom_logo_mobile_url'])) {
        $logoMobile = trim($_POST['custom_logo_mobile_url']);
    }

    $faviconPath = $settings['favicon_path'] ?? '';
    if (!empty($_FILES['favicon']['name'])) {
        $uploaded = upload_image_webp($_FILES['favicon'], 'assets/uploads/');
        if ($uploaded) $faviconPath = $uploaded;
    } elseif (isset($_POST['custom_favicon_url'])) {
        $faviconPath = trim($_POST['custom_favicon_url']);
    }

    $defaultThumb = $settings['default_thumbnail'] ?? '';
    if (!empty($_FILES['default_thumbnail']['name'])) {
        $uploaded = upload_image_webp($_FILES['default_thumbnail'], 'assets/uploads/news/');
        if ($uploaded) $defaultThumb = $uploaded;
    } elseif (isset($_POST['custom_default_thumbnail_url'])) {
        $defaultThumb = trim($_POST['custom_default_thumbnail_url']);
    }

    $watermarkPath = $settings['watermark_path'] ?? '';
    if (!empty($_FILES['watermark']['name'])) {
        $uploaded = upload_image_webp($_FILES['watermark'], 'assets/uploads/');
        if ($uploaded) $watermarkPath = $uploaded;
    } elseif (isset($_POST['custom_watermark_url'])) {
        $watermarkPath = trim($_POST['custom_watermark_url']);
    }

    // 2. Theme Styler & Colors
    $primaryColor = trim($_POST['primary_color'] ?? '#f3730d');
    $secondaryColor = trim($_POST['secondary_color'] ?? '#c82333');
    $accentColor = trim($_POST['accent_color'] ?? '#0abb75');
    $headerBgColor = trim($_POST['header_bg_color'] ?? '#ffffff');
    $topbarBgColor = trim($_POST['topbar_bg_color'] ?? '#f8f9fa');
    $navbarBgColor = trim($_POST['navbar_bg_color'] ?? '#ffffff');
    $footerBgColor = trim($_POST['footer_bg_color'] ?? '#1a1e21');
    $fontFamily = trim($_POST['font_family'] ?? 'SolaimanLipi');
    $fontSizeBase = trim($_POST['font_size_base'] ?? '16px');
    $themeStyle = trim($_POST['theme_style'] ?? 'classic_news');

    // 3. Header & Topbar controls
    $showTopbar = isset($_POST['show_topbar']) ? 1 : 0;
    $showDateBn = isset($_POST['show_date_bangla']) ? 1 : 0;
    $showDateEn = isset($_POST['show_date_english']) ? 1 : 0;
    $showWeather = isset($_POST['show_weather']) ? 1 : 0;
    $weatherCity = trim($_POST['weather_city'] ?? 'ঢাকা');
    $showLiveTv = isset($_POST['show_live_tv']) ? 1 : 0;
    $liveTvUrl = trim($_POST['live_tv_url'] ?? '');
    $showEpaper = isset($_POST['show_epaper']) ? 1 : 0;
    $epaperUrl = trim($_POST['epaper_url'] ?? '');
    $showTicker = isset($_POST['show_ticker']) ? 1 : 0;
    $tickerTitle = trim($_POST['ticker_title'] ?? 'সংবাদ শিরোনাম');

    // 4. Editorial & Contact
    $editorName = trim($_POST['editor_name'] ?? '');
    $executiveEditor = trim($_POST['executive_editor'] ?? '');
    $advisoryEditor = trim($_POST['advisory_editor'] ?? '');
    $publisherName = trim($_POST['publisher_name'] ?? '');
    $govRegNo = trim($_POST['gov_reg_no'] ?? '');
    $officeAddress = trim($_POST['office_address'] ?? '');
    $contactPhone = trim($_POST['contact_phone'] ?? '');
    $contactEmail = trim($_POST['contact_email'] ?? '');

    // 5. Social Media & Channels
    $facebookUrl = trim($_POST['facebook_url'] ?? '');
    $youtubeUrl = trim($_POST['youtube_url'] ?? '');
    $twitterUrl = trim($_POST['twitter_url'] ?? '');
    $instagramUrl = trim($_POST['instagram_url'] ?? '');
    $linkedinUrl = trim($_POST['linkedin_url'] ?? '');
    $tiktokUrl = trim($_POST['tiktok_url'] ?? '');
    $whatsappNumber = trim($_POST['whatsapp_number'] ?? '');
    $whatsappChannel = trim($_POST['whatsapp_channel_url'] ?? '');
    $telegramUrl = trim($_POST['telegram_url'] ?? '');

    // 6. Regional Defaults
    $defaultDivision = trim($_POST['default_division'] ?? 'ঢাকা');
    $defaultDistrict = trim($_POST['default_district'] ?? 'ঢাকা');

    // 7. SEO, Analytics & Code Injection
    $metaDescription = trim($_POST['meta_description'] ?? '');
    $metaKeywords = trim($_POST['meta_keywords'] ?? '');
    $ga4Id = trim($_POST['ga4_id'] ?? '');
    $fbPixelId = trim($_POST['fb_pixel_id'] ?? '');
    $googleSiteVer = trim($_POST['google_site_verification'] ?? '');
    $customHeadCode = $_POST['custom_head_code'] ?? '';
    $customFooterCode = $_POST['custom_footer_code'] ?? '';

    // 8. AI Settings
    $aiProvider = trim($_POST['ai_provider'] ?? 'smart');
    $geminiApiKey = trim($_POST['gemini_api_key'] ?? '');
    $geminiModel = trim($_POST['gemini_model'] ?? 'gemini-1.5-flash');

    // 9. Footer & Agency Whitelabel
    $footerAboutText = trim($_POST['footer_about_text'] ?? '');
    $copyrightText = trim($_POST['copyright_text'] ?? '');
    $techPartnerName = trim($_POST['tech_partner_name'] ?? 'DataEnvelope');
    $techPartnerUrl = trim($_POST['tech_partner_url'] ?? 'https://www.dataenvelope.com/');
    $showTechPartner = isset($_POST['show_tech_partner']) ? 1 : 0;

    try {
        $stmt = $db->prepare("
            UPDATE `settings` SET 
                `site_title` = :stitle,
                `site_tagline` = :stagline,
                `logo_path` = :logo,
                `logo_footer` = :logofoot,
                `logo_mobile` = :logomob,
                `favicon_path` = :fav,
                `default_thumbnail` = :defthumb,
                `watermark_path` = :wmark,
                `primary_color` = :pcolor,
                `secondary_color` = :scolor,
                `accent_color` = :acolor,
                `header_bg_color` = :hbg,
                `topbar_bg_color` = :tbg,
                `navbar_bg_color` = :nbg,
                `footer_bg_color` = :fbg,
                `font_family` = :ffam,
                `font_size_base` = :fsize,
                `theme_style` = :tstyle,
                `show_topbar` = :stopbar,
                `show_date_bangla` = :sdatebn,
                `show_date_english` = :sdateen,
                `show_weather` = :sweather,
                `weather_city` = :wcity,
                `show_live_tv` = :slivetv,
                `live_tv_url` = :livetvurl,
                `show_epaper` = :sepaper,
                `epaper_url` = :epaperurl,
                `show_ticker` = :sticker,
                `ticker_title` = :tickertitle,
                `editor_name` = :ed,
                `executive_editor` = :exed,
                `advisory_editor` = :aded,
                `publisher_name` = :pub,
                `gov_reg_no` = :greg,
                `office_address` = :addr,
                `contact_phone` = :phone,
                `contact_email` = :email,
                `facebook_url` = :fb,
                `youtube_url` = :yt,
                `twitter_url` = :tw,
                `instagram_url` = :ig,
                `linkedin_url` = :li,
                `tiktok_url` = :tt,
                `whatsapp_number` = :wanum,
                `whatsapp_channel_url` = :wachan,
                `telegram_url` = :tg,
                `default_division` = :ddiv,
                `default_district` = :ddist,
                `meta_description` = :mdesc,
                `meta_keywords` = :mkw,
                `ga4_id` = :ga4,
                `fb_pixel_id` = :fbpixel,
                `google_site_verification` = :gver,
                `custom_head_code` = :chead,
                `custom_footer_code` = :cfoot,
                `ai_provider` = :aiprov,
                `gemini_api_key` = :gkey,
                `gemini_model` = :gmod,
                `footer_about_text` = :fabt,
                `copyright_text` = :cright,
                `tech_partner_name` = :tpname,
                `tech_partner_url` = :tpurl,
                `show_tech_partner` = :tpshow
            WHERE `id` = 1
        ");

        $stmt->execute([
            ':stitle'     => $siteTitle,
            ':stagline'   => $siteTagline,
            ':logo'       => $logoPath,
            ':logofoot'   => $logoFooter,
            ':logomob'    => $logoMobile,
            ':fav'        => $faviconPath,
            ':defthumb'   => $defaultThumb,
            ':wmark'      => $watermarkPath,
            ':pcolor'     => $primaryColor,
            ':scolor'     => $secondaryColor,
            ':acolor'     => $accentColor,
            ':hbg'        => $headerBgColor,
            ':tbg'        => $topbarBgColor,
            ':nbg'        => $navbarBgColor,
            ':fbg'        => $footerBgColor,
            ':ffam'       => $fontFamily,
            ':fsize'      => $fontSizeBase,
            ':tstyle'     => $themeStyle,
            ':stopbar'    => $showTopbar,
            ':sdatebn'    => $showDateBn,
            ':sdateen'    => $showDateEn,
            ':sweather'   => $showWeather,
            ':wcity'      => $weatherCity,
            ':slivetv'    => $showLiveTv,
            ':livetvurl'  => $liveTvUrl,
            ':sepaper'    => $showEpaper,
            ':epaperurl'  => $epaperUrl,
            ':sticker'    => $showTicker,
            ':tickertitle'=> $tickerTitle,
            ':ed'         => $editorName,
            ':exed'       => $executiveEditor,
            ':aded'       => $advisoryEditor,
            ':pub'        => $publisherName,
            ':greg'       => $govRegNo,
            ':addr'       => $officeAddress,
            ':phone'      => $contactPhone,
            ':email'      => $contactEmail,
            ':fb'         => $facebookUrl,
            ':yt'         => $youtubeUrl,
            ':tw'         => $twitterUrl,
            ':ig'         => $instagramUrl,
            ':li'         => $linkedinUrl,
            ':tt'         => $tiktokUrl,
            ':wanum'      => $whatsappNumber,
            ':wachan'     => $whatsappChannel,
            ':tg'         => $telegramUrl,
            ':ddiv'       => $defaultDivision,
            ':ddist'      => $defaultDistrict,
            ':mdesc'      => $metaDescription,
            ':mkw'        => $metaKeywords,
            ':ga4'        => $ga4Id,
            ':fbpixel'    => $fbPixelId,
            ':gver'       => $googleSiteVer,
            ':chead'      => $customHeadCode,
            ':cfoot'      => $customFooterCode,
            ':aiprov'     => $aiProvider,
            ':gkey'       => $geminiApiKey,
            ':gmod'       => $geminiModel,
            ':fabt'       => $footerAboutText,
            ':cright'     => $copyrightText,
            ':tpname'     => $techPartnerName,
            ':tpurl'      => $techPartnerUrl,
            ':tpshow'     => $showTechPartner,
        ]);

        set_flash('success', 'All site customizer & configuration settings saved successfully!');
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

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Site Customizer & Control Center</h4>
        <p class="text-muted small mb-0">100% Turnkey Configuration: Brand, Theme Colors, Typography, Editorial, Social, SEO, and AI</p>
    </div>
    <div class="d-flex gap-2">
        <a href="../index.php" target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-box-arrow-up-right me-1"></i> Preview Live Portal
        </a>
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

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs nav-fill mb-4 bg-white p-2 rounded shadow-sm border" id="settingsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold text-dark py-2" id="branding-tab" data-bs-toggle="tab" data-bs-target="#tab-branding" type="button" role="tab">
                <i class="bi bi-globe me-1 text-warning"></i> ব্র্যান্ডিং ও পরিচিতি
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-dark py-2" id="theme-tab" data-bs-toggle="tab" data-bs-target="#tab-theme" type="button" role="tab">
                <i class="bi bi-palette me-1 text-primary"></i> থিম, রঙ ও ফন্ট
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-dark py-2" id="header-tab" data-bs-toggle="tab" data-bs-target="#tab-header" type="button" role="tab">
                <i class="bi bi-layout-text-window me-1 text-info"></i> হেডার ও টপবার
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-dark py-2" id="editorial-tab" data-bs-toggle="tab" data-bs-target="#tab-editorial" type="button" role="tab">
                <i class="bi bi-person-badge me-1 text-success"></i> সম্পাদকীয় ও যোগাযোগ
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-dark py-2" id="social-tab" data-bs-toggle="tab" data-bs-target="#tab-social" type="button" role="tab">
                <i class="bi bi-share me-1 text-danger"></i> সোশ্যাল মিডিয়া
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-dark py-2" id="regional-tab" data-bs-toggle="tab" data-bs-target="#tab-regional" type="button" role="tab">
                <i class="bi bi-geo-alt me-1 text-danger"></i> আঞ্চলিক ডিফল্ট
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-dark py-2" id="seo-tab" data-bs-toggle="tab" data-bs-target="#tab-seo" type="button" role="tab">
                <i class="bi bi-graph-up me-1 text-secondary"></i> এসইও ও অ্যানালিটিক্স
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-dark py-2" id="ai-tab" data-bs-toggle="tab" data-bs-target="#tab-ai" type="button" role="tab">
                <i class="bi bi-stars me-1 text-warning"></i> এআই সহকারী
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-dark py-2" id="footer-tab" data-bs-toggle="tab" data-bs-target="#tab-footer" type="button" role="tab">
                <i class="bi bi-shield-check me-1 text-dark"></i> ফুটার ও এজেন্সি
            </button>
        </li>
    </ul>

    <!-- Tab Content Panes -->
    <div class="tab-content mb-4" id="settingsTabContent">

        <!-- TAB 1: BRANDING & IDENTITY -->
        <div class="tab-pane fade show active" id="tab-branding" role="tabpanel">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="bi bi-globe me-2 text-warning"></i>ব্র্যান্ড পরিচিতি, লোগো ও ছবি
                </h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">পোর্টালের নাম (Site Title) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="site_title" value="<?= e($settings['site_title'] ?? '') ?>" required>
                        <div class="form-text small">যেমন: আমার খবর ডটকম বা Daily Bangla News</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">ট্যাগলাইন / স্লোগান (Site Tagline)</label>
                        <input type="text" class="form-control" name="site_tagline" value="<?= e($settings['site_tagline'] ?? '') ?>">
                        <div class="form-text small">যেমন: সত্যের সাথে, আগামীর পথে</div>
                    </div>

                    <div class="col-md-6">
                        <div class="border rounded p-3 bg-light">
                            <label class="form-label small fw-bold text-secondary">মূল হেডার লোগো (Header Logo)</label>
                            <div class="mb-2 p-2 bg-white rounded text-center border">
                                <?php if (!empty($settings['logo_path'])): ?>
                                    <img src="<?= e($settings['logo_path']) ?>" alt="Logo" class="img-fluid" style="max-height: 55px;">
                                <?php else: ?>
                                    <span class="text-muted small">No logo uploaded</span>
                                <?php endif; ?>
                            </div>
                            <input type="file" class="form-control form-control-sm mb-2" name="logo" accept="image/*">
                            <input type="url" class="form-control form-control-sm" name="custom_logo_url" placeholder="বা অনলাইন লোগো লিংক পেস্ট করুন" value="<?= (str_starts_with($settings['logo_path'] ?? '', 'http')) ? e($settings['logo_path']) : '' ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="border rounded p-3 bg-light">
                            <label class="form-label small fw-bold text-secondary">ফুটার লোগো (সাদা বা ডার্ক ব্যাকগ্রাউন্ডের জন্য)</label>
                            <div class="mb-2 p-2 bg-dark rounded text-center border">
                                <?php if (!empty($settings['logo_footer'])): ?>
                                    <img src="<?= e($settings['logo_footer']) ?>" alt="Footer Logo" class="img-fluid" style="max-height: 55px;">
                                <?php else: ?>
                                    <span class="text-white-50 small">মূল হেডার লোগো ব্যবহৃত হচ্ছে</span>
                                <?php endif; ?>
                            </div>
                            <input type="file" class="form-control form-control-sm mb-2" name="logo_footer" accept="image/*">
                            <input type="url" class="form-control form-control-sm" name="custom_logo_footer_url" placeholder="বা অনলাইন লোগো লিংক পেস্ট করুন" value="<?= (str_starts_with($settings['logo_footer'] ?? '', 'http')) ? e($settings['logo_footer']) : '' ?>">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="border rounded p-3 bg-light">
                            <label class="form-label small fw-bold text-secondary">মোবাইল হেডার লোগো (Mobile Header)</label>
                            <div class="mb-2 p-2 bg-white rounded text-center border">
                                <?php if (!empty($settings['logo_mobile'])): ?>
                                    <img src="<?= e($settings['logo_mobile']) ?>" alt="Mobile Logo" class="img-fluid" style="max-height: 38px;">
                                <?php else: ?>
                                    <span class="text-muted small">ঐচ্ছিক (ডিফল্ট লোগো ব্যবহৃত হবে)</span>
                                <?php endif; ?>
                            </div>
                            <input type="file" class="form-control form-control-sm mb-2" name="logo_mobile" accept="image/*">
                            <input type="url" class="form-control form-control-sm" name="custom_logo_mobile_url" placeholder="বা অনলাইন লিংক" value="<?= (str_starts_with($settings['logo_mobile'] ?? '', 'http')) ? e($settings['logo_mobile']) : '' ?>">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="border rounded p-3 bg-light">
                            <label class="form-label small fw-bold text-secondary">ফেভিকন (Favicon - 32x32)</label>
                            <div class="mb-2 p-2 bg-white rounded text-center border" style="height: 55px; display:flex; align-items:center; justify-content:center;">
                                <?php if (!empty($settings['favicon_path'])): ?>
                                    <img src="<?= e($settings['favicon_path']) ?>" alt="Favicon" width="32" height="32">
                                <?php else: ?>
                                    <span class="text-muted small">No favicon</span>
                                <?php endif; ?>
                            </div>
                            <input type="file" class="form-control form-control-sm mb-2" name="favicon" accept="image/*">
                            <input type="url" class="form-control form-control-sm" name="custom_favicon_url" placeholder="বা অনলাইন ফেভিকন লিংক" value="<?= (str_starts_with($settings['favicon_path'] ?? '', 'http')) ? e($settings['favicon_path']) : '' ?>">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="border rounded p-3 bg-light">
                            <label class="form-label small fw-bold text-secondary">ডিফল্ট থাম্বনেইল (ছবি না থাকলে)</label>
                            <div class="mb-2 p-2 bg-white rounded text-center border" style="height: 55px; display:flex; align-items:center; justify-content:center;">
                                <?php if (!empty($settings['default_thumbnail'])): ?>
                                    <img src="<?= e($settings['default_thumbnail']) ?>" alt="Default" class="img-fluid" style="max-height: 40px;">
                                <?php else: ?>
                                    <span class="text-muted small">সিস্টেম ডিফল্ট ভেক্টর ব্যবহৃত হচ্ছে</span>
                                <?php endif; ?>
                            </div>
                            <input type="file" class="form-control form-control-sm mb-2" name="default_thumbnail" accept="image/*">
                            <input type="url" class="form-control form-control-sm" name="custom_default_thumbnail_url" placeholder="বা অনলাইন ছবি লিংক" value="<?= (str_starts_with($settings['default_thumbnail'] ?? '', 'http')) ? e($settings['default_thumbnail']) : '' ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: THEME COLORS & TYPOGRAPHY -->
        <div class="tab-pane fade" id="tab-theme" role="tabpanel">
            <div class="card border-0 shadow-sm p-4">
                <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-palette me-2 text-primary"></i>লাইভ থিম স্টাইলার, রঙ ও টাইপোগ্রাফি
                    </h5>
                    <span class="badge bg-primary text-white">Live CSS Engine</span>
                </div>
                <p class="text-muted small mb-4">
                    এখানে যে রঙগুলো নির্বাচন করবেন তা পুরো ওয়েবসাইটের বাটন, হেডার, ফুটার, টিকার ও অ্যাকসেন্টে স্বয়ংক্রিয়ভাবে লাইভ প্রতিফলিত হবে।
                </p>

                <!-- Color Presets -->
                <div class="mb-4 p-3 bg-light rounded-3 border">
                    <label class="form-label small fw-bold text-secondary mb-2">জনপ্রিয় কালার প্যালেট প্রিসেট (১-ক্লিকে কালার পরিবর্তন করুন):</label>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-outline-dark" onclick="applyColorPreset('#f3730d', '#c82333', '#0abb75', '#ffffff', '#f8f9fa', '#ffffff', '#1a1e21')">
                            🟠 ক্লাসিক অরেঞ্জ ও রেড (Alokito Gour)
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="applyColorPreset('#0d6efd', '#dc3545', '#198754', '#ffffff', '#f1f5f9', '#ffffff', '#0f172a')">
                            🔵 রয়েল ব্লু ও গাঢ় নেভি
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="applyColorPreset('#b91c1c', '#1e293b', '#059669', '#ffffff', '#f8fafc', '#ffffff', '#111827')">
                            🔴 ক্র্যাবসন ডেইলি (Prothom Alo Style)
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="applyColorPreset('#047857', '#b45309', '#0284c7', '#ffffff', '#f0fdf4', '#ffffff', '#064e3b')">
                            🟢 এমারেল্ড গ্রীন (জাতীয় ও ইসলামিক)
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyColorPreset('#334155', '#e11d48', '#0ea5e9', '#ffffff', '#f8fafc', '#ffffff', '#020617')">
                            ⚫ মডার্ন স্লেট ও ব্ল্যাক
                        </button>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Brand Colors -->
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-secondary">প্রাইমারি কালার (Primary Brand Color)</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="primary_color_picker" value="<?= e($settings['primary_color'] ?? '#f3730d') ?>" onchange="syncColor('primary_color_picker', 'primary_color_input')">
                            <input type="text" class="form-control font-monospace" name="primary_color" id="primary_color_input" value="<?= e($settings['primary_color'] ?? '#f3730d') ?>" onchange="syncColor('primary_color_input', 'primary_color_picker')">
                        </div>
                        <div class="form-text small">বাটন, হেডিং হাইলাইট ও একটিভ লিংকের রঙ</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-secondary">সেকেন্ডারি কালার (Secondary Color)</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="secondary_color_picker" value="<?= e($settings['secondary_color'] ?? '#c82333') ?>" onchange="syncColor('secondary_color_picker', 'secondary_color_input')">
                            <input type="text" class="form-control font-monospace" name="secondary_color" id="secondary_color_input" value="<?= e($settings['secondary_color'] ?? '#c82333') ?>" onchange="syncColor('secondary_color_input', 'secondary_color_picker')">
                        </div>
                        <div class="form-text small">ব্রেকিং টিকার ব্যাজ ও সতর্কতা রঙ</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-secondary">অ্যাকসেন্ট কালার (Accent / Green)</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="accent_color_picker" value="<?= e($settings['accent_color'] ?? '#0abb75') ?>" onchange="syncColor('accent_color_picker', 'accent_color_input')">
                            <input type="text" class="form-control font-monospace" name="accent_color" id="accent_color_input" value="<?= e($settings['accent_color'] ?? '#0abb75') ?>" onchange="syncColor('accent_color_input', 'accent_color_picker')">
                        </div>
                        <div class="form-text small">সাকসেস ব্যাজ ও স্পেশাল হাইলাইটার</div>
                    </div>

                    <!-- Layout Component Colors -->
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-secondary">টপবার ব্যাকগ্রাউন্ড</label>
                        <div class="input-group input-group-sm">
                            <input type="color" class="form-control form-control-color" id="topbar_bg_picker" value="<?= e($settings['topbar_bg_color'] ?? '#f8f9fa') ?>" onchange="syncColor('topbar_bg_picker', 'topbar_bg_input')">
                            <input type="text" class="form-control font-monospace" name="topbar_bg_color" id="topbar_bg_input" value="<?= e($settings['topbar_bg_color'] ?? '#f8f9fa') ?>" onchange="syncColor('topbar_bg_input', 'topbar_bg_picker')">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-secondary">হেডার ব্যাকগ্রাউন্ড</label>
                        <div class="input-group input-group-sm">
                            <input type="color" class="form-control form-control-color" id="header_bg_picker" value="<?= e($settings['header_bg_color'] ?? '#ffffff') ?>" onchange="syncColor('header_bg_picker', 'header_bg_input')">
                            <input type="text" class="form-control font-monospace" name="header_bg_color" id="header_bg_input" value="<?= e($settings['header_bg_color'] ?? '#ffffff') ?>" onchange="syncColor('header_bg_input', 'header_bg_picker')">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-secondary">মেইন মেনুবার ব্যাকগ্রাউন্ড</label>
                        <div class="input-group input-group-sm">
                            <input type="color" class="form-control form-control-color" id="navbar_bg_picker" value="<?= e($settings['navbar_bg_color'] ?? '#ffffff') ?>" onchange="syncColor('navbar_bg_picker', 'navbar_bg_input')">
                            <input type="text" class="form-control font-monospace" name="navbar_bg_color" id="navbar_bg_input" value="<?= e($settings['navbar_bg_color'] ?? '#ffffff') ?>" onchange="syncColor('navbar_bg_input', 'navbar_bg_picker')">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-secondary">ফুটার ব্যাকগ্রাউন্ড</label>
                        <div class="input-group input-group-sm">
                            <input type="color" class="form-control form-control-color" id="footer_bg_picker" value="<?= e($settings['footer_bg_color'] ?? '#1a1e21') ?>" onchange="syncColor('footer_bg_picker', 'footer_bg_input')">
                            <input type="text" class="form-control font-monospace" name="footer_bg_color" id="footer_bg_input" value="<?= e($settings['footer_bg_color'] ?? '#1a1e21') ?>" onchange="syncColor('footer_bg_input', 'footer_bg_picker')">
                        </div>
                    </div>

                    <!-- Typography -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">বাংলা ফন্ট নির্বাচন (Bangla Typography)</label>
                        <select class="form-select" name="font_family" id="font_family_select">
                            <option value="SolaimanLipi" <?= (($settings['font_family'] ?? 'SolaimanLipi') === 'SolaimanLipi') ? 'selected' : '' ?>>
                                SolaimanLipi (সোলাইমান লিপি - সর্বাধিক পঠিত ক্লাসিক ফন্ট)
                            </option>
                            <option value="Kalpurush" <?= (($settings['font_family'] ?? '') === 'Kalpurush') ? 'selected' : '' ?>>
                                Kalpurush (কালপুরুষ - স্পষ্ট ও পরিচ্ছন্ন)
                            </option>
                            <option value="Hind Siliguri" <?= (($settings['font_family'] ?? '') === 'Hind Siliguri') ? 'selected' : '' ?>>
                                Hind Siliguri (হিন্দ শিলিগুড়ি - গুগল মডার্ন ফন্ট)
                            </option>
                            <option value="Anek Bangla" <?= (($settings['font_family'] ?? '') === 'Anek Bangla') ? 'selected' : '' ?>>
                                Anek Bangla (অনেক বাংলা - আধুনিক ভ্যারিয়েবল ফন্ট)
                            </option>
                            <option value="Noto Sans Bengali" <?= (($settings['font_family'] ?? '') === 'Noto Sans Bengali') ? 'selected' : '' ?>>
                                Noto Sans Bengali (গুগল নোটো সান্স বাংলা)
                            </option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">বেস ফন্ট সাইজ (Base Font Size)</label>
                        <select class="form-select" name="font_size_base">
                            <option value="15px" <?= (($settings['font_size_base'] ?? '') === '15px') ? 'selected' : '' ?>>15px (Compact - কমপ্যাক্ট)</option>
                            <option value="16px" <?= (($settings['font_size_base'] ?? '16px') === '16px') ? 'selected' : '' ?>>16px (Standard Default - স্ট্যান্ডার্ড)</option>
                            <option value="17px" <?= (($settings['font_size_base'] ?? '') === '17px') ? 'selected' : '' ?>>17px (Slightly Larger - আরামদায়ক)</option>
                            <option value="18px" <?= (($settings['font_size_base'] ?? '') === '18px') ? 'selected' : '' ?>>18px (Large - বড় ও স্পষ্ট)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: HEADER, TOPBAR & TICKER -->
        <div class="tab-pane fade" id="tab-header" role="tabpanel">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="bi bi-layout-text-window me-2 text-info"></i>হেডার, টপবার ও সংবাদ শিরোনাম টিকার
                </h5>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="border rounded p-3 bg-light">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-toggles me-1"></i>টপবার ভিজিবিলিটি ও উপাদান</h6>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="show_topbar" id="show_topbar" <?= !empty($settings['show_topbar']) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold small" for="show_topbar">টপবার প্রদর্শন করুন (Show Topbar)</label>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="show_date_bangla" id="show_date_bangla" <?= (!isset($settings['show_date_bangla']) || !empty($settings['show_date_bangla'])) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="show_date_bangla">বাংলা তারিখ দেখান (যেমন: রবিবার, ১৫ নভেম্বর ২০২৬)</label>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="show_date_english" id="show_date_english" <?= !empty($settings['show_date_english']) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="show_date_english">ইংরেজি তারিখ দেখান (Sunday, 15 November 2026)</label>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="show_weather" id="show_weather" <?= (!isset($settings['show_weather']) || !empty($settings['show_weather'])) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="show_weather">আবহাওয়ার শহর নির্দেশক প্রদর্শন করুন</label>
                            </div>

                            <div class="mb-2">
                                <label class="form-label small fw-bold text-secondary">আবহাওয়ার শহরের নাম</label>
                                <input type="text" class="form-control form-control-sm" name="weather_city" value="<?= e($settings['weather_city'] ?? 'ঢাকা') ?>" placeholder="e.g. ঢাকা বা চাঁপাইনবাবগঞ্জ">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="border rounded p-3 bg-light mb-3">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-tv me-1"></i>লাইভ টিভি ও ই-পেপার বোতাম</h6>

                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="show_live_tv" id="show_live_tv" <?= !empty($settings['show_live_tv']) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold small" for="show_live_tv">টপবারে "লাইভ টিভি" বাটন চালু করুন</label>
                            </div>
                            <input type="url" class="form-control form-control-sm mb-3" name="live_tv_url" value="<?= e($settings['live_tv_url'] ?? '') ?>" placeholder="YouTube Live Stream বা TV URL">

                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="show_epaper" id="show_epaper" <?= !empty($settings['show_epaper']) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold small" for="show_epaper">টপবারে "ই-পেপার" বাটন চালু করুন</label>
                            </div>
                            <input type="url" class="form-control form-control-sm" name="epaper_url" value="<?= e($settings['epaper_url'] ?? '') ?>" placeholder="E-Paper ওয়েবসাইট লিংক">
                        </div>

                        <div class="border rounded p-3 bg-light">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-broadcast me-1"></i>ব্রেকিং নিউজ টিকার</h6>

                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="show_ticker" id="show_ticker" <?= (!isset($settings['show_ticker']) || !empty($settings['show_ticker'])) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold small" for="show_ticker">হেডারের নিচে সংবাদ টিকার প্রদর্শন করুন</label>
                            </div>

                            <label class="form-label small fw-bold text-secondary">টিকার ব্যাজের শিরোনাম</label>
                            <input type="text" class="form-control form-control-sm" name="ticker_title" value="<?= e($settings['ticker_title'] ?? 'সংবাদ শিরোনাম') ?>" placeholder="যেমন: সংবাদ শিরোনাম বা ব্রেকিং নিউজ">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 4: EDITORIAL & CONTACT -->
        <div class="tab-pane fade" id="tab-editorial" role="tabpanel">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="bi bi-person-badge me-2 text-success"></i>সম্পাদকীয় পরিষদ, অফিস ও যোগাযোগ
                </h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">প্রধান সম্পাদক (Editor-in-Chief)</label>
                        <input type="text" class="form-control" name="editor_name" value="<?= e($settings['editor_name'] ?? '') ?>" placeholder="যেমন: মোহাঃ আব্দুল্লাহ">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">প্রকাশক (Publisher)</label>
                        <input type="text" class="form-control" name="publisher_name" value="<?= e($settings['publisher_name'] ?? '') ?>" placeholder="যেমন: মোহাঃ আব্দুল্লাহ">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">নির্বাহী সম্পাদক (Executive Editor - ঐচ্ছিক)</label>
                        <input type="text" class="form-control" name="executive_editor" value="<?= e($settings['executive_editor'] ?? '') ?>" placeholder="যেমন: আতিকুর রহমান">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">উপদেষ্টা সম্পাদক (Advisory Editor - ঐচ্ছিক)</label>
                        <input type="text" class="form-control" name="advisory_editor" value="<?= e($settings['advisory_editor'] ?? '') ?>" placeholder="যেমন: প্রফেসর ড. মোস্তাফিজুর রহমান">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label small fw-bold text-secondary">সরকারি নিবন্ধন / রেজিস্ট্রেশন নং (Govt Declaration / Reg No)</label>
                        <input type="text" class="form-control" name="gov_reg_no" value="<?= e($settings['gov_reg_no'] ?? '') ?>" placeholder="যেমন: তথ্য ও সম্প্রচার মন্ত্রণালয় কর্তৃক অনলাইন নিউজ পোর্টাল নিবন্ধন নং- ১২৩৪৫">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label small fw-bold text-secondary">প্রধান অফিস ও নিউজরুম ঠিকানা</label>
                        <textarea class="form-control" name="office_address" rows="3" placeholder="অফিসের পূর্ণ ঠিকানা লিখুন"><?= e($settings['office_address'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">যোগাযোগের ফোন নম্বর</label>
                        <input type="text" class="form-control" name="contact_phone" value="<?= e($settings['contact_phone'] ?? '') ?>" placeholder="যেমন: 01333479192 (সম্পাদক), 01333479193 (অফিস)">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">যোগাযোগের ইমেইল</label>
                        <input type="email" class="form-control" name="contact_email" value="<?= e($settings['contact_email'] ?? '') ?>" placeholder="editor@example.com">
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 5: SOCIAL MEDIA & CHANNELS -->
        <div class="tab-pane fade" id="tab-social" role="tabpanel">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="bi bi-share me-2 text-danger"></i>সোশ্যাল মিডিয়া প্রোফাইল ও কমিউনিটি লিংক
                </h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Facebook পেজ URL</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-primary"><i class="bi bi-facebook"></i></span>
                            <input type="url" class="form-control" name="facebook_url" value="<?= e($settings['facebook_url'] ?? '') ?>" placeholder="https://facebook.com/yourpage">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">YouTube চ্যানেল URL</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-danger"><i class="bi bi-youtube"></i></span>
                            <input type="url" class="form-control" name="youtube_url" value="<?= e($settings['youtube_url'] ?? '') ?>" placeholder="https://youtube.com/@yourchannel">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">X (Twitter) URL</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-dark"><i class="bi bi-twitter-x"></i></span>
                            <input type="url" class="form-control" name="twitter_url" value="<?= e($settings['twitter_url'] ?? '') ?>" placeholder="https://twitter.com/yourhandle">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Instagram প্রোফাইল URL</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-danger"><i class="bi bi-instagram"></i></span>
                            <input type="url" class="form-control" name="instagram_url" value="<?= e($settings['instagram_url'] ?? '') ?>" placeholder="https://instagram.com/yourhandle">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">WhatsApp চ্যানেল / গ্রুপ লিংক</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-success"><i class="bi bi-whatsapp"></i></span>
                            <input type="url" class="form-control" name="whatsapp_channel_url" value="<?= e($settings['whatsapp_channel_url'] ?? '') ?>" placeholder="https://whatsapp.com/channel/...">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Telegram চ্যানেল লিংক</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-info"><i class="bi bi-telegram"></i></span>
                            <input type="url" class="form-control" name="telegram_url" value="<?= e($settings['telegram_url'] ?? '') ?>" placeholder="https://t.me/yourchannel">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">LinkedIn পেজ URL</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-primary"><i class="bi bi-linkedin"></i></span>
                            <input type="url" class="form-control" name="linkedin_url" value="<?= e($settings['linkedin_url'] ?? '') ?>" placeholder="https://linkedin.com/company/...">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">TikTok প্রোফাইল URL</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-dark"><i class="bi bi-tiktok"></i></span>
                            <input type="url" class="form-control" name="tiktok_url" value="<?= e($settings['tiktok_url'] ?? '') ?>" placeholder="https://tiktok.com/@yourhandle">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 6: REGIONAL DEFAULTS -->
        <div class="tab-pane fade" id="tab-regional" role="tabpanel">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="bi bi-geo-alt me-2 text-danger"></i>আঞ্চলিক ও জেলাভিত্তিক সংবাদ ডিফল্ট পছন্দ
                </h5>
                <p class="text-muted small mb-4">
                    আপনার পোর্টালটি মূলত যে বিভাগ বা জেলার ওপর ভিত্তি করে কাজ করে সেটি সিলেক্ট করুন। সাইডবারের "আমার এলাকার খবর" ফিল্টার স্বয়ংক্রিয়ভাবে এই জেলাকে প্রাধান্য দেবে।
                </p>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">মূল বিভাগ (Primary Division)</label>
                        <select class="form-select" name="default_division" id="default_division">
                            <?php 
                            $divs = ['ঢাকা', 'চট্টগ্রাম', 'রাজশাহী', 'খুলনা', 'বরিশাল', 'সিলেট', 'রংপুর', 'ময়মনসিংহ'];
                            $currentDiv = $settings['default_division'] ?? 'ঢাকা';
                            foreach ($divs as $d):
                            ?>
                                <option value="<?= e($d) ?>" <?= ($currentDiv === $d) ? 'selected' : '' ?>><?= e($d) ?> বিভাগ</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">মূল জেলা (Primary District)</label>
                        <input type="text" class="form-control" name="default_district" value="<?= e($settings['default_district'] ?? 'ঢাকা') ?>" placeholder="যেমন: ঢাকা, চাঁপাইনবাবগঞ্জ, সিলেট, বগুড়া ইত্যাদি">
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 7: SEO, ANALYTICS & CODE INJECTION -->
        <div class="tab-pane fade" id="tab-seo" role="tabpanel">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="bi bi-graph-up me-2 text-secondary"></i>সার্চ ইঞ্জিন অপ্টিমাইজেশন (SEO) ও ট্র্যাকিং কোড
                </h5>

                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label small fw-bold text-secondary">ডিফল্ট মেটা ডেসক্রিপশন (Meta Description)</label>
                        <textarea class="form-control" name="meta_description" rows="2" placeholder="Google সার্চে পোর্টালের বর্ণনায় যা প্রদর্শিত হবে"><?= e($settings['meta_description'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label small fw-bold text-secondary">ডিফল্ট মেটা কিওয়ার্ডস (Meta Keywords - কমা দ্বারা পৃথককৃত)</label>
                        <input type="text" class="form-control" name="meta_keywords" value="<?= e($settings['meta_keywords'] ?? '') ?>" placeholder="বাংলা খবর, সর্বশেষ সংবাদ, ব্রেকিং নিউজ, জাতীয়">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-secondary">Google Search Console Verification Tag</label>
                        <input type="text" class="form-control font-monospace" name="google_site_verification" value="<?= e($settings['google_site_verification'] ?? '') ?>" placeholder="content code only">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-secondary">Google Analytics 4 (GA4) ID</label>
                        <input type="text" class="form-control font-monospace" name="ga4_id" value="<?= e($settings['ga4_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-secondary">Facebook Pixel ID</label>
                        <input type="text" class="form-control font-monospace" name="fb_pixel_id" value="<?= e($settings['fb_pixel_id'] ?? '') ?>" placeholder="123456789012345">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">কাস্টম &lt;head&gt; কোড ইনজেকশন</label>
                        <textarea class="form-control font-monospace" name="custom_head_code" rows="5" placeholder="<!-- Paste custom CSS, Google AdSense verification, or head scripts here -->"><?= e($settings['custom_head_code'] ?? '') ?></textarea>
                        <div class="form-text small">&lt;/head&gt; ট্যাগের ঠিক পূর্বে রেন্ডার হবে।</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">কাস্টম &lt;footer&gt; কোড ইনজেকশন</label>
                        <textarea class="form-control font-monospace" name="custom_footer_code" rows="5" placeholder="<!-- Paste custom chat widget, popup scripts, or body trackers here -->"><?= e($settings['custom_footer_code'] ?? '') ?></textarea>
                        <div class="form-text small">&lt;/body&gt; ট্যাগের ঠিক পূর্বে রেন্ডার হবে।</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 8: AI AUTO-GENERATOR -->
        <div class="tab-pane fade" id="tab-ai" role="tabpanel">
            <div class="card border-0 shadow-sm p-4">
                <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-stars me-2 text-warning"></i>এআই হেডলাইন ও কিওয়ার্ড অটো-জেনারেটর
                    </h5>
                    <span class="badge bg-warning text-dark fw-bold">Smart Journalistic Feature</span>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-secondary">পছন্দের অটো-জেনারেটর ইঞ্জিন</label>
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="ai_provider" id="provider_smart" value="smart" <?= (($settings['ai_provider'] ?? 'smart') === 'smart') ? 'checked' : '' ?> onchange="toggleAiEngine()">
                            <label class="form-check-label fw-bold text-dark" for="provider_smart" style="cursor:pointer;">
                                <i class="bi bi-cpu text-primary me-1"></i>বিল্ট-ইন স্মার্ট বাংলা NLP (Built-in Smart Bengali NLP)
                            </label>
                            <div class="text-muted small ps-4" style="font-size:12px;">
                                <span class="badge bg-success-subtle text-success border border-success-subtle me-1">সম্পূর্ণ ফ্রি ও অফলাইন</span>
                                কোনো এপিআই কী ছাড়াই সম্পূর্ণ বিনামূল্যে দ্রুত ও নির্ভরযোগ্যভাবে কাজ করে।
                            </div>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="ai_provider" id="provider_gemini" value="gemini" <?= (($settings['ai_provider'] ?? '') === 'gemini') ? 'checked' : '' ?> onchange="toggleAiEngine()">
                            <label class="form-check-label fw-bold text-dark" for="provider_gemini" style="cursor:pointer;">
                                <i class="bi bi-google text-danger me-1"></i>Google Gemini AI (গুগল জেমিনাই এআই)
                            </label>
                            <div class="text-muted small ps-4" style="font-size:12px;">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">অ্যাডভান্সড জার্নালিজম</span>
                                গুগল এআই ব্যবহার করে পেশাদার বাংলা শিরোনাম ও বিষয়ভিত্তিক সারসংক্ষেপ তৈরি করে।
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gemini Config Panel -->
                <div id="gemini_config_panel" class="border border-warning rounded-3 p-3 bg-warning bg-opacity-10 mb-3" style="<?= (($settings['ai_provider'] ?? '') === 'gemini') ? '' : 'display:none;' ?>">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-warning border-opacity-25">
                        <span class="fw-bold small text-dark"><i class="bi bi-sliders me-1 text-primary"></i>Google Gemini API সেটআপ</span>
                        <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener" class="small text-primary text-decoration-none fw-bold">
                            <i class="bi bi-box-arrow-up-right me-1"></i>ফ্রি API কী নিন &raquo;
                        </a>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-bold text-secondary">Gemini API Key</label>
                            <div class="input-group input-group-sm">
                                <input type="password" class="form-control font-monospace" name="gemini_api_key" id="gemini_api_key" value="<?= e($settings['gemini_api_key'] ?? '') ?>" placeholder="AIzaSy...">
                                <button class="btn btn-outline-secondary bg-white" type="button" onclick="toggleApiKeyVisibility()" title="Show/Hide">
                                    <i class="bi bi-eye" id="eye_icon"></i>
                                </button>
                                <button class="btn btn-dark" type="button" onclick="testGeminiConnection()" id="btn_test_gemini">
                                    <i class="bi bi-lightning-charge me-1"></i>Test
                                </button>
                            </div>
                            <div id="gemini_test_status" class="mt-2" style="display:none;"></div>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-secondary">Gemini Model</label>
                            <select class="form-select form-select-sm" name="gemini_model" id="gemini_model">
                                <option value="gemini-1.5-flash" <?= (($settings['gemini_model'] ?? 'gemini-1.5-flash') === 'gemini-1.5-flash') ? 'selected' : '' ?>>
                                    Gemini 1.5 Flash (সুপার ফাস্ট ও ফ্রি টিয়ার)
                                </option>
                                <option value="gemini-2.0-flash" <?= (($settings['gemini_model'] ?? '') === 'gemini-2.0-flash') ? 'selected' : '' ?>>
                                    Gemini 2.0 Flash (লেটেস্ট দ্রুততম মডেল)
                                </option>
                                <option value="gemini-1.5-pro" <?= (($settings['gemini_model'] ?? '') === 'gemini-1.5-pro') ? 'selected' : '' ?>>
                                    Gemini 1.5 Pro (উন্নত বিশ্লেষণাত্মক ক্ষমতা)
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 9: FOOTER, COPYRIGHT & AGENCY WHITELABEL -->
        <div class="tab-pane fade" id="tab-footer" role="tabpanel">
            <div class="card border-0 shadow-sm p-4">
                <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-shield-check me-2 text-dark"></i>ফুটার পরিচিতি, কপিরাইট ও এজেন্সি হোয়াইট-লেবেল
                    </h5>
                    <span class="badge bg-dark text-white">White-Label Freedom</span>
                </div>
                <p class="text-muted small mb-4">
                    আপনি যখন কোনো ক্লায়েন্টের কাছে এই ওয়েবসাইটটি বিক্রি করবেন, তখন চাইলে আপনার এজেন্সির নাম দিতে পারেন অথবা সম্পূর্ণ বন্ধ করে ক্লায়েন্টের নিজস্ব ব্র্যান্ডিং রাখতে পারেন।
                </p>

                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label small fw-bold text-secondary">ফুটারে সংক্ষিপ্ত পরিচিতি লেখা (Footer About Description)</label>
                        <textarea class="form-control" name="footer_about_text" rows="2" placeholder="সংক্ষিপ্ত বিবরণ"><?= e($settings['footer_about_text'] ?? 'দেশ ও বিদেশের সত্য ও বস্তুনিষ্ঠ সংবাদ সবার আগে পাওয়ার নির্ভরযোগ্য ডিজিটাল ঠিকানা।') ?></textarea>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label small fw-bold text-secondary">কপিরাইট নোটিশ ফরম্যাট (Copyright Notice Template)</label>
                        <input type="text" class="form-control" name="copyright_text" value="<?= e($settings['copyright_text'] ?? 'কপিরাইট &copy; {YEAR} {SITE_NAME} &bull; সর্বস্বত্ব সংরক্ষিত') ?>" placeholder="কপিরাইট &copy; {YEAR} {SITE_NAME} &bull; সর্বস্বত্ব সংরক্ষিত">
                        <div class="form-text small">
                            <code>{YEAR}</code> দিলে বর্তমান বাংলা বছর এবং <code>{SITE_NAME}</code> দিলে স্বয়ংক্রিয়ভাবে ওয়েবসাইটের নাম বসবে।
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="border rounded p-3 bg-light">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-laptop me-1"></i>কারিগরি সহায়তা ও এজেন্সি ব্র্যান্ডিং (Developer Credit)</h6>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="show_tech_partner" id="show_tech_partner" <?= (!isset($settings['show_tech_partner']) || !empty($settings['show_tech_partner'])) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold small" for="show_tech_partner">ফুটারে কারিগরি সহায়তাকারীর নাম প্রদর্শন করুন (Show Developer Credit)</label>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">এজেন্সি / ডেভেলপারের নাম (Tech Partner Name)</label>
                                    <input type="text" class="form-control form-control-sm" name="tech_partner_name" value="<?= e($settings['tech_partner_name'] ?? 'DataEnvelope') ?>" placeholder="e.g. DataEnvelope বা আপনার এজেন্সির নাম">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">এজেন্সির ওয়েবসাইট লিংক (Tech Partner URL)</label>
                                    <input type="url" class="form-control form-control-sm" name="tech_partner_url" value="<?= e($settings['tech_partner_url'] ?? 'https://www.dataenvelope.com/') ?>" placeholder="https://www.youragency.com/">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Fixed Floating Save Button Bar -->
    <div class="card border-0 shadow p-3 bg-white sticky-bottom rounded-3 border-top border-warning border-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span class="text-muted small">
                <i class="bi bi-info-circle me-1 text-primary"></i>সব পরিবর্তন নিশ্চিত করতে "Save All Settings" বাটনে চাপুন।
            </span>
            <button type="submit" class="btn btn-warning text-white fw-bold px-4 py-2 shadow-sm">
                <i class="bi bi-save2-fill me-1"></i> Save All Settings & Customizations
            </button>
        </div>
    </div>
</form>

<script>
function syncColor(fromId, toId) {
    var val = document.getElementById(fromId).value;
    document.getElementById(toId).value = val;
}

function applyColorPreset(primary, secondary, accent, headerBg, topbarBg, navbarBg, footerBg) {
    document.getElementById('primary_color_picker').value = primary;
    document.getElementById('primary_color_input').value = primary;

    document.getElementById('secondary_color_picker').value = secondary;
    document.getElementById('secondary_color_input').value = secondary;

    document.getElementById('accent_color_picker').value = accent;
    document.getElementById('accent_color_input').value = accent;

    document.getElementById('header_bg_picker').value = headerBg;
    document.getElementById('header_bg_input').value = headerBg;

    document.getElementById('topbar_bg_picker').value = topbarBg;
    document.getElementById('topbar_bg_input').value = topbarBg;

    document.getElementById('navbar_bg_picker').value = navbarBg;
    document.getElementById('navbar_bg_input').value = navbarBg;

    document.getElementById('footer_bg_picker').value = footerBg;
    document.getElementById('footer_bg_input').value = footerBg;
}

function toggleAiEngine() {
    var isGemini = document.getElementById('provider_gemini').checked;
    var panel = document.getElementById('gemini_config_panel');
    panel.style.display = isGemini ? 'block' : 'none';
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
        btn.innerHTML = '<i class="bi bi-lightning-charge me-1"></i>Test';
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
        btn.innerHTML = '<i class="bi bi-lightning-charge me-1"></i>Test';
        statusDiv.style.display = 'block';
        statusDiv.className = 'alert alert-danger py-2 small mb-0 mt-2';
        statusDiv.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>Network request error.';
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
