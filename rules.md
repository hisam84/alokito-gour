# Engineering Rules & Guidelines: Alokito Gour Clone

## 1. Non-Negotiable Core Rules

1. **Native PHP 8.2+ Compatibility**:
   - The entire application must run cleanly on native PHP 8.2+ without deprecated syntax or warnings.
   - Code must be compatible with standard cPanel shared hosting (Apache/LiteSpeed, MySQL 8.0/MariaDB).
   - **Zero Node/npm Process in Production**: No Node.js daemons, Webpack dev servers, or background worker processes are permitted in production deployment.

2. **Database & PDO Integrity**:
   - Direct `mysqli` or deprecated `mysql_*` functions are strictly forbidden.
   - All database interaction must flow through `PDO` with prepared statements and parameter binding (`$stmt->execute([':id' => $id])`).
   - `SELECT *` queries in high-traffic frontend paths are prohibited; queries must explicitly select only necessary fields (`id, title, slug, thumbnail, created_at, views, category_id`).

3. **Front-End Lightweight Standard**:
   - Pure HTML5, modern CSS, and Vanilla JavaScript.
   - Third-party client dependencies must be loaded via stable CDNs (Bootstrap 5 CSS, Bootstrap Icons, Summernote WYSIWYG).
   - No heavy single-page application (SPA) frameworks (React/Vue/Angular) in public frontend rendering; server-side rendering (SSR) ensures optimal TTFB and native search engine indexing.

4. **100% Dynamic Content Principle**:
   - Every single piece of content displayed on the frontend must be dynamic and manageable via the `/admin/` portal:
     - Header logos, favicon, and contact numbers.
     - Breaking news ticker items and lead stories.
     - Advertisement banners and custom AdSense scripts.
     - Menu items, categories, and footer copyright text.
   - No hardcoded news headlines or editorial copy in PHP templates.

---

## 2. Security Standards

1. **Input & Sanitization**:
   - Never trust `$_GET`, `$_POST`, or `$_COOKIE`.
   - String inputs must be trimmed and sanitized before validation.
   - HTML output must always pass through `e()` (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`).
   - Rich text from Summernote must be sanitized against script injection and unsafe attributes.

2. **Session & Authentication**:
   - Session verification must precede every administrative view via `admin/includes/auth_guard.php`.
   - Passwords must be hashed using `password_hash($password, PASSWORD_BCRYPT)`.
   - All state-altering administrative forms (POST/PUT/DELETE) must include and validate a CSRF token.

3. **File Upload Directives**:
   - Permitted MIME types: `image/jpeg`, `image/png`, `image/webp`.
   - Executable files (`.php`, `.phtml`, `.exe`, `.sh`) must be rejected.
   - Directory execution must be disabled in `/assets/uploads/`.
   - Auto-convert uploaded imagery to `.webp` format and generate optimized thumbnail sizes.

---

## 3. Code Style & File Conventions

1. **File & URL Naming**:
   - Use kebab-case for admin pages (`post-add.php`, `post-list.php`, `ads-manager.php`).
   - Use lowercase singular for public route targets (`index.php`, `single.php`, `category.php`, `search.php`).
   - Slugs must be clean, lowercase, and URL-safe.

2. **Function Naming**:
   - Helper functions use snake_case (e.g. `bn_date()`, `bn_number()`, `upload_image_webp()`, `get_ad()`, `e()`).
   - Database column names use snake_case (e.g. `category_id`, `is_lead`, `created_at`).

3. **Error Handling**:
   - PDO error mode set to `PDO::ERRMODE_EXCEPTION`.
   - Display errors off in production (`display_errors = 0`), log errors to server error log.
