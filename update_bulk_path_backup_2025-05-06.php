<?php
header('Content-Type: application/json; charset=UTF-8');

// DB 연결
$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
  echo json_encode(['success' => false, 'message' => 'DB 연결 실패']);
  exit;
}


// 입력 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);
$ids = $input['ids'] ?? [];

$newPathText = trim($input['path_text'] ?? '');
$newPathId = intval($input['path_id'] ?? 0);

// path_text가 빈 문자열이면 null로 바꿔 저장
if ($newPathText === '') {
  $newPathText = null;
}

// ❗path_text가 null이고 path_id가 있으면 자동으로 path_text 생성
if ($newPathText === null && $newPathId > 0) {
  $query = $conn->prepare("
    WITH RECURSIVE path_cte AS (
      SELECT id, parent_id, name
      FROM paths
      WHERE id = ?
      UNION ALL
      SELECT p.id, p.parent_id, p.name
      FROM paths p
      INNER JOIN path_cte c ON p.id = c.parent_id
    )
    SELECT GROUP_CONCAT(name ORDER BY id ASC SEPARATOR '/') AS full_path FROM path_cte
  ");
  $query->bind_param("i", $newPathId);
  $query->execute();
  $result = $query->get_result();
  if ($row = $result->fetch_assoc()) {
    $newPathText = $row['full_path'];
  } else {
    $newPathText = null;
  }
  $query->close();
}


 

// ID 정수 필터링
$ids = array_map('intval', $ids);
$placeholders = implode(',', array_fill(0, count($ids), '?'));

// SQL 준비
$sql = "UPDATE problems SET path_text = ?, path_id = ? WHERE id IN ($placeholders)";
$stmt = $conn->prepare($sql);

// 파라미터 바인딩
$types = 'si' . str_repeat('i', count($ids));
$params = array_merge([$newPathText, $newPathId], $ids);

// null 값을 바인딩할 때는 call_user_func_array를 써야 안전합니다
$tmp = [];
foreach ($params as $key => $value) {
  $tmp[$key] = &$params[$key];  // 참조 전달 필수
}
call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $tmp));

// 실행
if ($stmt->execute()) {
  echo json_encode(['success' => true, 'updated' => $stmt->affected_rows]);
} else {
  echo json_encode(['success' => false, 'message' => 'DB 업데이트 실패']);
}

$stmt->close();
$conn->close();
