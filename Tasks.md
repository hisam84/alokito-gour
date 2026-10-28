# Execution Backlog & Task Ledger: Alokito Gour Clone

## Phase 0: Prerequisite Foundation Documents (Mandatory)
- [x] Create `PRD.md` (Product Requirements Document)
- [x] Create `architecture.md` (System Architecture & ERD Specifications)
- [x] Create `Design.md` (Design Blueprint, Tokens, and Wireframes)
- [x] Create `rules.md` (Engineering Rules & Guidelines)
- [x] Create `Memory.md` (Project Context & Decisions Ledger)
- [x] Create `Tasks.md` (Granular Task Backlog)

---

## Phase 1: Database Architecture & Core Utilities
- [x] Create `setup.sql` with complete schema:
  - [x] `settings` table with initial site configurations
  - [x] `users` table with hashed superadmin credentials
  - [x] `categories` table with default regional & national categories
  - [x] `posts` table with indexes on `category_id`, `is_lead`, `is_breaking`, `views`, `created_at`
  - [x] `advertisements` table with standard positions
  - [x] Seed data for categories, sample lead articles, and settings
- [x] Create `config/db.php`:
  - [x] Secure PDO connection wrapper with UTF-8 support and error handling
  - [x] Helper functions for fetching settings and database instances
- [x] Create `includes/helpers.php`:
  - [x] Bengali date formatter (`bn_date()`)
  - [x] Bengali digit converter (`bn_number()`)
  - [x] URL slug generator (`slugify()`)
  - [x] HTML sanitization wrapper (`e()`)
  - [x] WebP image converter & thumbnail generator (`upload_image_webp()`)

---

## Phase 2: Secure Admin Management Panel (`/admin/`)
- [x] Admin Security & Layout:
  - [x] Create `admin/includes/auth_guard.php` (Session verification & CSRF token generator)
  - [x] Create `admin/includes/header.php`, `admin/includes/sidebar.php`, `admin/includes/footer.php`
  - [x] Create `admin/assets/css/admin.css` and `admin/assets/js/admin.js`
- [x] Admin Auth:
  - [x] Create `admin/login.php` with CSRF protection and password verification
  - [x] Create `admin/logout.php` with clean session destruction
- [x] Admin Features:
  - [x] Create `admin/index.php` (KPI metrics cards & recent news table)
  - [x] Create `admin/post-add.php` (Article creator with Summernote WYSIWYG & WebP upload)
  - [x] Create `admin/post-list.php` (News data table with pagination, category filter & search)
  - [x] Create `admin/post-edit.php` (Article editor & image replacer)
  - [x] Create `admin/categories.php` (Category CRUD, reordering, and menu toggle)
  - [x] Create `admin/ads-manager.php` (Banner advertisement & AdSense manager)
  - [x] Create `admin/settings.php` (Identity, logo/favicon upload, contact & social links)

---

## Phase 3: Public News Portal Frontend Clone
- [x] Global Inclusions:
  - [x] Create `includes/header.php` (Top bar with live Bengali date, logo, header banner ad)
  - [x] Create `includes/navbar.php` (Sticky category nav, mobile drawer, search modal)
  - [x] Create `includes/ticker.php` (Dynamic breaking news marquee ticker)
  - [x] Create `includes/sidebar.php` (Latest/Popular tabs, district filter, sidebar ad)
  - [x] Create `includes/footer.php` (Footer, editor details, office address, copyright)
- [x] Public Pages:
  - [x] Create `index.php` (Homepage with hero lead stories, category grids, sidebar)
  - [x] Create `single.php` (Article detail, view counter, social share, related news)
  - [x] Create `category.php` (Category archive with pagination)
  - [x] Create `search.php` (Full-text news search results)
- [x] Frontend Assets:
  - [x] Create `assets/css/style.css` (SolaimanLipi typography, color tokens, responsive grid)
  - [x] Create `assets/js/main.js` (Vanilla JS for mobile drawer, tabs, ticker marquee)
  - [x] Setup `assets/uploads/news/` and `assets/uploads/ads/` directories

---

## Phase 4: Production Optimization & Routing
- [x] Create `.htaccess` with mod_rewrite rules for clean URLs (`/news/id/slug`, `/category/slug`, `/search`)
- [x] Configure `mod_deflate` Gzip compression and `ExpiresDefault` browser caching
- [x] Harden `.htaccess` to block directory listing and prevent execution in uploads

---

## Phase 5: Verification & End-to-End Validation
- [x] Lint all PHP files (`php -l`) for syntax errors
- [x] Test database seed import (`setup.sql` & SQLite dual-sync)
- [x] Ingest 45+ real authentic news articles from `https://alokitogour.com/` across all categories
- [x] Verify right-side advertisement banner elasticity (100% fixed column width, flexible/unconstrained auto height, upload-ready with placeholder)
- [x] Validate end-to-end user journeys (Home, Article details with view counter, Category filter, Search, Admin panel, Media gallery)
- [x] Local server running and verified at `http://localhost:8000`
