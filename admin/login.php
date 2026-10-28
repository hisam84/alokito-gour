<?php
/**
 * Admin Login
 * Alokito Gour Clone
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

// Redirect if already authenticated
if (isset($_SESSION['admin_user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// Generate CSRF token for login form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $error = 'নিরাপত্তা যাচাই ব্যর্থ হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'অনুগ্রহ করে ইমেইল ও পাসওয়ার্ড প্রদান করুন।';
        } else {
            try {
                $db = get_db();
                $stmt = $db->prepare("SELECT * FROM `users` WHERE `email` = :email AND `status` = 1 LIMIT 1");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Session Fixation Defense
                    session_regenerate_id(true);
                    $_SESSION['admin_user_id'] = $user['id'];
                    $_SESSION['admin_user_name'] = $user['name'];
                    $_SESSION['admin_user_email'] = $user['email'];
                    $_SESSION['admin_user_role'] = $user['role'];

                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'প্রদত্ত ইমেইল বা পাসওয়ার্ড সঠিক নয়!';
                }
            } catch (Exception $e) {
                $error = 'সার্ভার সমস্যা: ' . $e->getMessage();
            }
        }
    }
}

$settings = get_settings();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অ্যাডমিন লগইন | <?= e($settings['site_title'] ?? 'আলোকিত গৌড়') ?></title>
    <!-- SolaimanLipi Font -->
    <link href="https://fonts.maateen.me/solaiman-lipi/font.css" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'SolaimanLipi', sans-serif;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            max-width: 420px;
            width: 100%;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        .login-header {
            background: #ffffff;
            padding: 30px 30px 10px;
            text-align: center;
        }
        .login-header img {
            max-height: 55px;
            margin-bottom: 12px;
        }
        .login-body {
            padding: 20px 30px 35px;
        }
        .btn-brand {
            background-color: #f3730d;
            border-color: #f3730d;
            color: #ffffff;
            font-weight: 600;
        }
        .btn-brand:hover {
            background-color: #d96206;
            border-color: #d96206;
            color: #ffffff;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <?php if (!empty($settings['logo_path'])): ?>
            <img src="<?= e($settings['logo_path']) ?>" alt="Logo">
        <?php else: ?>
            <h4 class="fw-bold text-dark mb-0">আলোকিত গৌড়</h4>
        <?php endif; ?>
        <p class="text-muted small mt-2 mb-0">অ্যাডমিন কন্ট্রোল প্যানেল লগইন</p>
    </div>

    <div class="login-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 px-3 small mb-3">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

            <div class="mb-3">
                <label for="email" class="form-label text-secondary small fw-medium">ইমেইল ঠিকানা</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email" value="<?= e($_POST['email'] ?? 'admin@alokitogour.com') ?>" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label text-secondary small fw-medium">পাসওয়ার্ড</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="bi bi-shield-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                </div>
                <div class="form-text small text-muted">ডিফল্ট পাসওয়ার্ড: <code>admin123</code></div>
            </div>

            <button type="submit" class="btn btn-brand w-100 py-2">
                <i class="bi bi-box-arrow-in-right me-1"></i> লগইন করুন
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="../index.php" class="text-decoration-none small text-muted">
                <i class="bi bi-arrow-left me-1"></i> মূল ওয়েবসাইটে ফিরে যান
            </a>
        </div>
    </div>
</div>

</body>
</html>
