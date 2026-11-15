<?php
/**
 * Public Footer - 100% White-Label & Customizable
 */

declare(strict_types=1);

$footDb = get_db();
$settings = get_settings();

$footerCats = $footDb->query("
    SELECT name, slug 
    FROM `categories` 
    WHERE `show_in_menu` = 1 
    ORDER BY `order_index` ASC 
    LIMIT 10
")->fetchAll();

$cmsPages = get_cms_pages(true);

$footerLogo = !empty($settings['logo_footer']) ? $settings['logo_footer'] : (!empty($settings['logo_path']) ? $settings['logo_path'] : '');
$siteTitle = $settings['site_title'] ?? 'বাংলা নিউজ পোর্টাল';

$footerAbout = !empty($settings['footer_about_text']) 
    ? $settings['footer_about_text'] 
    : 'দেশ ও বিদেশের সত্য ও বস্তুনিষ্ঠ সংবাদ সবার আগে পাওয়ার নির্ভরযোগ্য ডিজিটাল ঠিকানা।';

// Copyright text template
$currentYearBn = bn_number(date('Y'));
$defaultCopyright = "কপিরাইট &copy; {$currentYearBn} {$siteTitle} &bull; সর্বস্বত্ব সংরক্ষিত";
$customCopyright = $settings['copyright_text'] ?? '';
if (!empty($customCopyright)) {
    $copyrightRendered = str_replace(
        ['{YEAR}', '{SITE_NAME}', '{YEAR_EN}'],
        [$currentYearBn, $siteTitle, date('Y')],
        $customCopyright
    );
} else {
    $copyrightRendered = $defaultCopyright;
}

$showTech = (int)($settings['show_tech_partner'] ?? 1);
$techName = $settings['tech_partner_name'] ?? 'DataEnvelope';
$techUrl = $settings['tech_partner_url'] ?? 'https://www.dataenvelope.com/';
?>

<!-- Public Footer -->
<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <!-- Brand & Editor Info Column -->
            <div class="col-lg-5 col-md-6">
                <div class="footer-logo mb-3">
                    <?php if (!empty($footerLogo)): ?>
                        <img src="<?= e($footerLogo) ?>" alt="<?= e($siteTitle) ?>" class="img-fluid bg-white p-2 rounded" style="max-height: 60px;">
                    <?php else: ?>
                        <h4 class="text-white fw-bold mb-0"><?= e($siteTitle) ?></h4>
                    <?php endif; ?>
                </div>

                <div class="mb-3 text-white small">
                    <?php if (!empty($settings['editor_name'])): ?>
                    <p class="mb-1">
                        <strong class="text-warning">প্রধান সম্পাদক:</strong> <?= e($settings['editor_name']) ?>
                    </p>
                    <?php endif; ?>

                    <?php if (!empty($settings['executive_editor'])): ?>
                    <p class="mb-1">
                        <strong class="text-warning">নির্বাহী সম্পাদক:</strong> <?= e($settings['executive_editor']) ?>
                    </p>
                    <?php endif; ?>

                    <?php if (!empty($settings['advisory_editor'])): ?>
                    <p class="mb-1">
                        <strong class="text-warning">উপদেষ্টা সম্পাদক:</strong> <?= e($settings['advisory_editor']) ?>
                    </p>
                    <?php endif; ?>

                    <?php if (!empty($settings['publisher_name'])): ?>
                    <p class="mb-1">
                        <strong class="text-warning">প্রকাশক:</strong> <?= e($settings['publisher_name']) ?>
                    </p>
                    <?php endif; ?>

                    <?php if (!empty($settings['gov_reg_no'])): ?>
                    <p class="mb-1 text-light-50">
                        <i class="bi bi-patch-check-fill text-warning me-1"></i><?= e($settings['gov_reg_no']) ?>
                    </p>
                    <?php endif; ?>
                </div>

                <div class="footer-contact-info text-white-50 small">
                    <?php if (!empty($settings['office_address'])): ?>
                    <p class="mb-2"><i class="bi bi-geo-alt-fill me-2 text-warning"></i><?= e($settings['office_address']) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($settings['contact_phone'])): ?>
                    <p class="mb-2"><i class="bi bi-telephone-fill me-2 text-warning"></i><?= e($settings['contact_phone']) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($settings['contact_email'])): ?>
                    <p class="mb-0"><i class="bi bi-envelope-fill me-2 text-warning"></i><?= e($settings['contact_email']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Category Links Column -->
            <div class="col-lg-4 col-md-6">
                <h6 class="text-white border-bottom border-secondary pb-2 mb-3">সংবাদের বিষয়শ্রেণী</h6>
                <div class="row">
                    <div class="col-6">
                        <ul class="list-unstyled">
                            <li class="mb-1"><a href="/"><i class="bi bi-chevron-right me-1 text-warning small"></i>প্রচ্ছদ</a></li>
                            <?php foreach (array_slice($footerCats, 0, 5) as $fCat): ?>
                                <li class="mb-1">
                                    <a href="/category/<?= e($fCat['slug']) ?>">
                                        <i class="bi bi-chevron-right me-1 text-warning small"></i><?= e($fCat['name']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col-6">
                        <ul class="list-unstyled">
                            <?php foreach (array_slice($footerCats, 5) as $fCat): ?>
                                <li class="mb-1">
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
                <p class="footer-desc mb-3 text-white-50 small">
                    <?= e($footerAbout) ?>
                </p>

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <?php if (!empty($settings['facebook_url'])): ?>
                        <a href="<?= e($settings['facebook_url']) ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm"><i class="bi bi-facebook me-1"></i>Facebook</a>
                    <?php endif; ?>
                    <?php if (!empty($settings['youtube_url'])): ?>
                        <a href="<?= e($settings['youtube_url']) ?>" target="_blank" rel="noopener" class="btn btn-danger btn-sm"><i class="bi bi-youtube me-1"></i>YouTube</a>
                    <?php endif; ?>
                    <?php if (!empty($settings['whatsapp_channel_url'])): ?>
                        <a href="<?= e($settings['whatsapp_channel_url']) ?>" target="_blank" rel="noopener" class="btn btn-success btn-sm"><i class="bi bi-whatsapp me-1"></i>WhatsApp</a>
                    <?php endif; ?>
                </div>

                <!-- Dynamic Legal CMS Pages Links -->
                <?php if (!empty($cmsPages)): ?>
                <div class="footer-legal-links small text-white-50">
                    <?php 
                    $pageLinks = [];
                    foreach ($cmsPages as $p) {
                        $pageLinks[] = '<a href="/page/' . e($p['slug']) . '" class="text-white-50 text-decoration-none hover-light">' . e($p['title']) . '</a>';
                    }
                    echo implode(' | ', $pageLinks);
                    ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer-bottom d-flex flex-wrap justify-content-between align-items-center gap-2 pt-3 mt-4 border-top border-secondary">
            <div class="footer-copyright text-light small">
                <?= $copyrightRendered ?>
            </div>
            <?php if ($showTech && !empty($techName)): ?>
            <div class="footer-bottom-tech text-light small">
                কারিগরি সহায়তায়: <a href="<?= e($techUrl) ?>" target="_blank" rel="noopener" class="text-warning text-decoration-none fw-bold"><?= e($techName) ?></a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 Bundle JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Public Custom Scripts -->
<script src="/assets/js/main.js"></script>

<!-- Custom Footer Code Injection -->
<?php if (!empty($settings['custom_footer_code'])): ?>
<?= $settings['custom_footer_code'] ?>
<?php endif; ?>

</body>
</html>
