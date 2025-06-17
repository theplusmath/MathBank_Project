<?php
ob_start(); // 출력 버퍼링 시작
header('Content-Type: application/json');
ini_set('display_errors', 0);  // 화면에 에러 출력 안함
ini_set('log_errors', 1);      // 대신 로그에 저장
error_reporting(E_ALL);

// DB 연결
$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'DB 연결 실패: ' . $conn->connect_error]);
    exit;
}

// ID 유효성 검사
$id = intval($_GET['id'] ?? 0);
if (!$id) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => '문제 ID가 없습니다.']);
    exit;
}

// 원본 문제 조회
$result = $conn->query("SELECT * FROM problems WHERE id = $id");
if (!$result || $result->num_rows === 0) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => "ID $id: 문제를 찾을 수 없습니다."]);
    exit;
}

$row = $result->fetch_assoc();
$row['deleted_at'] = date('Y-m-d H:i:s');

// 필드 및 값 구성
$fields = array_keys($row);
$columns = implode(", ", $fields);
$values = implode(", ", array_map(function ($v) use ($conn) {
    return "'" . $conn->real_escape_string($v) . "'";
}, array_values($row)));

// deleted_problems 테이블에 삽입
$insertSQL = "INSERT INTO deleted_problems ($columns) VALUES ($values)";
if (!$conn->query($insertSQL)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => "ID $id 백업 실패: " . $conn->error]);
    exit;
}

// 원본에서 삭제
$deleteSQL = "DELETE FROM problems WHERE id = $id";
if (!$conn->query($deleteSQL)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => "ID $id 삭제 실패: " . $conn->error]);
    exit;
}

ob_end_clean();
echo json_encode([
    'success' => true,
    'message' => "ID $id 문제 삭제 완료"
]);
?>
