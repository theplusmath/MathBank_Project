<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB 연결 실패']);
    exit;
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => '문제 ID가 없습니다.']);
    exit;
}

$sql = "SELECT * FROM history_problems WHERE problem_id = $id ORDER BY updated_at DESC";
$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => $data
]);
?>
