<?php
/**
 * Homepage (Dynamic News Portal)
 * Alokito Gour Clone - 100% Authentic Clone of https://alokitogour.com/
 * Powered by Modular WordPress-style Page Builder Engine
 */

declare(strict_types=1);

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
