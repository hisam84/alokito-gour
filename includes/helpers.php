<?php
/**
 * Utility Helpers Library
 * Alokito Gour Clone
 */

declare(strict_types=1);

/**
 * Escape HTML output against XSS.
 */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Convert English digits to Bengali digits.
 */
function bn_number($number): string {
    $en = ['0','1','2','3','4','5','6','7','8','9'];
    $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
    return str_replace($en, $bn, (string)$number);
}

/**
 * Format datetime to Bengali Date.
 * e.g., 'বুধবার, ২৮ অক্টোবর ২০২৬, ১২:৩০ মিনিট'
 */
function bn_date($dateStr, bool $showTime = false): string {
    if (!$dateStr) return '';
    $timestamp = is_numeric($dateStr) ? (int)$dateStr : strtotime((string)$dateStr);
    if ($timestamp === false) return '';

    $daysEn = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $daysBn = ['শনিবার', 'রবিবার', 'সোমবার', 'মঙ্গলবার', 'বুধবার', 'বৃহস্পতিবার', 'শুক্রবার'];

    $monthsEn = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $monthsBn = ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];

    $dayName = str_replace($daysEn, $daysBn, date('l', $timestamp));
    $dayNum = bn_number(date('d', $timestamp));
    $monthName = str_replace($monthsEn, $monthsBn, date('F', $timestamp));
    $yearNum = bn_number(date('Y', $timestamp));

    $result = "{$dayName}, {$dayNum} {$monthName} {$yearNum}";

    if ($showTime) {
        $hour = (int)date('H', $timestamp);
        $period = ($hour >= 18) ? 'রাত' : (($hour >= 15) ? 'বিকাল' : (($hour >= 12) ? 'দুপুর' : (($hour >= 6) ? 'সকাল' : 'রাত')));
        $timeStr = bn_number(date('h:i', $timestamp));
        $result .= ", {$period} {$timeStr}";
    }

    return $result;
}

/**
 * Relative time in Bengali (e.g., '১০ মিনিট আগে', '২ ঘন্টা আগে', '৩ দিন আগে').
 */
function get_time_ago($dateStr): string {
    if (!$dateStr) return '';
    $timestamp = is_numeric($dateStr) ? (int)$dateStr : strtotime((string)$dateStr);
    if ($timestamp === false) return '';

    $difference = time() - $timestamp;

    if ($difference < 60) {
        return 'এইমাত্র';
    } elseif ($difference < 3600) {
        $minutes = max(1, (int)round($difference / 60));
        return bn_number($minutes) . ' মিনিট আগে';
    } elseif ($difference < 86400) {
        $hours = (int)round($difference / 3600);
        return bn_number($hours) . ' ঘণ্টা আগে';
    } elseif ($difference < 604800) {
        $days = (int)round($difference / 86400);
        return bn_number($days) . ' দিন আগে';
    } else {
        return bn_date($dateStr, false);
    }
}

/**
 * Generate a clean URL-friendly slug supporting Bengali and English characters.
 */
function slugify(string $text): string {
    // Replace non-alphanumeric/non-bengali characters with dashes
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text ?: 'news-' . bin2hex(random_bytes(3)));
}

/**
 * Get active advertisement by position.
 */
function get_ad(string $position): ?array {
    try {
        $db = get_db();
        $stmt = $db->prepare("SELECT * FROM `advertisements` WHERE `position` = :pos AND `status` = 1 LIMIT 1");
        $stmt->execute([':pos' => $position]);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Render advertisement HTML widget.
 */
function render_ad(string $position): string {
    $ad = get_ad($position);
    if (!$ad || empty($ad['status'])) return '';

    if (!empty($ad['custom_code'])) {
        return '<div class="ad-container ad-' . e($position) . ' mb-3 text-center">' . $ad['custom_code'] . '</div>';
    }

    if (!empty($ad['image_path'])) {
        $target = !empty($ad['target_url']) ? e($ad['target_url']) : '#';
        return '<div class="ad-container ad-' . e($position) . ' mb-3 text-center">
            <a href="' . $target . '" target="_blank" rel="noopener" class="d-block w-100">
                <img src="' . e($ad['image_path']) . '" alt="' . e($ad['title'] ?? 'Advertisement') . '" class="img-fluid rounded border shadow-sm w-100" style="width: 100%; height: auto; display: block;">
            </a>
        </div>';
    }

    // If active but no image uploaded yet, keep the designated space placeholder
    if ($position === 'sidebar') {
        return '<div class="ad-container ad-sidebar mb-3 text-center">
            <div class="ad-placeholder border border-2 border-dashed bg-light text-muted rounded p-4 d-flex flex-column align-items-center justify-content-center shadow-sm" style="min-height: 280px;">
                <i class="bi bi-badge-ad fs-1 text-secondary opacity-50 mb-1"></i>
                <span class="fw-bold small text-secondary">বিজ্ঞাপনের স্থান</span>
                <span class="small text-muted" style="font-size: 11px;">(অ্যাডমিন প্যানেল থেকে আপলোড করলে প্রদর্শিত হবে)</span>
            </div>
        </div>';
    }

    return '';
}

/**
 * Upload image and convert to WebP format if GD is available.
 * Preserves animated GIF files directly as .gif to maintain frame animations.
 */
function upload_image_webp(array $file, string $targetDir = 'assets/uploads/news/', int $maxWidth = 1200): ?string {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // Ensure target directory exists
    $absoluteTargetDir = dirname(__DIR__) . '/' . trim($targetDir, '/') . '/';
    if (!is_dir($absoluteTargetDir)) {
        mkdir($absoluteTargetDir, 0755, true);
    }

    $mime = mime_content_type($file['tmp_name']);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mime, $allowedMimes, true)) {
        return null;
    }

    $prefix = (strpos($targetDir, 'ads') !== false) ? 'ad_' : 'img_';
    $uniqueName = $prefix . bin2hex(random_bytes(6));

    // Special handling for GIF: preserve original animated GIF without lossy conversion
    if ($mime === 'image/gif') {
        $destFilename = $uniqueName . '.gif';
        $destPath = $absoluteTargetDir . $destFilename;
        $success = is_uploaded_file($file['tmp_name']) ? move_uploaded_file($file['tmp_name'], $destPath) : copy($file['tmp_name'], $destPath);
        if ($success) {
            return '/' . trim($targetDir, '/') . '/' . $destFilename;
        }
        return null;
    }

    // Check if GD WebP conversion is available
    if (function_exists('imagewebp') && function_exists('imagecreatefromstring')) {
        $imageString = file_get_contents($file['tmp_name']);
        if ($imageString === false) return null;

        $srcImg = @imagecreatefromstring($imageString);
        if (!$srcImg) return null;

        $width = imagesx($srcImg);
        $height = imagesy($srcImg);

        // Resize if width exceeds maxWidth
        if ($width > $maxWidth) {
            $newHeight = (int)round(($height / $width) * $maxWidth);
            $dstImg = imagecreatetruecolor($maxWidth, $newHeight);
            imagealphablending($dstImg, false);
            imagesavealpha($dstImg, true);
            imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
            imagedestroy($srcImg);
            $srcImg = $dstImg;
        }

        $destFilename = $uniqueName . '.webp';
        $destPath = $absoluteTargetDir . $destFilename;
        imagewebp($srcImg, $destPath, 82);
        imagedestroy($srcImg);

        return '/' . trim($targetDir, '/') . '/' . $destFilename;
    } else {
        // Fallback: Save original image extension
        $ext = ($mime === 'image/png') ? '.png' : (($mime === 'image/webp') ? '.webp' : '.jpg');
        $destFilename = $uniqueName . $ext;
        $destPath = $absoluteTargetDir . $destFilename;
        $success = is_uploaded_file($file['tmp_name']) ? move_uploaded_file($file['tmp_name'], $destPath) : copy($file['tmp_name'], $destPath);
        if ($success) {
            return '/' . trim($targetDir, '/') . '/' . $destFilename;
        }
    }

    return null;
}
