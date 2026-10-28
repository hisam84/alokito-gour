<?php
/**
 * Dynamic Widget Renderer & Page Builder Engine
 * Alokito Gour - WordPress-like Modular Drag-and-Drop Widgets
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/**
 * Fetch posts by category slug with in-memory caching
 */
function widget_get_posts_by_category(PDO $db, string $slug, int $limit = 4): array {
    static $catCache = [];
    $cacheKey = "{$slug}_{$limit}";
    if (isset($catCache[$cacheKey])) {
        return $catCache[$cacheKey];
    }

    $stmt = $db->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug 
        FROM `posts` p 
        JOIN `categories` c ON p.category_id = c.id 
        WHERE c.slug = :slug AND p.status = 'published' 
        ORDER BY p.id DESC 
        LIMIT :limit
    ");
    $stmt->bindValue(':slug', $slug);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $catCache[$cacheKey] = $posts;
    return $posts;
}

/**
 * Fetch category name by slug
 */
function widget_get_category_name(PDO $db, string $slug): string {
    static $nameCache = [];
    if (isset($nameCache[$slug])) {
        return $nameCache[$slug];
    }
    $stmt = $db->prepare("SELECT name FROM categories WHERE slug = :slug LIMIT 1");
    $stmt->execute([':slug' => $slug]);
    $cat = $stmt->fetch(PDO::FETCH_ASSOC);
    $name = $cat['name'] ?? ucfirst($slug);
    $nameCache[$slug] = $name;
    return $name;
}

/**
 * Fetch posts for Hero Lead section
 */
function widget_get_hero_posts(PDO $db, int $bottomCount = 3, int $subCount = 3): array {
    // 1. Main Lead
    $leadStmt = $db->query("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug 
        FROM `posts` p 
        LEFT JOIN `categories` c ON p.category_id = c.id 
        WHERE p.status = 'published' AND p.is_lead = 1 
        ORDER BY p.id DESC 
        LIMIT 1
    ");
    $mainLead = $leadStmt->fetch(PDO::FETCH_ASSOC);

    if (!$mainLead) {
        $mainLead = $db->query("
            SELECT p.*, c.name AS category_name, c.slug AS category_slug 
            FROM `posts` p 
            LEFT JOIN `categories` c ON p.category_id = c.id 
            WHERE p.status = 'published' 
            ORDER BY p.id DESC 
            LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);
    }

    $mainLeadId = (int)($mainLead['id'] ?? 0);

    // 2. Lead Bottom Posts
    $leadBottomPosts = [];
    if ($mainLeadId > 0 && $bottomCount > 0) {
        $leadBottomStmt = $db->prepare("
            SELECT p.*, c.name AS category_name, c.slug AS category_slug 
            FROM `posts` p 
            LEFT JOIN `categories` c ON p.category_id = c.id 
            WHERE p.status = 'published' AND p.id != :lead_id 
            ORDER BY p.id DESC 
            LIMIT :bcount
        ");
        $leadBottomStmt->bindValue(':lead_id', $mainLeadId, PDO::PARAM_INT);
        $leadBottomStmt->bindValue(':bcount', $bottomCount, PDO::PARAM_INT);
        $leadBottomStmt->execute();
        $leadBottomPosts = $leadBottomStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Sub-Leads
    $subLeads = [];
    if ($subCount > 0) {
        $excludeIds = array_merge([$mainLeadId], array_column($leadBottomPosts, 'id'));
        $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
        $subLeadStmt = $db->prepare("
            SELECT p.*, c.name AS category_name, c.slug AS category_slug 
            FROM `posts` p 
            LEFT JOIN `categories` c ON p.category_id = c.id 
            WHERE p.status = 'published' AND p.id NOT IN ($placeholders) 
            ORDER BY p.id DESC 
            LIMIT $subCount
        ");
        $subLeadStmt->execute($excludeIds);
        $subLeads = $subLeadStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    return [
        'mainLead' => $mainLead,
        'leadBottomPosts' => $leadBottomPosts,
        'subLeads' => $subLeads,
    ];
}

/**
 * Render Hero Lead Widget
 */
function render_widget_hero_lead(array $widget, PDO $db): string {
    $settings = $widget['settings'] ?? [];
    $bottomCount = (int)($settings['bottom_thumbs'] ?? 3);
    $subCount = (int)($settings['sub_leads'] ?? 3);

    $heroData = widget_get_hero_posts($db, $bottomCount, $subCount);
    $mainLead = $heroData['mainLead'];
    $leadBottomPosts = $heroData['leadBottomPosts'];
    $subLeads = $heroData['subLeads'];

    if (!$mainLead) {
        return '';
    }

    ob_start();
    ?>
    <section class="hero-top-section mb-4 pb-3 border-bottom clearfix" data-widget-id="<?= e($widget['id'] ?? 'hero') ?>">
        <div class="row g-3">
            <!-- Left: Main Lead Story + Bottom Items -->
            <div class="col-md-7">
                <article class="main-lead-card mb-2">
                    <a href="/news/<?= (int)$mainLead['id'] ?>/<?= e($mainLead['slug']) ?>" class="lead-img-box d-block">
                        <img src="<?= e($mainLead['thumbnail'] ?: 'https://images.unsplash.com/photo-1584467735871-8e85353a8413?w=800&q=80') ?>" alt="<?= e($mainLead['title']) ?>">
                    </a>
                    <div class="lead-content p-3">
                        <h2 class="lead-title fs-5 fw-bold mb-2">
                            <a href="/news/<?= (int)$mainLead['id'] ?>/<?= e($mainLead['slug']) ?>">
                                <?= e($mainLead['title']) ?>
                            </a>
                        </h2>
                        <p class="lead-excerpt text-muted small mb-0">
                            <?= e($mainLead['excerpt']) ?>
                        </p>
                    </div>
                </article>

                <?php if (!empty($leadBottomPosts)): ?>
                <div class="row g-2 pt-2 border-top">
                    <?php foreach ($leadBottomPosts as $bp): ?>
                        <div class="col-4">
                            <article class="lead-sub-thumb-card">
                                <a href="/news/<?= (int)$bp['id'] ?>/<?= e($bp['slug']) ?>" class="d-block mb-1 overflow-hidden rounded border" style="height: 75px;">
                                    <img src="<?= e($bp['thumbnail'] ?: 'https://images.unsplash.com/photo-1584467735871-8e85353a8413?w=300&q=80') ?>" alt="<?= e($bp['title']) ?>" class="w-100 h-100 object-fit-cover">
                                </a>
                                <h6 class="fw-bold mb-0 lh-sm" style="font-size: 0.8rem;">
                                    <a href="/news/<?= (int)$bp['id'] ?>/<?= e($bp['slug']) ?>" class="text-dark">
                                        <?= e($bp['title']) ?>
                                    </a>
                                </h6>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Middle: Stacked Sub-Lead Cards -->
            <div class="col-md-5">
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($subLeads as $idx => $sub): ?>
                        <article class="sub-lead-card <?= ($idx < count($subLeads) - 1) ? 'pb-2 border-bottom' : '' ?>">
                            <a href="/news/<?= (int)$sub['id'] ?>/<?= e($sub['slug']) ?>" class="sub-thumb-link">
                                <img src="<?= e($sub['thumbnail'] ?: 'https://images.unsplash.com/photo-1584467735871-8e85353a8413?w=400&q=80') ?>" alt="<?= e($sub['title']) ?>">
                            </a>
                            <h5>
                                <a href="/news/<?= (int)$sub['id'] ?>/<?= e($sub['slug']) ?>" class="text-dark">
                                    <?= e($sub['title']) ?>
                                </a>
                            </h5>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Render Ad Banner Widget
 */
function render_widget_ad_banner(array $widget, PDO $db, ?array $post = null): string {
    $settings = $widget['settings'] ?? [];
    $position = $settings['position'] ?? 'after_lead';
    $customCode = $settings['custom_code'] ?? '';

    // If rendering on a single news page and this is the sidebar ad slot
    if ($post && ($position === 'sidebar' || ($widget['id'] ?? '') === 'sec_side_top_ad')) {
        $adType = $post['sidebar_ad_type'] ?? 'global';
        if ($adType === 'none') {
            return '';
        }
        if ($adType === 'code' && !empty($post['sidebar_ad_code'])) {
            return '<div class="ad-container ad-sidebar mb-3 text-center" data-widget-id="' . e($widget['id'] ?? 'ad') . '">' . $post['sidebar_ad_code'] . '</div>';
        }
        if ($adType === 'custom' && !empty($post['sidebar_ad_image'])) {
            $target = !empty($post['sidebar_ad_url']) ? e($post['sidebar_ad_url']) : '#';
            return '<div class="ad-container ad-sidebar mb-3 text-center" data-widget-id="' . e($widget['id'] ?? 'ad') . '">
                <a href="' . $target . '" target="_blank" rel="noopener" class="d-block w-100">
                    <img src="' . e($post['sidebar_ad_image']) . '" alt="' . e($post['title'] ?? 'Advertisement') . '" class="img-fluid rounded border shadow-sm w-100" style="width: 100%; height: auto; display: block;">
                </a>
            </div>';
        }
    }

    if (!empty($customCode)) {
        return '<div class="ad-container mb-3 text-center" data-widget-id="' . e($widget['id'] ?? 'ad') . '">' . $customCode . '</div>';
    }

    return render_ad($position);
}

/**
 * Render Category Block Widget (1 Large Featured + List)
 */
function render_widget_category_block(array $widget, PDO $db): string {
    $settings = $widget['settings'] ?? [];
    $slug = $settings['category_slug'] ?? 'chapai-nawabganj';
    $title = !empty($widget['title']) ? $widget['title'] : widget_get_category_name($db, $slug);
    $limit = (int)($settings['limit'] ?? 5);
    $showMore = $settings['show_more'] ?? true;

    $posts = widget_get_posts_by_category($db, $slug, $limit);

    ob_start();
    ?>
    <section class="category-block mb-4" data-widget-id="<?= e($widget['id'] ?? 'cat_block') ?>">
        <div class="section-title">
            <span><?= e($title) ?></span>
            <?php if ($showMore): ?>
                <a href="/category/<?= e($slug) ?>" class="more-link">আরও খবর <i class="bi bi-chevron-right"></i></a>
            <?php endif; ?>
        </div>

        <div class="row g-3">
            <?php if (!empty($posts)): ?>
                <?php $first = $posts[0]; ?>
                <div class="col-md-6">
                    <div class="news-card">
                        <a href="/news/<?= (int)$first['id'] ?>/<?= e($first['slug']) ?>" class="card-thumb d-block">
                            <img src="<?= e($first['thumbnail'] ?: 'https://images.unsplash.com/photo-1584467735871-8e85353a8413?w=500&q=80') ?>" alt="<?= e($first['title']) ?>">
                        </a>
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="/news/<?= (int)$first['id'] ?>/<?= e($first['slug']) ?>"><?= e($first['title']) ?></a>
                            </h5>
                            <p class="card-meta mb-0"><i class="bi bi-clock me-1"></i><?= get_time_ago($first['created_at']) ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="bg-white p-3 rounded border">
                        <?php foreach (array_slice($posts, 1) as $item): ?>
                            <div class="news-list-item">
                                <div class="item-title">
                                    <a href="/news/<?= (int)$item['id'] ?>/<?= e($item['slug']) ?>">
                                        <i class="bi bi-caret-right-fill text-warning me-1"></i><?= e($item['title']) ?>
                                    </a>
                                    <div class="card-meta mt-1 ms-3"><i class="bi bi-clock me-1"></i><?= get_time_ago($item['created_at']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="col-12"><p class="text-muted small">এই বিভাগে এখনও কোনো সংবাদ নেই।</p></div>
            <?php endif; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Render Category Grid Widget (4-Card Grid)
 */
function render_widget_category_grid(array $widget, PDO $db): string {
    $settings = $widget['settings'] ?? [];
    $slug = $settings['category_slug'] ?? 'national';
    $title = !empty($widget['title']) ? $widget['title'] : widget_get_category_name($db, $slug);
    $limit = (int)($settings['limit'] ?? 4);
    $showMore = $settings['show_more'] ?? true;

    $posts = widget_get_posts_by_category($db, $slug, $limit);

    ob_start();
    ?>
    <section class="category-block mb-4" data-widget-id="<?= e($widget['id'] ?? 'cat_grid') ?>">
        <div class="section-title">
            <span><?= e($title) ?></span>
            <?php if ($showMore): ?>
                <a href="/category/<?= e($slug) ?>" class="more-link">আরও খবর <i class="bi bi-chevron-right"></i></a>
            <?php endif; ?>
        </div>

        <div class="row g-3">
            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $item): ?>
                    <div class="col-sm-6">
                        <div class="news-card">
                            <a href="/news/<?= (int)$item['id'] ?>/<?= e($item['slug']) ?>" class="card-thumb d-block">
                                <img src="<?= e($item['thumbnail'] ?: 'https://images.unsplash.com/photo-1611273426858-450d8e3c9fce?w=500&q=80') ?>" alt="<?= e($item['title']) ?>">
                            </a>
                            <div class="card-body">
                                <h6 class="card-title">
                                    <a href="/news/<?= (int)$item['id'] ?>/<?= e($item['slug']) ?>"><?= e($item['title']) ?></a>
                                </h6>
                                <span class="card-meta"><i class="bi bi-clock me-1"></i><?= get_time_ago($item['created_at']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12"><p class="text-muted small">এই বিভাগে এখনও কোনো সংবাদ নেই।</p></div>
            <?php endif; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Render Category Split Widget (2 Categories Side-by-Side in 2 Columns)
 */
function render_widget_category_split(array $widget, PDO $db): string {
    $settings = $widget['settings'] ?? [];
    $leftSlug = $settings['left_category'] ?? 'politics';
    $leftTitle = !empty($settings['left_title']) ? $settings['left_title'] : widget_get_category_name($db, $leftSlug);
    $rightSlug = $settings['right_category'] ?? 'international';
    $rightTitle = !empty($settings['right_title']) ? $settings['right_title'] : widget_get_category_name($db, $rightSlug);
    $limit = (int)($settings['limit'] ?? 4);

    $leftPosts = widget_get_posts_by_category($db, $leftSlug, $limit);
    $rightPosts = widget_get_posts_by_category($db, $rightSlug, $limit);

    // Icon lookup per slug for visual variety
    $iconMap = [
        'politics' => 'bi bi-dot text-danger',
        'international' => 'bi bi-dot text-danger',
        'economy' => 'bi bi-cash-stack text-success me-1',
        'law-rights' => 'bi bi-shield-check text-primary me-1',
        'tech' => 'bi bi-cpu text-warning me-1',
        'religion' => 'bi bi-moon-stars text-success me-1',
        'jobs-career' => 'bi bi-briefcase text-info me-1',
        'education-literature' => 'bi bi-book text-secondary me-1',
    ];

    $leftIcon = $iconMap[$leftSlug] ?? 'bi bi-caret-right-fill text-warning me-1';
    $rightIcon = $iconMap[$rightSlug] ?? 'bi bi-caret-right-fill text-warning me-1';

    ob_start();
    ?>
    <section class="category-block mb-4" data-widget-id="<?= e($widget['id'] ?? 'cat_split') ?>">
        <div class="row g-4">
            <!-- Left Category -->
            <div class="col-md-6">
                <div class="section-title">
                    <span><?= e($leftTitle) ?></span>
                    <a href="/category/<?= e($leftSlug) ?>" class="more-link">আরও <i class="bi bi-chevron-right"></i></a>
                </div>
                <?php if (!empty($leftPosts)): ?>
                    <div class="news-card mb-3">
                        <a href="/news/<?= (int)$leftPosts[0]['id'] ?>/<?= e($leftPosts[0]['slug']) ?>" class="card-thumb d-block">
                            <img src="<?= e($leftPosts[0]['thumbnail'] ?: 'https://images.unsplash.com/photo-1540910419892-4a36d2c3266c?w=500&q=80') ?>" alt="<?= e($leftPosts[0]['title']) ?>">
                        </a>
                        <div class="card-body">
                            <h6 class="card-title">
                                <a href="/news/<?= (int)$leftPosts[0]['id'] ?>/<?= e($leftPosts[0]['slug']) ?>"><?= e($leftPosts[0]['title']) ?></a>
                            </h6>
                            <span class="card-meta"><i class="bi bi-clock me-1"></i><?= get_time_ago($leftPosts[0]['created_at']) ?></span>
                        </div>
                    </div>
                    <?php foreach (array_slice($leftPosts, 1) as $item): ?>
                        <div class="news-list-item">
                            <a href="/news/<?= (int)$item['id'] ?>/<?= e($item['slug']) ?>" class="item-title">
                                <i class="<?= $leftIcon ?>"></i> <?= e($item['title']) ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted small">এই বিভাগে এখনও কোনো সংবাদ নেই।</p>
                <?php endif; ?>
            </div>

            <!-- Right Category -->
            <div class="col-md-6">
                <div class="section-title">
                    <span><?= e($rightTitle) ?></span>
                    <a href="/category/<?= e($rightSlug) ?>" class="more-link">আরও <i class="bi bi-chevron-right"></i></a>
                </div>
                <?php if (!empty($rightPosts)): ?>
                    <div class="news-card mb-3">
                        <a href="/news/<?= (int)$rightPosts[0]['id'] ?>/<?= e($rightPosts[0]['slug']) ?>" class="card-thumb d-block">
                            <img src="<?= e($rightPosts[0]['thumbnail'] ?: 'https://images.unsplash.com/photo-1532375810709-75b1da00537c?w=500&q=80') ?>" alt="<?= e($rightPosts[0]['title']) ?>">
                        </a>
                        <div class="card-body">
                            <h6 class="card-title">
                                <a href="/news/<?= (int)$rightPosts[0]['id'] ?>/<?= e($rightPosts[0]['slug']) ?>"><?= e($rightPosts[0]['title']) ?></a>
                            </h6>
                            <span class="card-meta"><i class="bi bi-clock me-1"></i><?= get_time_ago($rightPosts[0]['created_at']) ?></span>
                        </div>
                    </div>
                    <?php foreach (array_slice($rightPosts, 1) as $item): ?>
                        <div class="news-list-item">
                            <a href="/news/<?= (int)$item['id'] ?>/<?= e($item['slug']) ?>" class="item-title">
                                <i class="<?= $rightIcon ?>"></i> <?= e($item['title']) ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted small">এই বিভাগে এখনও কোনো সংবাদ নেই।</p>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Render Full Width Split Widget (e.g. Country & Sports)
 */
function render_widget_full_width_split(array $widget, PDO $db): string {
    $settings = $widget['settings'] ?? [];
    $leftSlug = $settings['left_category'] ?? 'country';
    $leftTitle = !empty($settings['left_title']) ? $settings['left_title'] : widget_get_category_name($db, $leftSlug);
    $rightSlug = $settings['right_category'] ?? 'sports';
    $rightTitle = !empty($settings['right_title']) ? $settings['right_title'] : widget_get_category_name($db, $rightSlug);
    $limit = (int)($settings['limit'] ?? 4);

    $leftPosts = widget_get_posts_by_category($db, $leftSlug, $limit);
    $rightPosts = widget_get_posts_by_category($db, $rightSlug, $limit);

    ob_start();
    ?>
    <section class="category-block mt-3 mb-4" data-widget-id="<?= e($widget['id'] ?? 'full_split') ?>">
        <div class="row g-4">
            <!-- Left Column -->
            <div class="col-lg-6">
                <div class="section-title">
                    <span><?= e($leftTitle) ?></span>
                    <a href="/category/<?= e($leftSlug) ?>" class="more-link">আরও খবর <i class="bi bi-chevron-right"></i></a>
                </div>
                <div class="row g-3">
                    <?php foreach ($leftPosts as $item): ?>
                        <div class="col-sm-6">
                            <div class="news-card h-100">
                                <a href="/news/<?= (int)$item['id'] ?>/<?= e($item['slug']) ?>" class="card-thumb d-block">
                                    <img src="<?= e($item['thumbnail'] ?: 'https://images.unsplash.com/photo-1507676184212-d03ab07a01bf?w=500&q=80') ?>" alt="<?= e($item['title']) ?>">
                                </a>
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <a href="/news/<?= (int)$item['id'] ?>/<?= e($item['slug']) ?>"><?= e($item['title']) ?></a>
                                    </h6>
                                    <span class="card-meta"><i class="bi bi-clock me-1"></i><?= get_time_ago($item['created_at']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-6">
                <div class="section-title">
                    <span><?= e($rightTitle) ?></span>
                    <a href="/category/<?= e($rightSlug) ?>" class="more-link">আরও খবর <i class="bi bi-chevron-right"></i></a>
                </div>
                <div class="row g-3">
                    <?php foreach ($rightPosts as $item): ?>
                        <div class="col-sm-6">
                            <div class="news-card h-100">
                                <a href="/news/<?= (int)$item['id'] ?>/<?= e($item['slug']) ?>" class="card-thumb d-block">
                                    <img src="<?= e($item['thumbnail'] ?: 'https://images.unsplash.com/photo-1540747913346-19e32dc3e97e?w=500&q=80') ?>" alt="<?= e($item['title']) ?>">
                                </a>
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <a href="/news/<?= (int)$item['id'] ?>/<?= e($item['slug']) ?>"><?= e($item['title']) ?></a>
                                    </h6>
                                    <span class="card-meta"><i class="bi bi-clock me-1"></i><?= get_time_ago($item['created_at']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Render Multimedia Gallery Widget
 */
function render_widget_multimedia_gallery(array $widget, PDO $db): string {
    $settings = $widget['settings'] ?? [];
    $title = !empty($widget['title']) ? $widget['title'] : ($settings['title'] ?? 'ছবি ও ভিডিও গ্যালারি');

    $galleryItems = [
        [
            'title' => 'চাঁপাইনবাবগঞ্জে পরিচ্ছন্নতা অভিযান',
            'image' => 'https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?w=500&q=80',
        ],
        [
            'title' => 'জেলা গোল্ডকাপ ফুটবল টুর্নামেন্ট',
            'image' => 'https://images.unsplash.com/photo-1540747913346-19e32dc3e97e?w=500&q=80',
        ],
        [
            'title' => 'ঐতিহ্যবাহী লোকজ যাত্রাপালা মঞ্চায়ন',
            'image' => 'https://images.unsplash.com/photo-1507676184212-d03ab07a01bf?w=500&q=80',
        ],
        [
            'title' => 'কক্সবাজার আন্তর্জাতিক বিমানবন্দর উন্নয়ন',
            'image' => 'https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=500&q=80',
        ],
    ];

    ob_start();
    ?>
    <section class="gallery-section my-5 p-4 rounded bg-dark text-white" data-widget-id="<?= e($widget['id'] ?? 'gallery') ?>">
        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom border-secondary pb-2">
            <h4 class="fw-bold mb-0 text-white"><i class="bi bi-camera-fill text-warning me-2"></i><?= e($title) ?></h4>
            <a href="#" class="text-warning text-decoration-none small">সব গ্যালারি <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-3">
            <?php foreach ($galleryItems as $item): ?>
                <div class="col-md-3 col-sm-6">
                    <div class="position-relative rounded overflow-hidden shadow-sm" style="aspect-ratio: 16/10;">
                        <img src="<?= e($item['image']) ?>" alt="<?= e($item['title']) ?>" class="w-100 h-100 object-fit-cover">
                        <span class="position-absolute bottom-0 start-0 end-0 p-2 bg-dark bg-opacity-75 small text-white"><?= e($item['title']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Render Custom HTML Widget
 */
function render_widget_custom_html(array $widget, PDO $db): string {
    $settings = $widget['settings'] ?? [];
    $title = $widget['title'] ?? '';
    $html = $settings['content'] ?? ($settings['custom_code'] ?? '');

    if (empty($html) && empty($title)) {
        return '';
    }

    ob_start();
    ?>
    <section class="custom-widget-block mb-4" data-widget-id="<?= e($widget['id'] ?? 'custom') ?>">
        <?php if (!empty($title)): ?>
            <div class="section-title">
                <span><?= e($title) ?></span>
            </div>
        <?php endif; ?>
        <div class="custom-content">
            <?= $html ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Render Sidebar Tabs Widget (Latest / Popular News)
 */
function render_widget_sidebar_tabs(array $widget, PDO $db): string {
    $settings = $widget['settings'] ?? [];
    $limit = (int)($settings['limit'] ?? 7);

    // Latest Posts
    $lStmt = $db->prepare("SELECT id, title, slug, created_at, views FROM `posts` WHERE `status` = 'published' ORDER BY `id` DESC LIMIT :limit");
    $lStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $lStmt->execute();
    $latestPosts = $lStmt->fetchAll(PDO::FETCH_ASSOC);

    // Popular Posts
    $pStmt = $db->prepare("SELECT id, title, slug, created_at, views FROM `posts` WHERE `status` = 'published' ORDER BY `views` DESC, `id` DESC LIMIT :limit");
    $pStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $pStmt->execute();
    $popularPosts = $pStmt->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
    ?>
    <div class="sidebar-widget mb-4" data-widget-id="<?= e($widget['id'] ?? 'sidebar_tabs') ?>">
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
    <?php
    return ob_get_clean();
}

/**
 * Render District Filter Widget
 */
function render_widget_district_filter(array $widget, PDO $db): string {
    require_once __DIR__ . '/bangladesh_geo.php';
    $title = !empty($widget['title']) ? $widget['title'] : 'আমার এলাকার খবর';
    return render_area_filter_bar([], false, $title);
}

/* Deprecated legacy district buttons */
function _old_render_widget_district_filter_deprecated(array $widget, PDO $db): string {
    ob_start();
    ?>
    <div class="sidebar-widget mb-4" data-widget-id="<?= e($widget['id'] ?? 'district_filter') ?>">
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
    <?php
    return ob_get_clean();
}

/**
 * Central Widget Dispatcher
 */
function render_single_widget(array $widget, PDO $db, ?array $post = null): string {
    $type = $widget['type'] ?? '';
    $status = $widget['status'] ?? 1;

    // Skip hidden/inactive widgets
    if ((int)$status === 0) {
        return '';
    }

    switch ($type) {
        case 'hero_lead':
            return render_widget_hero_lead($widget, $db);
        case 'ad_banner':
            return render_widget_ad_banner($widget, $db, $post);
        case 'category_block':
            return render_widget_category_block($widget, $db);
        case 'category_grid':
            return render_widget_category_grid($widget, $db);
        case 'category_split':
            return render_widget_category_split($widget, $db);
        case 'full_width_split':
            return render_widget_full_width_split($widget, $db);
        case 'multimedia_gallery':
            return render_widget_multimedia_gallery($widget, $db);
        case 'custom_html':
            return render_widget_custom_html($widget, $db);
        case 'sidebar_tabs':
            return render_widget_sidebar_tabs($widget, $db);
        case 'district_filter':
            return render_widget_district_filter($widget, $db);
        default:
            return '';
    }
}

/**
 * Render Dynamic News Article Sidebar
 * Renders widgets configured for 'news_sidebar' with per-post custom ad support
 */
function render_news_sidebar(?array $post, PDO $db, ?array $customWidgets = null): void {
    if ($customWidgets !== null) {
        $widgets = $customWidgets;
    } else {
        $layout = get_page_layout('news_sidebar', $db);
        $widgets = $layout['widgets'] ?? [];
    }

    if (!empty($widgets)) {
        foreach ($widgets as $w) {
            echo render_single_widget($w, $db, $post);
        }
    } else {
        // Fallback to default sidebar file
        include __DIR__ . '/sidebar.php';
    }
}

/**
 * Load page layout definition from database
 */
function get_page_layout(string $slug, PDO $db): ?array {
    $stmt = $db->prepare("SELECT * FROM page_layouts WHERE page_slug = :slug AND status = 1 LIMIT 1");
    $stmt->execute([':slug' => $slug]);
    $layout = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$layout) {
        return null;
    }

    $widgets = json_decode($layout['layout_data'], true);
    if (!is_array($widgets)) {
        $widgets = [];
    }

    $layout['widgets'] = $widgets;
    return $layout;
}

/**
 * Render Complete Dynamic Page Layout
 * Renders main widgets alongside the sidebar, and full-width widgets below.
 */
function render_page_layout(string $slug, PDO $db, array $options = []): void {
    if (!empty($options['custom_widgets']) && is_array($options['custom_widgets'])) {
        $widgets = $options['custom_widgets'];
    } else {
        $layout = get_page_layout($slug, $db);
        if (!$layout || empty($layout['widgets'])) {
            // Fallback message if no layout configured
            echo '<div class="alert alert-info text-center my-5">এই পাতার কোনো উইজেট সাজানো হয়নি। অ্যাডমিন প্যানেল থেকে পেজ বিল্ডারে যান।</div>';
            return;
        }
        $widgets = $layout['widgets'];
    }

    // Group widgets by container: 'main' (8-col next to sidebar) vs 'full' (12-col full width)
    $mainWidgets = [];
    $fullWidgets = [];

    foreach ($widgets as $w) {
        $type = $w['type'] ?? '';
        // Inherent full-width types default to 'full' unless explicitly set to 'main'
        $defaultContainer = in_array($type, ['full_width_split', 'multimedia_gallery'], true) ? 'full' : 'main';
        $container = $w['container'] ?? ($w['settings']['container'] ?? $defaultContainer);

        if ($container === 'full') {
            $fullWidgets[] = $w;
        } else {
            $mainWidgets[] = $w;
        }
    }

    echo '<div class="page-builder-layout" data-page-slug="' . e($slug) . '">';

    // 1. Render Main Section with Sidebar (8 cols + 4 cols) if main widgets exist
    if (!empty($mainWidgets)) {
        echo '<div class="row g-4">';
        echo '    <div class="col-lg-8">';
        foreach ($mainWidgets as $w) {
            echo render_single_widget($w, $db);
        }
        echo '    </div>';
        echo '    <div class="col-lg-4">';
        include __DIR__ . '/sidebar.php';
        echo '    </div>';
        echo '</div>';
    }

    // 2. Render Full-Width Widgets
    if (!empty($fullWidgets)) {
        foreach ($fullWidgets as $w) {
            echo render_single_widget($w, $db);
        }
    }

    echo '</div>';
}
