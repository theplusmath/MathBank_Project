<?php
header('Content-Type: application/json; charset=utf-8');
require_once '/var/www/config/db_connect.php';

// 모든 source_path 가져오기
$sql = "SELECT id, parent_id, name FROM source_path ORDER BY sort_order, id";
$result = $conn->query($sql);
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = [
        'id' => (int)$row['id'],
        'parent_id' => is_null($row['parent_id']) ? null : (int)$row['parent_id'],
        'name' => $row['name']
    ];
}

// 트리 구조로 변환
function buildTree($items, $parentId = null) {
    $branch = [];
    foreach ($items as $item) {
        if ($item['parent_id'] === $parentId) {
            $children = buildTree($items, $item['id']);
            if ($children) {
                $item['children'] = $children;
            }
            $branch[] = $item;
        }
    }
    return $branch;
}
$tree = buildTree($rows);
echo json_encode($tree, JSON_UNESCAPED_UNICODE);
?>
