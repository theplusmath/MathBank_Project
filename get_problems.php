<?php
header('Content-Type: application/json; charset=UTF-8');

// DB 접속 정보 (자신의 정보에 맞게 수정)
$host = 'localhost';
$user = 'theplusmath';
$password = 'wnstj1205+';
$dbname = 'theplusmath';

// MySQL 연결
$conn = new mysqli($host, $user, $password, $dbname);
$conn->set_charset('utf8mb4');

// 연결 오류 확인
if ($conn->connect_error) {
  echo json_encode(['success' => false, 'message' => 'DB 연결 실패: ' . $conn->connect_error]);
  exit;
}

// 문제 목록 가져오기
$sql = "SELECT id, title, path_text, type, difficulty, category, source, tags, created_at, origin_id, copied_by FROM problems ORDER BY id DESC";
$result = $conn->query($sql);

// 쿼리 오류 처리
if (!$result) {
  echo json_encode(['success' => false, 'message' => '문제 불러오기 실패: ' . $conn->error]);
  exit;
}

// 결과를 배열로 변환
$problems = [];
while ($row = $result->fetch_assoc()) {
  $problems[] = $row;
}

// 결과 반환
echo json_encode($problems, JSON_UNESCAPED_UNICODE);
$conn->close();
?>
