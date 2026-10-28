<?php
/**
 * Advertisement Manager - List View & Slot Placement
 * Alokito Gour Clone
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';

$pageTitle = 'Advertisement Banners | Alokito Gour';
$db = get_db();

// Standard Placement Positions
$positions = [
    'header_right' => [
        'label' => 'Header Right Banner',
        'size'  => '728x90 or 320x100',
        'desc'  => 'Displayed on the right side next to the main logo',
        'badge' => 'bg-primary'
    ],
    'after_lead' => [
        'label' => 'After Lead Banner',
        'size'  => '970x90 or 728x90',
        'desc'  => 'Displayed full width directly below main lead story',
        'badge' => 'bg-info text-dark'
    ],
    'sidebar' => [
        'label' => 'Sidebar Top Banner',
        'size'  => '100% width, auto height',
        'desc'  => 'Displayed at the top of the right sidebar',
        'badge' => 'bg-warning text-dark'
    ],
    'sidebar_bottom' => [
        'label' => 'Sidebar Bottom Banner',
        'size'  => '100% width, auto height',
        'desc'  => 'Displayed at the bottom of the right sidebar',
        'badge' => 'bg-secondary'
    ],
    'inside_article' => [
        'label' => 'Inside Article Banner',
        'size'  => '728x90 or responsive',
        'desc'  => 'Displayed between paragraphs in single news articles',
        'badge' => 'bg-success'
    ],
    'home_middle' => [
        'label' => 'Home Middle Banner',
        'size'  => '970x90 or 728x90',
        'desc'  => 'Displayed between home page news sections',
        'badge' => 'bg-dark'
    ],
    'footer_top' => [
        'label' => 'Footer Top Banner',
        'size'  => '970x90 or 728x90',
        'desc'  => 'Displayed full width right above the footer',
        'badge' => 'bg-indigo text-white'
    ],
    'none' => [
        'label' => 'No Placement (Draft / Saved)',
        'size'  => 'Any Size',
        'desc'  => 'Saved in system but not displayed on the site',
        'badge' => 'bg-light text-muted border'
    ],
];

// -------------------------------------------------------------
// Action: Delete Advertisement (GET)
// -------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $token = $_GET['csrf_token'] ?? '';
    if (hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $delId = (int)$_GET['id'];
        $stmt = $db->prepare("SELECT * FROM `advertisements` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => $delId]);
        $ad = $stmt->fetch();

        if ($ad) {
            // Remove local image file if exists
            if (!empty($ad['image_path']) && !str_starts_with($ad['image_path'], 'http')) {
                $filePath = dirname(__DIR__) . '/' . ltrim($ad['image_path'], '/');
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            $delStmt = $db->prepare("DELETE FROM `advertisements` WHERE `id` = :id");
            $delStmt->execute([':id' => $delId]);
            set_flash('success', 'Advertisement deleted successfully.');
        } else {
            set_flash('danger', 'Advertisement not found!');
        }
    } else {
        set_flash('danger', 'Security token is invalid!');
    }
    header('Location: ads-manager.php');
    exit;
}

// -------------------------------------------------------------
// Action: Toggle Status (GET)
// -------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $token = $_GET['csrf_token'] ?? '';
    if (hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $adId = (int)$_GET['id'];
        $stmt = $db->prepare("SELECT `id`, `position`, `status` FROM `advertisements` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => $adId]);
        $ad = $stmt->fetch();

        if ($ad) {
            $newStatus = $ad['status'] ? 0 : 1;
            
            // If turning on and position is assigned, optionally deactivate other ads on same position
            if ($newStatus === 1 && $ad['position'] !== 'none') {
                $db->prepare("UPDATE `advertisements` SET `status` = 0 WHERE `position` = :pos AND `id` != :id")
                   ->execute([':pos' => $ad['position'], ':id' => $adId]);
            }

            $update = $db->prepare("UPDATE `advertisements` SET `status` = :st WHERE `id` = :id");
            $update->execute([':st' => $newStatus, ':id' => $adId]);
            set_flash('success', 'Advertisement status updated successfully.');
        }
    }
    header('Location: ads-manager.php');
    exit;
}

// -------------------------------------------------------------
// Action: Quick Placement Change (POST)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_position') {
    verify_csrf_token();
    $adId = (int)($_POST['ad_id'] ?? 0);
    $newPos = trim($_POST['position'] ?? 'none');

    if ($adId > 0 && isset($positions[$newPos])) {
        // If assigned to a real slot, make it active and deactivate others in that slot
        if ($newPos !== 'none') {
            $db->prepare("UPDATE `advertisements` SET `status` = 0 WHERE `position` = :pos AND `id` != :id")
               ->execute([':pos' => $newPos, ':id' => $adId]);
            $db->prepare("UPDATE `advertisements` SET `position` = :pos, `status` = 1 WHERE `id` = :id")
               ->execute([':pos' => $newPos, ':id' => $adId]);
        } else {
            $db->prepare("UPDATE `advertisements` SET `position` = :pos, `status` = 0 WHERE `id` = :id")
               ->execute([':pos' => $newPos, ':id' => $adId]);
        }
        set_flash('success', 'Advertisement placement updated successfully.');
    }
    header('Location: ads-manager.php');
    exit;
}

// -------------------------------------------------------------
// Action: Add / Update Advertisement (POST)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] === 'save')) {
    verify_csrf_token();

    $adId = (int)($_POST['ad_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $position = trim($_POST['position'] ?? 'none');
    $targetUrl = trim($_POST['target_url'] ?? '#');
    $customCode = trim($_POST['custom_code'] ?? '');
    $status = isset($_POST['status']) ? 1 : 0;
    $imagePath = trim($_POST['existing_image'] ?? '');

    if (!isset($positions[$position])) {
        $position = 'none';
    }

    if (empty($title)) {
        set_flash('danger', 'Advertisement name is required.');
        header('Location: ads-manager.php');
        exit;
    }

    // Process image upload
    if (!empty($_FILES['image']['name'])) {
        $uploaded = upload_image_webp($_FILES['image'], 'assets/uploads/ads/');
        if ($uploaded) {
            $imagePath = $uploaded;
        }
    } elseif (!empty($_POST['custom_image_url'])) {
        $imagePath = trim($_POST['custom_image_url']);
    }

    if ($adId > 0) {
        // Update existing ad
        // If making active in a real slot, deactivate others on this slot
        if ($status === 1 && $position !== 'none') {
            $db->prepare("UPDATE `advertisements` SET `status` = 0 WHERE `position` = :pos AND `id` != :id")
               ->execute([':pos' => $position, ':id' => $adId]);
        }

        $stmt = $db->prepare("
            UPDATE `advertisements` 
            SET `title` = :title, 
                `position` = :pos, 
                `image_path` = :img, 
                `target_url` = :target, 
                `custom_code` = :code, 
                `status` = :status
            WHERE `id` = :id
        ");
        $stmt->execute([
            ':title'  => $title,
            ':pos'    => $position,
            ':img'    => $imagePath,
            ':target' => $targetUrl,
            ':code'   => $customCode,
            ':status' => $status,
            ':id'     => $adId,
        ]);
        set_flash('success', 'Advertisement "' . e($title) . '" updated successfully.');
    } else {
        // Insert new ad
        if ($status === 1 && $position !== 'none') {
            $db->prepare("UPDATE `advertisements` SET `status` = 0 WHERE `position` = :pos")
               ->execute([':pos' => $position]);
        }

        $stmt = $db->prepare("
            INSERT INTO `advertisements` (`title`, `position`, `image_path`, `target_url`, `custom_code`, `status`)
            VALUES (:title, :pos, :img, :target, :code, :status)
        ");
        $stmt->execute([
            ':title'  => $title,
            ':pos'    => $position,
            ':img'    => $imagePath,
            ':target' => $targetUrl,
            ':code'   => $customCode,
            ':status' => $status,
        ]);
        set_flash('success', 'New advertisement "' . e($title) . '" added successfully.');
    }

    header('Location: ads-manager.php');
    exit;
}

// -------------------------------------------------------------
// Fetch All Ads
// -------------------------------------------------------------
$filterPos = trim($_GET['filter_pos'] ?? '');
$filterStatus = isset($_GET['filter_status']) && $_GET['filter_status'] !== '' ? (int)$_GET['filter_status'] : null;
$search = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM `advertisements` WHERE 1=1";
$params = [];

if ($filterPos !== '' && isset($positions[$filterPos])) {
    $sql .= " AND `position` = :fpos";
    $params[':fpos'] = $filterPos;
}
if ($filterStatus !== null) {
    $sql .= " AND `status` = :fst";
    $params[':fst'] = $filterStatus;
}
if ($search !== '') {
    $sql .= " AND (`title` LIKE :q OR `target_url` LIKE :q)";
    $params[':q'] = '%' . $search . '%';
}
$sql .= " ORDER BY `status` DESC, `id` DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$adsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch current active ad per slot for summary
$activeSlotAds = [];
$activeSlotsStmt = $db->query("SELECT * FROM `advertisements` WHERE `status` = 1 AND `position` != 'none' ORDER BY `id` DESC");
foreach ($activeSlotsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (!isset($activeSlotAds[$row['position']])) {
        $activeSlotAds[$row['position']] = $row;
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">
            <i class="bi bi-badge-ad text-warning me-2"></i>Advertisement Banners
        </h4>
        <p class="text-muted small mb-0">Manage all advertisements, placements across slots, and client links</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-warning text-white fw-bold d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#adModal" onclick="openAddModal()">
            <i class="bi bi-plus-circle"></i>
            <span>Add New Advertisement</span>
        </button>
    </div>
</div>

<!-- Flash Message -->
<?php $flash = get_flash(); if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
        <?= $flash['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Active Placement Slots Overview -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-grid-fill text-primary"></i>
            <span>Key Website Ad Slots Status</span>
        </h6>
    </div>
    <div class="card-body p-3">
        <div class="row g-3">
            <?php 
            $keySlots = ['header_right', 'after_lead', 'sidebar', 'inside_article'];
            foreach ($keySlots as $slotKey): 
                $slotMeta = $positions[$slotKey];
                $activeAd = $activeSlotAds[$slotKey] ?? null;
            ?>
                <div class="col-md-6 col-xl-3">
                    <div class="border rounded-3 p-3 h-100 bg-light-subtle d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge <?= $slotMeta['badge'] ?> small"><?= e($slotMeta['label']) ?></span>
                                <?php if ($activeAd): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Live Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border">Empty</span>
                                <?php endif; ?>
                            </div>
                            <div class="small fw-bold text-dark mb-1 text-truncate" title="<?= e($activeAd['title'] ?? 'No ad assigned') ?>">
                                <?= e($activeAd['title'] ?? 'No ad assigned') ?>
                            </div>
                            <div class="text-muted" style="font-size: 11px;">
                                <?= e($slotMeta['desc']) ?>
                            </div>
                        </div>
                        <?php if ($activeAd && !empty($activeAd['image_path'])): ?>
                            <div class="mt-2 text-center bg-white p-1 rounded border">
                                <img src="<?= e($activeAd['image_path']) ?>" alt="Thumbnail" class="img-fluid rounded" style="max-height: 40px; object-fit: contain;">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Advertisements List Card -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3 border-bottom">
        <div class="row g-2 align-items-center justify-content-between">
            <div class="col-md-4">
                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-list-ul text-warning"></i>
                    <span>Advertisements List (Total: <?= count($adsList) ?>)</span>
                </h6>
            </div>
            
            <!-- Filters -->
            <div class="col-md-8">
                <form method="GET" action="ads-manager.php" class="d-flex flex-wrap align-items-center justify-content-md-end gap-2">
                    <!-- Placement Filter -->
                    <select name="filter_pos" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                        <option value="">All Placements</option>
                        <?php foreach ($positions as $pKey => $pMeta): ?>
                            <option value="<?= e($pKey) ?>" <?= $filterPos === $pKey ? 'selected' : '' ?>>
                                <?= e($pMeta['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Status Filter -->
                    <select name="filter_status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="1" <?= $filterStatus === 1 ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $filterStatus === 0 ? 'selected' : '' ?>>Inactive</option>
                    </select>

                    <!-- Search Input -->
                    <div class="input-group input-group-sm w-auto">
                        <input type="text" name="q" class="form-control" placeholder="Search ad / client..." value="<?= e($search) ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>

                    <?php if ($filterPos !== '' || $filterStatus !== null || $search !== ''): ?>
                        <a href="ads-manager.php" class="btn btn-light btn-sm text-danger" title="Reset Filters">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <?php if (empty($adsList)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-badge-ad fs-1 text-secondary opacity-50 mb-2"></i>
                <h6>No advertisements found</h6>
                <p class="small mb-3">Click the button above to create and place your first advertisement</p>
                <button type="button" class="btn btn-warning text-white btn-sm" data-bs-toggle="modal" data-bs-target="#adModal" onclick="openAddModal()">
                    <i class="bi bi-plus-circle me-1"></i>Add First Advertisement
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">#</th>
                            <th style="width: 140px;">Banner Preview</th>
                            <th>Ad Name & Target URL</th>
                            <th style="min-width: 220px;">Placement Slot</th>
                            <th style="width: 110px;" class="text-center">Status</th>
                            <th style="width: 130px;" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($adsList as $ad): 
                            $posKey = $ad['position'] ?? 'none';
                            $posMeta = $positions[$posKey] ?? $positions['none'];
                            $isLiveInSlot = ($ad['status'] == 1 && $posKey !== 'none');
                        ?>
                            <tr>
                                <!-- ID -->
                                <td class="text-center text-muted small fw-medium">
                                    #<?= e((string)$ad['id']) ?>
                                </td>

                                <!-- Banner Preview -->
                                <td>
                                    <?php if (!empty($ad['custom_code'])): ?>
                                        <div class="p-2 border rounded bg-light text-center small text-muted font-monospace" style="font-size:10px; max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <i class="bi bi-code-slash text-primary me-1"></i>Custom Code
                                        </div>
                                    <?php elseif (!empty($ad['image_path'])): ?>
                                        <div class="position-relative d-inline-block">
                                            <img src="<?= e($ad['image_path']) ?>" alt="Banner" class="rounded border shadow-sm" style="max-height: 48px; max-width: 130px; object-fit: contain; background: #fafafa;">
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">No Image</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Ad Name & Target URL -->
                                <td>
                                    <div class="fw-bold text-dark mb-1">
                                        <?= e($ad['title']) ?>
                                    </div>
                                    <?php if (!empty($ad['target_url']) && $ad['target_url'] !== '#'): ?>
                                        <a href="<?= e($ad['target_url']) ?>" target="_blank" rel="noopener" class="text-primary text-decoration-none small d-inline-flex align-items-center gap-1" style="font-size:12px;">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                            <span class="text-truncate" style="max-width: 240px;"><?= e($ad['target_url']) ?></span>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small" style="font-size:11px;">No target URL</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Placement Slot Selection (Fast Switch) -->
                                <td>
                                    <form method="POST" action="ads-manager.php" class="d-flex align-items-center gap-1 mb-1">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="quick_position">
                                        <input type="hidden" name="ad_id" value="<?= e((string)$ad['id']) ?>">
                                        
                                        <select name="position" class="form-select form-select-sm" onchange="this.form.submit()" style="font-size: 12.5px; border-color: #d1d5db;">
                                            <?php foreach ($positions as $pKey => $pMeta): ?>
                                                <option value="<?= e($pKey) ?>" <?= $posKey === $pKey ? 'selected' : '' ?>>
                                                    <?= e($pMeta['label']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                    <div class="d-flex align-items-center gap-1">
                                        <?php if ($isLiveInSlot): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 10px;">
                                                <i class="bi bi-check-circle me-1"></i>Visible on Site
                                            </span>
                                        <?php elseif ($posKey !== 'none'): ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 10px;">
                                                Assigned (Inactive)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary" style="font-size: 10px;">
                                                No Slot (Draft)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Status Toggle -->
                                <td class="text-center">
                                    <a href="ads-manager.php?action=toggle&id=<?= e((string)$ad['id']) ?>&csrf_token=<?= e($_SESSION['csrf_token'] ?? '') ?>" 
                                       class="btn btn-sm <?= $ad['status'] ? 'btn-success' : 'btn-outline-secondary' ?> py-1 px-2 text-decoration-none"
                                       title="Click to toggle status"
                                       style="font-size: 12px;">
                                        <?php if ($ad['status']): ?>
                                            <i class="bi bi-check-circle-fill me-1"></i>Active
                                        <?php else: ?>
                                            <i class="bi bi-dash-circle me-1"></i>Inactive
                                        <?php endif; ?>
                                    </a>
                                </td>

                                <!-- Actions -->
                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <!-- Edit Button -->
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                title="Edit Advertisement"
                                                onclick='openEditModal(<?= json_encode($ad, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- Delete Button -->
                                        <a href="ads-manager.php?action=delete&id=<?= e((string)$ad['id']) ?>&csrf_token=<?= e($_SESSION['csrf_token'] ?? '') ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           title="Delete Advertisement"
                                           onclick="return confirm('Are you sure you want to delete this advertisement?');">
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

<!-- ============================================================= -->
<!-- Add / Edit Advertisement Modal -->
<!-- ============================================================= -->
<div class="modal fade" id="adModal" tabindex="-1" aria-labelledby="adModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="ads-manager.php" enctype="multipart/form-data" id="adForm">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="ad_id" id="modal_ad_id" value="0">
                <input type="hidden" name="existing_image" id="modal_existing_image" value="">

                <div class="modal-header bg-light py-3">
                    <h5 class="modal-title fw-bold text-dark" id="adModalLabel">
                        <i class="bi bi-badge-ad text-warning me-2"></i>Add / Edit Advertisement
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- 1. Ad Title / Client Name (Required) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary small">
                                Ad Name / Client <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="title" id="modal_title" placeholder="e.g. Chapai Mattress Grand Fabrics" required>
                            <div class="form-text small" style="font-size:11px;">Enter an ad title or client name for identification.</div>
                        </div>

                        <!-- 2. Placement / Position Dropdown (Where to place) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary small">
                                Placement Slot <span class="text-danger">*</span>
                            </label>
                            <select name="position" id="modal_position" class="form-select fw-medium">
                                <?php foreach ($positions as $pKey => $pMeta): ?>
                                    <option value="<?= e($pKey) ?>">
                                        <?= e($pMeta['label']) ?> (<?= e($pMeta['size']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text small" style="font-size:11px;">You can change slot placement at any time or save as draft.</div>
                        </div>

                        <!-- 3. Target URL -->
                        <div class="col-12">
                            <label class="form-label fw-bold text-secondary small">Click Target Link (Target URL)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                                <input type="url" class="form-control" name="target_url" id="modal_target_url" placeholder="https://example.com or https://facebook.com/..." value="#">
                            </div>
                            <div class="form-text small" style="font-size:11px;">Website opened when visitors click the ad. Keep # if none.</div>
                        </div>

                        <!-- 4. Banner Image Upload -->
                        <div class="col-md-7">
                            <label class="form-label fw-bold text-secondary small">Banner Image (WebP / GIF / JPG / PNG)</label>
                            <input type="file" class="form-control" name="image" id="modal_image_input" accept="image/png,image/jpeg,image/webp,image/gif" onchange="previewSelectedImage(this)">
                            <div class="form-text small" style="font-size:11px;">Supports animated <strong>GIF</strong>, WebP, PNG, or JPG banner images.</div>
                        </div>

                        <!-- Image Preview Box -->
                        <div class="col-md-5">
                            <label class="form-label fw-bold text-secondary small">Current Banner Preview</label>
                            <div class="p-2 border rounded bg-light text-center d-flex align-items-center justify-content-center" style="min-height: 80px;">
                                <img src="" alt="Preview" id="modal_img_preview" class="img-fluid rounded border" style="max-height: 70px; display: none;">
                                <span id="modal_no_img_text" class="text-muted small fst-italic">No image selected</span>
                            </div>
                        </div>

                        <!-- 5. Direct Image URL (Fallback) -->
                        <div class="col-12">
                            <label class="form-label fw-bold text-secondary small">Or Direct Image Online URL</label>
                            <input type="url" class="form-control form-control-sm" name="custom_image_url" id="modal_custom_image_url" placeholder="https://domain.com/path/to/banner.png">
                        </div>

                        <!-- 6. Custom Script / Google AdSense -->
                        <div class="col-12">
                            <label class="form-label fw-bold text-secondary small">Custom HTML / Google AdSense Code (Optional)</label>
                            <textarea class="form-control font-monospace form-control-sm" name="custom_code" id="modal_custom_code" rows="3" placeholder="<script>...</script> or AdSense code"></textarea>
                            <div class="form-text small" style="font-size:11px;">If custom code is provided, it replaces the banner image on the website.</div>
                        </div>

                        <!-- 7. Active Status Switch -->
                        <div class="col-12">
                            <div class="form-check form-switch p-0 ps-5 mt-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="modal_status" name="status" value="1" checked style="cursor: pointer;">
                                <label class="form-check-label fw-bold text-dark small" for="modal_status" style="cursor: pointer;">
                                    Make advertisement active on site
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-white fw-bold btn-sm px-4" id="modal_submit_btn">
                        <i class="bi bi-check-lg me-1"></i>Save Advertisement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('adModalLabel').innerHTML = '<i class="bi bi-plus-circle text-warning me-2"></i>Add New Advertisement';
    document.getElementById('modal_ad_id').value = '0';
    document.getElementById('modal_existing_image').value = '';
    document.getElementById('modal_title').value = '';
    document.getElementById('modal_position').value = 'header_right';
    document.getElementById('modal_target_url').value = '#';
    document.getElementById('modal_custom_image_url').value = '';
    document.getElementById('modal_custom_code').value = '';
    document.getElementById('modal_image_input').value = '';
    document.getElementById('modal_status').checked = true;
    
    document.getElementById('modal_img_preview').style.display = 'none';
    document.getElementById('modal_img_preview').src = '';
    document.getElementById('modal_no_img_text').style.display = 'inline';
    document.getElementById('modal_submit_btn').innerHTML = '<i class="bi bi-check-lg me-1"></i>Save Advertisement';
}

function openEditModal(ad) {
    document.getElementById('adModalLabel').innerHTML = '<i class="bi bi-pencil-square text-primary me-2"></i>Edit Advertisement: ' + (ad.title || '');
    document.getElementById('modal_ad_id').value = ad.id;
    document.getElementById('modal_existing_image').value = ad.image_path || '';
    document.getElementById('modal_title').value = ad.title || '';
    document.getElementById('modal_position').value = ad.position || 'none';
    document.getElementById('modal_target_url').value = ad.target_url || '#';
    document.getElementById('modal_custom_code').value = ad.custom_code || '';
    document.getElementById('modal_image_input').value = '';
    document.getElementById('modal_status').checked = (parseInt(ad.status, 10) === 1);

    if (ad.image_path && ad.image_path.startsWith('http')) {
        document.getElementById('modal_custom_image_url').value = ad.image_path;
    } else {
        document.getElementById('modal_custom_image_url').value = '';
    }

    if (ad.image_path && ad.image_path.trim() !== '') {
        document.getElementById('modal_img_preview').src = ad.image_path;
        document.getElementById('modal_img_preview').style.display = 'inline-block';
        document.getElementById('modal_no_img_text').style.display = 'none';
    } else {
        document.getElementById('modal_img_preview').style.display = 'none';
        document.getElementById('modal_img_preview').src = '';
        document.getElementById('modal_no_img_text').style.display = 'inline';
    }

    document.getElementById('modal_submit_btn').innerHTML = '<i class="bi bi-check-lg me-1"></i>Save Changes';

    // Show modal
    var modalEl = document.getElementById('adModal');
    var modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modalInstance.show();
}

function previewSelectedImage(input) {
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
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
