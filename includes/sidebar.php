<?php
/**
 * Dynamic Public Sidebar Component
 * Supports custom blocks, page targeting (Home, Single, Category, Search, All), and position ordering
 * Alokito Gour News Portal
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/bangladesh_geo.php';

$sideDb = get_db();

// Determine current page context
if (!isset($currentPageContext)) {
    if (isset($post) && !empty($post['id'])) {
        $currentPageContext = 'single';
    } elseif (isset($category) && !empty($category['id'])) {
        $currentPageContext = 'category';
    } elseif (isset($_GET['division']) || basename($_SERVER['PHP_SELF'] ?? '') === 'area.php') {
        $currentPageContext = 'area';
    } elseif (isset($_GET['q']) || basename($_SERVER['PHP_SELF'] ?? '') === 'search.php') {
        $currentPageContext = 'search';
    } elseif (basename($_SERVER['PHP_SELF'] ?? '') === 'index.php' || empty($_SERVER['PATH_INFO'] ?? '')) {
        $currentPageContext = 'home';
    } else {
        $currentPageContext = 'all';
    }
}

// Fetch all active sidebar blocks ordered by position
try {
    $stmt = $sideDb->query("
        SELECT * FROM `sidebar_blocks` 
        WHERE `status` = 1 
        ORDER BY `position_order` ASC, `id` ASC
    ");
    $allBlocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $allBlocks = [];
}

// Filter blocks matching the current page context
$matchingBlocks = [];
foreach ($allBlocks as $blk) {
    $target = trim($blk['page_target'] ?? 'all');
    if ($target === 'all') {
        $matchingBlocks[] = $blk;
        continue;
    }

    $targets = array_map('trim', explode(',', $target));
    if (in_array($currentPageContext, $targets, true) || in_array('all', $targets, true)) {
        $matchingBlocks[] = $blk;
    }
}

// If no matching blocks found in database, fallback to standard defaults
if (empty($matchingBlocks)) {
    $matchingBlocks = [
        ['id' => 0, 'block_type' => 'ad_banner', 'show_title' => 0, 'settings' => json_encode(['position' => 'sidebar'])],
        ['id' => 0, 'block_type' => 'latest_popular', 'show_title' => 0, 'settings' => json_encode(['limit' => 7])],
        ['id' => 0, 'block_type' => 'district_filter', 'title' => 'আমার এলাকার খবর', 'show_title' => 1, 'settings' => json_encode([])],
    ];
}

// Helper: Query latest and popular posts only when needed
$sideLatestPosts = null;
$sidePopularPosts = null;

if (!function_exists('get_sidebar_latest_posts')) {
    function get_sidebar_latest_posts(PDO $db, int $limit = 7): array {
        global $sideLatestPosts;
        if ($sideLatestPosts === null) {
            $stmt = $db->prepare("
                SELECT id, title, slug, thumbnail, created_at, views 
                FROM `posts` 
                WHERE `status` = 'published' 
                ORDER BY `id` DESC 
                LIMIT :lim
            ");
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $sideLatestPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $sideLatestPosts;
    }
}

if (!function_exists('get_sidebar_popular_posts')) {
    function get_sidebar_popular_posts(PDO $db, int $limit = 7): array {
        global $sidePopularPosts;
        if ($sidePopularPosts === null) {
            $stmt = $db->prepare("
                SELECT id, title, slug, thumbnail, created_at, views 
                FROM `posts` 
                WHERE `status` = 'published' 
                ORDER BY `views` DESC, `id` DESC 
                LIMIT :lim
            ");
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $sidePopularPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $sidePopularPosts;
    }
}

// Render each matching sidebar block in order
foreach ($matchingBlocks as $block):
    $bType = $block['block_type'] ?? 'custom_html';
    $bTitle = $block['title'] ?? '';
    $showTitle = !empty($block['show_title']);
    $settings = !empty($block['settings']) ? json_decode($block['settings'], true) : [];
    if (!is_array($settings)) $settings = [];
?>

    <?php if ($bType === 'ad_banner'): ?>
        <!-- 1. Advertisement Banner Block -->
        <?php 
        $adPosition = $settings['position'] ?? 'sidebar';
        
        // Single Article page custom ad override check
        if (isset($post['sidebar_ad_type'])) {
            if ($post['sidebar_ad_type'] === 'none') {
                // Do not display
            } elseif ($post['sidebar_ad_type'] === 'code' && !empty($post['sidebar_ad_code'])) {
                echo '<div class="ad-container ad-sidebar mb-4 text-center">' . $post['sidebar_ad_code'] . '</div>';
            } elseif ($post['sidebar_ad_type'] === 'custom' && !empty($post['sidebar_ad_image'])) {
                $target = !empty($post['sidebar_ad_url']) ? e($post['sidebar_ad_url']) : '#';
                echo '<div class="ad-container ad-sidebar mb-4 text-center">
                    <a href="' . $target . '" target="_blank" rel="noopener" class="d-block w-100">
                        <img src="' . e($post['sidebar_ad_image']) . '" alt="Advertisement" class="img-fluid rounded border shadow-sm w-100" style="width: 100%; height: auto; display: block;">
                    </a>
                </div>';
            } else {
                echo render_ad($adPosition);
            }
        } else {
            echo render_ad($adPosition);
        }
        ?>

    <?php elseif ($bType === 'latest_popular'): ?>
        <!-- 2. Latest & Popular News Tabs Block -->
        <?php 
        $postLimit = (int)($settings['limit'] ?? 7);
        $latestPosts = get_sidebar_latest_posts($sideDb, $postLimit);
        $popularPosts = get_sidebar_popular_posts($sideDb, $postLimit);
        ?>
        <div class="sidebar-widget mb-4" data-sidebar-block="latest_popular">
            <?php if ($showTitle && !empty($bTitle)): ?>
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="bi bi-clock-history text-danger me-1"></i><?= e($bTitle) ?>
                </h6>
            <?php endif; ?>

            <ul class="nav nav-tabs sidebar-tabs mb-3 border-bottom" id="sidebarTab_<?= (int)($block['id'] ?? 1) ?>" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="latest-tab-<?= (int)($block['id'] ?? 1) ?>" data-bs-toggle="tab" data-bs-target="#latest-pane-<?= (int)($block['id'] ?? 1) ?>" type="button" role="tab" aria-controls="latest-pane" aria-selected="true">
                        <i class="bi bi-clock-history me-1"></i>সর্বশেষ
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="popular-tab-<?= (int)($block['id'] ?? 1) ?>" data-bs-toggle="tab" data-bs-target="#popular-pane-<?= (int)($block['id'] ?? 1) ?>" type="button" role="tab" aria-controls="popular-pane" aria-selected="false">
                        <i class="bi bi-fire me-1"></i>জনপ্রিয়
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="sidebarTabContent_<?= (int)($block['id'] ?? 1) ?>">
                <!-- Latest News Pane -->
                <div class="tab-pane fade show active" id="latest-pane-<?= (int)($block['id'] ?? 1) ?>" role="tabpanel" aria-labelledby="latest-tab-<?= (int)($block['id'] ?? 1) ?>" tabindex="0">
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
                <div class="tab-pane fade" id="popular-pane-<?= (int)($block['id'] ?? 1) ?>" role="tabpanel" aria-labelledby="popular-tab-<?= (int)($block['id'] ?? 1) ?>" tabindex="0">
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

    <?php elseif ($bType === 'district_filter'): ?>
        <!-- 3. Regional / District Filter Block -->
        <?= render_area_filter_bar([], true, !empty($bTitle) ? $bTitle : 'আমার এলাকার খবর') ?>
    <?php elseif ($bType === 'legacy_district_filter'): ?>
        <!-- 3. Regional / District Filter Block -->
        <div class="sidebar-widget mb-4" data-sidebar-block="district_filter">
            <?php if ($showTitle && !empty($bTitle)): ?>
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= e($bTitle) ?>
                </h6>
            <?php endif; ?>
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

    <?php elseif ($bType === 'category_news'): ?>
        <!-- 4. Category News List Block -->
        <?php 
        $catSlug = $settings['category_slug'] ?? 'chapai-nawabganj';
        $catLimit = (int)($settings['limit'] ?? 5);
        $catNewsStmt = $sideDb->prepare("
            SELECT p.id, p.title, p.slug, p.thumbnail, p.created_at 
            FROM `posts` p 
            INNER JOIN `categories` c ON p.category_id = c.id 
            WHERE c.slug = :cslug AND p.status = 'published' 
            ORDER BY p.id DESC 
            LIMIT :lim
        ");
        $catNewsStmt->bindValue(':cslug', $catSlug);
        $catNewsStmt->bindValue(':lim', $catLimit, PDO::PARAM_INT);
        $catNewsStmt->execute();
        $catItems = $catNewsStmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div class="sidebar-widget mb-4" data-sidebar-block="category_news">
            <?php if ($showTitle && !empty($bTitle)): ?>
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-card-heading text-primary me-1"></i><?= e($bTitle) ?></span>
                    <a href="/category/<?= e($catSlug) ?>" class="small text-danger text-decoration-none fw-normal" style="font-size:11px;">আরও &raquo;</a>
                </h6>
            <?php endif; ?>

            <div class="sidebar-category-list">
                <?php foreach ($catItems as $ci): ?>
                    <div class="d-flex align-items-center gap-2 mb-2 pb-2 border-bottom">
                        <?php if (!empty($ci['thumbnail'])): ?>
                            <img src="<?= e($ci['thumbnail']) ?>" alt="<?= e($ci['title']) ?>" class="rounded object-fit-cover flex-shrink-0" style="width: 60px; height: 44px;">
                        <?php endif; ?>
                        <div class="overflow-hidden">
                            <a href="/news/<?= (int)$ci['id'] ?>/<?= e($ci['slug']) ?>" class="d-block text-dark text-decoration-none small fw-medium lh-sm text-truncate-2">
                                <?= e($ci['title']) ?>
                            </a>
                            <span class="text-muted" style="font-size: 10.5px;">
                                <i class="bi bi-clock me-1"></i><?= get_time_ago($ci['created_at']) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    <?php elseif ($bType === 'image_banner'): ?>
        <!-- 5. Image Banner Block -->
        <?php 
        $imgUrl = $settings['image_url'] ?? '';
        $targetUrl = !empty($block['content']) ? $block['content'] : '#';
        ?>
        <?php if (!empty($imgUrl)): ?>
            <div class="sidebar-widget mb-4 text-center" data-sidebar-block="image_banner">
                <?php if ($showTitle && !empty($bTitle)): ?>
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 text-start">
                        <i class="bi bi-image text-primary me-1"></i><?= e($bTitle) ?>
                    </h6>
                <?php endif; ?>
                <a href="<?= e($targetUrl) ?>" target="_blank" rel="noopener" class="d-block w-100">
                    <img src="<?= e($imgUrl) ?>" alt="<?= e($bTitle) ?>" class="img-fluid rounded border shadow-sm w-100" style="width: 100%; height: auto; display: block;">
                </a>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- 6. Custom HTML / Notice / Embed Block (Default) -->
        <div class="sidebar-widget mb-4" data-sidebar-block="custom_html">
            <?php if ($showTitle && !empty($bTitle)): ?>
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="bi bi-pin-angle-fill text-warning me-1"></i><?= e($bTitle) ?>
                </h6>
            <?php endif; ?>
            <div class="custom-sidebar-content">
                <?= $block['content'] ?? '' ?>
            </div>
        </div>

    <?php endif; ?>

<?php endforeach; ?>
