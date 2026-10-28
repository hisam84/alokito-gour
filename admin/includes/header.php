<?php
/**
 * Admin Panel Header Layout
 * Alokito Gour Clone
 */

declare(strict_types=1);

$settings = get_settings();
$adminName = $_SESSION['admin_user_name'] ?? 'Admin';
$pageTitle = $pageTitle ?? 'Admin Dashboard | Alokito Gour';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    
    <!-- Google Bengali Font: SolaimanLipi -->
    <link href="https://fonts.maateen.me/solaiman-lipi/font.css" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Summernote WYSIWYG CSS (Lightweight Editor) -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.css" rel="stylesheet">
    
    <!-- Admin Custom CSS -->
    <link href="assets/css/admin.css" rel="stylesheet">
    
    <?php if (!empty($settings['favicon_path'])): ?>
    <link rel="shortcut icon" href="<?= e($settings['favicon_path']) ?>">
    <?php endif; ?>

    <script>
        // Check saved sidebar state immediately before render to prevent layout shift (FOUC)
        try {
            if (localStorage.getItem('admin_sidebar_collapsed') === 'true' && window.innerWidth >= 992) {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch(e) {}
    </script>
</head>
<body>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="admin-wrapper">
    <!-- Left Navigation Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Column -->
    <div class="admin-content">
        <!-- Top Navbar -->
        <header class="admin-topbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn-toggle-sidebar" id="sidebarToggle" aria-label="Toggle Sidebar" title="Collapse / Expand Sidebar">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <h5 class="mb-0 text-dark fw-bold d-none d-sm-block"><?= e($pageTitle) ?></h5>
            </div>

            <div class="d-flex align-items-center gap-3">
                <a href="../index.php" target="_blank" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-box-arrow-up-right"></i>
                    <span class="d-none d-md-inline">View Website</span>
                </a>

                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle d-flex align-items-center gap-2 border" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5 text-secondary"></i>
                        <span class="fw-medium text-dark"><?= e($adminName) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li><span class="dropdown-item-text small text-muted"><?= e($_SESSION['admin_user_email'] ?? '') ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Site Settings</a></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Main Workspace -->
        <main class="admin-main">
            <!-- Flash Message Banner -->
            <?php $flash = get_flash(); if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
