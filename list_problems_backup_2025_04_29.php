<?php
header('Content-Type: application/json; charset=UTF-8');

$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
  echo json_encode(['success' => false, 'message' => 'DB 연결 실패']);
  exit;
}

$sql = "SELECT id, title, path_text, type, difficulty, category, source, tags, created_at, origin_id, copied_by FROM problems ORDER BY id DESC";
$result = $conn->query($sql);

if (!$result) {
  echo json_encode(['success' => false, 'message' => '쿼리 실패']);
  exit;
}

$problems = [];
while ($row = $result->fetch_assoc()) {
  $problems[] = $row;
}

echo json_encode(['success' => true, 'data' => $problems]);

$conn->close();
?>
