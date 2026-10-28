<?php
/**
 * Sidebar & Custom Blocks Manager
 * Manages right-column blocks, page-specific targeting, ordering, and custom HTML/ad blocks
 * Alokito Gour Clone
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';

$pageTitle = 'Sidebar & Custom Blocks | Alokito Gour';
$db = get_db();

// Standard Block Types Definition
$blockTypes = [
    'custom_html' => [
        'name' => 'Custom HTML / Notice / Embed',
        'badge' => 'bg-primary',
        'icon' => 'bi-code-slash'
    ],
    'ad_banner' => [
        'name' => 'Advertisement Banner',
        'badge' => 'bg-warning text-dark',
        'icon' => 'bi-badge-ad'
    ],
    'latest_popular' => [
        'name' => 'Latest & Popular News Tabs',
        'badge' => 'bg-info text-dark',
        'icon' => 'bi-clock-history'
    ],
    'category_news' => [
        'name' => 'Category News List',
        'badge' => 'bg-success',
        'icon' => 'bi-card-heading'
    ],
    'image_banner' => [
        'name' => 'Image Banner with Link',
        'badge' => 'bg-purple text-white',
        'icon' => 'bi-image'
    ],
    'district_filter' => [
        'name' => 'Regional District Filter',
        'badge' => 'bg-danger',
        'icon' => 'bi-geo-alt-fill'
    ],
];

// Available Target Pages
$availablePages = [
    'all'      => 'All Pages (সকল পেজে)',
    'home'     => 'Homepage Only (শুধু হোমপেজে)',
    'single'   => 'Single News Only (শুধু সংবাদের বিস্তারিত পেজে)',
    'category' => 'Category Archive Only (শুধু ক্যাটাগরি পেজে)',
    'search'   => 'Search Results Only (শুধু সার্চ পেজে)'
];

// Fetch all categories for Category News block type
$categories = $db->query("SELECT id, name, slug FROM `categories` ORDER BY `order_index` ASC")->fetchAll(PDO::FETCH_ASSOC);

// -------------------------------------------------------------
// Action: Delete Block (GET)
// -------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $token = $_GET['csrf_token'] ?? '';
    if (hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $delId = (int)$_GET['id'];
        $stmt = $db->prepare("SELECT * FROM `sidebar_blocks` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => $delId]);
        $block = $stmt->fetch();

        if ($block) {
            $delStmt = $db->prepare("DELETE FROM `sidebar_blocks` WHERE `id` = :id");
            $delStmt->execute([':id' => $delId]);
            set_flash('success', 'Sidebar block deleted successfully.');
        } else {
            set_flash('danger', 'Sidebar block not found!');
        }
    } else {
        set_flash('danger', 'Security token is invalid!');
    }
    header('Location: sidebar-manager.php');
    exit;
}

// -------------------------------------------------------------
// Action: Toggle Status (GET)
// -------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $token = $_GET['csrf_token'] ?? '';
    if (hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $bId = (int)$_GET['id'];
        $stmt = $db->prepare("SELECT `id`, `status` FROM `sidebar_blocks` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => $bId]);
        $block = $stmt->fetch();

        if ($block) {
            $newStatus = $block['status'] ? 0 : 1;
            $update = $db->prepare("UPDATE `sidebar_blocks` SET `status` = :st WHERE `id` = :id");
            $update->execute([':st' => $newStatus, ':id' => $bId]);
            set_flash('success', 'Block status updated successfully.');
        }
    }
    header('Location: sidebar-manager.php');
    exit;
}

// -------------------------------------------------------------
// Action: Save / Update Block (POST)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_block') {
    verify_csrf_token();

    $blockId = (int)($_POST['block_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $blockType = trim($_POST['block_type'] ?? 'custom_html');
    $content = trim($_POST['content'] ?? '');
    $positionOrder = (int)($_POST['position_order'] ?? 10);
    $showTitle = isset($_POST['show_title']) ? 1 : 0;
    $status = isset($_POST['status']) ? 1 : 0;

    // Handle Page Targets
    $targetPagesInput = $_POST['page_target'] ?? ['all'];
    if (in_array('all', (array)$targetPagesInput, true) || empty($targetPagesInput)) {
        $pageTarget = 'all';
    } else {
        $pageTarget = implode(',', (array)$targetPagesInput);
    }

    if (!isset($blockTypes[$blockType])) {
        $blockType = 'custom_html';
    }

    if (empty($title)) {
        $title = $blockTypes[$blockType]['name'];
    }

    // Build Settings JSON based on block type
    $settingsArr = [];
    if ($blockType === 'ad_banner') {
        $settingsArr['position'] = trim($_POST['ad_position'] ?? 'sidebar');
    } elseif ($blockType === 'latest_popular') {
        $settingsArr['limit'] = max(3, min(20, (int)($_POST['tab_limit'] ?? 7)));
    } elseif ($blockType === 'category_news') {
        $settingsArr['category_slug'] = trim($_POST['category_slug'] ?? 'chapai-nawabganj');
        $settingsArr['limit'] = max(2, min(15, (int)($_POST['category_limit'] ?? 5)));
    } elseif ($blockType === 'image_banner') {
        $imageUrl = trim($_POST['existing_image'] ?? '');
        if (!empty($_FILES['image_file']['name'])) {
            $uploaded = upload_image_webp($_FILES['image_file'], 'assets/uploads/banners/');
            if ($uploaded) {
                $imageUrl = $uploaded;
            }
        } elseif (!empty($_POST['custom_image_url'])) {
            $imageUrl = trim($_POST['custom_image_url']);
        }
        $settingsArr['image_url'] = $imageUrl;
    }

    $settingsJson = json_encode($settingsArr, JSON_UNESCAPED_UNICODE);

    if ($blockId > 0) {
        // Update existing block
        $stmt = $db->prepare("
            UPDATE `sidebar_blocks`
            SET `title` = :title,
                `block_type` = :btype,
                `content` = :content,
                `page_target` = :page,
                `position_order` = :ord,
                `show_title` = :stitle,
                `status` = :status,
                `settings` = :settings,
                `updated_at` = CURRENT_TIMESTAMP
            WHERE `id` = :id
        ");
        $stmt->execute([
            ':title'    => $title,
            ':btype'    => $blockType,
            ':content'  => $content,
            ':page'     => $pageTarget,
            ':ord'      => $positionOrder,
            ':stitle'   => $showTitle,
            ':status'   => $status,
            ':settings' => $settingsJson,
            ':id'       => $blockId
        ]);
        set_flash('success', 'Sidebar block "' . e($title) . '" updated successfully.');
    } else {
        // Insert new block
        $stmt = $db->prepare("
            INSERT INTO `sidebar_blocks`
            (`title`, `block_type`, `content`, `page_target`, `position_order`, `show_title`, `status`, `settings`)
            VALUES (:title, :btype, :content, :page, :ord, :stitle, :status, :settings)
        ");
        $stmt->execute([
            ':title'    => $title,
            ':btype'    => $blockType,
            ':content'  => $content,
            ':page'     => $pageTarget,
            ':ord'      => $positionOrder,
            ':stitle'   => $showTitle,
            ':status'   => $status,
            ':settings' => $settingsJson
        ]);
        set_flash('success', 'New sidebar block "' . e($title) . '" created successfully.');
    }

    header('Location: sidebar-manager.php');
    exit;
}

// -------------------------------------------------------------
// Fetch All Blocks with Filtering
// -------------------------------------------------------------
$filterPage = trim($_GET['filter_page'] ?? '');
$filterType = trim($_GET['filter_type'] ?? '');
$filterStatus = isset($_GET['filter_status']) && $_GET['filter_status'] !== '' ? (int)$_GET['filter_status'] : null;

$sql = "SELECT * FROM `sidebar_blocks` WHERE 1=1";
$params = [];

if ($filterType !== '' && isset($blockTypes[$filterType])) {
    $sql .= " AND `block_type` = :ftype";
    $params[':ftype'] = $filterType;
}

if ($filterStatus !== null) {
    $sql .= " AND `status` = :fstatus";
    $params[':fstatus'] = $filterStatus;
}

if ($filterPage !== '') {
    if ($filterPage === 'all') {
        $sql .= " AND `page_target` = 'all'";
    } else {
        $sql .= " AND (`page_target` = 'all' OR `page_target` LIKE :fpage)";
        $params[':fpage'] = '%' . $filterPage . '%';
    }
}

$sql .= " ORDER BY `position_order` ASC, `id` ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$blocksList = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-layout-sidebar-inset-reverse text-warning me-2"></i>Sidebar & Custom Blocks
        </h4>
        <p class="text-muted small mb-0">Create custom sidebar blocks, select which pages to display them on, and set custom order positions.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-warning text-white fw-bold d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#blockModal" onclick="openAddModal()">
            <i class="bi bi-plus-circle"></i>
            <span>Add New Block</span>
        </button>
    </div>
</div>

<!-- Flash Alerts -->
<?php $flash = get_flash(); if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
        <?= $flash['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Blocks Table Card -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3 border-bottom">
        <div class="row g-2 align-items-center justify-content-between">
            <div class="col-md-4">
                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-list-ul text-warning"></i>
                    <span>Sidebar Blocks List (Total: <?= count($blocksList) ?>)</span>
                </h6>
            </div>

            <!-- Filters -->
            <div class="col-md-8">
                <form method="GET" action="sidebar-manager.php" class="d-flex flex-wrap align-items-center justify-content-md-end gap-2">
                    <!-- Page Filter -->
                    <select name="filter_page" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                        <option value="">All Page Targets</option>
                        <?php foreach ($availablePages as $pKey => $pLabel): ?>
                            <option value="<?= e($pKey) ?>" <?= $filterPage === $pKey ? 'selected' : '' ?>>
                                <?= e($pLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Type Filter -->
                    <select name="filter_type" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                        <option value="">All Block Types</option>
                        <?php foreach ($blockTypes as $tKey => $tMeta): ?>
                            <option value="<?= e($tKey) ?>" <?= $filterType === $tKey ? 'selected' : '' ?>>
                                <?= e($tMeta['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Status Filter -->
                    <select name="filter_status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="1" <?= $filterStatus === 1 ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $filterStatus === 0 ? 'selected' : '' ?>>Inactive</option>
                    </select>

                    <?php if ($filterPage !== '' || $filterType !== '' || $filterStatus !== null): ?>
                        <a href="sidebar-manager.php" class="btn btn-light btn-sm text-danger" title="Reset Filters">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <?php if (empty($blocksList)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-layout-sidebar-inset-reverse fs-1 text-secondary opacity-50 mb-2"></i>
                <h6>No sidebar blocks found</h6>
                <p class="small mb-3">Click the button above to create and place your first sidebar block.</p>
                <button type="button" class="btn btn-warning text-white btn-sm" data-bs-toggle="modal" data-bs-target="#blockModal" onclick="openAddModal()">
                    <i class="bi bi-plus-circle me-1"></i>Add First Block
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 70px;" class="text-center">Order</th>
                            <th>Block Title & Info</th>
                            <th style="width: 170px;">Block Type</th>
                            <th style="width: 200px;">Target Pages</th>
                            <th style="width: 110px;" class="text-center">Status</th>
                            <th style="width: 120px;" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blocksList as $block): 
                            $typeMeta = $blockTypes[$block['block_type']] ?? [
                                'name' => $block['block_type'],
                                'badge' => 'bg-secondary',
                                'icon' => 'bi-puzzle'
                            ];
                            $settings = json_decode($block['settings'] ?? '{}', true) ?: [];
                            $pageTarget = $block['page_target'] ?? 'all';
                            $targetList = array_map('trim', explode(',', $pageTarget));
                        ?>
                            <tr>
                                <!-- Position Order -->
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border px-2 py-1 fw-bold font-monospace" style="font-size: 12px;">
                                        #<?= (int)$block['position_order'] ?>
                                    </span>
                                </td>

                                <!-- Title & Preview -->
                                <td>
                                    <div class="fw-bold text-dark mb-1">
                                        <i class="bi <?= $typeMeta['icon'] ?> text-muted me-1"></i>
                                        <?= e($block['title']) ?>
                                        <?php if (!empty($block['show_title'])): ?>
                                            <span class="badge bg-light text-muted border ms-1" style="font-size: 10px;">Header Shown</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-secondary border ms-1" style="font-size: 10px;">Header Hidden</span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Content Preview snippet -->
                                    <div class="text-muted small text-truncate" style="max-width: 360px; font-size: 11.5px;">
                                        <?php if ($block['block_type'] === 'ad_banner'): ?>
                                            <span class="text-secondary"><i class="bi bi-aspect-ratio me-1"></i>Slot: <code><?= e($settings['position'] ?? 'sidebar') ?></code></span>
                                        <?php elseif ($block['block_type'] === 'latest_popular'): ?>
                                            <span class="text-secondary"><i class="bi bi-clock-history me-1"></i>Tabbed list: <?= (int)($settings['limit'] ?? 7) ?> items</span>
                                        <?php elseif ($block['block_type'] === 'category_news'): ?>
                                            <span class="text-secondary"><i class="bi bi-tag me-1"></i>Category: <code><?= e($settings['category_slug'] ?? 'chapai-nawabganj') ?></code> (<?= (int)($settings['limit'] ?? 5) ?> items)</span>
                                        <?php elseif ($block['block_type'] === 'image_banner'): ?>
                                            <span class="text-secondary"><i class="bi bi-link-45deg me-1"></i>Target: <?= e($block['content'] ?: '#') ?></span>
                                        <?php elseif (!empty($block['content'])): ?>
                                            <code><?= e(mb_substr(strip_tags($block['content']), 0, 60)) ?>...</code>
                                        <?php else: ?>
                                            <span class="fst-italic text-muted">Standard component</span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Block Type -->
                                <td>
                                    <span class="badge <?= $typeMeta['badge'] ?> fw-medium" style="font-size: 11px;">
                                        <i class="bi <?= $typeMeta['icon'] ?> me-1"></i><?= e($typeMeta['name']) ?>
                                    </span>
                                </td>

                                <!-- Target Pages -->
                                <td>
                                    <?php if ($pageTarget === 'all' || in_array('all', $targetList, true)): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 11px;">
                                            <i class="bi bi-globe me-1"></i>All Pages
                                        </span>
                                    <?php else: ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($targetList as $tPage): ?>
                                                <?php if ($tPage === 'home'): ?>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 10px;">Home</span>
                                                <?php elseif ($tPage === 'single'): ?>
                                                    <span class="badge bg-purple-subtle text-purple border" style="font-size: 10px; color:#7c3aed; background:#f5f3ff;">Single News</span>
                                                <?php elseif ($tPage === 'category'): ?>
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 10px;">Category</span>
                                                <?php elseif ($tPage === 'search'): ?>
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 10px;">Search</span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Status Toggle -->
                                <td class="text-center">
                                    <a href="sidebar-manager.php?action=toggle&id=<?= e((string)$block['id']) ?>&csrf_token=<?= e($_SESSION['csrf_token'] ?? '') ?>" 
                                       class="btn btn-sm <?= $block['status'] ? 'btn-success' : 'btn-outline-secondary' ?> py-1 px-2 text-decoration-none"
                                       title="Click to toggle status"
                                       style="font-size: 12px;">
                                        <?php if ($block['status']): ?>
                                            <i class="bi bi-check-circle-fill me-1"></i>Active
                                        <?php else: ?>
                                            <i class="bi bi-dash-circle me-1"></i>Inactive
                                        <?php endif; ?>
                                    </a>
                                </td>

                                <!-- Actions -->
                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary" title="Edit Block" onclick='openEditModal(<?= json_encode($block, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="sidebar-manager.php?action=delete&id=<?= e((string)$block['id']) ?>&csrf_token=<?= e($_SESSION['csrf_token'] ?? '') ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           title="Delete Block"
                                           onclick="return confirm('Are you sure you want to delete this block?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================================
     Add / Edit Block Modal
     ======================================================== -->
<div class="modal fade" id="blockModal" tabindex="-1" aria-labelledby="blockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="sidebar-manager.php" enctype="multipart/form-data" id="blockForm">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_block">
                <input type="hidden" name="block_id" id="modal_block_id" value="0">
                <input type="hidden" name="existing_image" id="modal_existing_image" value="">

                <div class="modal-header bg-light py-3">
                    <h5 class="modal-title fw-bold text-dark" id="blockModalLabel">
                        <i class="bi bi-layout-sidebar-inset-reverse text-warning me-2"></i>Add / Edit Sidebar Block
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- 1. Block Title -->
                        <div class="col-md-8">
                            <label class="form-label fw-bold text-secondary small">
                                Block Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="title" id="modal_title" placeholder="e.g. Official Facebook Page, Emergency Hotline, etc." required>
                        </div>

                        <!-- 2. Position Order -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-secondary small">
                                Position Order <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" name="position_order" id="modal_position_order" value="10" min="0" max="999" required>
                            <div class="form-text small" style="font-size:11px;">Smaller numbers appear higher in the sidebar.</div>
                        </div>

                        <!-- 3. Show Title Switch -->
                        <div class="col-12">
                            <div class="form-check form-switch ps-5">
                                <input class="form-check-input" type="checkbox" role="switch" id="modal_show_title" name="show_title" value="1" checked style="cursor: pointer;">
                                <label class="form-check-label fw-bold text-dark small" for="modal_show_title" style="cursor: pointer;">
                                    Display block title heading with decorative bar
                                </label>
                            </div>
                        </div>

                        <!-- 4. Block Type -->
                        <div class="col-12">
                            <label class="form-label fw-bold text-secondary small">
                                Block Type <span class="text-danger">*</span>
                            </label>
                            <select name="block_type" id="modal_block_type" class="form-select fw-medium" onchange="toggleBlockTypeFields()">
                                <?php foreach ($blockTypes as $bKey => $bMeta): ?>
                                    <option value="<?= e($bKey) ?>"><?= e($bMeta['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- 5. Dynamic Type Settings Panels -->
                        
                        <!-- 5a. Custom HTML / Notice Panel -->
                        <div class="col-12 type-panel" id="panel_custom_html">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label class="form-label fw-bold text-secondary small mb-0">Custom HTML / Embed Code</label>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size:11px;" onclick="insertTemplate('notice')">+ Notice Box</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size:11px;" onclick="insertTemplate('hotline')">+ Hotline Box</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size:11px;" onclick="insertTemplate('facebook')">+ Facebook Embed</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size:11px;" onclick="insertTemplate('youtube')">+ YouTube Embed</button>
                                </div>
                            </div>
                            <textarea class="form-control font-monospace form-control-sm" name="content" id="modal_content" rows="6" placeholder="<div class='card p-3'>...</div> বা যেকোনো এম্বেড কোড"></textarea>
                            <div class="form-text small" style="font-size:11px;">You can paste any HTML, iframe, video player, or announcement markup here.</div>
                        </div>

                        <!-- 5b. Ad Banner Panel -->
                        <div class="col-12 type-panel" id="panel_ad_banner" style="display:none;">
                            <label class="form-label fw-bold text-secondary small">Select Advertisement Slot</label>
                            <select name="ad_position" id="modal_ad_position" class="form-select form-select-sm">
                                <option value="sidebar">Sidebar Top Banner (Sidebar Top)</option>
                                <option value="sidebar_bottom">Sidebar Bottom Banner (Sidebar Bottom)</option>
                                <option value="after_lead">After Lead Banner</option>
                                <option value="inside_article">Inside Article Banner</option>
                            </select>
                            <div class="form-text small" style="font-size:11px;">This slot automatically serves the active advertisement configured in <a href="ads-manager.php" target="_blank">Ad Banners</a>.</div>
                        </div>

                        <!-- 5c. Latest / Popular Tabs Panel -->
                        <div class="col-12 type-panel" id="panel_latest_popular" style="display:none;">
                            <label class="form-label fw-bold text-secondary small">Posts Limit per Tab</label>
                            <input type="number" class="form-control form-control-sm" name="tab_limit" id="modal_tab_limit" value="7" min="3" max="20" style="max-width: 150px;">
                            <div class="form-text small" style="font-size:11px;">Number of recent and popular stories to show in the tabs.</div>
                        </div>

                        <!-- 5d. Category News Panel -->
                        <div class="col-12 type-panel" id="panel_category_news" style="display:none;">
                            <div class="row g-2">
                                <div class="col-md-8">
                                    <label class="form-label fw-bold text-secondary small">Select News Category</label>
                                    <select name="category_slug" id="modal_category_slug" class="form-select form-select-sm">
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= e($cat['slug']) ?>"><?= e($cat['name']) ?> (<?= e($cat['slug']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-secondary small">Number of Posts</label>
                                    <input type="number" class="form-control form-control-sm" name="category_limit" id="modal_category_limit" value="5" min="2" max="15">
                                </div>
                            </div>
                        </div>

                        <!-- 5e. Image Banner Panel -->
                        <div class="col-12 type-panel" id="panel_image_banner" style="display:none;">
                            <div class="row g-3">
                                <div class="col-md-7">
                                    <label class="form-label fw-bold text-secondary small">Upload Banner Image (GIF / WebP / JPG / PNG)</label>
                                    <input type="file" class="form-control form-control-sm" name="image_file" id="modal_image_file" accept="image/*" onchange="previewBannerImg(this)">
                                    <div class="mt-2">
                                        <label class="form-label fw-bold text-secondary small">Or Direct Image Online URL</label>
                                        <input type="url" class="form-control form-control-sm" name="custom_image_url" id="modal_custom_image_url" placeholder="https://domain.com/banner.png">
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-bold text-secondary small">Banner Preview</label>
                                    <div class="p-2 border rounded bg-light text-center d-flex align-items-center justify-content-center" style="min-height: 80px;">
                                        <img src="" alt="Preview" id="modal_img_preview" class="img-fluid rounded border" style="max-height: 80px; display: none;">
                                        <span id="modal_no_img_text" class="text-muted small fst-italic">No image selected</span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold text-secondary small">Target Click Link (URL)</label>
                                    <input type="url" class="form-control form-control-sm" name="image_target_url" id="modal_image_target_url" placeholder="https://..." value="#">
                                </div>
                            </div>
                        </div>

                        <!-- 6. Target Pages Selection (Which pages to display on) -->
                        <div class="col-12">
                            <label class="form-label fw-bold text-secondary small d-flex align-items-center justify-content-between">
                                <span>Which Pages to Display this Block on? <span class="text-danger">*</span></span>
                                <span class="text-muted fw-normal" style="font-size:11px;">Select all or customize per page</span>
                            </label>
                            
                            <div class="border rounded-3 p-3 bg-light-subtle">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="page_target_all" name="page_target[]" value="all" checked onchange="toggleAllPages(this)">
                                    <label class="form-check-label fw-bold text-dark" for="page_target_all">
                                        <i class="bi bi-globe text-success me-1"></i>All Pages (সকল পেজে দেখান)
                                    </label>
                                </div>

                                <div class="row g-2 ps-4 pt-1 border-top" id="specific_pages_container">
                                    <div class="col-sm-6">
                                        <div class="form-check">
                                            <input class="form-check-input page-check" type="checkbox" id="page_target_home" name="page_target[]" value="home" onchange="toggleSpecificPage()">
                                            <label class="form-check-label text-dark small" for="page_target_home">
                                                <i class="bi bi-house me-1 text-primary"></i>Homepage (হোমপেজ)
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-check">
                                            <input class="form-check-input page-check" type="checkbox" id="page_target_single" name="page_target[]" value="single" onchange="toggleSpecificPage()">
                                            <label class="form-check-label text-dark small" for="page_target_single">
                                                <i class="bi bi-file-earmark-text me-1 text-purple" style="color:#7c3aed;"></i>Single News Article (সংবাদ বিস্তারিত)
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-check">
                                            <input class="form-check-input page-check" type="checkbox" id="page_target_category" name="page_target[]" value="category" onchange="toggleSpecificPage()">
                                            <label class="form-check-label text-dark small" for="page_target_category">
                                                <i class="bi bi-tags me-1 text-warning"></i>Category Archive (ক্যাটাগরি পেজ)
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-check">
                                            <input class="form-check-input page-check" type="checkbox" id="page_target_search" name="page_target[]" value="search" onchange="toggleSpecificPage()">
                                            <label class="form-check-label text-dark small" for="page_target_search">
                                                <i class="bi bi-search me-1 text-info"></i>Search Results (অনুসন্ধান পেজ)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 7. Active Status Switch -->
                        <div class="col-12">
                            <div class="form-check form-switch ps-5 mt-1">
                                <input class="form-check-input" type="checkbox" role="switch" id="modal_status" name="status" value="1" checked style="cursor: pointer;">
                                <label class="form-check-label fw-bold text-dark small" for="modal_status" style="cursor: pointer;">
                                    Make this block active and visible on the website
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-white fw-bold btn-sm px-4" id="modal_submit_btn">
                        <i class="bi bi-check-lg me-1"></i>Save Block
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleBlockTypeFields() {
    var type = document.getElementById('modal_block_type').value;
    document.querySelectorAll('.type-panel').forEach(function(el) {
        el.style.display = 'none';
    });

    var panel = document.getElementById('panel_' + type);
    if (panel) {
        panel.style.display = 'block';
    }
}

function toggleAllPages(allCheck) {
    var checkBoxes = document.querySelectorAll('.page-check');
    if (allCheck.checked) {
        checkBoxes.forEach(function(cb) {
            cb.checked = false;
            cb.disabled = true;
        });
    } else {
        checkBoxes.forEach(function(cb) {
            cb.disabled = false;
        });
    }
}

function toggleSpecificPage() {
    var allCheck = document.getElementById('page_target_all');
    var anyChecked = false;
    document.querySelectorAll('.page-check').forEach(function(cb) {
        if (cb.checked) anyChecked = true;
    });

    if (anyChecked) {
        allCheck.checked = false;
    }
}

function insertTemplate(name) {
    var contentEl = document.getElementById('modal_content');
    if (name === 'notice') {
        contentEl.value = '<div class="alert alert-warning border shadow-sm p-3 mb-0">\n  <h6 class="fw-bold mb-1"><i class="bi bi-megaphone-fill me-1"></i>বিশেষ জরুরি নোটিশ</h6>\n  <p class="small mb-0">এখানে আপনার প্রয়োজনীয় নোটিশ বা ঘোষণার বার্তা লিখুন।</p>\n</div>';
    } else if (name === 'hotline') {
        contentEl.value = '<div class="card bg-danger text-white border-0 shadow-sm p-3">\n  <h6 class="fw-bold mb-1"><i class="bi bi-telephone-inbound-fill me-1"></i>জরুরি হটলাইন</h6>\n  <p class="small mb-2">যেকোনো তথ্য ও সংবাদ জানাতে যোগাযোগ করুন:</p>\n  <h5 class="fw-bold mb-0">০১৩৩৩৪৭৯১৯২</h5>\n</div>';
    } else if (name === 'facebook') {
        contentEl.value = '<div class="p-3 bg-light border rounded text-center">\n  <h6 class="fw-bold text-primary mb-2"><i class="bi bi-facebook me-1"></i>আমাদের ফেসবুক পেজ</h6>\n  <p class="small text-muted mb-2">প্রতিদিনের তাজা সংবাদ পেতে লাইক দিন আমাদের পেজে</p>\n  <a href="https://facebook.com/alokitogour" target="_blank" rel="noopener" class="btn btn-primary btn-sm px-3 fw-bold"><i class="bi bi-hand-thumbs-up-fill me-1"></i>Facebook Page</a>\n</div>';
    } else if (name === 'youtube') {
        contentEl.value = '<div class="ratio ratio-16x9 rounded overflow-hidden shadow-sm border">\n  <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="YouTube video" allowfullscreen></iframe>\n</div>';
    }
}

function previewBannerImg(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = document.getElementById('modal_img_preview');
            img.src = e.target.result;
            img.style.display = 'inline-block';
            document.getElementById('modal_no_img_text').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function openAddModal() {
    document.getElementById('blockModalLabel').innerHTML = '<i class="bi bi-plus-circle text-warning me-2"></i>Add New Sidebar Block';
    document.getElementById('modal_block_id').value = '0';
    document.getElementById('modal_existing_image').value = '';
    document.getElementById('modal_title').value = '';
    document.getElementById('modal_position_order').value = '10';
    document.getElementById('modal_show_title').checked = true;
    document.getElementById('modal_status').checked = true;
    document.getElementById('modal_block_type').value = 'custom_html';
    document.getElementById('modal_content').value = '';
    document.getElementById('modal_custom_image_url').value = '';
    document.getElementById('modal_image_target_url').value = '#';
    document.getElementById('modal_img_preview').style.display = 'none';
    document.getElementById('modal_img_preview').src = '';
    document.getElementById('modal_no_img_text').style.display = 'inline';

    var allCheck = document.getElementById('page_target_all');
    allCheck.checked = true;
    toggleAllPages(allCheck);

    toggleBlockTypeFields();
    document.getElementById('modal_submit_btn').innerHTML = '<i class="bi bi-check-lg me-1"></i>Save Block';
}

function openEditModal(block) {
    document.getElementById('blockModalLabel').innerHTML = '<i class="bi bi-pencil-square text-primary me-2"></i>Edit Block: ' + (block.title || '');
    document.getElementById('modal_block_id').value = block.id;
    document.getElementById('modal_title').value = block.title || '';
    document.getElementById('modal_position_order').value = block.position_order || 10;
    document.getElementById('modal_show_title').checked = (parseInt(block.show_title, 10) === 1);
    document.getElementById('modal_status').checked = (parseInt(block.status, 10) === 1);
    document.getElementById('modal_block_type').value = block.block_type || 'custom_html';
    document.getElementById('modal_content').value = block.content || '';

    var settings = {};
    try {
        settings = JSON.parse(block.settings || '{}');
    } catch(e) {}

    // Populate type-specific settings
    if (block.block_type === 'ad_banner') {
        document.getElementById('modal_ad_position').value = settings.position || 'sidebar';
    } else if (block.block_type === 'latest_popular') {
        document.getElementById('modal_tab_limit').value = settings.limit || 7;
    } else if (block.block_type === 'category_news') {
        document.getElementById('modal_category_slug').value = settings.category_slug || 'chapai-nawabganj';
        document.getElementById('modal_category_limit').value = settings.limit || 5;
    } else if (block.block_type === 'image_banner') {
        document.getElementById('modal_image_target_url').value = block.content || '#';
        var imgUrl = settings.image_url || '';
        document.getElementById('modal_existing_image').value = imgUrl;
        document.getElementById('modal_custom_image_url').value = imgUrl.startsWith('http') ? imgUrl : '';
        if (imgUrl) {
            document.getElementById('modal_img_preview').src = imgUrl;
            document.getElementById('modal_img_preview').style.display = 'inline-block';
            document.getElementById('modal_no_img_text').style.display = 'none';
        } else {
            document.getElementById('modal_img_preview').style.display = 'none';
            document.getElementById('modal_no_img_text').style.display = 'inline';
        }
    }

    // Set page targets
    var allCheck = document.getElementById('page_target_all');
    var pTarget = block.page_target || 'all';
    var tList = pTarget.split(',').map(function(s){ return s.trim(); });

    document.querySelectorAll('.page-check').forEach(function(cb) {
        cb.disabled = false;
        cb.checked = tList.indexOf(cb.value) !== -1;
    });

    if (pTarget === 'all' || tList.indexOf('all') !== -1) {
        allCheck.checked = true;
        toggleAllPages(allCheck);
    } else {
        allCheck.checked = false;
    }

    toggleBlockTypeFields();
    document.getElementById('modal_submit_btn').innerHTML = '<i class="bi bi-check-lg me-1"></i>Save Changes';

    // Show modal
    var modalEl = document.getElementById('blockModal');
    var modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modalInstance.show();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
