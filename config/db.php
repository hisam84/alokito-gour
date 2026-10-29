<?php
/**
 * Database Connection & Global Configuration
 * Alokito Gour Clone
 * 
 * Supports both MySQL (Production cPanel) and Local SQLite
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Driver ('auto', 'mysql', or 'sqlite')
define('DB_DRIVER', getenv('DB_DRIVER') ?: 'auto');

// MySQL Credentials (for cPanel / Production)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'alokito_gour');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

// SQLite File Path (for Local Development)
define('SQLITE_FILE', dirname(__DIR__) . '/database/alokito_gour.sqlite');

// Site URL configuration
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? 80) == 443)
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$protocol = $isHttps ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('SITE_URL', rtrim($protocol . $host, '/'));

/**
 * Returns the singleton PDO database connection instance.
 */
function get_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $driver = strtolower(DB_DRIVER);

    // If explicitly set to SQLite or local SQLite file exists
    if ($driver === 'sqlite' || ($driver === 'auto' && file_exists(SQLITE_FILE))) {
        try {
            $pdo = new PDO('sqlite:' . SQLITE_FILE);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch (PDOException $e) {
            if ($driver === 'sqlite') {
                die('<div style="font-family:sans-serif;padding:30px;line-height:1.6;max-width:650px;margin:40px auto;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;">
                    <h2 style="color:#e11d48;margin-top:0;">SQLite Connection Error</h2>
                    <p>Could not open SQLite database at <code>' . htmlspecialchars(SQLITE_FILE) . '</code>.</p>
                    <small style="color:#64748b;">Error: ' . htmlspecialchars($e->getMessage()) . '</small>
                </div>');
            }
            // Fall through to try MySQL if auto mode
        }
    }

    // Try MySQL Connection
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $mysqlEx) {
        // If MySQL fails and SQLite driver is available, initialize SQLite fallback
        if (in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            require_once dirname(__DIR__) . '/database/init_sqlite.php';
            $pdo = new PDO('sqlite:' . SQLITE_FILE);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        }

        die('<div style="font-family:sans-serif;padding:30px;line-height:1.6;max-width:650px;margin:40px auto;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;">
            <h2 style="color:#e11d48;margin-top:0;">Database Connection Error</h2>
            <p>Could not connect to MySQL database <code>' . htmlspecialchars(DB_NAME) . '</code> on <code>' . htmlspecialchars(DB_HOST) . '</code>.</p>
            <p><strong>Setup Instructions:</strong></p>
            <ol>
                <li>Run <code>php -c php.ini database/init_sqlite.php</code> to use local SQLite instantly.</li>
                <li>Or start your MySQL service and import <code>setup.sql</code>.</li>
            </ol>
            <small style="color:#64748b;">Error: ' . htmlspecialchars($mysqlEx->getMessage()) . '</small>
        </div>');
    }
}

/**
 * Fetch cached or direct site settings.
 */
function get_settings(): array {
    static $settings = null;
    if ($settings === null) {
        try {
            $db = get_db();
            $stmt = $db->query("SELECT * FROM `settings` WHERE `id` = 1 LIMIT 1");
            $settings = $stmt->fetch() ?: [];
        } catch (Exception $e) {
            $settings = [];
        }
    }
    return $settings;
}
