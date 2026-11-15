<?php
/**
 * Homepage (Dynamic News Portal)
 * Alokito Gour Clone - 100% Authentic Clone of https://alokitogour.com/
 * Powered by Modular WordPress-style Page Builder Engine
 */

declare(strict_types=1);

// Auto-detect fresh installation
if (!file_exists(__DIR__ . '/config/installed.lock') && !file_exists(__DIR__ . '/config/db_custom.php') && !file_exists(__DIR__ . '/database/alokito_gour.sqlite')) {
    header('Location: /install.php');
    exit;
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/widget_renderer.php';

$db = get_db();
$settings = get_settings();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/ticker.php';
?>

<div class="container my-3">
    <?php render_page_layout('home', $db); ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
