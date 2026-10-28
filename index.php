<?php
/**
 * Homepage (Dynamic News Portal)
 * Alokito Gour Clone - 100% Authentic Clone of https://alokitogour.com/
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

$db = get_db();
$settings = get_settings();

// 1. Fetch Main Lead Story
$leadStmt = $db->query("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug 
    FROM `posts` p 
    LEFT JOIN `categories` c ON p.category_id = c.id 
    WHERE p.status = 'published' AND p.is_lead = 1 
    ORDER BY p.id DESC 
    LIMIT 1
");
$mainLead = $leadStmt->fetch();

// Fallback to latest post if no explicit lead
if (!$mainLead) {
    $mainLead = $db->query("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug 
        FROM `posts` p 
        LEFT JOIN `categories` c ON p.category_id = c.id 
        WHERE p.status = 'published' 
        ORDER BY p.id DESC 
        LIMIT 1
    ")->fetch();
}

$mainLeadId = (int)($mainLead['id'] ?? 0);

// 2. Fetch 3 Small Stories under the main lead
$leadBottomStmt = $db->prepare("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug 
    FROM `posts` p 
    LEFT JOIN `categories` c ON p.category_id = c.id 
    WHERE p.status = 'published' AND p.id != :lead_id 
    ORDER BY p.id DESC 
    LIMIT 3
");
$leadBottomStmt->execute([':lead_id' => $mainLeadId]);
$leadBottomPosts = $leadBottomStmt->fetchAll();

$excludeIds = array_merge([$mainLeadId], array_column($leadBottomPosts, 'id'));
$inPlaceholders = implode(',', array_fill(0, count($excludeIds), '?'));

// 3. Fetch 3 Sub-Lead Stacked Stories (Middle column)
$subLeadStmt = $db->prepare("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug 
    FROM `posts` p 
    LEFT JOIN `categories` c ON p.category_id = c.id 
    WHERE p.status = 'published' AND p.id NOT IN ($inPlaceholders) 
    ORDER BY p.id DESC 
    LIMIT 3
");
$subLeadStmt->execute($excludeIds);
$subLeads = $subLeadStmt->fetchAll();

// Helper to fetch posts by category slug
function get_posts_by_category_slug(PDO $db, string $slug, int $limit = 4): array {
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
    return $stmt->fetchAll();
}

// Category news feeds
$chapaiNews     = get_posts_by_category_slug($db, 'chapai-nawabganj', 5);
$nationalNews   = get_posts_by_category_slug($db, 'national', 4);
$countryNews    = get_posts_by_category_slug($db, 'country', 4);
$politicsNews   = get_posts_by_category_slug($db, 'politics', 4);
$intlNews       = get_posts_by_category_slug($db, 'international', 4);
$economyNews    = get_posts_by_category_slug($db, 'economy', 4);
$lawRightsNews  = get_posts_by_category_slug($db, 'law-rights', 4);
$sportsNews     = get_posts_by_category_slug($db, 'sports', 4);
$techNews       = get_posts_by_category_slug($db, 'tech', 4);
$religionNews   = get_posts_by_category_slug($db, 'religion', 4);
$jobsNews       = get_posts_by_category_slug($db, 'jobs-career', 4);
$eduNews        = get_posts_by_category_slug($db, 'education-literature', 4);
$campusNews     = get_posts_by_category_slug($db, 'campus', 3);
$probasNews     = get_posts_by_category_slug($db, 'probas-ovibason', 3);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/ticker.php';
?>

<div class="container my-3">
    <!-- Top Main Content & Right Sidebar Grid -->
    <div class="row g-4">
        <!-- Left & Middle Column (8 Cols) -->
        <div class="col-lg-8">
            <!-- Top Hero Stories -->
            <?php if ($mainLead): ?>
            <section class="hero-top-section mb-4 pb-3 border-bottom clearfix">
                <div class="row g-3">
                    <!-- Left: Main Lead Story + 3 Bottom Items -->
                    <div class="col-md-7">
                        <!-- Primary Lead News Card -->
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

                        <!-- 3 Small Thumbnail Cards Directly Under Lead -->
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

                    <!-- Middle: 3 Stacked Sub-Lead Cards -->
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
            <?php endif; ?>

            <!-- Leaderboard Advertisement Banner (after_lead) -->
            <?= render_ad('after_lead') ?>

            <!-- Block 1: চাঁপাইনবাবগঞ্জ (Regional Lead Block) -->
            <section class="category-block mb-4">
                <div class="section-title">
                    <span>চাঁপাইনবাবগঞ্জ</span>
                    <a href="/category/chapai-nawabganj" class="more-link">আরও খবর <i class="bi bi-chevron-right"></i></a>
                </div>

                <div class="row g-3">
                    <?php if (!empty($chapaiNews)): ?>
                        <!-- First Chapai News Featured -->
                        <?php $cFirst = $chapaiNews[0]; ?>
                        <div class="col-md-6">
                            <div class="news-card">
                                <a href="/news/<?= (int)$cFirst['id'] ?>/<?= e($cFirst['slug']) ?>" class="card-thumb d-block">
                                    <img src="<?= e($cFirst['thumbnail'] ?: 'https://images.unsplash.com/photo-1584467735871-8e85353a8413?w=500&q=80') ?>" alt="<?= e($cFirst['title']) ?>">
                                </a>
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="/news/<?= (int)$cFirst['id'] ?>/<?= e($cFirst['slug']) ?>"><?= e($cFirst['title']) ?></a>
                                    </h5>
                                    <p class="card-meta mb-0"><i class="bi bi-clock me-1"></i><?= get_time_ago($cFirst['created_at']) ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Remaining Chapai News in List -->
                        <div class="col-md-6">
                            <div class="bg-white p-3 rounded border">
                                <?php foreach (array_slice($chapaiNews, 1) as $cItem): ?>
                                    <div class="news-list-item">
                                        <div class="item-title">
                                            <a href="/news/<?= (int)$cItem['id'] ?>/<?= e($cItem['slug']) ?>">
                                                <i class="bi bi-caret-right-fill text-warning me-1"></i><?= e($cItem['title']) ?>
                                            </a>
                                            <div class="card-meta mt-1 ms-3"><i class="bi bi-clock me-1"></i><?= get_time_ago($cItem['created_at']) ?></div>
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

            <!-- Block 2: জাতীয় (National News Grid) -->
            <section class="category-block mb-4">
                <div class="section-title">
                    <span>জাতীয়</span>
                    <a href="/category/national" class="more-link">আরও খবর <i class="bi bi-chevron-right"></i></a>
                </div>

                <div class="row g-3">
                    <?php if (!empty($nationalNews)): ?>
                        <?php foreach ($nationalNews as $nItem): ?>
                            <div class="col-sm-6">
                                <div class="news-card">
                                    <a href="/news/<?= (int)$nItem['id'] ?>/<?= e($nItem['slug']) ?>" class="card-thumb d-block">
                                        <img src="<?= e($nItem['thumbnail'] ?: 'https://images.unsplash.com/photo-1611273426858-450d8e3c9fce?w=500&q=80') ?>" alt="<?= e($nItem['title']) ?>">
                                    </a>
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <a href="/news/<?= (int)$nItem['id'] ?>/<?= e($nItem['slug']) ?>"><?= e($nItem['title']) ?></a>
                                        </h6>
                                        <span class="card-meta"><i class="bi bi-clock me-1"></i><?= get_time_ago($nItem['created_at']) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12"><p class="text-muted small">এই বিভাগে এখনও কোনো সংবাদ নেই।</p></div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Block 3: রাজনীতি ও আন্তর্জাতিক (2-Column Grid) -->
            <section class="category-block mb-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="section-title">
                            <span>রাজনীতি</span>
                            <a href="/category/politics" class="more-link">আরও <i class="bi bi-chevron-right"></i></a>
                        </div>
                        <?php if (!empty($politicsNews)): ?>
                            <div class="news-card mb-3">
                                <a href="/news/<?= (int)$politicsNews[0]['id'] ?>/<?= e($politicsNews[0]['slug']) ?>" class="card-thumb d-block">
                                    <img src="<?= e($politicsNews[0]['thumbnail'] ?: 'https://images.unsplash.com/photo-1540910419892-4a36d2c3266c?w=500&q=80') ?>" alt="<?= e($politicsNews[0]['title']) ?>">
                                </a>
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <a href="/news/<?= (int)$politicsNews[0]['id'] ?>/<?= e($politicsNews[0]['slug']) ?>"><?= e($politicsNews[0]['title']) ?></a>
                                    </h6>
                                    <span class="card-meta"><i class="bi bi-clock me-1"></i><?= get_time_ago($politicsNews[0]['created_at']) ?></span>
                                </div>
                            </div>
                            <?php foreach (array_slice($politicsNews, 1) as $pItem): ?>
                                <div class="news-list-item">
                                    <a href="/news/<?= (int)$pItem['id'] ?>/<?= e($pItem['slug']) ?>" class="item-title">
                                        <i class="bi bi-dot text-danger"></i> <?= e($pItem['title']) ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <div class="section-title">
                            <span>আন্তর্জাতিক</span>
                            <a href="/category/international" class="more-link">আরও <i class="bi bi-chevron-right"></i></a>
                        </div>
                        <?php if (!empty($intlNews)): ?>
                            <div class="news-card mb-3">
                                <a href="/news/<?= (int)$intlNews[0]['id'] ?>/<?= e($intlNews[0]['slug']) ?>" class="card-thumb d-block">
                                    <img src="<?= e($intlNews[0]['thumbnail'] ?: 'https://images.unsplash.com/photo-1532375810709-75b1da00537c?w=500&q=80') ?>" alt="<?= e($intlNews[0]['title']) ?>">
                                </a>
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <a href="/news/<?= (int)$intlNews[0]['id'] ?>/<?= e($intlNews[0]['slug']) ?>"><?= e($intlNews[0]['title']) ?></a>
                                    </h6>
                                    <span class="card-meta"><i class="bi bi-clock me-1"></i><?= get_time_ago($intlNews[0]['created_at']) ?></span>
                                </div>
                            </div>
                            <?php foreach (array_slice($intlNews, 1) as $iItem): ?>
                                <div class="news-list-item">
                                    <a href="/news/<?= (int)$iItem['id'] ?>/<?= e($iItem['slug']) ?>" class="item-title">
                                        <i class="bi bi-dot text-danger"></i> <?= e($iItem['title']) ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Block 4: অর্থনীতি ও আইন-অধিকার (2-Column Grid) -->
            <section class="category-block mb-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="section-title">
                            <span>অর্থনীতি</span>
                            <a href="/category/economy" class="more-link">আরও <i class="bi bi-chevron-right"></i></a>
                        </div>
                        <?php if (!empty($economyNews)): ?>
                            <div class="news-card mb-3">
                                <a href="/news/<?= (int)$economyNews[0]['id'] ?>/<?= e($economyNews[0]['slug']) ?>" class="card-thumb d-block">
                                    <img src="<?= e($economyNews[0]['thumbnail'] ?: 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=500&q=80') ?>" alt="<?= e($economyNews[0]['title']) ?>">
                                </a>
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <a href="/news/<?= (int)$economyNews[0]['id'] ?>/<?= e($economyNews[0]['slug']) ?>"><?= e($economyNews[0]['title']) ?></a>
                                    </h6>
                                    <span class="card-meta"><i class="bi bi-clock me-1"></i><?= get_time_ago($economyNews[0]['created_at']) ?></span>
                                </div>
                            </div>
                            <?php foreach (array_slice($economyNews, 1) as $eItem): ?>
                                <div class="news-list-item">
                                    <a href="/news/<?= (int)$eItem['id'] ?>/<?= e($eItem['slug']) ?>" class="item-title">
                                        <i class="bi bi-cash-stack text-success me-1"></i> <?= e($eItem['title']) ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <div class="section-title">
                            <span>আইন ও অধিকার</span>
                            <a href="/category/law-rights" class="more-link">আরও <i class="bi bi-chevron-right"></i></a>
                        </div>
                        <?php if (!empty($lawRightsNews)): ?>
                            <div class="news-card mb-3">
                                <a href="/news/<?= (int)$lawRightsNews[0]['id'] ?>/<?= e($lawRightsNews[0]['slug']) ?>" class="card-thumb d-block">
                                    <img src="<?= e($lawRightsNews[0]['thumbnail'] ?: 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?w=500&q=80') ?>" alt="<?= e($lawRightsNews[0]['title']) ?>">
                                </a>
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <a href="/news/<?= (int)$lawRightsNews[0]['id'] ?>/<?= e($lawRightsNews[0]['slug']) ?>"><?= e($lawRightsNews[0]['title']) ?></a>
                                    </h6>
                                    <span class="card-meta"><i class="bi bi-clock me-1"></i><?= get_time_ago($lawRightsNews[0]['created_at']) ?></span>
                                </div>
                            </div>
                            <?php foreach (array_slice($lawRightsNews, 1) as $lItem): ?>
                                <div class="news-list-item">
                                    <a href="/news/<?= (int)$lItem['id'] ?>/<?= e($lItem['slug']) ?>" class="item-title">
                                        <i class="bi bi-shield-check text-primary me-1"></i> <?= e($lItem['title']) ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Block 5: বিজ্ঞান ও প্রযুক্তি এবং ধর্ম -->
            <section class="category-block mb-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="section-title">
                            <span>বিজ্ঞান ও প্রযুক্তি</span>
                            <a href="/category/tech" class="more-link">আরও <i class="bi bi-chevron-right"></i></a>
                        </div>
                        <?php if (!empty($techNews)): ?>
                            <div class="news-card mb-3">
                                <a href="/news/<?= (int)$techNews[0]['id'] ?>/<?= e($techNews[0]['slug']) ?>" class="card-thumb d-block">
                                    <img src="<?= e($techNews[0]['thumbnail'] ?: 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=500&q=80') ?>" alt="<?= e($techNews[0]['title']) ?>">
                                </a>
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <a href="/news/<?= (int)$techNews[0]['id'] ?>/<?= e($techNews[0]['slug']) ?>"><?= e($techNews[0]['title']) ?></a>
                                    </h6>
                                    <span class="card-meta"><i class="bi bi-clock me-1"></i><?= get_time_ago($techNews[0]['created_at']) ?></span>
                                </div>
                            </div>
                            <?php foreach (array_slice($techNews, 1) as $tItem): ?>
                                <div class="news-list-item">
                                    <a href="/news/<?= (int)$tItem['id'] ?>/<?= e($tItem['slug']) ?>" class="item-title">
                                        <i class="bi bi-cpu text-warning me-1"></i> <?= e($tItem['title']) ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <div class="section-title">
                            <span>ধর্ম ও জীবন</span>
                            <a href="/category/religion" class="more-link">আরও <i class="bi bi-chevron-right"></i></a>
                        </div>
                        <?php if (!empty($religionNews)): ?>
                            <div class="news-card mb-3">
                                <a href="/news/<?= (int)$religionNews[0]['id'] ?>/<?= e($religionNews[0]['slug']) ?>" class="card-thumb d-block">
                                    <img src="<?= e($religionNews[0]['thumbnail'] ?: 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=500&q=80') ?>" alt="<?= e($religionNews[0]['title']) ?>">
                                </a>
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <a href="/news/<?= (int)$religionNews[0]['id'] ?>/<?= e($religionNews[0]['slug']) ?>"><?= e($religionNews[0]['title']) ?></a>
                                    </h6>
                                    <span class="card-meta"><i class="bi bi-clock me-1"></i><?= get_time_ago($religionNews[0]['created_at']) ?></span>
                                </div>
                            </div>
                            <?php foreach (array_slice($religionNews, 1) as $rItem): ?>
                                <div class="news-list-item">
                                    <a href="/news/<?= (int)$rItem['id'] ?>/<?= e($rItem['slug']) ?>" class="item-title">
                                        <i class="bi bi-moon-stars text-success me-1"></i> <?= e($rItem['title']) ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Block 6: চাকরি ও ক্যারিয়ার এবং শিক্ষা-সাহিত্য -->
            <section class="category-block mb-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="section-title">
                            <span>চাকরি ও ক্যারিয়ার</span>
                            <a href="/category/jobs-career" class="more-link">আরও <i class="bi bi-chevron-right"></i></a>
                        </div>
                        <?php if (!empty($jobsNews)): ?>
                            <div class="news-card mb-3">
                                <a href="/news/<?= (int)$jobsNews[0]['id'] ?>/<?= e($jobsNews[0]['slug']) ?>" class="card-thumb d-block">
                                    <img src="<?= e($jobsNews[0]['thumbnail'] ?: 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?w=500&q=80') ?>" alt="<?= e($jobsNews[0]['title']) ?>">
                                </a>
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <a href="/news/<?= (int)$jobsNews[0]['id'] ?>/<?= e($jobsNews[0]['slug']) ?>"><?= e($jobsNews[0]['title']) ?></a>
                                    </h6>
                                    <span class="card-meta"><i class="bi bi-clock me-1"></i><?= get_time_ago($jobsNews[0]['created_at']) ?></span>
                                </div>
                            </div>
                            <?php foreach (array_slice($jobsNews, 1) as $jItem): ?>
                                <div class="news-list-item">
                                    <a href="/news/<?= (int)$jItem['id'] ?>/<?= e($jItem['slug']) ?>" class="item-title">
                                        <i class="bi bi-briefcase text-info me-1"></i> <?= e($jItem['title']) ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <div class="section-title">
                            <span>শিক্ষা ও সাহিত্য</span>
                            <a href="/category/education-literature" class="more-link">আরও <i class="bi bi-chevron-right"></i></a>
                        </div>
                        <?php if (!empty($eduNews)): ?>
                            <div class="news-card mb-3">
                                <a href="/news/<?= (int)$eduNews[0]['id'] ?>/<?= e($eduNews[0]['slug']) ?>" class="card-thumb d-block">
                                    <img src="<?= e($eduNews[0]['thumbnail'] ?: 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?w=500&q=80') ?>" alt="<?= e($eduNews[0]['title']) ?>">
                                </a>
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <a href="/news/<?= (int)$eduNews[0]['id'] ?>/<?= e($eduNews[0]['slug']) ?>"><?= e($eduNews[0]['title']) ?></a>
                                    </h6>
                                    <span class="card-meta"><i class="bi bi-clock me-1"></i><?= get_time_ago($eduNews[0]['created_at']) ?></span>
                                </div>
                            </div>
                            <?php foreach (array_slice($eduNews, 1) as $edItem): ?>
                                <div class="news-list-item">
                                    <a href="/news/<?= (int)$edItem['id'] ?>/<?= e($edItem['slug']) ?>" class="item-title">
                                        <i class="bi bi-book text-secondary me-1"></i> <?= e($edItem['title']) ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>

        <!-- Right Column (4 Cols): Top Ad Board + Sidebar -->
        <div class="col-lg-4">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>
        </div>
    </div>

    <!-- Full-Width Section: সারাদেশ ও খেলাধুলা -->
    <section class="category-block mt-3 mb-4">
        <div class="row g-4">
            <!-- সারাদেশ -->
            <div class="col-lg-6">
                <div class="section-title">
                    <span>সারাদেশ</span>
                    <a href="/category/country" class="more-link">আরও খবর <i class="bi bi-chevron-right"></i></a>
                </div>
                <div class="row g-3">
                    <?php foreach ($countryNews as $cItem): ?>
                        <div class="col-sm-6">
                            <div class="news-card h-100">
                                <a href="/news/<?= (int)$cItem['id'] ?>/<?= e($cItem['slug']) ?>" class="card-thumb d-block">
                                    <img src="<?= e($cItem['thumbnail'] ?: 'https://images.unsplash.com/photo-1507676184212-d03ab07a01bf?w=500&q=80') ?>" alt="<?= e($cItem['title']) ?>">
                                </a>
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <a href="/news/<?= (int)$cItem['id'] ?>/<?= e($cItem['slug']) ?>"><?= e($cItem['title']) ?></a>
                                    </h6>
                                    <span class="card-meta"><i class="bi bi-clock me-1"></i><?= get_time_ago($cItem['created_at']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- খেলাধুলা -->
            <div class="col-lg-6">
                <div class="section-title">
                    <span>খেলাধুলা</span>
                    <a href="/category/sports" class="more-link">আরও খবর <i class="bi bi-chevron-right"></i></a>
                </div>
                <div class="row g-3">
                    <?php foreach ($sportsNews as $sItem): ?>
                        <div class="col-sm-6">
                            <div class="news-card h-100">
                                <a href="/news/<?= (int)$sItem['id'] ?>/<?= e($sItem['slug']) ?>" class="card-thumb d-block">
                                    <img src="<?= e($sItem['thumbnail'] ?: 'https://images.unsplash.com/photo-1540747913346-19e32dc3e97e?w=500&q=80') ?>" alt="<?= e($sItem['title']) ?>">
                                </a>
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <a href="/news/<?= (int)$sItem['id'] ?>/<?= e($sItem['slug']) ?>"><?= e($sItem['title']) ?></a>
                                    </h6>
                                    <span class="card-meta"><i class="bi bi-clock me-1"></i><?= get_time_ago($sItem['created_at']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Multimedia & Photo Gallery Section (Like Alokito Gour) -->
    <section class="gallery-section my-5 p-4 rounded bg-dark text-white">
        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom border-secondary pb-2">
            <h4 class="fw-bold mb-0 text-white"><i class="bi bi-camera-fill text-warning me-2"></i>ছবি ও ভিডিও গ্যালারি</h4>
            <a href="#" class="text-warning text-decoration-none small">সব গ্যালারি <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-3">
            <div class="col-md-3 col-sm-6">
                <div class="position-relative rounded overflow-hidden shadow-sm" style="aspect-ratio: 16/10;">
                    <img src="https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?w=500&q=80" alt="Gallery 1" class="w-100 h-100 object-fit-cover">
                    <span class="position-absolute bottom-0 start-0 end-0 p-2 bg-dark bg-opacity-75 small text-white">চাঁপাইনবাবগঞ্জে পরিচ্ছন্নতা অভিযান</span>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="position-relative rounded overflow-hidden shadow-sm" style="aspect-ratio: 16/10;">
                    <img src="https://images.unsplash.com/photo-1540747913346-19e32dc3e97e?w=500&q=80" alt="Gallery 2" class="w-100 h-100 object-fit-cover">
                    <span class="position-absolute bottom-0 start-0 end-0 p-2 bg-dark bg-opacity-75 small text-white">জেলা গোল্ডকাপ ফুটবল টুর্নামেন্ট</span>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="position-relative rounded overflow-hidden shadow-sm" style="aspect-ratio: 16/10;">
                    <img src="https://images.unsplash.com/photo-1507676184212-d03ab07a01bf?w=500&q=80" alt="Gallery 3" class="w-100 h-100 object-fit-cover">
                    <span class="position-absolute bottom-0 start-0 end-0 p-2 bg-dark bg-opacity-75 small text-white">ঐতিহ্যবাহী লোকজ যাত্রাপালা মঞ্চায়ন</span>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="position-relative rounded overflow-hidden shadow-sm" style="aspect-ratio: 16/10;">
                    <img src="https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=500&q=80" alt="Gallery 4" class="w-100 h-100 object-fit-cover">
                    <span class="position-absolute bottom-0 start-0 end-0 p-2 bg-dark bg-opacity-75 small text-white">কক্সবাজার আন্তর্জাতিক বিমানবন্দর উন্নয়ন</span>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
