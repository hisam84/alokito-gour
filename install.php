<?php
/**
 * Turnkey Web Installer & Setup Wizard
 * Commercial Multi-Client News Portal & Magazine CMS Template
 */

declare(strict_types=1);

$lockFile = __DIR__ . '/config/installed.lock';
$isLocked = file_exists($lockFile);

$step = (int)($_GET['step'] ?? 1);
$error = '';
$success = '';

// Check Server Requirements
$reqs = [
    'PHP >= 8.1'            => version_compare(PHP_VERSION, '8.1.0', '>='),
    'PDO Extension'         => extension_loaded('pdo'),
    'PDO MySQL / SQLite'    => extension_loaded('pdo_mysql') || extension_loaded('pdo_sqlite'),
    'GD Image Library'      => extension_loaded('gd'),
    'MBString Extension'    => extension_loaded('mbstring'),
    'JSON Extension'        => extension_loaded('json'),
    'cURL Extension'        => extension_loaded('curl'),
    'Writable uploads/'     => is_writable(__DIR__ . '/assets/uploads') || @mkdir(__DIR__ . '/assets/uploads', 0755, true),
    'Writable config/'      => is_writable(__DIR__ . '/config'),
    'Writable database/'    => is_writable(__DIR__ . '/database') || @mkdir(__DIR__ . '/database', 0755, true),
];

$allReqsPassed = !in_array(false, $reqs, true);

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLocked) {
    $action = $_POST['action'] ?? '';

    // Step 2 Test DB Connection
    if ($action === 'test_db') {
        header('Content-Type: application/json');
        $driver = $_POST['driver'] ?? 'mysql';
        if ($driver === 'sqlite') {
            echo json_encode(['success' => true, 'message' => 'SQLite is ready to use instantly.']);
            exit;
        }

        $host = trim($_POST['db_host'] ?? '127.0.0.1');
        $port = trim($_POST['db_port'] ?? '3306');
        $name = trim($_POST['db_name'] ?? '');
        $user = trim($_POST['db_user'] ?? 'root');
        $pass = $_POST['db_pass'] ?? '';

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            $testPdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            echo json_encode(['success' => true, 'message' => 'Database connection successful!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
        }
        exit;
    }

    // Step 5: Finalize Installation
    if ($action === 'execute_install') {
        $driver = $_POST['driver'] ?? 'mysql';
        $dbHost = trim($_POST['db_host'] ?? '127.0.0.1');
        $dbPort = trim($_POST['db_port'] ?? '3306');
        $dbName = trim($_POST['db_name'] ?? 'news_portal');
        $dbUser = trim($_POST['db_user'] ?? 'root');
        $dbPass = $_POST['db_pass'] ?? '';

        $siteTitle = trim($_POST['site_title'] ?? 'বাংলা অনলাইন নিউজ পোর্টাল');
        $siteTagline = trim($_POST['site_tagline'] ?? 'সত্য ও বস্তুনিষ্ঠ সংবাদের বিশ্বস্ত ঠিকানা');
        $primaryColor = trim($_POST['primary_color'] ?? '#f3730d');
        $defaultDivision = trim($_POST['default_division'] ?? 'ঢাকা');
        $defaultDistrict = trim($_POST['default_district'] ?? 'ঢাকা');

        $adminName = trim($_POST['admin_name'] ?? 'Super Admin');
        $adminEmail = trim($_POST['admin_email'] ?? 'admin@example.com');
        $adminPassword = $_POST['admin_password'] ?? '';
        $importDemo = isset($_POST['import_demo']) ? 1 : 0;

        if (empty($adminEmail) || empty($adminPassword)) {
            $error = 'Admin email and password are required.';
        } else {
            try {
                // 1. Establish PDO
                if ($driver === 'sqlite') {
                    $sqliteFile = __DIR__ . '/database/alokito_gour.sqlite';
                    $pdo = new PDO('sqlite:' . $sqliteFile);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                } else {
                    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
                    $pdo = new PDO($dsn, $dbUser, $dbPass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);
                }

                // 2. Run Migration & Setup
                require_once __DIR__ . '/database/auto_migrate.php';
                run_template_migrations($pdo);

                // 3. Update Settings Table with buyer's identity
                $upSettings = $pdo->prepare("
                    UPDATE `settings` SET 
                        `site_title` = :title,
                        `site_tagline` = :tagline,
                        `primary_color` = :pcolor,
                        `default_division` = :ddiv,
                        `default_district` = :ddist
                    WHERE `id` = 1
                ");
                $upSettings->execute([
                    ':title'   => $siteTitle,
                    ':tagline' => $siteTagline,
                    ':pcolor'  => $primaryColor,
                    ':ddiv'    => $defaultDivision,
                    ':ddist'   => $defaultDistrict,
                ]);

                // 4. Create / Update Superadmin Account
                $hashedPass = password_hash($adminPassword, PASSWORD_BCRYPT);
                $chkUser = $pdo->prepare("SELECT id FROM `users` WHERE `email` = :email LIMIT 1");
                $chkUser->execute([':email' => $adminEmail]);
                $existingId = $chkUser->fetchColumn();

                if ($existingId) {
                    $upUser = $pdo->prepare("UPDATE `users` SET `name` = :name, `password` = :pass, `role` = 'superadmin', `status` = 1 WHERE `id` = :id");
                    $upUser->execute([':name' => $adminName, ':pass' => $hashedPass, ':id' => $existingId]);
                } else {
                    $insUser = $pdo->prepare("INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`) VALUES (:name, :email, :pass, 'superadmin', 1)");
                    $insUser->execute([':name' => $adminName, ':email' => $adminEmail, ':pass' => $hashedPass]);
                }

                // 5. Write `config/db_custom.php`
                $customConfig = "<?php\n"
                    . "// Generated by Web Installer\n"
                    . "define('DB_DRIVER', " . var_export($driver, true) . ");\n"
                    . "define('DB_HOST', " . var_export($dbHost, true) . ");\n"
                    . "define('DB_PORT', " . var_export($dbPort, true) . ");\n"
                    . "define('DB_NAME', " . var_export($dbName, true) . ");\n"
                    . "define('DB_USER', " . var_export($dbUser, true) . ");\n"
                    . "define('DB_PASS', " . var_export($dbPass, true) . ");\n";
                file_put_contents(__DIR__ . '/config/db_custom.php', $customConfig);

                // 6. Lock Installer
                file_put_contents($lockFile, "Installed on " . date('Y-m-d H:i:s') . "\nAdmin: " . $adminEmail . "\n");

                $step = 6; // Success step!
            } catch (Exception $e) {
                $error = 'Installation Error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Installer & Setup Wizard | Turnkey News Portal CMS</title>
    <!-- SolaimanLipi Font -->
    <link href="https://fonts.maateen.me/solaiman-lipi/font.css" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
        }
        .installer-card {
            max-width: 780px;
            width: 100%;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.4);
            overflow: hidden;
        }
        .installer-header {
            background: #ffffff;
            border-bottom: 2px solid #f1f5f9;
            padding: 24px 32px;
        }
        .installer-body {
            padding: 32px;
        }
        .step-pill {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            background: #e2e8f0;
            color: #64748b;
        }
        .step-pill.active {
            background: #f3730d;
            color: #ffffff;
        }
        .step-pill.completed {
            background: #10b981;
            color: #ffffff;
        }
    </style>
</head>
<body>

<div class="installer-card">
    <div class="installer-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-warning bg-opacity-10 p-2 rounded-3 text-warning">
                <i class="bi bi-newspaper fs-3"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0 text-dark">Turnkey News Portal Installer</h4>
                <small class="text-muted">White-Label Commercial Deployment Wizard</small>
            </div>
        </div>
        <span class="badge bg-dark px-3 py-2">Version 2.0.0</span>
    </div>

    <div class="installer-body">
        <?php if ($isLocked && $step !== 6): ?>
            <!-- Already Installed Lock Warning -->
            <div class="text-center py-4">
                <div class="display-3 text-warning mb-3"><i class="bi bi-shield-lock-fill"></i></div>
                <h4 class="fw-bold text-dark">System Already Installed & Locked</h4>
                <p class="text-muted max-w-md mx-auto mb-4" style="max-width: 500px;">
                    This News Portal CMS is already configured and locked for security. If you need to re-run this setup wizard, please manually delete the file <code>config/installed.lock</code> from your server.
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="index.php" class="btn btn-outline-secondary px-4"><i class="bi bi-house me-1"></i> Visit Website</a>
                    <a href="admin/login.php" class="btn btn-warning text-white fw-bold px-4"><i class="bi bi-lock me-1"></i> Admin Login</a>
                </div>
            </div>
        <?php else: ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 small mb-4">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
                </div>
            <?php endif; ?>

            <!-- Wizard Step Navigator -->
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom text-center">
                <div class="d-flex align-items-center gap-2">
                    <span class="step-pill <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : '' ?>">1</span>
                    <span class="small fw-bold d-none d-sm-inline">System Check</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="step-pill <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' ?>">2</span>
                    <span class="small fw-bold d-none d-sm-inline">Database</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="step-pill <?= $step >= 3 ? ($step > 3 ? 'completed' : 'active') : '' ?>">3</span>
                    <span class="small fw-bold d-none d-sm-inline">Site Identity</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="step-pill <?= $step >= 4 ? ($step > 4 ? 'completed' : 'active') : '' ?>">4</span>
                    <span class="small fw-bold d-none d-sm-inline">Admin Account</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="step-pill <?= $step >= 5 ? ($step >= 6 ? 'completed' : 'active') : '' ?>">5</span>
                    <span class="small fw-bold d-none d-sm-inline">Ready!</span>
                </div>
            </div>

            <!-- STEP 1: REQUIREMENTS CHECK -->
            <?php if ($step === 1): ?>
                <h5 class="fw-bold text-dark mb-3">Step 1: Hosting & Server Compatibility Check</h5>
                <p class="text-muted small mb-4">We are verifying that your hosting server meets all performance, database, and security requirements.</p>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light small">
                            <tr>
                                <th>Requirement / Extension</th>
                                <th class="text-center" style="width: 140px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reqs as $name => $passed): ?>
                                <tr>
                                    <td class="fw-medium text-dark small"><?= e($name) ?></td>
                                    <td class="text-center">
                                        <?php if ($passed): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i> Passed</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-x-circle-fill me-1"></i> Missing</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-muted">PHP Version: <strong><?= PHP_VERSION ?></strong></span>
                    <?php if ($allReqsPassed): ?>
                        <a href="install.php?step=2" class="btn btn-warning text-white fw-bold px-4 py-2 shadow-sm">
                            Next: Configure Database &raquo;
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary px-4 py-2" disabled>Please fix requirements above</button>
                    <?php endif; ?>
                </div>

            <!-- STEP 2: DATABASE CONFIG -->
            <?php elseif ($step === 2): ?>
                <h5 class="fw-bold text-dark mb-3">Step 2: Database Connection Setup</h5>
                <p class="text-muted small mb-4">Enter your MySQL database credentials (found in cPanel MySQL Databases) or select SQLite for instant zero-config testing.</p>

                <form method="POST" action="install.php?step=3" id="db_form">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Database Driver</label>
                        <select class="form-select" name="driver" id="db_driver" onchange="toggleDbFields()">
                            <option value="mysql" selected>MySQL / MariaDB (Recommended for cPanel Hosting)</option>
                            <option value="sqlite">SQLite 3 (Instant Zero-Config Development)</option>
                        </select>
                    </div>

                    <div id="mysql_fields" class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-secondary">Database Host</label>
                            <input type="text" class="form-control" name="db_host" id="db_host" value="127.0.0.1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-secondary">Port</label>
                            <input type="text" class="form-control" name="db_port" id="db_port" value="3306" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-secondary">Database Name</label>
                            <input type="text" class="form-control" name="db_name" id="db_name" value="news_portal" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Database Username</label>
                            <input type="text" class="form-control" name="db_user" id="db_user" value="root" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Database Password</label>
                            <input type="password" class="form-control" name="db_pass" id="db_pass" placeholder="Leave empty if none">
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-dark btn-sm" onclick="testDbConnection()" id="btn_test_db">
                            <i class="bi bi-lightning-charge me-1"></i> Test Database Connection
                        </button>
                        <div id="db_test_status" class="mt-2" style="display:none;"></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <a href="install.php?step=1" class="btn btn-outline-secondary">&laquo; Back</a>
                        <button type="submit" class="btn btn-warning text-white fw-bold px-4 py-2">
                            Next: Portal Identity &raquo;
                        </button>
                    </div>
                </form>

            <!-- STEP 3: SITE IDENTITY & CUSTOMIZER -->
            <?php elseif ($step === 3): ?>
                <h5 class="fw-bold text-dark mb-3">Step 3: Portal Identity & Theme Customization</h5>
                <p class="text-muted small mb-4">Set your brand name, tagline, primary color, and default regional preference (all fully editable later in Admin).</p>

                <form method="POST" action="install.php?step=4">
                    <!-- Forward DB config -->
                    <input type="hidden" name="driver" value="<?= e($_POST['driver'] ?? 'mysql') ?>">
                    <input type="hidden" name="db_host" value="<?= e($_POST['db_host'] ?? '127.0.0.1') ?>">
                    <input type="hidden" name="db_port" value="<?= e($_POST['db_port'] ?? '3306') ?>">
                    <input type="hidden" name="db_name" value="<?= e($_POST['db_name'] ?? 'news_portal') ?>">
                    <input type="hidden" name="db_user" value="<?= e($_POST['db_user'] ?? 'root') ?>">
                    <input type="hidden" name="db_pass" value="<?= e($_POST['db_pass'] ?? '') ?>">

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-secondary">পোর্টালের নাম (Portal Name) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="site_title" value="বাংলা অনলাইন নিউজ পোর্টাল" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-secondary">ট্যাগলাইন / স্লোগান (Tagline)</label>
                            <input type="text" class="form-control" name="site_tagline" value="সত্য ও বস্তুনিষ্ঠ সংবাদের বিশ্বস্ত ঠিকানা">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">প্রাইমারি ব্র্যান্ড রঙ (Primary Color)</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" value="#f3730d" id="pcolor_picker" onchange="document.getElementById('pcolor_text').value = this.value">
                                <input type="text" class="form-control font-monospace" name="primary_color" id="pcolor_text" value="#f3730d">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">প্রধান বিভাগ (Primary Division)</label>
                            <select class="form-select" name="default_division">
                                <option value="ঢাকা" selected>ঢাকা বিভাগ</option>
                                <option value="চট্টগ্রাম">চট্টগ্রাম বিভাগ</option>
                                <option value="রাজশাহী">রাজশাহী বিভাগ</option>
                                <option value="খুলনা">খুলনা বিভাগ</option>
                                <option value="বরিশাল">বরিশাল বিভাগ</option>
                                <option value="সিলেট">সিলেট বিভাগ</option>
                                <option value="রংপুর">রংপুর বিভাগ</option>
                                <option value="ময়মনসিংহ">ময়মনসিংহ বিভাগ</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-secondary">প্রধান জেলা (Primary District)</label>
                            <input type="text" class="form-control" name="default_district" value="ঢাকা" placeholder="যেমন: ঢাকা, সিলেট, কুমিল্লা, বগুড়া ইত্যাদি">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <a href="install.php?step=2" class="btn btn-outline-secondary">&laquo; Back</a>
                        <button type="submit" class="btn btn-warning text-white fw-bold px-4 py-2">
                            Next: Superadmin Account &raquo;
                        </button>
                    </div>
                </form>

            <!-- STEP 4: ADMIN ACCOUNT -->
            <?php elseif ($step === 4): ?>
                <h5 class="fw-bold text-dark mb-3">Step 4: Create Superadmin Account</h5>
                <p class="text-muted small mb-4">Set up the master administrator account that will have full access to the control panel.</p>

                <form method="POST" action="install.php?step=5">
                    <!-- Forward previous inputs -->
                    <input type="hidden" name="driver" value="<?= e($_POST['driver'] ?? 'mysql') ?>">
                    <input type="hidden" name="db_host" value="<?= e($_POST['db_host'] ?? '127.0.0.1') ?>">
                    <input type="hidden" name="db_port" value="<?= e($_POST['db_port'] ?? '3306') ?>">
                    <input type="hidden" name="db_name" value="<?= e($_POST['db_name'] ?? 'news_portal') ?>">
                    <input type="hidden" name="db_user" value="<?= e($_POST['db_user'] ?? 'root') ?>">
                    <input type="hidden" name="db_pass" value="<?= e($_POST['db_pass'] ?? '') ?>">
                    <input type="hidden" name="site_title" value="<?= e($_POST['site_title'] ?? '') ?>">
                    <input type="hidden" name="site_tagline" value="<?= e($_POST['site_tagline'] ?? '') ?>">
                    <input type="hidden" name="primary_color" value="<?= e($_POST['primary_color'] ?? '') ?>">
                    <input type="hidden" name="default_division" value="<?= e($_POST['default_division'] ?? '') ?>">
                    <input type="hidden" name="default_district" value="<?= e($_POST['default_district'] ?? '') ?>">

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-secondary">Administrator Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="admin_name" value="প্রধান সম্পাদক" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-secondary">Login Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="admin_email" value="admin@example.com" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-secondary">Login Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="admin_password" placeholder="Create a strong password" required minlength="6">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <a href="install.php?step=3" class="btn btn-outline-secondary">&laquo; Back</a>
                        <button type="submit" class="btn btn-warning text-white fw-bold px-4 py-2">
                            Next: Final Confirmation &raquo;
                        </button>
                    </div>
                </form>

            <!-- STEP 5: FINAL CONFIRMATION -->
            <?php elseif ($step === 5): ?>
                <h5 class="fw-bold text-dark mb-3">Step 5: Ready to Install & Launch</h5>
                <p class="text-muted small mb-4">Review your settings below and click "Execute Installation" to complete setup.</p>

                <form method="POST" action="install.php">
                    <input type="hidden" name="action" value="execute_install">
                    <!-- Forward all values -->
                    <input type="hidden" name="driver" value="<?= e($_POST['driver'] ?? 'mysql') ?>">
                    <input type="hidden" name="db_host" value="<?= e($_POST['db_host'] ?? '127.0.0.1') ?>">
                    <input type="hidden" name="db_port" value="<?= e($_POST['db_port'] ?? '3306') ?>">
                    <input type="hidden" name="db_name" value="<?= e($_POST['db_name'] ?? 'news_portal') ?>">
                    <input type="hidden" name="db_user" value="<?= e($_POST['db_user'] ?? 'root') ?>">
                    <input type="hidden" name="db_pass" value="<?= e($_POST['db_pass'] ?? '') ?>">
                    <input type="hidden" name="site_title" value="<?= e($_POST['site_title'] ?? '') ?>">
                    <input type="hidden" name="site_tagline" value="<?= e($_POST['site_tagline'] ?? '') ?>">
                    <input type="hidden" name="primary_color" value="<?= e($_POST['primary_color'] ?? '') ?>">
                    <input type="hidden" name="default_division" value="<?= e($_POST['default_division'] ?? '') ?>">
                    <input type="hidden" name="default_district" value="<?= e($_POST['default_district'] ?? '') ?>">
                    <input type="hidden" name="admin_name" value="<?= e($_POST['admin_name'] ?? '') ?>">
                    <input type="hidden" name="admin_email" value="<?= e($_POST['admin_email'] ?? '') ?>">
                    <input type="hidden" name="admin_password" value="<?= e($_POST['admin_password'] ?? '') ?>">

                    <div class="bg-light p-4 rounded-3 border mb-4">
                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Installation Summary</h6>
                        <ul class="list-unstyled mb-0 small">
                            <li class="mb-2"><strong>Portal Title:</strong> <?= e($_POST['site_title'] ?? '') ?></li>
                            <li class="mb-2"><strong>Database:</strong> <?= e($_POST['driver'] ?? 'mysql') ?> (<?= e($_POST['db_name'] ?? '') ?>)</li>
                            <li class="mb-2"><strong>Brand Color:</strong> <span class="badge" style="background:<?= e($_POST['primary_color'] ?? '#f3730d') ?>; color:#fff;"><?= e($_POST['primary_color'] ?? '') ?></span></li>
                            <li class="mb-2"><strong>Primary Region:</strong> <?= e($_POST['default_district'] ?? '') ?>, <?= e($_POST['default_division'] ?? '') ?></li>
                            <li class="mb-0"><strong>Superadmin:</strong> <?= e($_POST['admin_name'] ?? '') ?> (<?= e($_POST['admin_email'] ?? '') ?>)</li>
                        </ul>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="import_demo" id="import_demo" value="1" checked>
                        <label class="form-check-label small fw-bold text-dark" for="import_demo">
                            ইনস্টলেশনের সাথে ডেমো সংবাদ ও ক্যাটাগরি যুক্ত করুন (Recommended)
                        </label>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="install.php?step=4" class="btn btn-outline-secondary">&laquo; Back</a>
                        <button type="submit" class="btn btn-success fw-bold px-5 py-2 shadow">
                            <i class="bi bi-rocket-takeoff me-1"></i> Execute Installation & Launch!
                        </button>
                    </div>
                </form>

            <!-- STEP 6: SUCCESS -->
            <?php elseif ($step === 6): ?>
                <div class="text-center py-4">
                    <div class="display-3 text-success mb-3"><i class="bi bi-check-circle-fill"></i></div>
                    <h3 class="fw-bold text-dark mb-2">Congratulations! Portal Installed Successfully</h3>
                    <p class="text-muted max-w-md mx-auto mb-4" style="max-width: 520px;">
                        Your turnkey commercial news portal has been successfully configured. Security lock file <code>config/installed.lock</code> has been created to prevent unauthorized re-installation.
                    </p>

                    <div class="bg-light p-3 rounded border mx-auto mb-4 text-start" style="max-width: 480px;">
                        <h6 class="fw-bold text-dark mb-2">Your Admin Credentials:</h6>
                        <p class="mb-1 small"><strong>Email:</strong> <?= e($_POST['admin_email'] ?? 'admin@example.com') ?></p>
                        <p class="mb-0 small"><strong>Password:</strong> <em>(The password you entered in Step 4)</em></p>
                    </div>

                    <div class="d-flex justify-content-center gap-3">
                        <a href="index.php" class="btn btn-outline-secondary px-4 py-2"><i class="bi bi-globe me-1"></i> View Live Portal</a>
                        <a href="admin/login.php" class="btn btn-warning text-white fw-bold px-4 py-2 shadow"><i class="bi bi-speedometer2 me-1"></i> Login to Admin Panel</a>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<script>
function toggleDbFields() {
    var driver = document.getElementById('db_driver').value;
    var mysqlDiv = document.getElementById('mysql_fields');
    mysqlDiv.style.display = (driver === 'sqlite') ? 'none' : 'flex';
}

function testDbConnection() {
    var driver = document.getElementById('db_driver').value;
    var host = document.getElementById('db_host').value;
    var port = document.getElementById('db_port').value;
    var name = document.getElementById('db_name').value;
    var user = document.getElementById('db_user').value;
    var pass = document.getElementById('db_pass').value;

    var btn = document.getElementById('btn_test_db');
    var statusDiv = document.getElementById('db_test_status');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Testing...';
    statusDiv.style.display = 'none';

    var fd = new FormData();
    fd.append('action', 'test_db');
    fd.append('driver', driver);
    fd.append('db_host', host);
    fd.append('db_port', port);
    fd.append('db_name', name);
    fd.append('db_user', user);
    fd.append('db_pass', pass);

    fetch('install.php', {
        method: 'POST',
        body: fd
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-lightning-charge me-1"></i> Test Database Connection';
        statusDiv.style.display = 'block';
        if (data.success) {
            statusDiv.className = 'alert alert-success py-2 small mb-0 mt-2';
            statusDiv.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>' + data.message;
        } else {
            statusDiv.className = 'alert alert-danger py-2 small mb-0 mt-2';
            statusDiv.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>' + data.message;
        }
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-lightning-charge me-1"></i> Test Database Connection';
        statusDiv.style.display = 'block';
        statusDiv.className = 'alert alert-danger py-2 small mb-0 mt-2';
        statusDiv.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>Connection error.';
    });
}
</script>

</body>
</html>
