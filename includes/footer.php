<?php
/**
 * Public Footer
 * Alokito Gour Clone
 */

declare(strict_types=1);

$footDb = get_db();
$footerCats = $footDb->query("
    SELECT name, slug 
    FROM `categories` 
    WHERE `show_in_menu` = 1 
    ORDER BY `order_index` ASC 
    LIMIT 10
")->fetchAll();

$settings = get_settings();
?>

<!-- Public Footer -->
<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <!-- Brand & Editor Info Column -->
            <div class="col-lg-5 col-md-6">
                <div class="footer-logo mb-3">
                    <?php if (!empty($settings['logo_path'])): ?>
                        <img src="<?= e($settings['logo_path']) ?>" alt="Logo" class="img-fluid bg-white p-2 rounded">
                    <?php else: ?>
                        <h4 class="text-white fw-bold">আলোকিত গৌড়</h4>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <p class="mb-1 text-white">
                        <strong class="text-warning">প্রধান সম্পাদক ও প্রকাশক:</strong> <?= e($settings['editor_name'] ?? 'মোহাঃ আব্দুল্লাহ') ?>
                    </p>
                    <p class="mb-1 text-white">
                        <strong class="text-warning">প্রকাশক:</strong> <?= e($settings['publisher_name'] ?? 'মোহাঃ আব্দুল্লাহ') ?>
                    </p>
                </div>

                <div class="footer-contact-info">
                    <p class="mb-2"><i class="bi bi-geo-alt-fill me-2 text-warning"></i><?= e($settings['office_address'] ?? 'ক্লাব সুপার মার্কেট (৪র্থ তলা), সদর, চাঁপাইনবাবগঞ্জ।') ?></p>
                    <p class="mb-2"><i class="bi bi-telephone-fill me-2 text-warning"></i><?= e($settings['contact_phone'] ?? '01333479192 (সম্পাদক), 01333479193 (অফিস)') ?></p>
                    <p class="mb-0"><i class="bi bi-envelope-fill me-2 text-warning"></i><?= e($settings['contact_email'] ?? 'editor@alokitogour.com') ?></p>
                </div>
            </div>

            <!-- Quick Category Links Column -->
            <div class="col-lg-4 col-md-6">
                <h6 class="text-white border-bottom border-secondary pb-2 mb-3">সংবাদের বিষয়শ্রেণী</h6>
                <div class="row">
                    <div class="col-6">
                        <ul>
                            <li><a href="/"><i class="bi bi-chevron-right me-1 text-warning small"></i>প্রচ্ছদ</a></li>
                            <?php foreach (array_slice($footerCats, 0, 5) as $fCat): ?>
                                <li>
                                    <a href="/category/<?= e($fCat['slug']) ?>">
                                        <i class="bi bi-chevron-right me-1 text-warning small"></i><?= e($fCat['name']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col-6">
                        <ul>
                            <?php foreach (array_slice($footerCats, 5) as $fCat): ?>
                                <li>
                                    <a href="/category/<?= e($fCat['slug']) ?>">
                                        <i class="bi bi-chevron-right me-1 text-warning small"></i><?= e($fCat['name']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Portal Identity & Connect Column -->
            <div class="col-lg-3 col-md-12">
                <h6 class="text-white border-bottom border-secondary pb-2 mb-3">সংযুক্ত থাকুন</h6>
                <p class="footer-desc mb-3">
                    চাঁপাইনবাবগঞ্জ, রাজশাহী ও দেশ-বিদেশের সত্য ও বস্তুনিষ্ঠ সংবাদ সবার আগে পাওয়ার নির্ভরযোগ্য ঠিকানা।
                </p>

                <div class="d-flex gap-2 mb-3">
                    <?php if (!empty($settings['facebook_url'])): ?>
                        <a href="<?= e($settings['facebook_url']) ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm"><i class="bi bi-facebook me-1"></i>Facebook</a>
                    <?php endif; ?>
                    <?php if (!empty($settings['youtube_url'])): ?>
                        <a href="<?= e($settings['youtube_url']) ?>" target="_blank" rel="noopener" class="btn btn-danger btn-sm"><i class="bi bi-youtube me-1"></i>YouTube</a>
                    <?php endif; ?>
                </div>

                <div class="footer-legal-links small">
                    <a href="/privacy-policy" class="me-2">গোপনীয়তা নীতি</a> | 
                    <a href="/contact" class="ms-2">যোগাযোগ</a>
                </div>
            </div>
        </div>

        <div class="footer-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="footer-copyright text-light">
                কপিরাইট &copy; ২০২৬ <a href="/" class="text-white fw-bold text-decoration-none">আলোকিত গৌড়</a> &bull; সর্বস্বত্ব সংরক্ষিত
            </div>
            <div class="footer-bottom-tech text-light">
                কারিগরি সহায়তায়: <a href="https://www.dataenvelope.com/" target="_blank" rel="noopener" class="text-warning text-decoration-none fw-bold">DataEnvelope</a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 Bundle JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Public Custom Scripts -->
<script src="/assets/js/main.js"></script>

</body>
</html>
