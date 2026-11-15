<?php
/**
 * CMS & Legal Pages Manager
 * Manage custom pages (About Us, Contact, Privacy, Terms, Editorial Policy, etc.)
 * 100% White-Label & Commercial Ready
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';

$db = get_db();
$settings = get_settings();
$pageTitle = 'Custom Pages & Legal CMS | ' . e($settings['site_title'] ?? 'Admin Panel');

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

// Handle POST actions (Create, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $postAction = $_POST['form_action'] ?? '';

    if ($postAction === 'delete') {
        $delId = (int)($_POST['id'] ?? 0);
        if ($delId > 0) {
            $stmt = $db->prepare("DELETE FROM `pages` WHERE `id` = :id");
            $stmt->execute([':id' => $delId]);
            set_flash('success', 'Page deleted successfully.');
        }
        header('Location: pages.php');
        exit;
    }

    if ($postAction === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $content = $_POST['content'] ?? '';
        $metaTitle = trim($_POST['meta_title'] ?? '');
        $metaDesc = trim($_POST['meta_description'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['published', 'draft'], true) ? $_POST['status'] : 'published';
        $showInMenu = isset($_POST['show_in_menu']) ? 1 : 0;
        $showInFooter = isset($_POST['show_in_footer']) ? 1 : 0;
        $orderIndex = (int)($_POST['order_index'] ?? 0);

        if (empty($title)) {
            set_flash('danger', 'Page title is required.');
            header('Location: pages.php' . ($id > 0 ? "?action=edit&id={$id}" : '?action=add'));
            exit;
        }

        if (empty($slug)) {
            $slug = slugify($title);
        } else {
            $slug = slugify($slug);
        }

        try {
            if ($id > 0) {
                // Update
                $stmt = $db->prepare("
                    UPDATE `pages` SET 
                        `title` = :title,
                        `slug` = :slug,
                        `content` = :content,
                        `meta_title` = :mtitle,
                        `meta_description` = :mdesc,
                        `status` = :status,
                        `show_in_menu` = :smenu,
                        `show_in_footer` = :sfoot,
                        `order_index` = :ord
                    WHERE `id` = :id
                ");
                $stmt->execute([
                    ':title'   => $title,
                    ':slug'    => $slug,
                    ':content' => $content,
                    ':mtitle'  => $metaTitle,
                    ':mdesc'   => $metaDesc,
                    ':status'  => $status,
                    ':smenu'   => $showInMenu,
                    ':sfoot'   => $showInFooter,
                    ':ord'     => $orderIndex,
                    ':id'      => $id,
                ]);
                set_flash('success', 'Page updated successfully.');
            } else {
                // Insert
                $stmt = $db->prepare("
                    INSERT INTO `pages` (`title`, `slug`, `content`, `meta_title`, `meta_description`, `status`, `show_in_menu`, `show_in_footer`, `order_index`)
                    VALUES (:title, :slug, :content, :mtitle, :mdesc, :status, :smenu, :sfoot, :ord)
                ");
                $stmt->execute([
                    ':title'   => $title,
                    ':slug'    => $slug,
                    ':content' => $content,
                    ':mtitle'  => $metaTitle,
                    ':mdesc'   => $metaDesc,
                    ':status'  => $status,
                    ':smenu'   => $showInMenu,
                    ':sfoot'   => $showInFooter,
                    ':ord'     => $orderIndex,
                ]);
                set_flash('success', 'New page created successfully.');
            }
            header('Location: pages.php');
            exit;
        } catch (Exception $e) {
            set_flash('danger', 'Database error: ' . $e->getMessage());
            header('Location: pages.php' . ($id > 0 ? "?action=edit&id={$id}" : '?action=add'));
            exit;
        }
    }
}

// Fetch single page if editing
$editPage = null;
if ($action === 'edit' && $editId > 0) {
    $stmt = $db->prepare("SELECT * FROM `pages` WHERE `id` = :id LIMIT 1");
    $stmt->execute([':id' => $editId]);
    $editPage = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$editPage) {
        set_flash('danger', 'Page not found.');
        header('Location: pages.php');
        exit;
    }
}

// Fetch all pages
$allPages = $db->query("SELECT * FROM `pages` ORDER BY `order_index` ASC, `id` ASC")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>

<!-- Summernote WYSIWYG CDN -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs5.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs5.min.js"></script>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Custom Pages & Legal CMS</h4>
        <p class="text-muted small mb-0">Create and manage About Us, Privacy Policy, Terms, Contact Us, and editorial policies</p>
    </div>
    <?php if ($action === 'list'): ?>
        <a href="pages.php?action=add" class="btn btn-warning text-white fw-bold shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Create New Page
        </a>
    <?php else: ?>
        <a href="pages.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to All Pages
        </a>
    <?php endif; ?>
</div>

<?php $flash = get_flash(); if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- Add / Edit Page Form -->
    <div class="card border-0 shadow-sm p-4 mb-4">
        <h5 class="fw-bold text-dark border-bottom pb-3 mb-4">
            <i class="bi bi-<?= $action === 'edit' ? 'pencil-square' : 'file-earmark-plus' ?> text-warning me-2"></i>
            <?= $action === 'edit' ? 'Edit Page: ' . e($editPage['title']) : 'Create New Custom Page' ?>
        </h5>

        <form method="POST" action="pages.php">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="save">
            <input type="hidden" name="id" value="<?= (int)($editPage['id'] ?? 0) ?>">

            <div class="row g-3">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Page Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" id="page_title" value="<?= e($editPage['title'] ?? '') ?>" placeholder="e.g. আমাদের সম্পর্কে (About Us)" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">URL Slug</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted small">/page/</span>
                            <input type="text" class="form-control font-monospace" name="slug" id="page_slug" value="<?= e($editPage['slug'] ?? '') ?>" placeholder="about-us">
                        </div>
                        <small class="text-muted" style="font-size:11px;">Leave empty to auto-generate from title.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Page Content <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="summernote_editor" name="content" rows="12"><?= $editPage['content'] ?? '' ?></textarea>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="bg-light p-3 rounded-3 border mb-3">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-sliders me-1"></i>Publish Settings</h6>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Status</label>
                            <select class="form-select form-select-sm" name="status">
                                <option value="published" <?= (($editPage['status'] ?? 'published') === 'published') ? 'selected' : '' ?>>Published (Live)</option>
                                <option value="draft" <?= (($editPage['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Draft</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Display Order Index</label>
                            <input type="number" class="form-control form-control-sm" name="order_index" value="<?= (int)($editPage['order_index'] ?? 0) ?>" min="0">
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="show_in_menu" id="show_in_menu" <?= !empty($editPage['show_in_menu']) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="show_in_menu">
                                Show in Main Header Navbar
                            </label>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="show_in_footer" id="show_in_footer" <?= (!isset($editPage['show_in_footer']) || !empty($editPage['show_in_footer'])) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="show_in_footer">
                                Show in Footer Legal Links
                            </label>
                        </div>

                        <button type="submit" class="btn btn-warning text-white fw-bold w-100 py-2 shadow-sm">
                            <i class="bi bi-check-circle me-1"></i> Save Page
                        </button>
                    </div>

                    <div class="bg-light p-3 rounded-3 border mb-3">
                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-search me-1"></i>SEO Meta Settings</h6>
                        
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-secondary">Meta Title</label>
                            <input type="text" class="form-control form-control-sm" name="meta_title" value="<?= e($editPage['meta_title'] ?? '') ?>" placeholder="Search Engine Title">
                        </div>

                        <div class="mb-1">
                            <label class="form-label small fw-bold text-secondary">Meta Description</label>
                            <textarea class="form-control form-control-sm" name="meta_description" rows="3" placeholder="Brief summary for search engines"><?= e($editPage['meta_description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
    $(document).ready(function() {
        $('#summernote_editor').summernote({
            placeholder: 'Write page content here...',
            tabsize: 2,
            height: 350,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });

        // Auto slugify title if adding new page
        $('#page_title').on('input', function() {
            var currentSlug = $('#page_slug').val();
            if (!currentSlug || '<?= $action ?>' === 'add') {
                var title = $(this).val().toLowerCase()
                    .replace(/[^\w\s\u0980-\u09FF-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                $('#page_slug').val(title);
            }
        });
    });
    </script>

<?php else: ?>
    <!-- List All Pages Table -->
    <div class="card border-0 shadow-sm p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">Order</th>
                        <th>Title & Slug</th>
                        <th>Status</th>
                        <th>Menu / Footer</th>
                        <th class="text-end" style="width: 160px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allPages)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-folder2-open fs-1 d-block mb-2 text-secondary"></i>
                                No custom pages created yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allPages as $pg): ?>
                            <tr>
                                <td class="fw-bold text-muted"><?= (int)$pg['order_index'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($pg['title']) ?></div>
                                    <div class="text-muted small font-monospace">
                                        <a href="/page/<?= e($pg['slug']) ?>" target="_blank" class="text-decoration-none text-primary">
                                            /page/<?= e($pg['slug']) ?> <i class="bi bi-box-arrow-up-right small"></i>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($pg['status'] === 'published'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Published</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= !empty($pg['show_in_menu']) ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-muted border' ?> me-1">
                                        <i class="bi bi-compass me-1"></i>Navbar: <?= !empty($pg['show_in_menu']) ? 'Yes' : 'No' ?>
                                    </span>
                                    <span class="badge <?= !empty($pg['show_in_footer']) ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-light text-muted border' ?>">
                                        <i class="bi bi-layout-text-window-reverse me-1"></i>Footer: <?= !empty($pg['show_in_footer']) ? 'Yes' : 'No' ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="pages.php?action=edit&id=<?= (int)$pg['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="pages.php" onsubmit="return confirm('Are you sure you want to delete this page?');" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$pg['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
