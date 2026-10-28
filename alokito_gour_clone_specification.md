# Master Blueprint: Dynamic News Portal Clone (Alokito Gour)

## 0. Mandatory Pre-requisite Phase (Must Execute First)

Before writing any application code, database scripts, or assets, the Antigravity agent **MUST** initialize the project workspace by creating the following 6 core documentation and planning files:

1. **`PRD.md` (Product Requirements Document)**:
   * Detailed breakdown of all functional and non-functional requirements.
   * User personas (Super Admin, Editor, Reporter, Reader).
   * Feature checklist (Live news ticker, dynamic categories, lead stories, pagination, image optimization, rich-text editor, advertisement engine).
   * Success metrics (cPanel zero-dependency, sub-second TTFB, 100% dynamic control).

2. **`architecture.md`**:
   * Complete technical architecture: PHP PDO data flow, routing via Apache `.htaccess`, directory hierarchy.
   * Database ERD and relationships between `users`, `categories`, `posts`, `advertisements`, and `settings`.
   * Security architecture: Prepared statements (SQLi prevention), output escaping (`htmlspecialchars` against XSS), CSRF tokens, session fixation defense, and secure upload mime-validation.

3. **`Design.md`**:
   * Design tokens extracted via REA from `https://alokitogour.com/`: color palette (primary red, dark navy/slate, white/gray backgrounds), font typography stack (SolaimanLipi, Kalpurush, Noto Sans Bengali).
   * Responsive layout specifications: Mobile (< 640px), Tablet (640px - 1024px), Desktop (> 1024px).
   * Component wireframes and layout blueprints (Header, Navbar, Hero Lead grid, 2-column Category blocks, Sidebar tabs, Footer).

4. **`rules.md`**:
   * Coding standards and non-negotiables for this repository:
     * PHP 8.2+ native compatibility; strictly no Node/npm process required in production.
     * Clean OOP/Procedural modular PHP with PDO; no raw unescaped input.
     * Pure CSS / lightweight Tailwind CDN / Vanilla JS only (no heavy client-side frameworks).
     * Every frontend piece must connect to the database via Admin Panel settings.

5. **`Memory.md`**:
   * Project status ledger and context retention log.
   * Tracks active state, key decisions made, discovered challenges during REA inspection, and environmental variables for cPanel deployment.

6. **`Tasks.md`**:
   * Granular, phased execution backlog with actionable checkboxes (`[ ]` / `[x]`).
   * Step-by-step task tracking: Schema build -> Database connection -> Admin CMS -> Public Templates -> SEO & Performance tuning -> Testing & Final Polish.

---

## 1. Project Overview & Objectives

* **Target Reference**: `https://alokitogour.com/` (আলোকিত গৌড় - Regional & National Bengali News Portal).
* **Goal**: Build an exact visual clone of the frontend, backed by a 100% dynamic, custom, secure, ultra-fast PHP & MySQL backend.
* **Hosting Environment**: Standard Shared/Cloud cPanel (Apache / LiteSpeed, PHP 8.1 - 8.3+, MySQL / MariaDB, phpMyAdmin).
* **Core Principles**:
  * Zero heavy Node.js runtime dependence in production.
  * Fully mobile-responsive and lightweight (Vanilla CSS / Tailwind CDN / compiled micro-CSS).
  * Every single frontend section must be manageable via an Admin Panel.
  * SEO-friendly clean URLs (`.htaccess` rewriting).

---

## 2. Antigravity Agent Directive: Reverse-Engineering (REA) Workflow

Antigravity will leverage the **REA (Reverse Engineering Agent) Skill** for inspecting the reference site:

1. **Asset & Structure Inspection**:
   * Use `inspect_web_page` and `analyze_web_bundle` targeting `https://alokitogour.com/` to capture the DOM structure, responsive breakpoints, header/navbar hierarchy, category grid alignments, typography (SolaimanLipi / Kalpurush / Noto Sans Bengali font stacks), and color schemes.
2. **Dynamic Section Extraction**:
   * Trace and catalogue every distinct visual block:
     * Top Bar (Date in Bengali, social handles, breaking ticker).
     * Main Header (Logo, header banner ad placement).
     * Primary Navigation bar with responsive drawer menu.
     * Lead News Hero Section (1 Main Lead + 2-4 Sub-leads).
     * Category Grid Blocks (e.g., চাঁপাইনবাবগঞ্জ / স্থানীয় সংবাদ, জাতীয়, রাজনীতি, আন্তর্জাতিক, খেলাধুলা, বিজ্ঞান ও প্রযুক্তি, ধর্ম, বিনোদন).
     * Tabbed / Sidebar Widgets (সর্বশেষ, সর্বাধিক পঠিত, ফটো গ্যালারি / ভিডিও).
     * Footer (Editor info, address, copyright, dynamic category links).
3. **Reconstruction**:
   * Reconstruct the extracted layout into pure, semantic PHP templates (`.php`) with no external bloated frameworks.

---

## 3. Tech Stack & Architectural Decisions

| Layer | Technology | Reason |
| :--- | :--- | :--- |
| **Backend** | Native PHP 8.2+ (Clean Modular / MVC Pattern) | Native cPanel execution, zero process overhead. |
| **Database** | MySQL 8.0 / MariaDB (Prepared Statements via PDO) | High security against SQL injection, ultra-fast indexing. |
| **Frontend** | HTML5, Modern CSS / Tailwind CSS, Vanilla JS | Super-fast loading, minimal Time-to-First-Byte (TTFB). |
| **Rich Text Editor** | Summernote or TinyMCE (via CDN) | Lightweight Bengali news editing with image insert. |
| **Routing** | Apache `.htaccess` Mod_Rewrite | Clean slugs: `/news/{id}/{slug}` or `/{category}/{slug}`. |
| **Image Pipeline** | PHP GD / WebP Converter | Automatically optimize uploaded images to WebP format. |

---

## 4. Directory & File Structure (cPanel Standard)

```
/public_html/
├── PRD.md                    # Core requirement specifications
├── architecture.md           # System and database architecture
├── Design.md                 # UI/UX, tokens, and wireframe blueprint
├── rules.md                  # Project rules & coding standards
├── Memory.md                 # Context retention and state memory
├── Tasks.md                  # Step-by-step task tracking ledger
├── assets/
│   ├── css/
│   │   └── style.css          # Core custom styling (extracted & polished)
│   ├── js/
│   │   └── main.js           # Lightweight UI interactions (drawer, ticker, tabs)
│   └── uploads/              # Dynamic media folder (chmod 755)
│       └── news/
├── config/
│   └── db.php                # Database connection with PDO & error logging
├── includes/
│   ├── header.php            # Dynamic Meta, Top Bar, Header Logo & Ad
│   ├── navbar.php            # Dynamic Menu fetched from DB
│   ├── ticker.php            # Dynamic Breaking News Ticker
│   ├── sidebar.php           # Latest / Most read / Category widget
│   └── footer.php            # Dynamic Footer, address, social links
├── admin/
│   ├── assets/               # Admin styling and scripts
│   ├── includes/
│   │   ├── auth_guard.php    # Session authentication validation
│   │   ├── header.php
│   │   └── sidebar.php
│   ├── index.php             # Dashboard Overview (KPI stats, recent activity)
│   ├── login.php             # Secure login with CSRF token & password_hash
│   ├── logout.php
│   ├── post-add.php          # Create news with rich editor & image upload
│   ├── post-list.php         # Data table with pagination, edit & delete
│   ├── post-edit.php         # Update existing news
│   ├── categories.php        # Manage categories and navbar orders
│   ├── ads-manager.php       # Dynamic Ad banner injection
│   └── settings.php          # Site identity (Logo, favicon, phone, address, meta)
├── .htaccess                 # Pretty URLs, Gzip compression, browser cache
├── index.php                 # Home page (All dynamic widgets)
├── single.php                # Article view page (Views counter, social share)
├── category.php              # Category archive with pagination
├── search.php                # Full-text search result page
└── setup.sql                 # Ready-to-import database schema
```

---

## 5. Database Schema (`setup.sql`)

```sql
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `site_title` VARCHAR(255) NOT NULL DEFAULT 'Alokito Gour',
  `site_tagline` VARCHAR(255) NULL,
  `logo_path` VARCHAR(255) NULL,
  `favicon_path` VARCHAR(255) NULL,
  `editor_name` VARCHAR(150) NULL,
  `publisher_name` VARCHAR(150) NULL,
  `office_address` TEXT NULL,
  `contact_phone` VARCHAR(50) NULL,
  `contact_email` VARCHAR(100) NULL,
  `facebook_url` VARCHAR(255) NULL,
  `youtube_url` VARCHAR(255) NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('superadmin', 'editor', 'reporter') DEFAULT 'editor',
  `status` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL UNIQUE,
  `order_index` INT DEFAULT 0,
  `show_in_menu` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `posts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `title` VARCHAR(300) NOT NULL,
  `slug` VARCHAR(350) NOT NULL UNIQUE,
  `excerpt` TEXT NULL,
  `content` LONGTEXT NOT NULL,
  `thumbnail` VARCHAR(255) NULL,
  `caption` VARCHAR(255) NULL,
  `reporter_name` VARCHAR(150) NULL,
  `is_lead` TINYINT(1) DEFAULT 0,
  `is_breaking` TINYINT(1) DEFAULT 0,
  `views` INT DEFAULT 0,
  `status` ENUM('published', 'draft') DEFAULT 'published',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`category_id`),
  INDEX (`is_lead`),
  INDEX (`is_breaking`),
  INDEX (`views`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `advertisements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `position` VARCHAR(50) NOT NULL UNIQUE, -- 'header_right', 'after_lead', 'sidebar', 'inside_article'
  `title` VARCHAR(100) NULL,
  `image_path` VARCHAR(255) NULL,
  `target_url` VARCHAR(255) NULL,
  `custom_code` TEXT NULL,
  `status` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 6. Performance & cPanel Optimization Directives

1. **Gzip & Leverage Browser Caching (`.htaccess`)**:
   * Inject `mod_deflate` rules for HTML, CSS, JS.
   * Inject `ExpiresDefault` headers (images: 1 month, css/js: 1 week).
2. **Image Auto-Conversion**:
   * Backend upload handler must convert `.jpg`/`.png` into `.webp` format and generate a small thumbnail version for grid lists.
3. **Database Efficiency**:
   * All frontend queries must only fetch required columns (`id, title, slug, thumbnail, created_at, views`) rather than `SELECT *`.
   * Indexing on `status`, `created_at`, `views`, and `category_id` to handle tens of thousands of visitors without throttling shared CPU.

---

## 7. Execution Checklist for Antigravity

When feeding this blueprint into Antigravity, prompt the agent as follows:

```
Follow ALOKITOGOUR_CLONE_MASTER.md strictly:
1. STEP 1 (Mandatory Prerequisite): Before writing code, use your REA skill to inspect https://alokitogour.com/ and generate the 6 foundation documents:
   - PRD.md
   - architecture.md
   - Design.md
   - rules.md
   - Memory.md
   - Tasks.md
2. STEP 2: Proceed with the task list defined in Tasks.md to generate the database schema (setup.sql) and full PHP/MySQL codebase.
3. Keep all components completely dynamic and optimized for cPanel deployment.
```