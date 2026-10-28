<?php
/**
 * Admin Panel Sidebar Navigation
 * Alokito Gour Clone
 */

declare(strict_types=1);

$currentScript = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <a href="index.php" class="text-white text-decoration-none d-flex align-items-center gap-2" title="Alokito Gour Admin">
            <i class="bi bi-newspaper fs-4 text-warning sidebar-brand-icon flex-shrink-0"></i>
            <div class="sidebar-brand-text">
                <span class="d-block lh-1 text-white fw-bold">Alokito Gour</span>
                <span class="d-block small text-warning" style="font-size:0.75rem;">Control Panel</span>
            </div>
        </a>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="index.php" class="<?= $currentScript === 'index.php' ? 'active' : '' ?>" title="Dashboard">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="menu-header">News & Content</li>
        <li>
            <a href="post-add.php" class="<?= $currentScript === 'post-add.php' ? 'active' : '' ?>" title="Publish New Post">
                <i class="bi bi-pencil-square"></i>
                <span>Publish New Post</span>
            </a>
        </li>
        <li>
            <a href="post-list.php" class="<?= in_array($currentScript, ['post-list.php', 'post-edit.php']) ? 'active' : '' ?>" title="All Posts List">
                <i class="bi bi-card-list"></i>
                <span>All Posts List</span>
            </a>
        </li>
        <li>
            <a href="photo-card.php" class="<?= $currentScript === 'photo-card.php' ? 'active' : '' ?>" title="Facebook Photo Card">
                <i class="bi bi-card-image text-info"></i>
                <span>Photo Card Generator</span>
            </a>
        </li>
        <li>
            <a href="categories.php" class="<?= $currentScript === 'categories.php' ? 'active' : '' ?>" title="Categories">
                <i class="bi bi-tags"></i>
                <span>Categories</span>
            </a>
        </li>
        <li>
            <a href="page-builder.php" class="<?= $currentScript === 'page-builder.php' ? 'active' : '' ?>" title="Page Builder">
                <i class="bi bi-layout-text-window-reverse"></i>
                <span>Page Builder</span>
            </a>
        </li>
        <li>
            <a href="sidebar-manager.php" class="<?= $currentScript === 'sidebar-manager.php' ? 'active' : '' ?>" title="Sidebar Blocks">
                <i class="bi bi-layout-sidebar-inset-reverse"></i>
                <span>Sidebar Blocks</span>
            </a>
        </li>

        <li class="menu-header">Marketing & Config</li>
        <li>
            <a href="ads-manager.php" class="<?= $currentScript === 'ads-manager.php' ? 'active' : '' ?>" title="Ad Banners">
                <i class="bi bi-badge-ad"></i>
                <span>Ad Banners</span>
            </a>
        </li>
        <li>
            <a href="settings.php" class="<?= $currentScript === 'settings.php' ? 'active' : '' ?>" title="Site Settings">
                <i class="bi bi-sliders"></i>
                <span>Site Settings</span>
            </a>
        </li>

        <li class="menu-header">Account</li>
        <li>
            <a href="logout.php" class="text-danger" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>

    <div class="p-3 text-center border-top border-secondary text-muted small sidebar-footer" style="font-size: 0.75rem;">
        <span class="sidebar-footer-text">Version 1.0.0 &bull; Production Ready</span>
        <span class="sidebar-footer-short d-none">v1.0</span>
    </div>
</aside>
