<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "theplusmath", "wnstj1205+", "theplusmath");
$conn->set_charset("utf8mb4");

// 모든 경로 불러오기
$sql = "SELECT id, parent_id, name FROM path ORDER BY parent_id, id";
$result = $conn->query($sql);

$nodes = [];
$tree = [];

// id 기준으로 트리 구조 준비
while ($row = $result->fetch_assoc()) {
    $row['children'] = [];
    $nodes[$row['id']] = $row;
}

// 트리 구성
foreach ($nodes as $id => &$node) {
    if ($node['parent_id'] == 0) {
        $tree[$node['name']] = &$node['children'];
    } else {
        $parent = &$nodes[$node['parent_id']];
        $parent['children'][$node['name']] = &$node['children'];
    }
}

echo json_encode($tree, JSON_UNESCAPED_UNICODE);
$conn->close();
