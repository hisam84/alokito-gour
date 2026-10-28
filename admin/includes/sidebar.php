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
        <a href="index.php" class="text-white text-decoration-none d-flex align-items-center gap-2">
            <i class="bi bi-newspaper fs-4 text-warning"></i>
            <div>
                <span class="d-block lh-1 text-white fw-bold">আলোকিত গৌড়</span>
                <span class="d-block small text-warning" style="font-size:0.75rem;">কন্ট্রোল প্যানেল</span>
            </div>
        </a>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="index.php" class="<?= $currentScript === 'index.php' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                <span>ড্যাশবোর্ড</span>
            </a>
        </li>

        <li class="menu-header">সংবাদ ও কন্টেন্ট</li>
        <li>
            <a href="post-add.php" class="<?= $currentScript === 'post-add.php' ? 'active' : '' ?>">
                <i class="bi bi-pencil-square"></i>
                <span>নতুন সংবাদ প্রকাশ</span>
            </a>
        </li>
        <li>
            <a href="post-list.php" class="<?= in_array($currentScript, ['post-list.php', 'post-edit.php']) ? 'active' : '' ?>">
                <i class="bi bi-card-list"></i>
                <span>সকল সংবাদ তালিকা</span>
            </a>
        </li>
        <li>
            <a href="categories.php" class="<?= $currentScript === 'categories.php' ? 'active' : '' ?>">
                <i class="bi bi-tags"></i>
                <span>ক্যাটাগরি ব্যবস্থাপনা</span>
            </a>
        </li>
        <li>
            <a href="page-builder.php" class="<?= $currentScript === 'page-builder.php' ? 'active' : '' ?>">
                <i class="bi bi-layout-text-window-reverse"></i>
                <span>পেজ বিল্ডার (Page Builder)</span>
            </a>
        </li>

        <li class="menu-header">মার্কেটিং ও কনফিগারেশন</li>
        <li>
            <a href="ads-manager.php" class="<?= $currentScript === 'ads-manager.php' ? 'active' : '' ?>">
                <i class="bi bi-badge-ad"></i>
                <span>বিজ্ঞাপন ব্যানার</span>
            </a>
        </li>
        <li>
            <a href="settings.php" class="<?= $currentScript === 'settings.php' ? 'active' : '' ?>">
                <i class="bi bi-sliders"></i>
                <span>সাইট পরিচয় ও সেটিংস</span>
            </a>
        </li>

        <li class="menu-header">অ্যাকাউন্ট</li>
        <li>
            <a href="logout.php" class="text-danger">
                <i class="bi bi-box-arrow-right"></i>
                <span>লগআউট</span>
            </a>
        </li>
    </ul>

    <div class="p-3 text-center border-top border-secondary text-muted small" style="font-size: 0.75rem;">
        সংস্করণ ১.০.০ &bull; cPanel Ready
    </div>
</aside>
