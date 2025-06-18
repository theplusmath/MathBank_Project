<?php
// 오류 표시 설정
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("/var/www/config/db_connect.php"); // $conn 사용

header('Content-Type: application/json; charset=utf-8');

// MySQLi 쿼리
$sql = "SELECT id, parent_id, name, depth, sort_order FROM paths ORDER BY sort_order ASC, id ASC";
$result = $conn->query($sql);

$paths = [];
while ($row = $result->fetch_assoc()) {
    $paths[] = [
        'id' => (int)$row['id'],
        'parent_id' => is_null($row['parent_id']) ? null : (int)$row['parent_id'],
        'name' => $row['name'],
        'depth' => (int)$row['depth'],
        'sort_order' => (int)$row['sort_order']
    ];
}

echo json_encode($paths, JSON_UNESCAPED_UNICODE);
