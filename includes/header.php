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
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$hostName}{$reqUri}";
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <title><?= $pageTitle ?></title>
    <meta name="description" content="<?= $metaDesc ?>">
    <meta name="keywords" content="চাঁপাইনবাবগঞ্জ, রাজশাহী, বাংলা খবর, সর্বশেষ খবর, ব্রেকিং নিউজ, আলোকিত গৌড়">
    
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

<!-- Top Bar -->
<div class="site-topbar">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-calendar-event text-warning"></i>
                <span><?= bn_date(time(), false) ?></span>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="social-icons d-none d-sm-block">
                    <?php if (!empty($settings['facebook_url'])): ?>
                        <a href="<?= e($settings['facebook_url']) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($settings['youtube_url'])): ?>
                        <a href="<?= e($settings['youtube_url']) ?>" target="_blank" rel="noopener" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                    <?php endif; ?>
                </div>
                <a href="/admin/login.php" class="text-muted small border-start ps-3" title="অ্যাডমিন প্যানেল"><i class="bi bi-lock me-1"></i>লগইন</a>
            </div>
        </div>
    </div>
</div>

<!-- Main Branding Header -->
<header class="site-header">
    <div class="container">
        <div class="row align-items-center g-3">
            <div class="col-md-4 text-center text-md-start">
                <a href="/" class="brand-logo d-inline-block">
                    <?php if (!empty($settings['logo_path'])): ?>
                        <img src="<?= e($settings['logo_path']) ?>" alt="<?= e($siteTitle) ?>" class="img-fluid">
                    <?php else: ?>
                        <h2 class="fw-bold text-dark mb-0">আলোকিত গৌড়</h2>
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
