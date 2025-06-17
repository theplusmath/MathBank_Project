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

$sql = "SELECT id, title, path_text, type, difficulty, category, source, tags, deleted_at, copied_by FROM deleted_problems ORDER BY deleted_at DESC";
$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['success' => false, 'message' => '쿼리 실패: ' . $conn->error]);
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => $data
]);
?>
