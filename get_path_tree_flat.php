<?php
header('Content-Type: application/json ; charset=utf-8' );

$conn = new mysqli("localhost", "theplusmath", "wnstj1205+", "theplusmath");
$conn->set_charset("utf8mb4");

// 모든 경로를 depth 순서대로 가져옴
$sql = "SELECT id, parent_id, name, depth FROM path ORDER BY depth, parent_id, id";
$result = $conn->query($sql);

$flat = [];

while ($row = $result->fetch_assoc()) {
    $flat[] = [
        'id' => (int)$row['id'],
        'parent_id' => (int)$row['parent_id'],
        'name' => $row['name'],
        'depth' => (int)$row['depth']
    ];
}

echo json_encode($flat, JSON_UNESCAPED_UNICODE);
$conn->close();
