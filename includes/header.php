<?php
/**
 * Public Header - 100% White-Label & Customizable News Portal Template
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers.php';

$settings = get_settings();

// Dynamic Meta Values
$siteTitle = $settings['site_title'] ?? 'বাংলা অনলাইন নিউজ পোর্টাল';
$siteTagline = $settings['site_tagline'] ?? 'সত্য ও বস্তুনিষ্ঠ সংবাদের বিশ্বস্ত ঠিকানা';
$pageTitle = !empty($metaTitle) ? e($metaTitle) . ' | ' . e($siteTitle) : e($siteTitle);
$metaDesc = !empty($metaDescription) ? e($metaDescription) : (!empty($settings['meta_description']) ? e($settings['meta_description']) : e($siteTagline));
$metaImg = !empty($metaImage) ? e($metaImage) : (!empty($settings['logo_path']) ? e($settings['logo_path']) : get_default_thumbnail());
$metaKw = !empty($metaKeywords) ? e($metaKeywords) : (!empty($settings['meta_keywords']) ? e($settings['meta_keywords']) : 'বাংলা খবর, সর্বশেষ খবর, ব্রেকিং নিউজ, জাতীয় সংবাদ');

$hostName = $_SERVER['HTTP_HOST'] ?? 'localhost';
$reqUri = $_SERVER['REQUEST_URI'] ?? '/';
$isReqHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || ((isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'));
$currentUrl = ($isReqHttps ? "https" : "http") . "://{$hostName}{$reqUri}";

// Theme colors & typography defaults
$primaryColor = !empty($settings['primary_color']) ? $settings['primary_color'] : '#f3730d';
$secondaryColor = !empty($settings['secondary_color']) ? $settings['secondary_color'] : '#c82333';
$accentColor = !empty($settings['accent_color']) ? $settings['accent_color'] : '#0abb75';
$headerBg = !empty($settings['header_bg_color']) ? $settings['header_bg_color'] : '#ffffff';
$topbarBg = !empty($settings['topbar_bg_color']) ? $settings['topbar_bg_color'] : '#f8f9fa';
$navbarBg = !empty($settings['navbar_bg_color']) ? $settings['navbar_bg_color'] : '#ffffff';
$footerBg = !empty($settings['footer_bg_color']) ? $settings['footer_bg_color'] : '#1a1e21';
$fontFamily = !empty($settings['font_family']) ? $settings['font_family'] : 'SolaimanLipi';
$fontSizeBase = !empty($settings['font_size_base']) ? $settings['font_size_base'] : '16px';

$showTopbar = (int)($settings['show_topbar'] ?? 1);
$showDateBn = (int)($settings['show_date_bangla'] ?? 1);
$showDateEn = (int)($settings['show_date_english'] ?? 0);
$showWeather = (int)($settings['show_weather'] ?? 1);
$weatherCity = !empty($settings['weather_city']) ? $settings['weather_city'] : 'ঢাকা';
$showLiveTv = (int)($settings['show_live_tv'] ?? 0);
$liveTvUrl = $settings['live_tv_url'] ?? '#';
$showEpaper = (int)($settings['show_epaper'] ?? 0);
$epaperUrl = $settings['epaper_url'] ?? '#';
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <title><?= $pageTitle ?></title>
    <meta name="description" content="<?= $metaDesc ?>">
    <meta name="keywords" content="<?= $metaKw ?>">
    
    <?php if (!empty($settings['google_site_verification'])): ?>
    <meta name="google-site-verification" content="<?= e($settings['google_site_verification']) ?>">
    <?php endif; ?>
    
    <!-- OpenGraph & Twitter Meta -->
    <meta property="og:title" content="<?= $pageTitle ?>">
    <meta property="og:description" content="<?= $metaDesc ?>">
    <?php if (!empty($metaImg)): ?>
    <meta property="og:image" content="<?= $metaImg ?>">
    <meta name="twitter:image" content="<?= $metaImg ?>">
    <?php endif; ?>
    <meta property="og:url" content="<?= e($currentUrl) ?>">
    <meta property="og:type" content="article">
    <meta name="twitter:card" content="summary_large_image">

    <!-- Bengali Fonts CDN -->
    <?php if ($fontFamily === 'Hind Siliguri'): ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php elseif ($fontFamily === 'Anek Bangla'): ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anek+Bangla:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php elseif ($fontFamily === 'Noto Sans Bengali'): ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php elseif ($fontFamily === 'Kalpurush'): ?>
    <link href="https://fonts.maateen.me/kalpurush/font.css" rel="stylesheet">
    <?php else: ?>
    <!-- SolaimanLipi Default -->
    <link href="https://fonts.maateen.me/solaiman-lipi/font.css" rel="stylesheet">
    <?php endif; ?>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Main Stylesheet -->
    <link href="/assets/css/style.css" rel="stylesheet">
    
    <!-- Dynamic Custom Theme Styling Engine -->
    <style id="custom-theme-variables">
    :root {
        --brand-primary: <?= e($primaryColor) ?>;
        --brand-deeporange: <?= e($primaryColor) ?>;
        --brand-red: <?= e($secondaryColor) ?>;
        --brand-green: <?= e($accentColor) ?>;
        --topbar-bg: <?= e($topbarBg) ?>;
        --header-bg: <?= e($headerBg) ?>;
        --navbar-bg: <?= e($navbarBg) ?>;
        --footer-bg: <?= e($footerBg) ?>;
        --base-font-size: <?= e($fontSizeBase) ?>;
    }
    body {
        font-family: '<?= e($fontFamily) ?>', 'SolaimanLipi', Arial, sans-serif !important;
        font-size: var(--base-font-size);
    }
    .site-topbar { background-color: var(--topbar-bg) !important; }
    .site-header { background-color: var(--header-bg) !important; }
    .site-navbar { background-color: var(--navbar-bg) !important; border-bottom: 2px solid var(--brand-primary) !important; }
    .site-footer { background-color: var(--footer-bg) !important; }
    .btn-warning, .bg-warning {
        background-color: var(--brand-primary) !important;
        border-color: var(--brand-primary) !important;
    }
    .text-warning {
        color: var(--brand-primary) !important;
    }
    .border-warning {
        border-color: var(--brand-primary) !important;
    }
    .ticker-badge {
        background-color: var(--brand-red) !important;
    }
    .section-title {
        border-left-color: var(--brand-primary) !important;
    }
    .badge-primary-custom {
        background-color: var(--brand-primary) !important;
        color: #fff !important;
    }
    .live-tv-btn {
        background: #dc3545;
        color: #fff !important;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 12px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .epaper-btn {
        background: #0d6efd;
        color: #fff !important;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 12px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    </style>
    
    <?php if (!empty($settings['favicon_path'])): ?>
    <link rel="shortcut icon" href="<?= e($settings['favicon_path']) ?>">
    <?php endif; ?>

    <!-- Google Analytics 4 (GA4) -->
    <?php if (!empty($settings['ga4_id'])): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($settings['ga4_id']) ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '<?= e($settings['ga4_id']) ?>');
    </script>
    <?php endif; ?>

    <!-- Facebook Pixel Code -->
    <?php if (!empty($settings['fb_pixel_id'])): ?>
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '<?= e($settings['fb_pixel_id']) ?>');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?= e($settings['fb_pixel_id']) ?>&ev=PageView&noscript=1"/></noscript>
    <?php endif; ?>

    <!-- Custom Head Code Injection -->
    <?php if (!empty($settings['custom_head_code'])): ?>
    <?= $settings['custom_head_code'] ?>
    <?php endif; ?>
</head>
<body>

<?php if ($showTopbar): ?>
<!-- Top Bar -->
<div class="site-topbar">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3 small flex-wrap">
                <?php if ($showDateBn): ?>
                <div class="d-flex align-items-center gap-1">
                    <i class="bi bi-calendar-event text-warning"></i>
                    <span><?= bn_date(time(), false) ?></span>
                </div>
                <?php endif; ?>

                <?php if ($showDateEn): ?>
                <div class="d-none d-md-flex align-items-center gap-1 border-start ps-3 text-secondary">
                    <i class="bi bi-calendar3"></i>
                    <span><?= date('l, d F Y') ?></span>
                </div>
                <?php endif; ?>

                <?php if ($showWeather): ?>
                <div class="d-none d-lg-flex align-items-center gap-1 border-start ps-3 text-muted">
                    <i class="bi bi-cloud-sun text-warning"></i>
                    <span><?= e($weatherCity) ?></span>
                </div>
                <?php endif; ?>

                <?php if ($showLiveTv): ?>
                <a href="<?= e($liveTvUrl) ?>" class="live-tv-btn ms-1" target="_blank" rel="noopener">
                    <i class="bi bi-broadcast"></i> লাইভ টিভি
                </a>
                <?php endif; ?>

                <?php if ($showEpaper): ?>
                <a href="<?= e($epaperUrl) ?>" class="epaper-btn ms-1" target="_blank" rel="noopener">
                    <i class="bi bi-newspaper"></i> ই-পেপার
                </a>
                <?php endif; ?>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="social-icons d-none d-sm-flex align-items-center gap-2">
                    <?php if (!empty($settings['facebook_url'])): ?>
                        <a href="<?= e($settings['facebook_url']) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($settings['youtube_url'])): ?>
                        <a href="<?= e($settings['youtube_url']) ?>" target="_blank" rel="noopener" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($settings['twitter_url'])): ?>
                        <a href="<?= e($settings['twitter_url']) ?>" target="_blank" rel="noopener" aria-label="X"><i class="bi bi-twitter-x"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($settings['instagram_url'])): ?>
                        <a href="<?= e($settings['instagram_url']) ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($settings['whatsapp_channel_url'])): ?>
                        <a href="<?= e($settings['whatsapp_channel_url']) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                    <?php endif; ?>
                </div>
                <a href="/admin/login.php" class="text-muted small border-start ps-3" title="অ্যাডমিন প্যানেল"><i class="bi bi-lock me-1"></i>লগইন</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Main Branding Header -->
<header class="site-header">
    <div class="container">
        <div class="row align-items-center g-3">
            <div class="col-md-4 text-center text-md-start">
                <a href="/" class="brand-logo d-inline-block">
                    <?php if (!empty($settings['logo_path'])): ?>
                        <img src="<?= e($settings['logo_path']) ?>" alt="<?= e($siteTitle) ?>" class="img-fluid">
                    <?php else: ?>
                        <h2 class="fw-bold text-dark mb-0"><?= e($siteTitle) ?></h2>
                    <?php endif; ?>
                </a>
                <?php if (!empty($settings['site_tagline'])): ?>
                    <p class="small text-muted mb-0 mt-1 d-none d-md-block"><?= e($settings['site_tagline']) ?></p>
                <?php endif; ?>
            </div>

            <div class="col-md-8 text-center text-md-end">
                <?= render_ad('header_right') ?>
            </div>
        </div>
    </div>
</header>
