<?php
header('Content-Type: application/json; charset=utf-8'); // JSON 응답 명시
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '/var/www/config/db_connect.php';

$parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : null;

// 🚩 파라미터가 "없거나", "빈문자열"이거나, "null"이면 최상위
if (!isset($_GET['parent_id']) || $_GET['parent_id'] === '' || strtolower($_GET['parent_id']) === 'null') {
    $stmt = $conn->prepare("SELECT * FROM source_path WHERE parent_id IS NULL ORDER BY sort_order, name");
    $stmt->execute();
} else {
    $parent_id = intval($_GET['parent_id']);
    $stmt = $conn->prepare("SELECT * FROM source_path WHERE parent_id = ? ORDER BY sort_order, name");
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
}

$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = ['id' => $row['id'], 'name' => $row['name']];
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);


?>
