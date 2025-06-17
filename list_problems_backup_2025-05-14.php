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


$searchScope = $_GET['scope'] ?? 'both'; // title, question, both
$searchOr = $_GET['or'] ?? '';
$searchNot = $_GET['not'] ?? '';
$invalidPath = $_GET['invalid_path'] ?? ''; // ✅ 경로 없음 필터 수신




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
// ✅ 경로 없는 문제 필터 조건
if ($invalidPath === '1') {
  $where[] = "(path_text IS NULL OR path_text = '')";
}



// ✅ OR 키워드 처리
if (!empty($searchOr)) {
  $orTerms = array_filter(array_map('trim', explode(',', $searchOr)));
  $orConditions = [];

  foreach ($orTerms as $term) {
    $escaped = '%' . $conn->real_escape_string($term) . '%';
    if ($searchScope === 'title') {
      $orConditions[] = "title LIKE '$escaped'";
    } elseif ($searchScope === 'question') {
      $orConditions[] = "question LIKE '$escaped'";
    } else {
      $orConditions[] = "(title LIKE '$escaped' OR question LIKE '$escaped')";
    }
  }

  if (!empty($orConditions)) {
    $where[] = '(' . implode(' OR ', $orConditions) . ')';
  }
}

// ✅ NOT 키워드 처리
if (!empty($searchNot)) {
  $notTerms = array_filter(array_map('trim', explode(',', $searchNot)));
  foreach ($notTerms as $term) {
    $escaped = '%' . $conn->real_escape_string($term) . '%';
    if ($searchScope === 'title') {
      $where[] = "title NOT LIKE '$escaped'";
    } elseif ($searchScope === 'question') {
      $where[] = "question NOT LIKE '$escaped'";
    } else {
      $where[] = "(title NOT LIKE '$escaped' AND question NOT LIKE '$escaped')";
    }
  }
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

