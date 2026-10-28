<?php
/**
 * Breaking News Ticker Component
 * Alokito Gour Clone
 */

declare(strict_types=1);

$tickerDb = get_db();
$tickerPosts = $tickerDb->query("
    SELECT id, title, slug 
    FROM `posts` 
    WHERE `status` = 'published' AND `is_breaking` = 1 
    ORDER BY `id` DESC 
    LIMIT 10
")->fetchAll();

// Fallback to latest published if no breaking flags
if (empty($tickerPosts)) {
    $tickerPosts = $tickerDb->query("
        SELECT id, title, slug 
        FROM `posts` 
        WHERE `status` = 'published' 
        ORDER BY `id` DESC 
        LIMIT 8
    ")->fetchAll();
}
?>

<?php if (!empty($tickerPosts)): ?>
<section class="ticker-section">
    <div class="container">
        <div class="d-flex align-items-center">
            <div class="ticker-badge">
                <i class="bi bi-broadcast"></i>
                <span>সংবাদ শিরোনাম</span>
            </div>

            <div class="ticker-marquee-wrapper">
                <div class="ticker-marquee">
                    <?php foreach ($tickerPosts as $item): ?>
                        <a href="/news/<?= (int)$item['id'] ?>/<?= e($item['slug']) ?>">
                            <i class="bi bi-dot text-danger"></i> <?= e($item['title']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
