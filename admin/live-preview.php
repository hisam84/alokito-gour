<?php
/**
 * Real-Time Live Preview Renderer for the Elementor-Style Page Builder
 * Renders the page with drag/select overlays that talk to the builder
 * via postMessage (same-origin iframe).
 * Alokito Gour News Portal
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';
require_once dirname(__DIR__) . '/includes/widget_renderer.php';

header('X-Frame-Options: SAMEORIGIN');

// Lets the widget renderer know it is inside the builder preview
define('ALOKITO_BUILDER_PREVIEW', true);

$db = get_db();
$settings = get_settings();

$slug = trim((string)($_GET['page'] ?? ($_POST['page'] ?? 'home')));
$builderMode = !empty($_POST['builder']) || !empty($_GET['builder']);

// Read the in-memory draft widgets passed from the builder
$customWidgets = null;
if (!empty($_POST['layout_data'])) {
    $decoded = json_decode((string)$_POST['layout_data'], true);
    if (is_array($decoded)) {
        $customWidgets = $decoded;
    }
}

$widgetsForPreview = $customWidgets;
if ($widgetsForPreview === null) {
    $layoutRow = $db->prepare("SELECT layout_data FROM page_layouts WHERE page_slug = ? LIMIT 1");
    $layoutRow->execute([$slug]);
    $raw = $layoutRow->fetchColumn();
    $decoded = $raw ? json_decode((string)$raw, true) : [];
    $widgetsForPreview = is_array($decoded) ? $decoded : [];
}

$visibleWidgetCount = 0;
foreach ($widgetsForPreview as $previewWidget) {
    if ((int)($previewWidget['status'] ?? 1) !== 0) {
        $visibleWidgetCount++;
    }
}

// Sample post for the news sidebar preview
$samplePost = null;
if ($slug === 'news_sidebar') {
    $stmt = $db->query("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug
        FROM `posts` p
        LEFT JOIN `categories` c ON p.category_id = c.id
        WHERE p.status = 'published'
        ORDER BY p.id DESC
        LIMIT 1
    ");
    $samplePost = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Preview - <?= e($slug) ?> | Alokito Gour</title>

    <!-- Bengali Font: SolaimanLipi -->
    <link href="https://fonts.maateen.me/solaiman-lipi/font.css" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Public Theme Stylesheet -->
    <link href="/assets/css/style.css" rel="stylesheet">

    <style>
        html { scroll-behavior: auto; }
        body { background-color: #ffffff; margin: 0; padding: 0; }

        /* Hide the site's own sticky helpers inside the editor canvas */
        .back-to-top, .floating-social { display: none !important; }

        /* ---------- Builder overlay chrome ---------- */
        [data-widget-id] { position: relative; }

        .ebx-hover {
            outline: 2px dashed rgba(243, 115, 13, .8) !important;
            outline-offset: 3px;
            border-radius: 4px;
            cursor: pointer;
        }

        .ebx-selected {
            outline: 2px solid #f3730d !important;
            outline-offset: 3px;
            border-radius: 4px;
        }

        .ebx-tb {
            position: fixed;
            z-index: 2147483000;
            display: none;
            align-items: center;
            gap: 1px;
            background: #0f172a;
            border-radius: 7px;
            padding: 3px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .35);
            font-family: 'SolaimanLipi', system-ui, -apple-system, 'Segoe UI', sans-serif;
            max-width: 92vw;
        }

        .ebx-tb .ebx-label {
            font-size: 11px;
            font-weight: 700;
            color: #cbd5e1;
            padding: 0 7px 0 6px;
            max-width: 170px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ebx-tb button {
            all: unset;
            box-sizing: border-box;
            display: grid;
            place-items: center;
            width: 26px;
            height: 26px;
            border-radius: 5px;
            cursor: pointer;
            color: #e2e8f0;
            font-size: 12px;
            line-height: 1;
            transition: background .12s ease, color .12s ease;
        }

        .ebx-tb button:hover { background: #334155; color: #fff; }
        .ebx-tb button.ebx-del:hover { background: #b91c1c; }

        .ebx-tb .ebx-grip {
            display: grid;
            place-items: center;
            width: 24px;
            height: 26px;
            color: #94a3b8;
            cursor: grab;
            border-radius: 5px;
        }

        .ebx-tb .ebx-grip:hover { background: #334155; color: #fff; }
        .ebx-tb .ebx-grip:active { cursor: grabbing; }

        .ebx-drop-line {
            position: fixed;
            display: none;
            z-index: 2147483000;
            height: 3px;
            background: #f3730d;
            border-radius: 3px;
            box-shadow: 0 0 0 3px rgba(243, 115, 13, .18);
            pointer-events: none;
        }

        .ebx-drop-line::before {
            content: "+";
            position: absolute;
            left: -9px;
            top: -8px;
            width: 17px;
            height: 17px;
            border-radius: 50%;
            background: #f3730d;
            color: #fff;
            font-family: system-ui, sans-serif;
            font-size: 13px;
            font-weight: 700;
            line-height: 17px;
            text-align: center;
        }

        body.ebx-is-dragging { cursor: grabbing !important; }
        body.ebx-is-dragging [data-widget-id] { transition: outline-color .12s ease; }

        /* Empty canvas dropzone */
        .ebx-empty {
            margin: 34px auto;
            max-width: 720px;
            border: 2px dashed #f7b27a;
            border-radius: 16px;
            background: linear-gradient(180deg, #fffaf4, #fff5e9);
            padding: 54px 24px;
            text-align: center;
            font-family: 'SolaimanLipi', sans-serif;
        }

        .ebx-empty .ebx-empty-icon {
            width: 62px;
            height: 62px;
            margin: 0 auto 14px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            background: #fff;
            color: #f3730d;
            font-size: 28px;
            box-shadow: 0 10px 24px rgba(243, 115, 13, .16);
        }

        .ebx-empty h3 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #9a3412;
            margin: 0 0 6px;
        }

        .ebx-empty p {
            font-size: .9rem;
            color: #b4530a;
            margin: 0;
        }

        .ebx-empty.ebx-drop-active {
            border-color: #f3730d;
            background: #fff2e2;
        }
    </style>
</head>
<body>

<?php
include dirname(__DIR__) . '/includes/header.php';
include dirname(__DIR__) . '/includes/navbar.php';
include dirname(__DIR__) . '/includes/ticker.php';
?>

<div class="container my-3">
    <?php if ($builderMode && $visibleWidgetCount === 0): ?>
        <div class="ebx-empty" data-ebx-empty="1">
            <div class="ebx-empty-icon"><i class="bi bi-plus-square-dotted"></i></div>
            <h3>এখানে উইজেট ড্রপ করুন</h3>
            <p>বাম দিকের লাইব্রেরি থেকে যেকোনো আইকন টেনে এনে ছেড়ে দিন, অথবা কেবল ক্লিক করুন।</p>
        </div>
    <?php elseif ($slug === 'news_sidebar'): ?>
        <!-- News Sidebar In-Context Preview -->
        <div class="row g-4">
            <div class="col-lg-8">
                <article class="bg-white p-4 rounded-3 border shadow-sm mb-4">
                    <span class="badge bg-primary-subtle text-primary mb-2"><?= e($samplePost['category_name'] ?? 'জাতীয়') ?></span>
                    <h2 class="fw-bold mb-3"><?= e($samplePost['title'] ?? 'নমুনা সংবাদ শিরোনাম') ?></h2>
                    <div class="d-flex align-items-center gap-2 text-muted small mb-3 border-bottom pb-2">
                        <i class="bi bi-person-fill text-warning"></i>
                        <span><?= e($samplePost['reporter_name'] ?? 'নিজস্ব প্রতিবেদক') ?></span>
                        <span>&bull;</span>
                        <span><?= bn_date($samplePost['created_at'] ?? 'now', true) ?></span>
                    </div>

                    <?php if (!empty($samplePost['thumbnail'])): ?>
                        <div class="mb-3 rounded overflow-hidden">
                            <img src="<?= e($samplePost['thumbnail']) ?>" alt="Post Thumbnail" class="img-fluid w-100 object-fit-cover" style="max-height: 400px;">
                        </div>
                    <?php endif; ?>

                    <div class="article-body">
                        <?= $samplePost['content'] ?? '<p>এটি একটি ডেমো নিউজ আর্টিকেল ভিউ। ডানপাশের সাইডবারে পেজ বিল্ডার দিয়ে সাজানো ব্লকগুলো লাইভ প্রদর্শিত হচ্ছে।</p>' ?>
                    </div>
                </article>
            </div>

            <div class="col-lg-4">
                <div class="p-2 border border-warning rounded bg-light mb-2 text-center small text-warning fw-bold">
                    <i class="bi bi-arrow-down-circle me-1"></i>Live Sidebar Blocks
                </div>
                <?php render_news_sidebar($samplePost, $db, $customWidgets); ?>
            </div>
        </div>
    <?php else: ?>
        <?php render_page_layout($slug, $db, ['custom_widgets' => $customWidgets]); ?>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

<!-- Bootstrap 5 Bundle JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($builderMode): ?>
<script>
/**
 * Builder canvas bridge
 * Turns the rendered page into a selectable / sortable Elementor-style canvas.
 */
(function () {
    'use strict';

    if (window.__ebxReady) { return; }
    window.__ebxReady = true;

    var post = function (message) {
        message.source = 'alokito-preview';
        try { parent.postMessage(message, '*'); } catch (e) {}
    };

    var meta = {};            // widgetId -> { title, type, hidden }
    var selectedId = null;
    var hoverId = null;
    var toolbarId = null;
    var dragId = null;

    function escapeSelector(value) {
        if (window.CSS && CSS.escape) { return CSS.escape(value); }
        return String(value).replace(/([^\w-])/g, '\\$1');
    }

    function allWidgets() {
        return Array.prototype.slice.call(document.querySelectorAll('[data-widget-id]'));
    }

    function widgetById(id) {
        if (!id) { return null; }
        return document.querySelector('[data-widget-id="' + escapeSelector(id) + '"]');
    }

    function widgetIdOf(node) {
        var el = node && node.closest ? node.closest('[data-widget-id]') : null;
        return el ? el.getAttribute('data-widget-id') : null;
    }

    /* ---------------- toolbar ---------------- */
    var tb = document.createElement('div');
    tb.className = 'ebx-tb';
    tb.innerHTML =
        '<span class="ebx-label"></span>' +
        '<button type="button" data-act="edit" title="কন্টেন্ট এডিট করুন"><i class="bi bi-sliders2"></i></button>' +
        '<button type="button" data-act="duplicate" title="ডুপ্লিকেট"><i class="bi bi-copy"></i></button>' +
        '<button type="button" data-act="toggle" title="লুকান / দেখান"><i class="bi bi-eye-slash"></i></button>' +
        '<button type="button" data-act="delete" class="ebx-del" title="ডিলিট"><i class="bi bi-trash3"></i></button>' +
        '<span class="ebx-grip" draggable="true" title="টেনে সাজান"><i class="bi bi-grip-vertical"></i></span>';
    document.body.appendChild(tb);

    var tbLabel = tb.querySelector('.ebx-label');
    var tbToggleIcon = tb.querySelector('[data-act="toggle"] i');

    /* ---------------- drop indicator ---------------- */
    var dropLine = document.createElement('div');
    dropLine.className = 'ebx-drop-line';
    document.body.appendChild(dropLine);

    function showToolbar(id) {
        var el = widgetById(id);
        if (!el) { hideToolbar(); return; }
        toolbarId = id;
        var info = meta[id] || {};
        tbLabel.textContent = info.title || 'Widget';
        if (tbToggleIcon) {
            tbToggleIcon.className = info.hidden ? 'bi bi-eye' : 'bi bi-eye-slash';
        }
        tb.style.display = 'flex';
        positionToolbar();
    }

    function positionToolbar() {
        if (!toolbarId) { return; }
        var el = widgetById(toolbarId);
        if (!el) { hideToolbar(); return; }

        var rect = el.getBoundingClientRect();
        var tbRect = tb.getBoundingClientRect();
        var top = rect.top - tbRect.height - 6;

        if (top < 4) { top = Math.min(window.innerHeight - tbRect.height - 4, rect.top + 4); }
        var left = Math.max(6, Math.min(rect.left, window.innerWidth - tbRect.width - 6));

        tb.style.top = Math.max(4, top) + 'px';
        tb.style.left = left + 'px';
    }

    function hideToolbar() {
        if (selectedId) { showToolbar(selectedId); return; }
        toolbarId = null;
        tb.style.display = 'none';
    }

    /* ---------------- selection / hover ---------------- */
    function applySelection() {
        allWidgets().forEach(function (el) {
            var id = el.getAttribute('data-widget-id');
            el.classList.toggle('ebx-selected', id === selectedId);
            el.classList.toggle('ebx-hover', id === hoverId && id !== selectedId);
        });
        if (selectedId) { showToolbar(selectedId); } else { hideToolbar(); }
    }

    function select(id) {
        selectedId = id || null;
        applySelection();
        post({ action: 'select', id: selectedId });
    }

    document.addEventListener('mouseover', function (e) {
        if (tb.contains(e.target)) { return; }
        var id = widgetIdOf(e.target);
        if (!id || id === hoverId) { return; }
        hoverId = id;
        applySelection();
        if (!selectedId || selectedId !== hoverId) { showToolbar(hoverId); }
    });

    document.addEventListener('mouseout', function (e) {
        var fromId = widgetIdOf(e.target);
        if (!fromId) { return; }
        var to = e.relatedTarget;
        if (to && (widgetIdOf(to) === fromId || tb.contains(to))) { return; }
        hoverId = null;
        applySelection();
        if (selectedId) { showToolbar(selectedId); } else { hideToolbar(); }
    });

    tb.addEventListener('mouseenter', function () {
        if (toolbarId) { hoverId = toolbarId; applySelection(); }
    });

    tb.addEventListener('mousedown', function (e) {
        if (!e.target.closest('.ebx-grip')) { e.preventDefault(); }
    });

    tb.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-act]');
        if (!btn) { return; }
        e.preventDefault();
        e.stopPropagation();

        var id = toolbarId || selectedId;
        if (!id) { return; }
        var act = btn.getAttribute('data-act');

        if (act === 'edit') { select(id); }
        else if (act === 'duplicate') { post({ action: 'duplicate', id: id }); }
        else if (act === 'toggle') { post({ action: 'toggle', id: id }); }
        else if (act === 'delete') { post({ action: 'delete', id: id }); }
    });

    /* ---------------- click to select ---------------- */
    document.addEventListener('click', function (e) {
        if (tb.contains(e.target)) { return; }

        var id = widgetIdOf(e.target);
        if (!id) { return; }

        if (e.metaKey || e.ctrlKey) { return; }   // ⌘/Ctrl + click opens links

        e.preventDefault();
        e.stopPropagation();
        select(id);
    }, true);

    // Prevent the browser from starting its own drag on images/links
    document.addEventListener('dragstart', function (e) {
        if (!tb.contains(e.target)) { e.preventDefault(); }
    }, true);

    /* ---------------- drag & drop (add + reorder) ---------------- */
    var dragPayloadKind = null;

    tb.querySelector('.ebx-grip').addEventListener('dragstart', function (e) {
        var id = toolbarId || selectedId;
        if (!id) { return; }
        dragId = id;
        dragPayloadKind = 'move';
        document.body.classList.add('ebx-is-dragging');
        if (e.dataTransfer) {
            e.dataTransfer.effectAllowed = 'move';
            try { e.dataTransfer.setData('application/x-alokito-move', id); } catch (err) {}
            try { e.dataTransfer.setData('text/plain', id); } catch (err) {}
        }
    });

    function clearDragState() {
        dragId = null;
        dragPayloadKind = null;
        document.body.classList.remove('ebx-is-dragging');
        dropLine.style.display = 'none';
        var empty = document.querySelector('[data-ebx-empty]');
        if (empty) { empty.classList.remove('ebx-drop-active'); }
    }

    document.addEventListener('dragend', clearDragState);

    function pointTarget(x, y) {
        var node = null;
        try {
            if (document.elementFromPoint) { node = document.elementFromPoint(x, y); }
        } catch (err) { node = null; }
        if (!node) { return null; }

        var el = node.closest ? node.closest('[data-widget-id]') : null;
        if (!el) { return null; }

        var rect = el.getBoundingClientRect();
        return {
            id: el.getAttribute('data-widget-id'),
            position: (y > rect.top + rect.height / 2) ? 'after' : 'before',
            rect: rect
        };
    }

    function externalDragType(e) {
        if (dragPayloadKind === 'move') { return null; }
        var type = null;
        try { type = e.dataTransfer ? e.dataTransfer.getData('text/plain') : null; } catch (err) { type = null; }
        if (!type) {
            try { type = parent.__ebDragType || null; } catch (err) { type = null; }
        }
        return type;
    }

    document.addEventListener('dragover', function (e) {
        var internal = dragPayloadKind === 'move';
        var isInternalDrag = e.dataTransfer && e.dataTransfer.types &&
            Array.prototype.indexOf.call(e.dataTransfer.types, 'application/x-alokito-move') !== -1;

        if (!internal && !isInternalDrag && !externalDragType(e)) { return; }

        e.preventDefault();
        if (e.dataTransfer) { e.dataTransfer.dropEffect = isInternalDrag || internal ? 'move' : 'copy'; }

        var target = pointTarget(e.clientX, e.clientY);
        var empty = document.querySelector('[data-ebx-empty]');

        if (target) {
            var rect = target.rect;
            var top = (target.position === 'after') ? rect.bottom : rect.top;
            dropLine.style.left = Math.max(4, rect.left) + 'px';
            dropLine.style.width = Math.max(40, rect.width) + 'px';
            dropLine.style.top = (top - 2) + 'px';
            dropLine.style.display = 'block';
            if (empty) { empty.classList.remove('ebx-drop-active'); }
        } else {
            dropLine.style.display = 'none';
            if (empty) { empty.classList.add('ebx-drop-active'); }
        }
    });

    document.addEventListener('drop', function (e) {
        var isMove = (dragPayloadKind === 'move') || (e.dataTransfer && e.dataTransfer.types &&
            Array.prototype.indexOf.call(e.dataTransfer.types, 'application/x-alokito-move') !== -1);

        var target = pointTarget(e.clientX, e.clientY);

        if (isMove) {
            e.preventDefault();
            var movingId = dragId;
            if (!movingId && e.dataTransfer) {
                try { movingId = e.dataTransfer.getData('application/x-alokito-move'); } catch (err) {}
            }
            clearDragState();

            if (movingId && target && target.id !== movingId) {
                post({ action: 'move', id: movingId, targetId: target.id, position: target.position });
            } else if (movingId && !target) {
                var last = allWidgets().filter(function (el) { return el.getAttribute('data-widget-id') !== movingId; }).pop();
                if (last) { post({ action: 'move', id: movingId, targetId: last.getAttribute('data-widget-id'), position: 'after' }); }
            }
            return;
        }

        var type = externalDragType(e);
        if (!type) { return; }

        e.preventDefault();
        clearDragState();

        if (target) {
            post({ action: 'add', type: type, targetId: target.id, position: target.position });
        } else {
            post({ action: 'add', type: type, targetId: null, position: 'after' });
        }
    });

    document.addEventListener('dragleave', function (e) {
        if (!e.relatedTarget) { dropLine.style.display = 'none'; }
    });

    /* ---------------- parent messages ---------------- */
    window.addEventListener('message', function (e) {
        var data = e.data;
        if (!data || data.source !== 'alokito-builder') { return; }

        if (data.action === 'init' && data.payload) {
            meta = {};
            (data.payload.items || []).forEach(function (item) { meta[item.id] = item; });
            selectedId = data.payload.selectedId || null;
            applySelection();
            if (data.payload.focusId) {
                var focusEl = widgetById(data.payload.focusId);
                if (focusEl) { focusEl.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
            }
        } else if (data.action === 'update-meta' && data.payload) {
            meta = {};
            (data.payload.items || []).forEach(function (item) { meta[item.id] = item; });
            if (toolbarId) { showToolbar(toolbarId); }
        } else if (data.action === 'select') {
            selectedId = data.id || null;
            applySelection();
            if (data.focus && selectedId) {
                var el = widgetById(selectedId);
                if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
            }
        } else if (data.action === 'scroll') {
            window.scrollTo(0, Number(data.y) || 0);
            positionToolbar();
        } else if (data.action === 'hello') {
            post({ action: 'ready' });
        }
    });

    /* ---------------- viewport bookkeeping ---------------- */
    window.addEventListener('scroll', function () {
        if (toolbarId && tb.style.display !== 'none') { positionToolbar(); }
        clearTimeout(window.__ebScrollTimer);
        window.__ebScrollTimer = setTimeout(function () {
            post({ action: 'scroll', y: window.scrollY || 0 });
        }, 160);
    }, { passive: true });

    window.addEventListener('resize', positionToolbar);

    /* ---------------- announce readiness ---------------- */
    post({ action: 'ready' });
    setTimeout(function () { post({ action: 'ready' }); }, 600);
})();
</script>
<?php endif; ?>

</body>
</html>
