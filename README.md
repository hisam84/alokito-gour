# Alokito Gour (আলোকিত গৌড়) - Dynamic News Portal Clone

An authentic, high-performance, and responsive clone of the popular Bengali news portal **[Alokito Gour](https://alokitogour.com/)**, engineered with pure native **PHP 8.2+**, **SQLite/MySQL PDO**, **Bootstrap 5**, and custom **SolaimanLipi** typography.

---

## 🌟 Key Features

- **⚡ Zero Process Overhead**: 100% native PHP without any Node.js, npm, or heavy framework dependencies in production.
- **🔄 Dual Database Layer**: 
  - **SQLite** (`database/alokito_gour.sqlite`): Runs instantly out-of-the-box locally with zero server configuration.
  - **MySQL / MariaDB** (`setup.sql`): Production-ready schema with indexed queries and full seed data for cPanel / phpMyAdmin deployment.
- **📰 53+ Real Bengali News Articles**: Populated with authentic news covering 17 categories (চাঁপাইনবাবগঞ্জ, জাতীয়, রাজনীতি, আন্তর্জাতিক, অর্থনীতি, বিজ্ঞান ও প্রযুক্তি, ধর্ম ও জীবন, চাকরি, শিক্ষা, ইত্যাদি).
- **📢 Dynamic Breaking Ticker**: Live headline marquee with red "শিরোনাম" badge.
- **🎯 Elastic Advertisement Engine**: 
  - Fixed-width & unrestricted auto-height ad slots (`height: auto !important; max-height: none !important;`).
  - Graceful dashed placeholder when ad slot is unassigned.
  - Upload-and-display banner management.
- **🛡️ Secure Admin Panel (`/admin/`)**:
  - Full CRUD for news articles, categories, ads, and site settings.
  - Summernote rich text editor & PHP GD WebP image conversion pipeline.
  - CSRF protection, password hashing (`password_hash`), and authentication guards.
- **🌐 Clean SEO URLs**: Apache `.htaccess` rewriting (`/news/{id}/{slug}`, `/category/{slug}`, `/search`).

---

## 🚀 Quick Start (Local Development)

### Prerequisites
- PHP 8.2+ installed with `pdo_sqlite`, `sqlite3`, `gd`, `mbstring`, `fileinfo`, `curl` extensions enabled.

### 1-Click Run
Double-click `run-server.bat` or execute in terminal:

```bash
php -c php.ini -S 0.0.0.0:8000 router.php
```

Then open in your browser:
- **Public Portal**: [http://localhost:8000](http://localhost:8000)
- **Admin CMS**: [http://localhost:8000/admin/](http://localhost:8000/admin/)

### Default Admin Credentials
- **Email**: `admin@alokitogour.com`
- **Password**: `Admin@123456`

---

## 📦 Production Deployment (cPanel / Apache)

1. **Upload Files**: Upload the project directory to `public_html/` on your cPanel hosting.
2. **Database Import**:
   - Create a MySQL database (e.g. `alokito_gour`) and database user in cPanel.
   - Open **phpMyAdmin**, select the database, and import `setup.sql`.
3. **Database Credentials**:
   - Edit `config/db.php` and set your MySQL credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'your_cpanel_dbname');
     define('DB_USER', 'your_cpanel_dbuser');
     define('DB_PASS', 'your_cpanel_dbpass');
     ```
4. **Permissions**: Ensure `assets/uploads/` is writable (`chmod 755`).

---

## 📁 Project Structure

```
├── .htaccess                     # Apache clean URL routing & Gzip compression
├── admin/                        # Secure CMS management dashboard
│   ├── assets/                   # Admin CSS & JS
│   ├── includes/                 # Admin guards, sidebar & header
│   ├── ads-manager.php           # Banner advertisement manager
│   ├── categories.php            # News taxonomy CRUD
│   ├── index.php                 # KPI dashboard overview
│   ├── login.php / logout.php    # Session authentication
│   ├── post-add.php              # News composer with WYSIWYG
│   ├── post-edit.php             # News editor
│   ├── post-list.php             # Paginated articles datatable
│   └── settings.php              # Identity, logo, contact info
├── assets/
│   ├── css/style.css             # Core CSS & SolaimanLipi typography
│   ├── js/main.js                # Frontend interactions & drawer
│   └── uploads/                  # WebP news images & ad banners
├── config/
│   └── db.php                    # Dual driver (SQLite / MySQL) connection
├── database/
│   ├── alokito_gour.sqlite       # Local development SQLite database
│   ├── init_sqlite.php           # SQLite initializer
│   ├── seed_full_news.php        # 53-article real news seeder
│   └── export_mysql_seed.php     # MySQL setup.sql exporter
├── includes/
│   ├── footer.php                # High-contrast footer & credits
│   ├── header.php                # Meta tags, topbar, branding
│   ├── helpers.php               # Bengali date, numbers, WebP upload, ads
│   ├── navbar.php                # Sticky dynamic navigation bar
│   ├── sidebar.php               # Tabbed latest/popular widget & ads
│   └── ticker.php                # Breaking news marquee
├── category.php                  # Category archive page
├── index.php                     # Dynamic homepage
├── router.php                    # Built-in PHP development server router
├── run-server.bat                # 1-click Windows launcher
├── search.php                    # Full-text news search results
├── setup.sql                     # Full MySQL schema & seed data
└── single.php                    # Article reader & view counter
```

---

## 📜 License & Attribution

- Built as a faithful clone of **[Alokito Gour](https://alokitogour.com/)**.
- Fonts: SolaimanLipi, Kalpurush.
- Licensed under the MIT License.
