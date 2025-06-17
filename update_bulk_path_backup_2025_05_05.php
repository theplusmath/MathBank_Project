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

if (empty($ids) || !$newPathText) {
  echo json_encode(['success' => false, 'message' => '필수 데이터 누락']);
  exit;
}

// ID 정수 필터링
$ids = array_map('intval', $ids);
$placeholders = implode(',', array_fill(0, count($ids), '?'));

// SQL 준비
$sql = "UPDATE problems SET path_text = ?, path_id = ? WHERE id IN ($placeholders)";
$stmt = $conn->prepare($sql);

// 파라미터 바인딩
$types = 'si' . str_repeat('i', count($ids));  // s: path_text, i: path_id, i * ids
$params = array_merge([$newPathText, $newPathId], $ids);
$stmt->bind_param($types, ...$params);

// 실행
if ($stmt->execute()) {
  echo json_encode(['success' => true, 'updated' => $stmt->affected_rows]);
} else {
  echo json_encode(['success' => false, 'message' => 'DB 업데이트 실패']);
}

$stmt->close();
$conn->close();
