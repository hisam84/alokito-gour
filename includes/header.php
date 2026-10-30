<?php
/**
 * Public Header
 * Alokito Gour Clone
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers.php';

$settings = get_settings();

// Dynamic Meta Values
$siteTitle = $settings['site_title'] ?? 'Alokito Gour - আলোকিত গৌড়';
$pageTitle = !empty($metaTitle) ? e($metaTitle) . ' | ' . e($siteTitle) : e($siteTitle);
$metaDesc = !empty($metaDescription) ? e($metaDescription) : e($settings['site_tagline'] ?? 'চাঁপাইনবাবগঞ্জ, রাজশাহী ও দেশ-বিদেশের সব খবর সবার আগে পেতে ভিজিট করুন আলোকিত গৌড় ডট কম।');
$metaImg = !empty($metaImage) ? e($metaImage) : (!empty($settings['logo_path']) ? e($settings['logo_path']) : '');
$hostName = $_SERVER['HTTP_HOST'] ?? 'localhost';
$reqUri = $_SERVER['REQUEST_URI'] ?? '/';
$isReqHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || ((isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'));
$currentUrl = ($isReqHttps ? "https" : "http") . "://{$hostName}{$reqUri}";

// Bangla calendar date used in the reference site's header.
$headerToday = new DateTimeImmutable('now');
$headerYear = (int)$headerToday->format('Y');
$banglaYearStart = new DateTimeImmutable($headerYear . '-04-14');
if ($headerToday < $banglaYearStart) {
    $banglaYearStart = new DateTimeImmutable(($headerYear - 1) . '-04-14');
}
$banglaYear = (int)$banglaYearStart->format('Y') - 593;
$falgunYear = (int)$banglaYearStart->format('Y') + 1;
$isFalgunLeapYear = checkdate(2, 29, $falgunYear);
$banglaMonths = ['বৈশাখ', 'জ্যৈষ্ঠ', 'আষাঢ়', 'শ্রাবণ', 'ভাদ্র', 'আশ্বিন', 'কার্তিক', 'অগ্রহায়ণ', 'পৌষ', 'মাঘ', 'ফাল্গুন', 'চৈত্র'];
$banglaMonthLengths = [31, 31, 31, 31, 31, 30, 30, 30, 30, 30, $isFalgunLeapYear ? 31 : 30, 30];
$banglaDayOffset = (int)$banglaYearStart->diff($headerToday)->format('%a');
$banglaCalendarDate = '';
foreach ($banglaMonths as $monthIndex => $banglaMonth) {
    if ($banglaDayOffset < $banglaMonthLengths[$monthIndex]) {
        $banglaCalendarDate = bn_number($banglaDayOffset + 1) . ' ' . $banglaMonth . ' ' . bn_number($banglaYear);
        break;
    }
    $banglaDayOffset -= $banglaMonthLengths[$monthIndex];
}
$headerEmail = 'alokitogour@gmail.com';
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <title><?= $pageTitle ?></title>
    <meta name="description" content="<?= $metaDesc ?>">
    <meta name="keywords" content="<?= !empty($metaKeywords) ? e($metaKeywords) : 'চাঁপাইনবাবগঞ্জ, রাজশাহী, বাংলা খবর, সর্বশেষ খবর, ব্রেকিং নিউজ, আলোকিত গৌড়' ?>">
    
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

    <!-- Bengali Font: SolaimanLipi -->
    <link href="https://fonts.maateen.me/solaiman-lipi/font.css" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Main Stylesheet -->
    <link href="/assets/css/style.css" rel="stylesheet">
    
    <?php if (!empty($settings['favicon_path'])): ?>
    <link rel="shortcut icon" href="<?= e($settings['favicon_path']) ?>">
    <?php endif; ?>
</head>
<body>

<!-- Reference-style Top Bar -->
<div class="site-topbar">
    <div class="container">
        <div class="topbar-main">
            <div class="topbar-info">
                <a href="mailto:<?= e($headerEmail) ?>" class="header-email">
                    <i class="bi bi-envelope"></i>
                    <span><?= e($headerEmail) ?></span>
                </a>
                <span class="topbar-date">
                    <i class="bi bi-calendar-event"></i>
                    <?= bn_date(time(), false) ?>
                </span>
                <span class="topbar-date topbar-bangla-date"><?= e($banglaCalendarDate) ?></span>
            </div>

            <div class="topbar-actions">
                <div class="social-icons">
                    <?php if (!empty($settings['facebook_url'])): ?>
                        <a href="<?= e($settings['facebook_url']) ?>" target="_blank" rel="noopener" aria-label="Facebook" class="social-facebook"><i class="bi bi-facebook"></i></a>
                    <?php endif; ?>
                    <a href="https://twitter.com/" target="_blank" rel="noopener" aria-label="Twitter" class="social-twitter"><i class="bi bi-twitter"></i></a>
                    <?php if (!empty($settings['youtube_url'])): ?>
                        <a href="<?= e($settings['youtube_url']) ?>" target="_blank" rel="noopener" aria-label="YouTube" class="social-youtube"><i class="bi bi-youtube"></i></a>
                    <?php endif; ?>
                </div>
                <button type="button" class="header-search" data-bs-toggle="modal" data-bs-target="#searchModal" aria-label="সংবাদ অনুসন্ধান">
                    <i class="bi bi-search"></i>
                </button>
                <a href="https://alokitogour.com/video-gallery/gallery/video" class="header-video-link">
                    <i class="bi bi-camera-video"></i>
                    <span>ভিডিও</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Reference-style Main Branding Header -->
<header class="site-header">
    <div class="container">
        <div class="header-branding">
            <a href="/" class="brand-logo" aria-label="আলোকিত গৌড় - প্রচ্ছদ">
                <?php if (!empty($settings['logo_path'])): ?>
                    <img src="<?= e($settings['logo_path']) ?>" alt="আলোকিত গৌড়">
                <?php else: ?>
                    <span class="brand-logo-fallback">আলোকিত গৌড়</span>
                <?php endif; ?>
            </a>

            <div class="header-ad">
                <?= render_ad('header_right') ?>
            </div>
        </div>
    </div>
</header>
