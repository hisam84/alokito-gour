<?php
/**
 * Public Navbar Navigation
 * Alokito Gour Clone
 */

declare(strict_types=1);

$navDb = get_db();
$menuCategories = $navDb->query("
    SELECT * FROM `categories` 
    WHERE `show_in_menu` = 1 
    ORDER BY `order_index` ASC, `id` ASC
")->fetchAll();

$primaryCount = 7;
$primaryNav = array_slice($menuCategories, 0, $primaryCount);
$moreNav = array_slice($menuCategories, $primaryCount);

$currentSlug = $_GET['slug'] ?? '';
$isHome = empty($currentSlug) && basename($_SERVER['PHP_SELF']) === 'index.php';
?>
<!-- Main Navigation Bar -->
<nav class="navbar navbar-expand-lg site-navbar p-0">
    <div class="container">
        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler my-2 border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNavDrawer" aria-controls="mobileNavDrawer" aria-label="Toggle navigation">
            <i class="bi bi-list fs-2 text-dark"></i>
        </button>

        <a class="navbar-brand d-lg-none fw-bold text-dark fs-6" href="/">আলোকিত গৌড়</a>

        <!-- Desktop Nav Items -->
        <div class="collapse navbar-collapse d-none d-lg-block" id="desktopNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $isHome ? 'active text-warning' : '' ?>" href="/"><i class="bi bi-house-door-fill me-1"></i>প্রচ্ছদ</a>
                </li>
                
                <?php foreach ($primaryNav as $cat): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentSlug === $cat['slug']) ? 'active text-warning' : '' ?>" href="/category/<?= e($cat['slug']) ?>">
                            <?= e($cat['name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>

                <?php if (!empty($moreNav)): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            আরও
                        </a>
                        <ul class="dropdown-menu border-0 shadow">
                            <?php foreach ($moreNav as $cat): ?>
                                <li>
                                    <a class="dropdown-item" href="/category/<?= e($cat['slug']) ?>"><?= e($cat['name']) ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- Search Icon -->
            <div class="d-flex align-items-center">
                <button type="button" class="btn btn-link text-dark p-2" data-bs-toggle="modal" data-bs-target="#searchModal" title="সংবাদ অনুসন্ধান">
                    <i class="bi bi-search fs-5"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Search Trigger -->
        <div class="d-lg-none">
            <button type="button" class="btn btn-link text-dark p-2" data-bs-toggle="modal" data-bs-target="#searchModal" title="অনুসন্ধান">
                <i class="bi bi-search fs-5"></i>
            </button>
        </div>
    </div>
</nav>

<!-- Mobile Offcanvas Navigation Drawer -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileNavDrawer" aria-labelledby="mobileNavLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold text-dark" id="mobileNavLabel">মেনু</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form action="/search" method="GET" class="mb-3">
            <div class="input-group">
                <input type="text" class="form-control" name="q" placeholder="সংবাদ খুঁজুন..." required>
                <button class="btn btn-warning text-white" type="submit"><i class="bi bi-search"></i></button>
            </div>
        </form>

        <ul class="list-unstyled">
            <li class="border-bottom py-2">
                <a href="/" class="fw-bold text-dark text-decoration-none d-block"><i class="bi bi-house-door me-2 text-warning"></i>প্রচ্ছদ</a>
            </li>
            <?php foreach ($menuCategories as $cat): ?>
                <li class="border-bottom py-2">
                    <a href="/category/<?= e($cat['slug']) ?>" class="fw-medium text-dark text-decoration-none d-block">
                        <?= e($cat['name']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<!-- Search Modal -->
<div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="searchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="searchModalLabel">সংবাদ অনুসন্ধান</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form action="/search" method="GET">
                    <div class="input-group input-group-lg">
                        <input type="text" class="form-control fs-6" id="mainSearchInput" name="q" placeholder="কী খুঁজতে চান লিখুন..." required>
                        <button class="btn btn-warning text-white px-4 fw-bold" type="submit">খুঁজুন</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
