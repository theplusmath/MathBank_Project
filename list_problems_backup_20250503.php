<?php
header('Content-Type: application/json; charset=UTF-8');

// DB 연결
$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
  echo json_encode(['success' => false, 'message' => 'DB 연결 실패']);
  exit;
}

// ✅ 1. 검색 조건 수집
$where = [];
$params = [];

function escape($str) {
  return '%' . addslashes($str) . '%';
}

if (!empty($_GET['title'])) {
  if (!empty($_GET['include_question'])) {
    $where[] = "(title LIKE ? OR question LIKE ?)";
    $params[] = escape($_GET['title']);
    $params[] = escape($_GET['title']);
  } else {
    $where[] = "title LIKE ?";
    $params[] = escape($_GET['title']);
  }
}
if (!empty($_GET['tags'])) {
  $where[] = "tags LIKE ?";
  $params[] = escape($_GET['tags']);
}
if (!empty($_GET['source'])) {
  $where[] = "source = ?";
  $params[] = $_GET['source'];
}
if (!empty($_GET['path_text'])) {
  $where[] = "path_text LIKE ?";
  $params[] = escape($_GET['path_text']);
}
if (!empty($_GET['type'])) {
  $where[] = "type = ?";
  $params[] = $_GET['type'];
}
if (!empty($_GET['category'])) {
  $where[] = "category = ?";
  $params[] = $_GET['category'];
}
if (!empty($_GET['difficulty'])) {
  $where[] = "difficulty = ?";
  $params[] = $_GET['difficulty'];
}
if (!empty($_GET['copied_by'])) {
  $where[] = "copied_by = ?";
  $params[] = $_GET['copied_by'];
}

// ✅ 2. SQL 구성
$sql = "SELECT id, title, question, path_text, type, difficulty, category, source, tags, created_at, origin_id, copied_by FROM problems";

if (!empty($where)) {
  $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY id DESC";

// ✅ 3. SQL 실행
$stmt = $conn->prepare($sql);

if ($params) {
  $types = str_repeat("s", count($params));
  $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// ✅ 4. 결과 반환
$problems = [];
while ($row = $result->fetch_assoc()) {
  $problems[] = $row;
}

echo json_encode(['success' => true, 'data' => $problems]);

$stmt->close();
$conn->close();
?>

