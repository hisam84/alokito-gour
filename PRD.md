# Product Requirements Document (PRD): Alokito Gour Clone

## 1. Executive Summary & Objectives
The purpose of this project is to build an exact visual and functional clone of **Alokito Gour** (`https://alokitogour.com/`), a premier regional and national Bengali news portal headquartered in Chapainawabganj, Bangladesh.
The system is engineered for standard shared or cloud cPanel hosting environments running native PHP 8.2+ and MySQL/MariaDB. It offers 100% dynamic content management via a secure custom Admin Panel, sub-second Time-to-First-Byte (TTFB), zero Node.js dependency in production, responsive layout across all device viewports, and clean SEO-optimized URLs.

---

## 2. User Personas

| Persona | Role & Objectives | Key Actions |
| :--- | :--- | :--- |
| **Reader / Visitor** | Local and international Bengali readers seeking real-time news, district reports, politics, sports, and opinions. | Browse categories, read articles, filter by district, share to social media, search archives, read breaking ticker. |
| **Reporter** | Field journalists contributing stories, district news, photos, and breaking updates. | Draft stories, upload photos with captions, specify byline and category. |
| **Editor** | Editorial board reviewing, polishing, fact-checking, and categorizing content. | Edit/publish submitted articles, toggle lead/breaking stories, manage category taxonomies. |
| **Super Admin** | Portal owner / webmaster managing system configurations and monetisation. | Manage users/roles, manage advertisement slots (headers, banners, sidebars), update site branding (logo, favicon, contact info, social links). |

---

## 3. Core Functional Requirements

### 3.1 Public Portal Frontend
1. **Top Bar Header**:
   - Current live date and day formatted in standard Bengali (e.g. *বুধবার, ২৮ অক্টোবর ২০২৬*).
   - Social media icons with dynamic links (Facebook, YouTube).
   - E-paper / Video gallery link.
2. **Main Branding & Header Banner**:
   - High-resolution portal logo with mobile responsive scaling.
   - Dedicated dynamic leaderboard advertisement zone (`header_right`).
3. **Primary Navigation Bar**:
   - Sticky navbar containing all active categories ordered by priority.
   - Dropdown menus for division/district subdivisions and nested topics (বিজ্ঞান ও প্রযুক্তি, ধর্ম, শিক্ষা, ইত্যাদি).
   - Responsive off-canvas drawer / mobile hamburger menu for touch devices.
   - Integrated instant search modal / quick input bar.
4. **Breaking News Ticker ("সংবাদ শিরোনাম")**:
   - Dynamic real-time scrolling ticker showcasing breaking stories flagged with `is_breaking = 1`.
   - Pause on mouse hover / touch interaction.
5. **Hero Lead News Section**:
   - **Primary Lead**: Dominant headline card with high-resolution image, prominent title, excerpt, byline, and relative timestamp.
   - **Sub-Leads**: 2 to 4 secondary highlight stories arranged in an organized grid adjacent to the primary lead.
6. **Advertisement Injection Zones**:
   - `header_right` (Leaderboard banner next to logo)
   - `after_lead` (Full-width responsive leaderboard banner beneath hero section)
   - `sidebar` (Sticky or inline 300x250 / 300x600 banner ads)
   - `inside_article` (Mid-content monetization slot in single news view)
7. **Multi-Column Category Blocks**:
   - Distinct highlighted blocks for:
     - চাঁপাইনবাবগঞ্জ (Regional / Local Lead + sub-item list)
     - জাতীয় (National news)
     - সারাদেশ (Division filter: ঢাকা, চট্টগ্রাম, রাজশাহী, বরিশাল, খুলনা, সিলেট, রংপুর, ময়মনসিংহ)
     - রাজনীতি (Politics)
     - আন্তর্জাতিক (International)
     - খেলাধুলা (Sports)
     - অর্থনীতি (Economy)
     - বিনোদন (Entertainment)
     - বিজ্ঞান ও প্রযুক্তি (Tech)
     - শিক্ষা ও সাহিত্য (Education & Literature)
     - ধর্ম ও জীবন (Religion)
8. **Sidebar Widgets**:
   - Tabbed widget: **সর্বশেষ** (Latest articles by timestamp) and **জনপ্রিয়** (Most read articles ranked by view count).
   - Division / District archive filter.
   - Advertisement block.
9. **Single Article View (`single.php`)**:
   - Dynamic Breadcrumb trail (`প্রচ্ছদ > ক্যাটাগরি > শিরোনাম`).
   - Bengali headline, reporter byline, formatted publication date, and view counter.
   - Social sharing bar (Facebook, WhatsApp, Twitter/X, Copy Link).
   - Primary thumbnail with caption.
   - Full article body formatted with clean typography.
   - Mid-article advertisement banner (`inside_article`).
   - Related stories grid from the same category.
10. **Category Archive (`category.php`)**:
    - Filtered news feed with thumbnail, title, excerpt, and timestamp.
    - Clean pagination (Previous, Page Numbers, Next).
11. **Search Results (`search.php`)**:
    - Full-text search matching titles and content.
12. **Footer Section**:
    - Secondary logo and slogan.
    - Editor & Publisher information (e.g. *প্রধান সম্পাদক ও প্রকাশক: মোহাঃ আব্দুল্লাহ*).
    - Registered office address, contact phone numbers (সম্পাদক, অফিস, বিজ্ঞাপন), email.
    - Quick links to categories, privacy policy, contact page.
    - Copyright notice and developer credits.

---

### 3.2 Dynamic Admin CMS (`/admin/`)
1. **Authentication & Security**:
   - Secure login with CSRF token verification, password hashing (`password_hash` with BCRYPT/ARGON2ID), and session fixation prevention.
   - Session authentication guard (`auth_guard.php`) protecting all administrative routes.
   - Logout mechanism with complete session destruction.
2. **Dashboard Overview (`index.php`)**:
   - Key Performance Indicator (KPI) cards: Total Articles, Total Views, Active Categories, Active Advertisements.
   - Recent News table with quick actions and publication status.
3. **News Article Management**:
   - **Add Article (`post-add.php`)**:
     - Title, Slug (auto-generated from title or custom), Category selector.
     - WYSIWYG Rich Text Editor (Summernote) supporting headings, formatting, links, and embedded images.
     - Featured Image uploader with auto-conversion to WebP format and thumbnail generation.
     - Image caption, Reporter byline.
     - Flags: `is_lead` (featured on home hero), `is_breaking` (included in top ticker).
     - Publication status: `published` or `draft`.
   - **Article List (`post-list.php`)**:
     - Paginated tabular view with thumbnail preview, title, category, views, date, and status.
     - Filter by category and search by keyword.
     - Direct actions: View on site, Edit, Delete (with confirmation modal).
   - **Edit Article (`post-edit.php`)**:
     - Full update capabilities with image replacement or retention.
4. **Category Management (`categories.php`)**:
   - Add new category, edit name and slug.
   - Reorder display position in navbar menu.
   - Toggle visibility in primary navigation (`show_in_menu`).
   - Delete categories with cascade safety.
5. **Advertisement Management (`ads-manager.php`)**:
   - Pre-configured slots: `header_right`, `after_lead`, `sidebar`, `inside_article`.
   - Support for both image banners (upload image + target URL) and custom HTML / Google AdSense script tags.
   - Active / Inactive toggle switch.
6. **Site Settings & Identity (`settings.php`)**:
   - Site Title, Tagline, Logo upload, Favicon upload.
   - Editor Name, Publisher Name.
   - Office Address, Phone Numbers (Editor, Office, Advertising), Contact Email.
   - Social Media URLs (Facebook, YouTube).

---

## 4. Non-Functional Requirements & Performance
1. **Hosting Compatibility**:
   - Runs out-of-the-box on standard cPanel, Apache / LiteSpeed web servers.
   - Zero Node.js or background daemon process requirement.
2. **Database Performance**:
   - All queries use explicit column selection (avoiding `SELECT *`).
   - Database tables indexed on `category_id`, `is_lead`, `is_breaking`, `views`, and `created_at`.
3. **Asset & Image Optimization**:
   - Automated conversion of uploaded news imagery to `.webp` format to reduce payload by up to 70%.
   - Generation of dedicated thumbnail sizes for fast list rendering.
4. **Caching & Compression**:
   - Apache `.htaccess` with `mod_deflate` (Gzip) for text, HTML, CSS, and JS.
   - Long-lived browser caching headers (`ExpiresDefault`) for static media.
5. **Security Baseline**:
   - 100% prepared statements via PHP PDO for all SQL operations.
   - Output escaping with `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
   - Upload MIME-type and extension whitelisting.

---

## 5. Success Metrics
- **Visual Accuracy**: 100% match with the design language, fonts, and layout structure of `https://alokitogour.com/`.
- **TTFB (Time To First Byte)**: Under 250ms on standard cPanel shared hosting.
- **Dynamic Control**: 100% of frontend text, logos, categories, articles, ticker items, and banners editable from the Admin CMS.
