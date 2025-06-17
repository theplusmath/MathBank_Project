<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB 연결 실패: ' . $conn->connect_error]);
    exit;
}

// 입력 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);
$ids = $input['ids'] ?? [];

if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => '삭제할 문제가 선택되지 않았습니다.']);
    exit;
}

$deletedCount = 0;
$skippedIds = [];

foreach ($ids as $id) {
    $id = intval($id);
    
    // 1. 삭제할 문제 조회
    $result = $conn->query("SELECT * FROM problems WHERE id = $id");
    if (!$result || $result->num_rows === 0) {
        $skippedIds[] = $id;
        continue;
    }

    $row = $result->fetch_assoc();
    $row['deleted_at'] = date('Y-m-d H:i:s');

    // 2. 필드 및 값 추출
    $fields = array_keys($row);
    $columns = implode(", ", $fields);
    $values = implode(", ", array_map(function ($v) use ($conn) {
        if (is_null($v)) return "NULL";
        return "'" . $conn->real_escape_string($v) . "'";
    }, array_values($row)));

    // 3. 백업 테이블로 복사
    $insertSQL = "INSERT INTO deleted_problems ($columns) VALUES ($values)";
    if (!$conn->query($insertSQL)) {
        $skippedIds[] = $id;
        continue;
    }

    // 4. 원본 문제 삭제
    $deleteSQL = "DELETE FROM problems WHERE id = $id";
    if (!$conn->query($deleteSQL)) {
        $skippedIds[] = $id;
        continue;
    }

    $deletedCount++;
}

// ? 결과 출력
echo json_encode([
    'success' => true,
    'deleted' => $deletedCount,
    'skipped' => $skippedIds
]);
?>
