<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// DB 연결 (경로는 서버 환경에 맞게 조정)
require_once '/var/www/config/db_connect.php';

// parent_id 파라미터 처리
$parent_id = isset($_GET['parent_id']) ? $_GET['parent_id'] : null;

// 'null', 빈 문자열, 파라미터 미전달 → 최상위만
if (!isset($_GET['parent_id']) || $_GET['parent_id'] === '' || strtolower($_GET['parent_id']) === 'null') {
    $stmt = $conn->prepare("SELECT id, name FROM source_path WHERE parent_id IS NULL ORDER BY sort_order, name");
    $stmt->execute();
} else {
    $stmt = $conn->prepare("SELECT id, name FROM source_path WHERE parent_id = ? ORDER BY sort_order, name");
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
}

$result = $stmt->get_result();
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'id'   => $row['id'],
        'name' => $row['name']
    ];
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
