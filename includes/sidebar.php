<?php
/**
 * Public Sidebar Widget
 * Alokito Gour Clone
 */

declare(strict_types=1);

$sideDb = get_db();

// 1. Fetch Latest 7 Posts
$latestPosts = $sideDb->query("
    SELECT id, title, slug, thumbnail, created_at, views 
    FROM `posts` 
    WHERE `status` = 'published' 
    ORDER BY `id` DESC 
    LIMIT 7
")->fetchAll();

// 2. Fetch Most Popular 7 Posts
$popularPosts = $sideDb->query("
    SELECT id, title, slug, thumbnail, created_at, views 
    FROM `posts` 
    WHERE `status` = 'published' 
    ORDER BY `views` DESC, `id` DESC 
    LIMIT 7
")->fetchAll();
?>

<!-- Top Sidebar Advertisement Board -->
<?= render_ad('sidebar') ?>

<!-- Tabbed Widget: Latest / Popular -->
<div class="sidebar-widget">
    <ul class="nav nav-tabs sidebar-tabs mb-3 border-bottom" id="sidebarTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="latest-tab" data-bs-toggle="tab" data-bs-target="#latest-pane" type="button" role="tab" aria-controls="latest-pane" aria-selected="true">
                <i class="bi bi-clock-history me-1"></i>সর্বশেষ
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="popular-tab" data-bs-toggle="tab" data-bs-target="#popular-pane" type="button" role="tab" aria-controls="popular-pane" aria-selected="false">
                <i class="bi bi-fire me-1"></i>জনপ্রিয়
            </button>
        </li>
    </ul>

    <div class="tab-content" id="sidebarTabContent">
        <!-- Latest News Pane -->
        <div class="tab-pane fade show active" id="latest-pane" role="tabpanel" aria-labelledby="latest-tab" tabindex="0">
            <?php $counter = 1; foreach ($latestPosts as $item): ?>
                <div class="tab-post-item">
                    <div class="item-num"><?= bn_number($counter++) ?></div>
                    <div class="item-content">
                        <a href="/news/<?= (int)$item['id'] ?>/<?= e($item['slug']) ?>" class="item-title d-block">
                            <?= e($item['title']) ?>
                        </a>
                        <span class="small text-muted" style="font-size:11px;">
                            <i class="bi bi-clock me-1"></i><?= get_time_ago($item['created_at']) ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Popular News Pane -->
        <div class="tab-pane fade" id="popular-pane" role="tabpanel" aria-labelledby="popular-tab" tabindex="0">
            <?php $counter = 1; foreach ($popularPosts as $item): ?>
                <div class="tab-post-item">
                    <div class="item-num"><?= bn_number($counter++) ?></div>
                    <div class="item-content">
                        <a href="/news/<?= (int)$item['id'] ?>/<?= e($item['slug']) ?>" class="item-title d-block">
                            <?= e($item['title']) ?>
                        </a>
                        <span class="small text-muted" style="font-size:11px;">
                            <i class="bi bi-eye me-1"></i><?= bn_number($item['views']) ?> বার পঠিত
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Regional / District Filter Widget -->
<div class="sidebar-widget">
    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
        <i class="bi bi-geo-alt-fill text-danger me-1"></i>আমার এলাকার খবর
    </h6>
    <div class="d-flex flex-wrap gap-2">
        <a href="/category/chapai-nawabganj" class="btn btn-sm btn-outline-secondary">চাঁপাইনবাবগঞ্জ</a>
        <a href="/category/country" class="btn btn-sm btn-outline-secondary">রাজশাহী</a>
        <a href="/category/country" class="btn btn-sm btn-outline-secondary">ঢাকা</a>
        <a href="/category/country" class="btn btn-sm btn-outline-secondary">চট্টগ্রাম</a>
        <a href="/category/country" class="btn btn-sm btn-outline-secondary">খুলনা</a>
        <a href="/category/country" class="btn btn-sm btn-outline-secondary">বরিশাল</a>
        <a href="/category/country" class="btn btn-sm btn-outline-secondary">সিলেট</a>
        <a href="/category/country" class="btn btn-sm btn-outline-secondary">রংপুর</a>
        <a href="/category/country" class="btn btn-sm btn-outline-secondary">ময়মনসিংহ</a>
    </div>
</div>
