# Commercial Turnkey News Portal & Magazine CMS Template
## Buyer & Reseller Master Implementation Guide

Welcome to the **Commercial Turnkey News Portal & Magazine CMS Template**. This package is engineered specifically for agencies, developers, and entrepreneurs who want to brand, customize, and sell professional Bengali/English online news portals to multiple clients without touching any source code.

---

## 1. Key Value Propositions for Multi-Client Sale

1. **100% White-Label & Client-Customizable**:
   - Every single piece of branding, text, logo, color, contact info, editorial name, social media link, advertisement slot, legal policy, and SEO tag can be customized directly through the **Admin Dashboard**.
   - No hardcoded portal names or publisher details in templates.

2. **Zero-Overhead Native PHP Architecture**:
   - Built with high-performance native PHP 8.1+ and secure PDO.
   - Runs effortlessly on shared cPanel hosting (sub-$2/month hosting), LiteSpeed, Apache, Nginx, or VPS.
   - Sub-second Time-To-First-Byte (TTFB) with built-in WebP image optimization and Gzip compression.

3. **5-Minute Turnkey Web Installer (`install.php`)**:
   - Includes an interactive, step-by-step web installation wizard that checks server compatibility, connects to MySQL/SQLite, configures initial branding, creates the superadmin account, and locks itself for security.

4. **Modular Visual Theme Styler & Bengali Typography**:
   - Live color pickers for Primary Brand Color, Secondary Accent, Topbar, Header, Navbar, and Footer backgrounds.
   - 1-click popular color presets (Classic Orange/Red, Royal Blue, Crimson Daily, Emerald Green, Modern Slate).
   - Dynamic Bengali font selector (`SolaimanLipi`, `Kalpurush`, `Hind Siliguri`, `Anek Bangla`, `Noto Sans Bengali`) and base font-size scaler.

5. **Elementor-Style 3-Pane Page Builder (`admin/page-builder.php`)**:
   - Drag-and-drop icon library on the left, real-time live preview canvas in the center, and live content editor on the right.
   - Allows clients to customize their homepage layout anytime.

6. **Custom & Legal Pages CMS (`admin/pages.php`)**:
   - Built-in WYSIWYG editor (Summernote) for legal, company, and contact pages.
   - Pre-seeded with 6 essential pages: *আমাদের সম্পর্কে (About Us)*, *গোপনীয়তা নীতি (Privacy Policy)*, *ব্যবহারের শর্তাবলী (Terms)*, *যোগাযোগ (Contact Us)*, *সম্পাদকীয় নীতিমালা (Editorial Policy)*, and *বিজ্ঞাপন দর ও নিয়মাবলী (Ad Rates)*.
   - Clean, SEO-optimized URLs (`/about-us`, `/privacy-policy`, `/contact`, `/page/{slug}`).

7. **All-Bangladesh Regional News System (`area.php`)**:
   - Hierarchical division, district, and upazila news filter covering all 8 divisions and 64 districts.
   - Client can set their home district/division as the default in Admin Settings.

8. **Facebook Photo Card Generator (`admin/photo-card.php`)**:
   - Instant high-resolution branded social cards with news headline, thumbnail, date, website watermark, and advertisement slot.

9. **Smart Journalistic AI Assistant**:
   - Integrated offline Bengali NLP summarizer (free, zero API required) + optional Google Gemini AI integration for automatic headline, excerpt, and keyword generation.

10. **Agency Whitelabel & Developer Credit**:
    - You can brand the footer with your own agency name (*"কারিগরি সহায়তায়: Your Agency"*) or turn it off entirely with a single checkbox toggle.

---

## 2. Installation & Quick Start Guide

### Option A: Using the Automated Web Installer (Recommended for Clients)
1. Upload the template ZIP file to your web server (e.g., `public_html` in cPanel).
2. Extract all files into the public directory.
3. Open your browser and navigate to:
   ```
   https://yourdomain.com/install.php
   ```
4. Follow the 5-step installer wizard:
   - **Step 1: System Check** (Verifies PHP 8.1+, PDO, GD, permissions).
   - **Step 2: Database** (Enter MySQL Host, Database Name, Username, and Password).
   - **Step 3: Site Identity** (Set Portal Name, Tagline, Brand Color, and Primary Region).
   - **Step 4: Superadmin Account** (Create Admin Name, Email, and Password).
   - **Step 5: Execute & Launch** (Imports schema, saves config, creates security lock).
5. Done! Your portal is live.

### Option B: Manual cPanel Deployment
1. Create a MySQL database and database user in cPanel.
2. Import `setup.sql` into your database using **phpMyAdmin**.
3. Create `config/db_custom.php` with your database credentials:
   ```php
   <?php
   define('DB_DRIVER', 'mysql');
   define('DB_HOST', 'localhost');
   define('DB_PORT', '3306');
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_database_user');
   define('DB_PASS', 'your_database_password');
   ```
4. Log in to the Admin Panel:
   - **URL**: `https://yourdomain.com/admin/login.php`
   - **Default Email**: `admin@alokitogour.com`
   - **Default Password**: `Admin@123456`
5. Go to **Site Settings** (`admin/settings.php`) and customize your portal!

### Option C: Local Development Testing
For rapid local testing using the built-in PHP development server and SQLite:
- **Windows**: Double-click `run-server.bat` or run:
  ```bash
  php -c php.ini -S localhost:8000 router.php
  ```
- Open `http://localhost:8000` in your web browser.

---

## 3. Admin Control Center Configuration Breakdown

Navigate to `admin/settings.php` in the dashboard to access the 9 dedicated configuration tabs:

| Tab | Features & Customization Options |
|---|---|
| **১. ব্র্যান্ডিং ও পরিচিতি** | Site Title, Tagline, Header Logo, Footer Logo, Mobile Logo, Favicon, Default News Thumbnail, and Watermark image. |
| **২. থিম, রঙ ও ফন্ট** | Live Color Pickers (Primary, Secondary, Accent, Topbar, Header, Navbar, Footer), 1-click color palette presets, Google Bengali font selector (`SolaimanLipi`, `Kalpurush`, `Hind Siliguri`, `Anek Bangla`, `Noto Sans Bengali`), and font-size scaling. |
| **৩. হেডার ও টপবার** | Show/Hide Topbar, Bengali date toggle, English date toggle, Live weather widget & city name, Live TV button & stream URL, E-Paper button & link, Breaking news ticker & ticker title. |
| **৪. সম্পাদকীয় ও যোগাযোগ** | Chief Editor name, Executive Editor, Advisory Editor, Publisher, Govt Registration/Declaration number, Office address, Newsroom address, Phone numbers, and Contact email. |
| **৫. সোশ্যাল মিডিয়া** | Facebook Page, Facebook Group, YouTube Channel, X (Twitter), Instagram, WhatsApp Community, Telegram Channel, LinkedIn, and TikTok. |
| **৬. আঞ্চলিক ডিফল্ট** | Sets the default primary division and district so local portals in Sylhet, Chittagong, Dhaka, Rajshahi, etc., feel native out of the box. |
| **৭. এসইও ও অ্যানালিটিক্স** | Meta description, Meta keywords, Google Search Console verification tag, Google Analytics 4 (GA4) ID, Facebook Pixel ID, custom `<head>` scripts, and custom `<footer>` scripts. |
| **৮. এআই সহকারী** | Choose between built-in Smart Bengali NLP (100% free and offline) or Google Gemini AI with live API key connectivity test. |
| **৯. ফুটার ও এজেন্সি** | Footer description text, dynamic copyright format (`{YEAR}`, `{SITE_NAME}`), and Developer/Agency whitelabel toggle (*Show/Hide credit, agency name, agency URL*). |

---

## 4. Selling to Clients: Commercial Checklist

When delivering this website to a paying client:
- [ ] Run the installer or import the database for the client.
- [ ] In `admin/settings.php` (Tab 1), upload the client's logo, favicon, and set their portal name and tagline.
- [ ] In Tab 2, apply their brand colors and preferred Bengali font.
- [ ] In Tab 4, enter their editor's name, office address, and contact numbers.
- [ ] In Tab 5, connect their official Facebook page, YouTube channel, and WhatsApp group.
- [ ] In Tab 6, set their local division and district.
- [ ] In Tab 9, set your Agency name as the technical partner, or turn it off if the client requested an unbranded white-label.
- [ ] In `admin/pages.php`, review the 6 legal and informational pages (About Us, Privacy Policy, Terms, Contact Us, etc.) and ensure the client's information matches.
- [ ] In `admin/categories.php`, customize or reorder their news categories.
- [ ] In `admin/ads-manager.php`, insert their sponsors' banners or Google AdSense codes.
- [ ] Provide the client with their Admin Login URL: `https://clientdomain.com/admin/login.php`.

---

## 5. File Structure Reference

```
├── admin/                     # Administrative CMS Dashboard
│   ├── includes/              # Admin layouts (header, sidebar, footer, auth guard)
│   ├── assets/                # Admin styling, JavaScript, and page builder scripts
│   ├── settings.php           # Master 9-tab Customizer & Configuration Center
│   ├── pages.php              # Custom Pages & Legal CMS CRUD
│   ├── page-builder.php       # 3-Pane Elementor-style modular builder
│   ├── photo-card.php         # Facebook Photo Card generator
│   ├── categories.php         # News category manager
│   ├── ads-manager.php        # Advertisement slots manager
│   └── post-add.php           # News article publisher with Summernote
├── assets/                    # Public assets (CSS, JS, uploads)
│   ├── css/style.css          # Dynamic stylesheet powered by CSS variables
│   └── uploads/               # News, ads, and branding image directory (0755)
├── config/
│   ├── db.php                 # Core database connection & fallback driver
│   ├── db_custom.php          # Dynamic config generated by Web Installer
│   └── installed.lock         # Security lock file preventing installer overwrite
├── database/
│   ├── auto_migrate.php       # Automatic schema migrator & default page seeder
│   └── init_sqlite.php        # SQLite database bootstrapper
├── includes/
│   ├── bangladesh_geo.php     # 64-district Bangladesh geographic database
│   ├── footer.php             # Fully customizable footer
│   ├── header.php             # Dynamic header with live CSS & font injection
│   ├── helpers.php            # Bengali dates, slugify, WebP image pipeline
│   ├── navbar.php             # Responsive sticky navigation & mobile drawer
│   ├── sidebar.php            # Dynamic sidebar widget renderer
│   ├── ticker.php             # Breaking news marquee ticker
│   └── widget_renderer.php    # Page builder widget rendering engine
├── .htaccess                  # Production rewrite rules, Gzip compression, and caching
├── area.php                   # All-Bangladesh regional news filtering
├── category.php               # Category archive with pagination
├── index.php                  # Modular dynamic homepage
├── install.php                # Turnkey 5-step Web Installer
├── page.php                   # Page builder & CMS page renderer
├── router.php                 # Built-in PHP development server router
├── search.php                 # Full-text news search results
├── setup.sql                  # Complete MySQL schema & seed data
├── single.php                 # Article details, view counter & related stories
└── TEMPLATE_GUIDE.md          # Commercial Buyer & Reseller Documentation
```

---

*Engineered for high performance, commercial flexibility, and client satisfaction.*
