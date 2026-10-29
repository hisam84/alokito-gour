# Project Memory & Context Retention Ledger: Alokito Gour Clone

## 1. Project Information
- **Repository**: Alokito Gour Clone
- **Target Reference**: `https://alokitogour.com/` (চাঁপাইনবাবগঞ্জ ও জাতীয় বাংলা অনলাইন নিউজ পোর্টাল)
- **Primary Domain**: Regional news coverage for Chapainawabganj, Rajshahi division, national and international politics, sports, entertainment, and opinions.
- **Tech Stack**: Native PHP 8.2+, MySQL 8.0/MariaDB, Apache/LiteSpeed `.htaccess`, Vanilla JS, Bootstrap 5 CDN, Summernote CDN.

---

## 2. Key Architectural Decisions Recorded

1. **Native PHP + PDO Architecture**:
   - Chosen over Laravel or complex PHP frameworks to guarantee zero-overhead cPanel shared hosting deployment and instant execution with sub-second TTFB.
2. **Reverse-Engineering (REA) Findings Implemented**:
   - Primary Accent Color: Warm Orange (`#f3730d`), Deep Red (`#c82333`), Green badges (`#0abb75`).
   - Bengali Font Stack: `SolaimanLipi` via `https://fonts.maateen.me/solaiman-lipi/font.css`, with fallback `Kalpurush` and `Noto Sans Bengali`.
   - Dynamic Advertisements: 4 crucial ad slots identified (`header_right`, `after_lead`, `sidebar`, `inside_article`).
   - Editorial Metadata: Headline, Reporter Name, Published DateTime in Bengali (`bn_date()`), View Counter, Social Sharing (Facebook, WhatsApp, Twitter).
3. **WebP Pipeline**:
   - Integrated GD-based WebP conversion on upload in `upload_image_webp()` to preserve bandwidth on shared cPanel hosting.
4. **Clean Slugs & Routing**:
   - Apache mod_rewrite rules map `/news/{id}/{slug}` to `single.php` and `/category/{slug}` to `category.php`.

---

## 3. Environment & Deployment Ledger

- **Database Credentials (Default Development)**:
  - Host: `127.0.0.1`
  - Database: `alokito_gour`
  - User: `root`
  - Password: `` (empty in standard XAMPP/WAMP/local dev)
- **Default Superadmin Account**:
  - Email: `admin@alokitogour.com`
  - Password: `[Protected]`
- **Required File Permissions (cPanel)**:
  - `/assets/uploads/` -> `0755`
  - `/assets/uploads/news/` -> `0755`
  - `/assets/uploads/ads/` -> `0755`
  - Configuration files (`config/db.php`) -> `0644`

---

## 4. Current State & History Log

- **Phase 0**: Completed all 6 foundation documents (`PRD.md`, `architecture.md`, `Design.md`, `rules.md`, `Memory.md`, `Tasks.md`).
- **Phase 1**: Database Schema (`setup.sql`), PDO connection (`config/db.php`), and core helpers (`includes/helpers.php`) created and syntax verified.
- **Phase 2**: Admin Panel CMS fully implemented (`login.php`, `logout.php`, `index.php`, `post-add.php`, `post-list.php`, `post-edit.php`, `categories.php`, `ads-manager.php`, `settings.php`) with CSRF protection, session auth guard, and Summernote rich-text editor.
- **Phase 3**: Public News Portal frontend clone completed (`header.php`, `navbar.php`, `ticker.php`, `sidebar.php`, `footer.php`, `index.php`, `single.php`, `category.php`, `search.php`, `style.css`, `main.js`).
- **Phase 4**: Production `.htaccess` routing, Gzip compression, browser caching, and upload security implemented.
- **Phase 5**: Codebase syntax validated across all PHP scripts.
