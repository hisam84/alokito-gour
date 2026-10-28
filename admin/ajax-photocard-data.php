<?php
/**
 * AJAX Data Endpoint for Facebook Photo Card Generator
 * Alokito Gour
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';

header('Content-Type: application/json; charset=UTF-8');

$action = trim($_GET['action'] ?? $_POST['action'] ?? '');
$db = get_db();

function format_card_date(string $dateStr): string {
    $timestamp = strtotime($dateStr);
    if ($timestamp === false) {
        $timestamp = time();
    }
    $monthsEn = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $monthsBn = ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];
    
    $dayNum = bn_number(date('d', $timestamp));
    $monthName = str_replace($monthsEn, $monthsBn, date('F', $timestamp));
    $yearNum = bn_number(date('Y', $timestamp));

    return "{$dayNum} {$monthName} {$yearNum}";
}

try {
    switch ($action) {
        case 'search_posts':
            $q = trim($_GET['q'] ?? '');
            $limit = 20;

            if ($q !== '') {
                $stmt = $db->prepare("
                    SELECT p.id, p.title, p.thumbnail, p.created_at, c.name AS category_name
                    FROM posts p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.title LIKE :query OR p.id = :id_search
                    ORDER BY p.id DESC
                    LIMIT :limit
                ");
                $stmt->bindValue(':query', '%' . $q . '%', PDO::PARAM_STR);
                $stmt->bindValue(':id_search', is_numeric($q) ? (int)$q : 0, PDO::PARAM_INT);
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            } else {
                $stmt = $db->prepare("
                    SELECT p.id, p.title, p.thumbnail, p.created_at, c.name AS category_name
                    FROM posts p
                    LEFT JOIN categories c ON p.category_id = c.id
                    ORDER BY p.id DESC
                    LIMIT :limit
                ");
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            }

            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $result = array_map(function ($post) {
                return [
                    'id'            => (int)$post['id'],
                    'title'         => $post['title'],
                    'thumbnail'     => $post['thumbnail'] ?: '',
                    'category_name' => $post['category_name'] ?? 'সাধারণ',
                    'date_bn'       => format_card_date($post['created_at']),
                    'created_at'    => $post['created_at'],
                ];
            }, $posts);

            echo json_encode(['success' => true, 'posts' => $result]);
            break;

        case 'get_post':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid article ID']);
                exit;
            }

            $stmt = $db->prepare("
                SELECT p.id, p.title, p.thumbnail, p.created_at, c.name AS category_name
                FROM posts p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$post) {
                echo json_encode(['success' => false, 'message' => 'Article not found']);
                exit;
            }

            echo json_encode([
                'success' => true,
                'post'    => [
                    'id'            => (int)$post['id'],
                    'title'         => $post['title'],
                    'thumbnail'     => $post['thumbnail'] ?: '',
                    'category_name' => $post['category_name'] ?? 'সাধারণ',
                    'date_bn'       => format_card_date($post['created_at']),
                    'created_at'    => $post['created_at'],
                ]
            ]);
            break;

        case 'get_ads':
            $stmt = $db->query("
                SELECT id, title, image_path, position, target_url, status
                FROM advertisements
                WHERE status = 1 AND image_path IS NOT NULL AND image_path != ''
                ORDER BY id DESC
            ");
            $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'ads' => $ads]);
            break;

        case 'upload_ad':
            $title = trim($_POST['title'] ?? 'Photo Card Ad ' . date('d/m/Y H:i'));
            $targetUrl = trim($_POST['target_url'] ?? '#');
            $imagePath = null;

            if (!empty($_FILES['image']['name'])) {
                $uploaded = upload_image_webp($_FILES['image'], 'assets/uploads/ads/');
                if ($uploaded) {
                    $imagePath = $uploaded;
                }
            }

            if (!$imagePath) {
                echo json_encode(['success' => false, 'message' => 'Failed to upload ad image file.']);
                exit;
            }

            $stmt = $db->prepare("
                INSERT INTO advertisements (title, image_path, target_url, position, status)
                VALUES (:title, :image_path, :target_url, 'photocard', 1)
            ");
            $stmt->execute([
                ':title'      => $title,
                ':image_path' => $imagePath,
                ':target_url' => $targetUrl,
            ]);

            $newAdId = (int)$db->lastInsertId();

            echo json_encode([
                'success' => true,
                'ad' => [
                    'id'         => $newAdId,
                    'title'      => $title,
                    'image_path' => $imagePath,
                    'position'   => 'photocard',
                    'target_url' => $targetUrl,
                ]
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
