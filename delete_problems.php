<?php
require_once 'functions.php';

header('Content-Type: application/json');
$conn = connectDB();

// ID 받기
$id = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => '? 유효하지 않은 문제 ID']);
    exit;
}

// 1?? 원본 문제 가져오기
$stmt = $conn->prepare("SELECT * FROM problems WHERE id = ?");
bindParams($stmt, 'i', [$id]);
$stmt->execute();
$result = $stmt->get_result();
$problem = $result->fetch_assoc();
$stmt->close();

if (!$problem) {
    echo json_encode(['success' => false, 'message' => '? 해당 ID의 문제를 찾을 수 없습니다']);
    exit;
}

// 2?? 백업 테이블에 INSERT
$columns = array_keys($problem);
$placeholders = implode(', ', array_fill(0, count($columns), '?'));
$colString = implode(', ', $columns);
$sqlInsert = "INSERT INTO deleted_problems ($colString) VALUES ($placeholders)";
$stmtInsert = $conn->prepare($sqlInsert);
$types = guessParamTypes($problem);
$params = array_values($problem);

if (!bindParams($stmtInsert, $types, $params) || !$stmtInsert->execute()) {
    echo json_encode(['success' => false, 'message' => '?? 삭제 전 백업 실패: ' . $stmtInsert->error]);
    exit;
}
$stmtInsert->close();

// 3?? 실제 삭제 실행
$stmtDelete = $conn->prepare("DELETE FROM problems WHERE id = ?");
bindParams($stmtDelete, 'i', [$id]);

if (!$stmtDelete->execute()) {
    echo json_encode(['success' => false, 'message' => '?? 문제 삭제 실패: ' . $stmtDelete->error]);
    exit;
}
$stmtDelete->close();

echo json_encode(['success' => true, 'message' => "? 문제 ID {$id} 삭제 완료 및 백업됨"]);
