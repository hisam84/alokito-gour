/**
 * Alokito Gour — Elementor-Style Page Builder Client
 * ---------------------------------------------------
 * • Left panel  : widget library (icon tiles) + structure navigator
 * • Center      : real-time live preview (iframe, click-to-select)
 * • Right panel : live content editor (inspector) for the selected widget
 */

(function () {
    'use strict';

    /* ==========================================================
       Widget registry
       ========================================================== */
    var WIDGETS = {
        hero_lead: {
            label: 'হিরো লিড',
            en: 'Hero Lead Stories',
            icon: 'bi-newspaper',
            group: 'news',
            accent: '#e11d48',
            soft: '#ffe4e9',
            defaultTitle: 'প্রধান ও শীর্ষ সংবাদ',
            defaultContainer: 'main',
            defaultSettings: { bottom_thumbs: 3, sub_leads: 3 }
        },
        category_block: {
            label: 'ক্যাটাগরি ব্লক',
            en: 'Category Block (1 Big + List)',
            icon: 'bi-card-heading',
            group: 'news',
            accent: '#2563eb',
            soft: '#dbeafe',
            defaultTitle: 'চাঁপাইনবাবগঞ্জ',
            defaultContainer: 'main',
            defaultSettings: { category_slug: 'chapai-nawabganj', limit: 5, show_more: true }
        },
        category_grid: {
            label: 'ক্যাটাগরি গ্রিড',
            en: 'Category Grid (4 Thumb Cards)',
            icon: 'bi-grid-fill',
            group: 'news',
            accent: '#0891b2',
            soft: '#cffafe',
            defaultTitle: 'জাতীয়',
            defaultContainer: 'main',
            defaultSettings: { category_slug: 'national', limit: 4, show_more: true }
        },
        category_split: {
            label: 'দুই কলাম',
            en: 'Dual Category (Side-by-side)',
            icon: 'bi-layout-split',
            group: 'news',
            accent: '#7c3aed',
            soft: '#ede9fe',
            defaultTitle: 'রাজনীতি ও আন্তর্জাতিক',
            defaultContainer: 'main',
            defaultSettings: {
                left_category: 'politics', left_title: 'রাজনীতি',
                right_category: 'international', right_title: 'আন্তর্জাতিক',
                limit: 4
            }
        },
        full_width_split: {
            label: 'ফুল উইডথ',
            en: 'Full-Width Double Block',
            icon: 'bi-distribute-horizontal',
            group: 'news',
            accent: '#0f766e',
            soft: '#ccfbf1',
            defaultTitle: 'সারাদেশ ও খেলাধুলা',
            defaultContainer: 'full',
            defaultSettings: {
                left_category: 'country', left_title: 'সারাদেশ',
                right_category: 'sports', right_title: 'খেলাধুলা',
                limit: 4
            }
        },
        sidebar_tabs: {
            label: 'লেটেস্ট ট্যাব',
            en: 'Latest & Popular News Tabs',
            icon: 'bi-clock-history',
            group: 'news',
            accent: '#4f46e5',
            soft: '#e0e7ff',
            defaultTitle: 'সর্বশেষ ও জনপ্রিয়',
            defaultContainer: 'main',
            defaultSettings: { limit: 7 }
        },
        district_filter: {
            label: 'এলাকার খবর',
            en: 'Local News (District Filter)',
            icon: 'bi-geo-alt-fill',
            group: 'news',
            accent: '#15803d',
            soft: '#dcfce7',
            defaultTitle: 'আমার এলাকার খবর',
            defaultContainer: 'main',
            defaultSettings: {}
        },
        multimedia_gallery: {
            label: 'গ্যালারি',
            en: 'Photo & Video Gallery',
            icon: 'bi-camera-fill',
            group: 'media',
            accent: '#db2777',
            soft: '#fce7f3',
            defaultTitle: 'ছবি ও ভিডিও গ্যালারি',
            defaultContainer: 'full',
            defaultSettings: { title: 'ছবি ও ভিডিও গ্যালারি', limit: 4 }
        },
        ad_banner: {
            label: 'বিজ্ঞাপন',
            en: 'Advertisement Banner',
            icon: 'bi-badge-ad-fill',
            group: 'utility',
            accent: '#d97706',
            soft: '#fef3c7',
            defaultTitle: 'বিজ্ঞাপন ব্যানার',
            defaultContainer: 'main',
            defaultSettings: { position: 'after_lead', custom_code: '' }
        },
        custom_html: {
            label: 'কাস্টম HTML',
            en: 'Custom HTML / Embed',
            icon: 'bi-code-slash',
            group: 'utility',
            accent: '#475569',
            soft: '#e2e8f0',
            defaultTitle: 'কাস্টম নোটিশ / এমবেড',
            defaultContainer: 'main',
            defaultSettings: { content: '<div class="p-3 bg-light border rounded text-center">কাস্টম নোটিশ বা এমবেড কোড এখানে বসান…</div>' }
        }
    };

    var GROUPS = [
        { key: 'news', label: 'সংবাদ ও ক্যাটাগরি' },
        { key: 'media', label: 'মিডিয়া' },
        { key: 'utility', label: 'ইউটিলিটি ও বিজ্ঞাপন' }
    ];

    var AD_POSITIONS = [
        { value: 'after_lead', label: 'লিডের পর (After Lead)' },
        { value: 'header_right', label: 'হেডার ডান পাশ (Header Right)' },
        { value: 'sidebar', label: 'সাইডবার টপ' },
        { value: 'sidebar_bottom', label: 'সাইডবার বটম' },
        { value: 'inside_article', label: 'আর্টিকেলের ভেতরে' }
    ];

    var CFG = window.ALOKITO_BUILDER || {};
    var CATEGORIES = Array.isArray(CFG.categories) ? CFG.categories : [];

    var state = {
        widgets: [],
        selectedId: null,
        device: 'desktop',
        mode: 'split',
        dirty: false,
        history: [],
        future: [],
        scrollY: 0,
        saveInFlight: false,
        previewTimer: null,
        loading: false,
        pendingFocusId: null
    };

    var dom = {};

    /* ==========================================================
       Helpers
       ========================================================== */
    function $(sel, ctx) { return (ctx || document).querySelector(sel); }
    function $$(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

    function esc(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function clone(obj) { return JSON.parse(JSON.stringify(obj)); }

    function newId(type) {
        return 'sec_' + type + '_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 5);
    }

    function metaOf(widget) {
        return WIDGETS[widget.type] || {
            label: widget.type, en: widget.type, icon: 'bi-puzzle', group: 'utility',
            accent: '#475569', soft: '#e2e8f0', defaultTitle: widget.type,
            defaultContainer: 'main', defaultSettings: {}
        };
    }

    function catName(slug) {
        for (var i = 0; i < CATEGORIES.length; i++) {
            if (CATEGORIES[i].slug === slug) return CATEGORIES[i].name;
        }
        return slug || '—';
    }

    function adLabel(key) {
        for (var i = 0; i < AD_POSITIONS.length; i++) {
            if (AD_POSITIONS[i].value === key) return AD_POSITIONS[i].label;
        }
        return key || '—';
    }

    function selectedWidget() {
        for (var i = 0; i < state.widgets.length; i++) {
            if (state.widgets[i].id === state.selectedId) return state.widgets[i];
        }
        return null;
    }

    function indexOfId(id) {
        for (var i = 0; i < state.widgets.length; i++) {
            if (state.widgets[i].id === id) return i;
        }
        return -1;
    }

    function normalizeWidget(raw) {
        var meta = WIDGETS[raw.type] || metaOf(raw);
        var settings = {};
        var defaults = meta.defaultSettings || {};
        var saved = (raw.settings && typeof raw.settings === 'object') ? raw.settings : {};

        Object.keys(defaults).forEach(function (k) { settings[k] = defaults[k]; });
        Object.keys(saved).forEach(function (k) { settings[k] = saved[k]; });

        return {
            id: raw.id || newId(raw.type),
            type: raw.type,
            title: typeof raw.title === 'string' && raw.title.length ? raw.title : meta.defaultTitle,
            container: (raw.container === 'full') ? 'full' : (raw.container === 'main' ? 'main' : meta.defaultContainer),
            status: (raw.status === 0 || raw.status === '0') ? 0 : 1,
            settings: settings
        };
    }

    function describe(w) {
        var s = w.settings || {};
        var prefix = (w.container === 'full') ? 'ফুল উইডথ · ' : '';
        var body;

        switch (w.type) {
            case 'category_block':
            case 'category_grid':
                body = catName(s.category_slug) + ' · ' + (s.limit || 4) + ' পোস্ট';
                break;
            case 'category_split':
            case 'full_width_split':
                body = (s.left_title || catName(s.left_category)) + ' ↔ ' + (s.right_title || catName(s.right_category));
                break;
            case 'ad_banner':
                body = 'স্লট: ' + adLabel(s.position);
                break;
            case 'hero_lead':
                body = 'লিড + ' + (s.bottom_thumbs === undefined ? 3 : s.bottom_thumbs) + ' থাম্ব + ' + (s.sub_leads === undefined ? 3 : s.sub_leads) + ' সাব-লিড';
                break;
            case 'multimedia_gallery':
                body = (s.limit || 4) + ' আইটেম';
                break;
            case 'custom_html':
                body = 'কাস্টম HTML';
                break;
            case 'sidebar_tabs':
                body = (s.limit || 7) + ' পোস্ট × ২ ট্যাব';
                break;
            case 'district_filter':
                body = 'বিভাগ · জেলা · উপজেলা';
                break;
            default:
                body = metaOf(w).en;
        }
        return prefix + body;
    }

    /* ==========================================================
       Boot
       ========================================================== */
    var booted = false;

    document.addEventListener('DOMContentLoaded', function () {
        if (booted) { return; }
        booted = true;

        cacheDom();
        state.widgets = (CFG.widgets || []).map(normalizeWidget);

        buildLibrary();
        bindEvents();
        renderNavigator();
        renderInspector();
        updateCounters();
        updateHistoryButtons();
        initSortable();

        // Give the canvas more room on smaller desktops by folding the
        // (icon) admin sidebar — this is not persisted to other admin pages.
        if (window.innerWidth < 1600) {
            document.documentElement.classList.add('sidebar-collapsed');
            document.body.classList.add('sidebar-collapsed');
        }

        fitPreview();
        setTimeout(fitPreview, 320);
        window.addEventListener('message', onPreviewMessage);

        // If the preview iframe already finished loading before the message
        // listener was attached, nudge it to re-handshake.
        setTimeout(function () { postToPreview({ action: 'hello' }); }, 700);
    });

    function cacheDom() {
        dom.app = $('#ebApp');
        dom.library = $('#ebLibrary');
        dom.search = $('#ebWidgetSearch');
        dom.navList = $('#ebNavList');
        dom.navEmpty = $('#ebNavEmpty');
        dom.navCount = $('#ebNavCount');
        dom.libraryCount = $('#ebLibraryCount');
        dom.widgetCount = $('#ebWidgetCount');
        dom.inspector = $('#ebInspector');
        dom.inspectorContent = $('#ebInspectorContent');
        dom.frame = $('#ebPreviewFrame');
        dom.frameShell = $('#ebFrameShell');
        dom.frameWrap = $('.eb-frame-wrap');
        dom.syncChip = $('#ebSyncChip');
        dom.saveBtn = $('#ebSaveBtn');
        dom.saveState = $('#ebSaveState');
        dom.toast = $('#ebToast');
        dom.toastText = $('#ebToastText');
        dom.undo = $('#ebUndo');
        dom.redo = $('#ebRedo');
    }

    /* ==========================================================
       Widget library (left panel, icon tiles)
       ========================================================== */
    function buildLibrary() {
        var html = '';
        var total = 0;

        GROUPS.forEach(function (group) {
            var types = Object.keys(WIDGETS).filter(function (t) { return WIDGETS[t].group === group.key; });
            if (!types.length) return;

            html += '<div class="eb-group-title">' + esc(group.label) + '</div>';
            html += '<div class="eb-widget-grid">';

            types.forEach(function (type) {
                var meta = WIDGETS[type];
                total++;
                html += '<button type="button" class="eb-tile" draggable="true" data-type="' + esc(type) + '"' +
                    ' data-search="' + esc((meta.label + ' ' + meta.en + ' ' + type).toLowerCase()) + '"' +
                    ' style="--tile-accent:' + meta.accent + ';--tile-soft:' + meta.soft + '"' +
                    ' title="' + esc(meta.en) + '">' +
                    '<span class="eb-tile-icon"><i class="bi ' + meta.icon + '"></i></span>' +
                    '<span class="eb-tile-label">' + esc(meta.label) + '</span>' +
                    '</button>';
            });

            html += '</div>';
        });

        dom.library.innerHTML = html;
        if (dom.libraryCount) dom.libraryCount.textContent = String(total);
    }

    function bindTileDrag() {
        // Event delegation is bound once in bindEvents(); dragstart needs capture
        dom.library.addEventListener('dragstart', function (e) {
            var tile = e.target.closest ? e.target.closest('.eb-tile') : null;
            if (!tile) return;
            var type = tile.getAttribute('data-type');
            window.__ebDragType = type;
            if (e.dataTransfer) {
                e.dataTransfer.effectAllowed = 'copy';
                try { e.dataTransfer.setData('text/plain', type); } catch (err) {}
                try { e.dataTransfer.setData('application/x-alokito-widget', type); } catch (err) {}
            }
            markPreviewDropHint(true);
        });

        dom.library.addEventListener('dragend', function () {
            window.__ebDragType = null;
            markPreviewDropHint(false);
        });
    }

    function markPreviewDropHint(on) {
        var shell = dom.frameShell;
        if (!shell) return;
        shell.classList.toggle('is-dragging-new', !!on);
    }

    function filterLibrary(term) {
        term = String(term || '').trim().toLowerCase();
        var tiles = $$('.eb-tile', dom.library);
        var visible = 0;

        tiles.forEach(function (tile) {
            var haystack = tile.getAttribute('data-search') || '';
            var match = !term || haystack.indexOf(term) !== -1;
            tile.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        $$('.eb-group-title', dom.library).forEach(function (title) {
            var grid = title.nextElementSibling;
            var any = false;
            if (grid) {
                any = $$('.eb-tile', grid).some(function (t) { return t.style.display !== 'none'; });
            }
            title.style.display = any ? '' : 'none';
            if (grid) grid.style.display = any ? '' : 'none';
        });

        var empty = $('#ebLibraryEmpty');
        if (!visible) {
            if (!empty) {
                empty = document.createElement('div');
                empty.id = 'ebLibraryEmpty';
                empty.className = 'eb-library-empty';
                empty.textContent = 'কোনো উইজেট খুঁজে পাওয়া যায়নি।';
                dom.library.appendChild(empty);
            }
            empty.style.display = '';
        } else if (empty) {
            empty.style.display = 'none';
        }
    }

    /* ==========================================================
       Navigator (structure list)
       ========================================================== */
    function renderNavigator() {
        if (!dom.navList) return;

        dom.navCount.textContent = String(state.widgets.length);

        if (!state.widgets.length) {
            dom.navList.innerHTML = '';
            dom.navEmpty.hidden = false;
            return;
        }

        dom.navEmpty.hidden = true;
        dom.navList.innerHTML = state.widgets.map(function (w) {
            var meta = metaOf(w);
            var selected = (w.id === state.selectedId);
            var hidden = w.status === 0;

            return '<li class="eb-nav-item' + (selected ? ' is-selected' : '') + (hidden ? ' is-off' : '') + '" data-id="' + esc(w.id) + '">' +
                '<span class="eb-nav-grip" title="টেনে সাজান"><i class="bi bi-grip-vertical"></i></span>' +
                '<span class="eb-nav-icon" style="' + (selected ? 'background:' + meta.accent + ';color:#fff' : '') + '">' +
                    '<i class="bi ' + meta.icon + '"></i></span>' +
                '<span class="eb-nav-text">' +
                    '<span class="eb-nav-title">' + esc(w.title || meta.label) + '</span>' +
                    '<span class="eb-nav-meta">' + esc(describe(w)) + '</span>' +
                '</span>' +
                '<span class="eb-nav-actions">' +
                    '<button type="button" data-act="toggle" title="' + (hidden ? 'দেখান' : 'লুকান') + '">' +
                        '<i class="bi ' + (hidden ? 'bi-eye-slash' : 'bi-eye') + '"></i></button>' +
                    '<button type="button" data-act="duplicate" title="ডুপ্লিকেট"><i class="bi bi-copy"></i></button>' +
                    '<button type="button" class="eb-danger" data-act="delete" title="ডিলিট"><i class="bi bi-trash3"></i></button>' +
                '</span>' +
                '</li>';
        }).join('');
    }

    function initSortable() {
        if (typeof Sortable === 'undefined' || !dom.navList) return;

        new Sortable(dom.navList, {
            handle: '.eb-nav-grip',
            animation: 160,
            ghostClass: 'sortable-ghost',
            onStart: function () { pushHistory(); },
            onEnd: function () {
                var ids = $$('.eb-nav-item', dom.navList).map(function (item) { return item.getAttribute('data-id'); });
                state.widgets.sort(function (a, b) { return ids.indexOf(a.id) - ids.indexOf(b.id); });
                renderNavigator();
                markDirty();
                schedulePreview(false);
            }
        });
    }

    /* ==========================================================
       Inspector (right panel)
       ========================================================== */
    function renderInspector() {
        var w = selectedWidget();

        if (!w) {
            dom.inspectorContent.innerHTML = emptyInspectorHtml();
            dom.app.classList.remove('eb-inspector-open');
            return;
        }

        var meta = metaOf(w);

        dom.inspectorContent.innerHTML =
            '<div class="eb-insp-head">' +
                '<span class="eb-insp-icon" style="background:' + meta.accent + '"><i class="bi ' + meta.icon + '"></i></span>' +
                '<div class="eb-insp-titles">' +
                    '<div class="eb-insp-title" id="ebInspTitle">' + esc(w.title || meta.label) + '</div>' +
                    '<div class="eb-insp-sub">' + esc(meta.en) + '</div>' +
                '</div>' +
                '<button type="button" class="eb-insp-close" data-insp="close" title="সিলেকশন বাতিল করুন"><i class="bi bi-x-lg"></i></button>' +
            '</div>' +
            '<div class="eb-insp-body" id="ebInspectorBody">' + inspectorBody(w, meta) + '</div>' +
            '<div class="eb-insp-foot">' +
                '<button type="button" class="eb-btn" data-insp="duplicate"><i class="bi bi-copy"></i> ডুপ্লিকেট</button>' +
                '<button type="button" class="eb-btn eb-danger" data-insp="delete"><i class="bi bi-trash3"></i> ডিলিট</button>' +
            '</div>';

        if (isOverlayInspector()) dom.app.classList.add('eb-inspector-open');
    }

    function isOverlayInspector() {
        return window.innerWidth <= 1100;
    }

    function emptyInspectorHtml() {
        return '<div class="eb-insp-empty">' +
            '<div class="eb-empty-icon"><i class="bi bi-sliders2"></i></div>' +
            '<h6>কন্টেন্ট এডিট করুন</h6>' +
            '<p>প্রিভিউতে যেকোনো সেকশনে ক্লিক করুন — তার সব কনটেন্ট ও সেটিংস এখানে খুলে যাবে।</p>' +
            '<div class="eb-tips">' +
                '<div class="eb-tip"><i class="bi bi-hand-index-thumb-fill"></i><span>বাম দিকের আইকনে ক্লিক করে নতুন সেকশন যোগ করুন</span></div>' +
                '<div class="eb-tip"><i class="bi bi-arrows-move"></i><span>স্ট্রাকচার ট্যাব থেকে টেনে সেকশনের ক্রম বদলান</span></div>' +
                '<div class="eb-tip"><i class="bi bi-lightning-charge-fill"></i><span>প্রতিটি পরিবর্তন রিয়েল-টাইম প্রিভিউতে সাথে সাথে দেখা যায়</span></div>' +
            '</div>' +
            '</div>';
    }

    function groupHtml(title, icon, inner) {
        return '<div class="eb-field-group">' +
            '<div class="eb-field-group-title"><i class="bi ' + icon + '"></i>' + esc(title) + '</div>' +
            inner + '</div>';
    }

    function fieldHtml(label, control, hint) {
        return '<div class="eb-field">' +
            '<div class="eb-field-label"><span>' + esc(label) + '</span>' +
            (hint ? '<span class="hint">' + esc(hint) + '</span>' : '') + '</div>' +
            control + '</div>';
    }

    function inputHtml(scope, key, value, opts) {
        opts = opts || {};
        var type = opts.type || 'text';
        return '<input class="eb-input" type="' + type + '" data-scope="' + scope + '" data-key="' + esc(key) + '"' +
            (opts.int ? ' data-int="1"' : '') +
            ' value="' + esc(value === undefined || value === null ? '' : value) + '"' +
            ' placeholder="' + esc(opts.placeholder || '') + '"' +
            (opts.min !== undefined ? ' min="' + opts.min + '"' : '') +
            (opts.max !== undefined ? ' max="' + opts.max + '"' : '') +
            '>';
    }

    function numberHtml(scope, key, value, min, max) {
        return inputHtml(scope, key, value, { type: 'number', int: true, min: min, max: max });
    }

    function selectHtml(scope, key, value, options) {
        var html = '<select class="eb-select" data-scope="' + scope + '" data-key="' + esc(key) + '">';
        options.forEach(function (opt) {
            html += '<option value="' + esc(opt.value) + '"' + (String(opt.value) === String(value) ? ' selected' : '') + '>' +
                esc(opt.label) + '</option>';
        });
        return html + '</select>';
    }

    function textareaHtml(scope, key, value, rows) {
        return '<textarea class="eb-textarea" rows="' + (rows || 5) + '" data-scope="' + scope + '" data-key="' + esc(key) + '">' +
            esc(value || '') + '</textarea>';
    }

    function switchHtml(scope, key, checked, label) {
        return '<label class="eb-switch"><span>' + esc(label) + '</span>' +
            '<input type="checkbox" data-scope="' + scope + '" data-key="' + esc(key) + '"' + (checked ? ' checked' : '') + '></label>';
    }

    function categoryOptions(current) {
        var options = CATEGORIES.map(function (c) {
            return { value: c.slug, label: c.name + ' (' + c.slug + ')' };
        });
        if (!options.length) options.push({ value: current || 'national', label: current || 'national' });
        return options;
    }

    function inspectorBody(w, meta) {
        var s = w.settings || {};
        var html = '';

        /* ---- content group ---- */
        var content = fieldHtml('সেকশন শিরোনাম', inputHtml('root', 'title', w.title, { placeholder: 'শিরোনাম লিখুন' }), meta.en);

        switch (w.type) {
            case 'hero_lead':
                content += '<div class="eb-row">' +
                    '<div>' + fieldHtml('বটম থাম্বনেইল', numberHtml('settings', 'bottom_thumbs', s.bottom_thumbs === undefined ? 3 : s.bottom_thumbs, 0, 6)) + '</div>' +
                    '<div>' + fieldHtml('সাব-লিড সংখ্যা', numberHtml('settings', 'sub_leads', s.sub_leads === undefined ? 3 : s.sub_leads, 1, 6)) + '</div>' +
                    '</div>';
                break;

            case 'category_block':
            case 'category_grid':
                content += fieldHtml('ক্যাটাগরি', selectHtml('settings', 'category_slug', s.category_slug, categoryOptions(s.category_slug)));
                content += fieldHtml('পোস্ট সংখ্যা', numberHtml('settings', 'limit', s.limit || 4, 1, 15));
                content += switchHtml('settings', 'show_more', s.show_more !== false, '“আরও সংবাদ” লিংক দেখান');
                break;

            case 'category_split':
            case 'full_width_split':
                content += fieldHtml('বাম কলাম ক্যাটাগরি', selectHtml('settings', 'left_category', s.left_category, categoryOptions(s.left_category)));
                content += fieldHtml('বাম কলাম শিরোনাম', inputHtml('settings', 'left_title', s.left_title, { placeholder: 'যেমন: রাজনীতি' }));
                content += fieldHtml('ডান কলাম ক্যাটাগরি', selectHtml('settings', 'right_category', s.right_category, categoryOptions(s.right_category)));
                content += fieldHtml('ডান কলাম শিরোনাম', inputHtml('settings', 'right_title', s.right_title, { placeholder: 'যেমন: আন্তর্জাতিক' }));
                content += fieldHtml('প্রতি কলামে পোস্ট', numberHtml('settings', 'limit', s.limit || 4, 1, 10));
                break;

            case 'ad_banner':
                content += fieldHtml('বিজ্ঞাপন স্লট', selectHtml('settings', 'position', s.position || 'after_lead', AD_POSITIONS));
                content += fieldHtml('কাস্টম অ্যাড কোড', textareaHtml('settings', 'custom_code', s.custom_code, 4), 'ঐচ্ছিক');
                content += '<div class="eb-tip" style="margin-top:2px"><i class="bi bi-info-circle"></i>' +
                    '<span>ব্যানার আপলোড করতে <a href="ads-manager.php" target="_blank">বিজ্ঞাপন ম্যানেজার</a> ব্যবহার করুন।</span></div>';
                break;

            case 'custom_html':
                content += fieldHtml('HTML / এমবেড কোড', textareaHtml('settings', 'content', s.content, 8));
                break;

            case 'multimedia_gallery':
                content += fieldHtml('গ্যালারি আইটেম সংখ্যা', numberHtml('settings', 'limit', s.limit || 4, 2, 8));
                break;

            case 'sidebar_tabs':
                content += fieldHtml('প্রতি ট্যাবে পোস্ট', numberHtml('settings', 'limit', s.limit || 7, 3, 15));
                break;

            case 'district_filter':
                content += '<div class="eb-tip"><i class="bi bi-geo-alt"></i>' +
                    '<span>এই উইজেটে বিভাগ, জেলা ও উপজেলা নির্বাচনের ফিল্টার বার দেখানো হয়। কোনো অতিরিক্ত সেটিংস প্রয়োজন নেই।</span></div>';
                break;
        }

        html += groupHtml('কন্টেন্ট', 'bi-text-paragraph', content);

        /* ---- layout group ---- */
        var layout = fieldHtml('লেআউট পজিশন', selectHtml('root', 'container', w.container, [
            { value: 'main', label: 'মেইন কলাম (৮ কলাম + সাইডবার)' },
            { value: 'full', label: 'ফুল উইডথ (১২ কলাম)' }
        ]));
        layout += switchHtml('root', 'status', w.status !== 0, 'প্রিভিউ ও সাইটে দেখান');
        html += groupHtml('লেআউট ও ভিজিবিলিটি', 'bi-aspect-ratio', layout);

        return html;
    }

    /* ==========================================================
       Model mutations
       ========================================================== */
    function addWidget(type, targetId, position) {
        var meta = WIDGETS[type];
        if (!meta) return;

        pushHistory();

        var widget = normalizeWidget({
            id: newId(type),
            type: type,
            title: meta.defaultTitle,
            container: meta.defaultContainer,
            status: 1,
            settings: clone(meta.defaultSettings || {})
        });

        var insertAt = state.widgets.length;
        if (targetId) {
            var idx = indexOfId(targetId);
            if (idx !== -1) insertAt = idx + (position === 'after' ? 1 : 0);
        } else if (state.selectedId) {
            var selIdx = indexOfId(state.selectedId);
            if (selIdx !== -1) insertAt = selIdx + 1;
        }

        state.widgets.splice(insertAt, 0, widget);
        state.selectedId = widget.id;
        state.pendingFocusId = widget.id;
        dom.app.classList.remove('eb-library-open');

        renderNavigator();
        renderInspector();
        updateCounters();
        markDirty();
        schedulePreview(true);
        toast('“' + meta.label + '” সেকশন যোগ হয়েছে');
        postToPreview({ action: 'select', id: widget.id, focus: true });
    }

    function selectWidget(id, opts) {
        opts = opts || {};
        if (!id) return;

        var changed = state.selectedId !== id;
        state.selectedId = id;

        renderNavigator();
        renderInspector();

        if (changed || opts.force) {
            postToPreview({ action: 'select', id: id, focus: !!opts.focus });
        }
    }

    function deselect() {
        state.selectedId = null;
        renderNavigator();
        renderInspector();
        postToPreview({ action: 'select', id: null });
    }

    function duplicateWidget(id) {
        var idx = indexOfId(id);
        if (idx === -1) return;

        pushHistory();
        var copy = clone(state.widgets[idx]);
        copy.id = newId(copy.type);
        copy.title = copy.title + ' (কপি)';
        state.widgets.splice(idx + 1, 0, copy);
        state.selectedId = copy.id;

        renderNavigator();
        renderInspector();
        updateCounters();
        markDirty();
        schedulePreview(true);
        toast('সেকশন ডুপ্লিকেট হয়েছে');
    }

    function removeWidget(id) {
        var idx = indexOfId(id);
        if (idx === -1) return;

        pushHistory();
        var removed = state.widgets.splice(idx, 1)[0];
        if (state.selectedId === id) state.selectedId = null;

        renderNavigator();
        renderInspector();
        updateCounters();
        markDirty();
        schedulePreview(true);
        toast('“' + (removed.title || metaOf(removed).label) + '” ডিলিট হয়েছে');
    }

    function toggleWidget(id) {
        var w = state.widgets[indexOfId(id)];
        if (!w) return;
        pushHistory();
        w.status = (w.status === 0) ? 1 : 0;
        renderNavigator();
        renderInspector();
        markDirty();
        schedulePreview(true);
    }

    function moveWidgetTo(id, targetId, position) {
        var from = indexOfId(id);
        if (from === -1) return;
        if (id === targetId) return;

        pushHistory();

        var widget = state.widgets.splice(from, 1)[0];
        var to = indexOfId(targetId);
        if (to === -1) {
            to = state.widgets.length;
        } else if (position === 'after') {
            to = to + 1;
        }
        state.widgets.splice(to, 0, widget);

        renderNavigator();
        markDirty();
        schedulePreview(true);
        toast('সেকশনের ক্রম পরিবর্তন হয়েছে');
    }

    /* ==========================================================
       Dirty state, history, toasts
       ========================================================== */
    function markDirty() {
        state.dirty = true;
        dom.saveBtn.classList.add('is-dirty');
        dom.saveState.classList.remove('is-busy');
        dom.saveState.classList.add('is-dirty');
        $('.txt', dom.saveState).textContent = 'অসংরক্ষিত পরিবর্তন';
    }

    function markClean(message) {
        state.dirty = false;
        dom.saveBtn.classList.remove('is-dirty');
        dom.saveState.classList.remove('is-dirty', 'is-busy');
        $('.txt', dom.saveState).textContent = message || 'সব পরিবর্তন সংরক্ষিত';
    }

    function pushHistory() {
        state.history.push(JSON.stringify({ widgets: state.widgets, selectedId: state.selectedId }));
        if (state.history.length > 60) state.history.shift();
        state.future.length = 0;
        updateHistoryButtons();
    }

    function updateHistoryButtons() {
        dom.undo.disabled = !state.history.length;
        dom.redo.disabled = !state.future.length;
    }

    function undo() {
        if (!state.history.length) return;
        state.future.push(JSON.stringify({ widgets: state.widgets, selectedId: state.selectedId }));
        restore(JSON.parse(state.history.pop()));
        updateHistoryButtons();
        toast('আগের অবস্থায় ফেরা হয়েছে');
    }

    function redo() {
        if (!state.future.length) return;
        state.history.push(JSON.stringify({ widgets: state.widgets, selectedId: state.selectedId }));
        restore(JSON.parse(state.future.pop()));
        updateHistoryButtons();
        toast('পরের অবস্থায় ফেরা হয়েছে');
    }

    function restore(snapshot) {
        state.widgets = snapshot.widgets.map(normalizeWidget);
        state.selectedId = snapshot.selectedId && indexOfId(snapshot.selectedId) !== -1 ? snapshot.selectedId : null;
        renderNavigator();
        renderInspector();
        updateCounters();
        markDirty();
        schedulePreview(true);
    }

    var toastTimer = null;
    function toast(message, isError) {
        if (!dom.toast) return;
        dom.toastText.textContent = message;
        dom.toast.classList.toggle('is-error', !!isError);
        dom.toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { dom.toast.classList.remove('show'); }, 2600);
    }

    /* ==========================================================
       Live preview sync
       ========================================================== */
    function previewPayload(extra) {
        var payload = {
            items: state.widgets.map(function (w) {
                return {
                    id: w.id,
                    title: w.title || metaOf(w).label,
                    type: w.type,
                    hidden: w.status === 0
                };
            }),
            selectedId: state.selectedId
        };
        if (extra) Object.keys(extra).forEach(function (k) { payload[k] = extra[k]; });
        return payload;
    }

    function postToPreview(message) {
        if (!dom.frame || !dom.frame.contentWindow) return;
        message.source = 'alokito-builder';
        try { dom.frame.contentWindow.postMessage(message, '*'); } catch (err) {}
    }

    function setSync(status) {
        if (!dom.syncChip) return;
        dom.syncChip.classList.remove('is-syncing', 'is-error');

        if (status === 'syncing') {
            dom.syncChip.classList.add('is-syncing');
            $('.txt', dom.syncChip).textContent = 'প্রিভিউ আপডেট হচ্ছে…';
        } else if (status === 'error') {
            dom.syncChip.classList.add('is-error');
            $('.txt', dom.syncChip).textContent = 'প্রিভিউ লোড হয়নি';
        } else {
            $('.txt', dom.syncChip).textContent = 'লাইভ প্রিভিউ';
        }
    }

    function setFrameLoading(on) {
        state.loading = !!on;
        if (dom.frameShell) dom.frameShell.classList.toggle('is-loading', !!on);
    }

    function schedulePreview(immediate) {
        clearTimeout(state.previewTimer);
        setSync('syncing');
        state.previewTimer = setTimeout(refreshPreview, immediate ? 20 : 380);
    }

    function getPreviewScroll() {
        try {
            return dom.frame.contentWindow ? (dom.frame.contentWindow.scrollY || 0) : state.scrollY;
        } catch (err) {
            return state.scrollY;
        }
    }

    var previewRequestId = 0;

    function refreshPreview() {
        if (!dom.frame) return;

        var requestId = ++previewRequestId;
        var scrollY = getPreviewScroll();
        var formData = new FormData();
        formData.append('page', CFG.pageSlug || 'home');
        formData.append('builder', '1');
        formData.append('layout_data', JSON.stringify(state.widgets));

        setFrameLoading(true);

        fetch(CFG.previewUrl || 'live-preview.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
            .then(function (res) { return res.text(); })
            .then(function (html) {
                if (requestId !== previewRequestId) return;   // stale response

                dom.frame.onload = function () {
                    dom.frame.onload = null;
                    postToPreview({ action: 'init', payload: previewPayload({ focusId: state.pendingFocusId }) });
                    state.pendingFocusId = null;
                    postToPreview({ action: 'scroll', y: scrollY });
                    setFrameLoading(false);
                    setSync('ready');
                };

                dom.frame.srcdoc = html;
            })
            .catch(function (err) {
                console.error('Live preview failed:', err);
                setFrameLoading(false);
                setSync('error');
            });
    }

    function onPreviewMessage(event) {
        var data = event.data;
        if (!data || data.source !== 'alokito-preview') return;

        switch (data.action) {
            case 'ready':
                postToPreview({ action: 'init', payload: previewPayload() });
                postToPreview({ action: 'scroll', y: state.scrollY });
                setSync('ready');
                break;
            case 'hello':
                postToPreview({ action: 'init', payload: previewPayload() });
                break;
            case 'select':
                selectWidget(data.id);
                break;
            case 'deselect':
                deselect();
                break;
            case 'add':
                addWidget(data.type, data.targetId, data.position);
                break;
            case 'move':
                moveWidgetTo(data.id, data.targetId, data.position);
                break;
            case 'duplicate':
                duplicateWidget(data.id);
                break;
            case 'delete':
                removeWidget(data.id);
                break;
            case 'toggle':
                toggleWidget(data.id);
                break;
            case 'scroll':
                state.scrollY = Number(data.y) || 0;
                break;
        }
    }

    /* ==========================================================
       Save
       ========================================================== */
    function saveLayout() {
        if (state.saveInFlight) return;
        state.saveInFlight = true;

        dom.saveState.classList.remove('is-dirty');
        dom.saveState.classList.add('is-busy');
        $('.txt', dom.saveState).textContent = 'সংরক্ষণ হচ্ছে…';
        dom.saveBtn.disabled = true;

        var formData = new FormData();
        formData.append('action', 'save_layout');
        formData.append('csrf_token', CFG.csrfToken || '');
        formData.append('page_slug', CFG.pageSlug || 'home');
        formData.append('layout_data', JSON.stringify(state.widgets));

        fetch(CFG.saveUrl || 'page-builder.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                state.saveInFlight = false;
                dom.saveBtn.disabled = false;

                if (data && data.success) {
                    markClean('সংরক্ষিত · ' + (data.saved_at || 'এখন'));
                    state.scrollY = getPreviewScroll();
                    state.history.length = 0;
                    state.future.length = 0;
                    updateHistoryButtons();
                    toast(data.message || 'লেআউট সেভ হয়েছে');
                } else {
                    markDirty();
                    toast((data && data.message) || 'সেভ করা যায়নি', true);
                }
            })
            .catch(function (err) {
                state.saveInFlight = false;
                dom.saveBtn.disabled = false;
                markDirty();
                toast('সার্ভার ত্রুটি: ' + err.message, true);
            });
    }

    /* ==========================================================
       UI events
       ========================================================== */
    function bindEvents() {
        /* ---- library: click to add ---- */
        dom.library.addEventListener('click', function (e) {
            var tile = e.target.closest('.eb-tile');
            if (!tile) return;
            addWidget(tile.getAttribute('data-type'));
        });

        bindTileDrag();

        if (dom.search) {
            dom.search.addEventListener('input', function () { filterLibrary(this.value); });
        }

        /* ---- tabs ---- */
        var tabs = $$('.eb-tab', $('#ebTabs'));
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                tabs.forEach(function (t) { t.classList.remove('active'); });
                tab.classList.add('active');
                var key = tab.getAttribute('data-tab');
                $$('.eb-tab-panel').forEach(function (panel) {
                    panel.hidden = panel.getAttribute('data-panel') !== key;
                });
            });
        });

        /* ---- navigator interactions ---- */
        dom.navList.addEventListener('click', function (e) {
            var item = e.target.closest('.eb-nav-item');
            if (!item) return;
            var id = item.getAttribute('data-id');
            var actBtn = e.target.closest('[data-act]');

            if (actBtn) {
                e.stopPropagation();
                var act = actBtn.getAttribute('data-act');
                if (act === 'toggle') toggleWidget(id);
                else if (act === 'duplicate') duplicateWidget(id);
                else if (act === 'delete') removeWidget(id);
                return;
            }

            selectWidget(id, { force: true, focus: true });
        });

        /* ---- inspector interactions ---- */
        dom.inspectorContent.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-insp]');
            if (!btn) return;
            var act = btn.getAttribute('data-insp');
            var w = selectedWidget();

            if (act === 'close') deselect();
            else if (!w) return;
            else if (act === 'duplicate') duplicateWidget(w.id);
            else if (act === 'delete') removeWidget(w.id);
        });

        // Live-edit: every keystroke updates the model, then the preview (debounced)
        dom.inspectorContent.addEventListener('input', onInspectorInput);
        dom.inspectorContent.addEventListener('change', onInspectorInput);
        dom.inspectorContent.addEventListener('focusin', function (e) {
            if (e.target.matches('[data-key]')) pushHistory();
        });

        /* ---- toolbar ---- */
        dom.saveBtn.addEventListener('click', saveLayout);
        $('#ebRefreshPreview').addEventListener('click', function () {
            refreshPreview();
            toast('প্রিভিউ রিফ্রেশ হয়েছে');
        });

        dom.undo.addEventListener('click', undo);
        dom.redo.addEventListener('click', redo);

        $$('#ebDeviceSwitch [data-device]').forEach(function (btn) {
            btn.addEventListener('click', function () { setDevice(btn.getAttribute('data-device')); });
        });

        $$('#ebModeSwitch [data-mode]').forEach(function (btn) {
            btn.addEventListener('click', function () { setMode(btn.getAttribute('data-mode')); });
        });

        $('#ebToggleFocus').addEventListener('click', function () {
            setMode(state.mode === 'focus' ? 'split' : 'focus');
        });

        /* ---- mobile panels ---- */
        $('#ebToggleLibrary').addEventListener('click', function () {
            dom.app.classList.toggle('eb-library-open');
        });
        $('#ebOpenLibrary').addEventListener('click', function () {
            dom.app.classList.add('eb-library-open');
        });
        $('#ebCloseLibrary').addEventListener('click', function () {
            dom.app.classList.remove('eb-library-open');
        });

        /* ---- keyboard shortcuts ---- */
        document.addEventListener('keydown', function (e) {
            var typing = /^(INPUT|TEXTAREA|SELECT)$/.test((document.activeElement && document.activeElement.tagName) || '');
            var modifier = e.ctrlKey || e.metaKey;

            if (modifier && e.key.toLowerCase() === 's') {
                e.preventDefault();
                saveLayout();
            } else if (modifier && e.key.toLowerCase() === 'z') {
                e.preventDefault();
                if (e.shiftKey) redo(); else undo();
            } else if (e.key === 'Escape') {
                if (document.activeElement && document.activeElement.blur) document.activeElement.blur();
                dom.app.classList.remove('eb-library-open', 'eb-inspector-open');
                if (!typing) deselect();
            } else if ((e.key === 'Delete' || e.key === 'Backspace') && !typing && state.selectedId) {
                e.preventDefault();
                removeWidget(state.selectedId);
            }
        });

        /* ---- unsaved-changes guard ---- */
        window.addEventListener('beforeunload', function (e) {
            if (state.dirty) {
                e.preventDefault();
                e.returnValue = '';
                return '';
            }
        });

        var resizeTimer = null;
        window.addEventListener('resize', function () {
            if (!isOverlayInspector()) dom.app.classList.remove('eb-inspector-open');
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(fitPreview, 120);
        });
    }

    function onInspectorInput(e) {
        var control = e.target;
        if (!control || !control.matches('[data-key]')) return;

        var w = selectedWidget();
        if (!w) return;

        var scope = control.getAttribute('data-scope');
        var key = control.getAttribute('data-key');
        var value;

        if (control.type === 'checkbox') {
            value = control.checked;
            if (scope === 'root' && key === 'status') value = control.checked ? 1 : 0;
            if (scope === 'settings') value = control.checked;
        } else if (control.getAttribute('data-int') === '1' || control.type === 'number') {
            value = parseInt(control.value, 10);
            if (isNaN(value)) value = 0;
            var min = parseInt(control.getAttribute('min'), 10);
            var max = parseInt(control.getAttribute('max'), 10);
            if (!isNaN(min) && value < min) value = min;
            if (!isNaN(max) && value > max) value = max;
        } else {
            value = control.value;
        }

        if (scope === 'settings') {
            if (!w.settings) w.settings = {};
            w.settings[key] = value;
        } else {
            w[key] = value;
        }

        // Keep the inspector header + navigator title in sync while typing
        if (scope === 'root' && key === 'title') {
            var titleEl = $('#ebInspTitle');
            if (titleEl) titleEl.textContent = value || metaOf(w).label;
        }

        renderNavigator();
        updateCounters();
        markDirty();

        // send meta immediately so the toolbar labels stay correct
        postToPreview({ action: 'update-meta', payload: previewPayload() });

        schedulePreview(false);
    }

    var DEVICE_WIDTHS = { desktop: 1280, tablet: 834, mobile: 390 };

    /**
     * Scale the real device viewport to fit the available canvas space.
     * Keeps the true layout width (so a desktop preview is never squashed
     * into the mobile breakpoint) while always filling the canvas.
     */
    function fitPreview() {
        if (!dom.frameShell || !dom.frameWrap) return;

        var availW = dom.frameWrap.clientWidth || 0;
        var availH = dom.frameWrap.clientHeight || 0;
        if (!availW || !availH) return;

        var targetW = DEVICE_WIDTHS[state.device] || 1280;
        var scale = Math.min(1, availW / targetW);
        var shellH = Math.max(320, availH / scale);

        dom.frameShell.style.width = targetW + 'px';
        dom.frameShell.style.height = shellH + 'px';
        dom.frameShell.style.transform = 'scale(' + scale.toFixed(4) + ')';
        dom.frameShell.style.left = Math.max(0, Math.round((availW - targetW * scale) / 2)) + 'px';
        dom.frameShell.style.top = '0px';
    }

    function setDevice(device) {
        state.device = device;
        dom.frameShell.className = 'eb-frame-shell device-' + device;
        $$('#ebDeviceSwitch [data-device]').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-device') === device);
        });
        fitPreview();
    }

    function setMode(mode) {
        state.mode = mode;
        dom.app.classList.toggle('eb-focus', mode === 'focus');
        $$('#ebModeSwitch [data-mode]').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-mode') === mode);
        });
        if (mode === 'focus') {
            dom.app.classList.remove('eb-library-open', 'eb-inspector-open');
        }
        setTimeout(fitPreview, 60);
    }

    function updateCounters() {
        if (dom.widgetCount) dom.widgetCount.textContent = String(state.widgets.length);
        if (dom.navCount) dom.navCount.textContent = String(state.widgets.length);
    }
})();
