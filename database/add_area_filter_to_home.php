<?php
/**
 * Migration: Add "আমার এলাকার খবর" (district_filter) to homepage layout if not present
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';

$pdo = get_db();

$stmt = $pdo->prepare("SELECT id, layout_data FROM page_layouts WHERE page_slug = ?");
$stmt->execute(['home']);
$row = $stmt->fetch();

if (!$row) {
    echo "No home layout found.\n";
    exit;
}

$layout = json_decode($row['layout_data'], true);
if (!is_array($layout)) {
    echo "Invalid layout JSON.\n";
    exit;
}

$hasDistrictFilter = false;
foreach ($layout as $w) {
    if (($w['type'] ?? '') === 'district_filter') {
        $hasDistrictFilter = true;
        break;
    }
}

if (!$hasDistrictFilter) {
    $newWidget = [
        'id' => 'sec_area_filter',
        'type' => 'district_filter',
        'title' => 'আমার এলাকার খবর',
        'container' => 'main',
        'settings' => []
    ];

    // Find position of ad_banner (after_lead) or insert at index 2
    $insertIndex = 2;
    foreach ($layout as $idx => $w) {
        if (($w['type'] ?? '') === 'ad_banner') {
            $insertIndex = $idx + 1;
            break;
        }
    }

    array_splice($layout, $insertIndex, 0, [$newWidget]);

    $newJson = json_encode($layout, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $up = $pdo->prepare("UPDATE page_layouts SET layout_data = ? WHERE id = ?");
    $up->execute([$newJson, $row['id']]);

    echo "Successfully added 'district_filter' (আমার এলাকার খবর) to homepage layout at index $insertIndex.\n";
} else {
    echo "'district_filter' widget already exists in homepage layout.\n";
}
